# M4 — Assessment & Debriefing Design

**Project:** Tactical Scenario Lab — Institutional Edition 1.0  
**Date:** 2026-08-08  
**Milestone:** M4 — Assessment & Debriefing  
**Branch:** `feature/m4-assessment-debriefing`  
**Base:** `main` after M3 merge (`c135cd12ef2415d91b7e2ba4636bfbd23dac8759`)  
**Status:** approved design, implementation pending written-spec review

---

## 1. Goal

Move assessment and debriefing out of the reusable `Scenario` aggregate and into each concrete `ScenarioExecution`, so every training run can be evaluated independently, reproducibly and auditably.

M4 must provide:

- structured rubric criteria;
- evidence per criterion;
- weighted scoring;
- explicit distinction between critical-error catalog and observed occurrences;
- key-time records;
- hybrid final scoring;
- structured debriefing separating fact, interpretation and recommendation;
- action plan;
- immutable finalized assessment;
- controlled migration of legacy evaluation data.

The milestone ends when the active application no longer writes new assessment/debriefing data to the legacy fields on `Scenario`.

---

## 2. Architectural boundary

The M4 core domain is:

```text
Scenario
  └── ScenarioVersion
        └── ScenarioExecution
              └── ExecutionAssessment
                    ├── AssessmentCriterion
                    │     └── AssessmentEvidence
                    ├── CriticalErrorOccurrence
                    ├── KeyTimeRecord
                    └── ExecutionDebrief
                          ├── DebriefEntry
                          └── ActionItem
```

`ScenarioVersion` remains the immutable training definition. `ScenarioExecution` remains the concrete run. `ExecutionAssessment` becomes the single evaluation aggregate for that run.

No assessment mutation may alter `ScenarioVersion` or execution history.

---

## 3. Chosen approach

### Selected: normalized assessment domain

M4 will use normalized relational entities rather than a single JSON assessment payload.

Reasons:

- stronger referential integrity;
- easier tenant isolation;
- easier auditing;
- reliable scoring calculations;
- explicit immutable finalization;
- future M5 reporting without parsing opaque blobs;
- easier validation of event/evidence references;
- better separation of fact, interpretation and recommendation.

### Rejected: JSON-only assessment

JSON would reduce initial migration count, but would make cross-reference integrity, reporting and controlled evolution weaker.

### Deferred: full institutional rubric-template engine

Reusable rubric templates, rubric catalogs, template versioning and organization-wide policy configuration are useful future product capabilities, but are not required to finish M4. M4 evaluates an execution with a concrete rubric snapshot.

---

## 4. Assessment lifecycle

`ExecutionAssessment.status` has exactly:

```text
draft
finalized
```

Rules:

1. one assessment per `ScenarioExecution`;
2. assessment may be created while execution is `draft`, `running` or `completed`;
3. criteria may be prepared before the execution starts;
4. evidence, critical-error occurrences and key times may be collected while execution is `running` or after it is `completed`;
5. assessment may be finalized only when the execution is `completed`;
6. finalized assessment is immutable through the M4 application domain;
7. no edit/delete HTTP routes exist for a finalized assessment or its children;
8. reopening/amendment of a finalized assessment is explicitly out of scope for M4.

A cancelled execution cannot be finalized as an assessment.

---

## 5. ExecutionAssessment

Proposed fields:

- `id` internal primary key;
- `uuid` public unique identifier;
- `organization_id`;
- `scenario_execution_id` unique;
- `status` = `draft|finalized`;
- `pass_threshold` decimal, snapshot default `70.00`;
- `base_score` nullable decimal;
- `penalty_points` nullable decimal;
- `evaluator_adjustment` integer default `0`;
- `adjustment_justification` nullable text;
- `final_score` nullable decimal;
- `result` = `pending|passed|failed`;
- `automatic_fail` boolean default false;
- `finalized_at` nullable timestamp;
- `finalized_by_user_id` nullable FK;
- `legacy_imported_at` nullable timestamp;
- timestamps.

Constraints:

- unique assessment per execution;
- organization must match execution organization;
- finalized scoring snapshot cannot be modified afterward;
- `pass_threshold` is fixed at 70.00 for M4-created assessments. Policy configuration is deferred.

---

## 6. Rubric criteria

`AssessmentCriterion` fields:

- public UUID;
- `execution_assessment_id`;
- `code` optional stable short identifier;
- `label`;
- `description` nullable;
- `weight` decimal percentage;
- `score` nullable decimal from 0 to 100;
- `evaluator_notes` nullable;
- `position` integer;
- timestamps.

### Initialization

When an assessment is first created:

- each nonblank `ScenarioVersion.learning_objectives` entry seeds one criterion;
- weights are distributed as evenly as possible to two decimal places;
- the final criterion receives any rounding remainder so the total is exactly `100.00`;
- if there are no learning objectives, assessment starts with no criteria and the evaluator must add criteria before finalization.

### Draft editing

While assessment is draft, an evaluator with `evaluations.manage` may:

- add criteria;
- edit label/description/weight/score/notes;
- remove criteria;
- reorder criteria.

### Finalization invariants

Finalization requires:

- at least one criterion;
- every criterion scored;
- every score in 0..100;
- every weight > 0;
- total criterion weight exactly `100.00`;
- at least one evidence record per criterion.

---

## 7. Evidence

`AssessmentEvidence` fields:

- public UUID;
- `assessment_criterion_id`;
- optional `execution_event_id`;
- `statement` objective text;
- `observed_at` timestamp;
- `created_by_user_id`;
- timestamps.

Rules:

- evidence belongs indirectly to exactly one execution through its criterion/assessment;
- an optional linked `ExecutionEvent` must belong to the same execution;
- cross-execution event references are rejected before mutation;
- statement is required and length-limited;
- evidence may be created only while assessment is draft;
- evidence timestamps must not precede execution start when `started_at` exists;
- for completed executions, evidence timestamps must not exceed `completed_at`;
- finalized evidence is immutable.

Evidence is intentionally textual plus an optional trusted timeline reference. File attachments are out of scope for M4.

---

## 8. Weighted score

Each criterion score is 0..100 and each criterion weight is a percentage.

The base score is:

```text
base_score = Σ(criterion_score × criterion_weight) / 100
```

The result is rounded to two decimal places using one centralized calculator service.

No controller or Blade view may implement an independent scoring formula.

---

## 9. Critical-error catalog vs occurrence

`ScenarioVersion.critical_errors` remains the immutable catalog of errors that should be monitored.

M4 introduces `CriticalErrorOccurrence` to represent what was actually observed in one execution.

Fields:

- public UUID;
- `execution_assessment_id`;
- `catalog_label_snapshot`;
- `rule` = `record|penalty|automatic_fail`;
- `penalty_points` decimal default 0;
- optional `execution_event_id`;
- `observed_at`;
- `notes` nullable;
- `source` = `m4|legacy`;
- timestamps.

### New M4 occurrences

For `source=m4`:

- `catalog_label_snapshot` must match a value in the execution's `ScenarioVersion.critical_errors` catalog;
- optional event must belong to the same execution;
- `record` requires zero penalty;
- `penalty` requires `penalty_points > 0` and `<= 100`;
- `automatic_fail` sets the assessment automatic-fail flag regardless of numerical score;
- duplicate occurrence of the same catalog item is rejected unless a later design explicitly introduces repeated occurrences.

### Legacy occurrences

Legacy import may preserve an old observed label even if it no longer matches the current catalog. Such records are marked `source=legacy`, use `rule=record`, apply no inferred penalty, and preserve the original text without inventing semantics.

---

## 10. Hybrid scoring model

M4 uses the approved hybrid model.

### Step 1 — base score

Weighted rubric produces `base_score` in 0..100.

### Step 2 — penalties

`penalty_points` is the sum of all `CriticalErrorOccurrence` records with `rule=penalty`.

### Step 3 — evaluator adjustment

Evaluator may apply an integer adjustment from `-10` to `+10` points.

Rules:

- adjustment `0` requires no justification;
- any nonzero adjustment requires a human-readable justification;
- justification is length-limited and stored on the assessment;
- the adjustment is included in the finalized score snapshot;
- finalized adjustment cannot be changed.

### Step 4 — final numerical score

```text
final_score = clamp(base_score - penalty_points + evaluator_adjustment, 0, 100)
```

### Step 5 — pass/fail

```text
if any automatic_fail occurrence:
    result = failed
else if final_score >= 70.00:
    result = passed
else:
    result = failed
```

An automatic fail does not rewrite the numerical score; it changes the result and is displayed explicitly as the reason numerical score did not determine approval.

The calculator returns both score components and result so the UI can explain the outcome transparently.

---

## 11. Key times

`KeyTimeRecord` represents an assessment-relevant timing observation.

Fields:

- public UUID;
- `execution_assessment_id`;
- `label`;
- `occurred_at`;
- `elapsed_seconds` integer;
- optional `reference_seconds` integer;
- optional `notes`;
- timestamps.

Rules:

- execution must have `started_at` before a key time can be recorded;
- `elapsed_seconds` is calculated by the backend from `execution.started_at` and `occurred_at`; clients do not submit the authoritative elapsed value;
- `occurred_at` cannot precede execution start;
- if execution is completed, `occurred_at` cannot exceed completion;
- reference is optional and nonnegative;
- finalized key times are immutable.

No advanced SLA/statistical analytics are introduced in M4.

---

## 12. Structured debrief

Each assessment has at most one `ExecutionDebrief`.

`ExecutionDebrief` fields:

- public UUID;
- `execution_assessment_id` unique;
- timestamps.

`DebriefEntry` fields:

- public UUID;
- `execution_debrief_id`;
- `kind` = `fact|interpretation|recommendation|legacy_unstructured`;
- `content`;
- `position`;
- `created_by_user_id`;
- timestamps.

### New M4 entries

HTTP creation permits only:

- `fact`;
- `interpretation`;
- `recommendation`.

`legacy_unstructured` is reserved exclusively for migration code.

### Finalization requirement

A nonlegacy M4 assessment must have at least:

- one fact;
- one interpretation;
- one recommendation.

This prevents debriefing from collapsing back into one ambiguous free-text note.

### Semantic intent

- **fact:** what objectively happened;
- **interpretation:** what the event/result means;
- **recommendation:** what should be maintained or changed.

The application must not automatically classify evaluator prose between these categories.

---

## 13. Action plan

`ActionItem` fields:

- public UUID;
- `execution_debrief_id`;
- `action`;
- optional `responsible_person_id`;
- optional `responsible_label`;
- optional `due_date`;
- `status` = `open|in_progress|completed|cancelled`;
- optional `notes`;
- timestamps.

Rules:

- at least one of `responsible_person_id` or `responsible_label` must be provided when a responsible party is assigned;
- if a person is used, the person must belong to the same active organization context;
- action plan items may exist without a due date;
- status transitions remain simple in M4; workflow automation/reminders are not part of this milestone;
- assessment finalization freezes action-item content for the M4 historical record.

Action items are available but are not mandatory for finalization because a completed exercise may legitimately produce no corrective action.

---

## 14. Authorization model

Existing abilities are reused.

### Read

`scenarios.view` may view assessment/debriefing for an execution in the active organization.

### Write

`evaluations.manage` is required for all M4 mutations:

- create assessment;
- add/edit/remove criteria;
- score criteria;
- add evidence;
- add/remove observed critical errors;
- record key times;
- add/edit/remove debrief entries;
- add/edit/remove action items;
- set evaluator adjustment;
- finalize assessment.

`scenarios.manage` alone does not grant evaluation-write access.

### Tenant isolation

Before every mutation:

- resolve active organization through `ActiveOrganization`;
- require `evaluations.manage`;
- verify `ExecutionAssessment.organization_id === active organization`;
- verify referenced execution/event/person belongs to the same allowed context.

No organization identifier is trusted from request payloads.

---

## 15. Public identifiers

Every new externally addressable M4 aggregate uses `HasPublicUuid`:

- `ExecutionAssessment`;
- `AssessmentCriterion`;
- `AssessmentEvidence`;
- `CriticalErrorOccurrence`;
- `KeyTimeRecord`;
- `ExecutionDebrief`;
- `DebriefEntry`;
- `ActionItem`.

Instructor-facing routes/forms use UUID route model binding or UUID form values. Numeric database IDs remain internal implementation details.

---

## 16. Finalization service

Finalization belongs in a dedicated domain service, not the controller.

Suggested boundary:

`ExecutionAssessmentManager::finalize(ExecutionAssessment $assessment, User $evaluator)`

Within one transaction it must:

1. reload assessment with row lock;
2. reject already-finalized assessment;
3. reload and validate execution state;
4. require execution `completed`;
5. validate rubric invariants;
6. validate evidence requirement;
7. validate structured debrief requirement;
8. validate evaluator adjustment and justification;
9. calculate base score;
10. calculate penalties;
11. detect automatic fail;
12. calculate final numerical score;
13. derive result;
14. persist the scoring snapshot;
15. persist `finalized_at` and evaluator;
16. return the fresh finalized assessment.

This is the single authoritative finalization path.

---

## 17. Immutability

After finalization, application-level mutation of the assessment aggregate is blocked.

At minimum, model/service guards must prevent update/delete of:

- assessment scoring fields;
- criteria;
- evidence;
- critical-error occurrences;
- key times;
- debrief entries;
- action items.

No HTTP mutation routes should succeed after finalization.

Database-level immutable-history enforcement may be considered in M6, but M4 must have strong application-domain protection and tests.

---

## 18. Legacy migration strategy

Current legacy fields on `Scenario` include:

- `score`;
- `debrief_notes`;
- `observed_critical_errors`;
- lifecycle fields historically used by legacy execution/evaluation.

### Migration target

For each legacy scenario with assessment data:

1. resolve its historical/backfilled `ScenarioExecution` sequence 1;
2. if that execution has no M4 assessment, create one marked with `legacy_imported_at`;
3. create one criterion labeled `Avaliação legada importada`, weight `100.00`, score equal to the legacy score when available;
4. create one evidence statement indicating the score was imported from the legacy assessment record; this is provenance text, not a fabricated observation;
5. import each observed critical-error string as `CriticalErrorOccurrence(source=legacy, rule=record, penalty_points=0)`;
6. import nonblank `debrief_notes` as exactly one `DebriefEntry(kind=legacy_unstructured)` without reclassifying the prose as fact/interpretation/recommendation;
7. preserve the legacy fields in the database for rollback/audit compatibility during Institutional Edition 1.0 work;
8. stop all new application writes to the legacy assessment fields after M4 activation.

### Migration safety

If a scenario with legacy assessment data cannot be mapped to an execution, the migration must not guess. It must leave the legacy source untouched and surface/document the unresolved case for the M4 audit.

No data is silently discarded or semantically reinterpreted.

---

## 19. Legacy endpoint retirement

`ScenarioController::evaluate` and the active `scenarios.evaluate` form/route are transitional debt from pre-M4 architecture.

M4 completion requires:

- no new UI path writing assessment data to `Scenario`;
- execution cockpit links to M4 assessment page;
- new assessment operations target `ScenarioExecution`/`ExecutionAssessment`;
- legacy route may be removed once migration and regression tests prove compatibility;
- legacy database columns are not dropped in M4.

Dropping old columns is deferred until later release-hardening once rollback confidence exists.

---

## 20. UX structure

M4 adds a dedicated assessment page rather than turning the operational cockpit into one oversized form.

Entry point from execution cockpit:

```text
Avaliação & Debriefing
```

Page hierarchy:

```text
Execution context
  ↓
Assessment summary
  - base score
  - penalties
  - evaluator adjustment
  - final score
  - pass/fail
  ↓
Rubric
  - criteria
  - weights
  - scores
  - evidence
  ↓
Critical errors
  - catalog item
  - observed rule
  - penalty/automatic fail
  ↓
Key times
  ↓
Structured debrief
  - facts
  - interpretations
  - recommendations
  ↓
Action plan
```

### UI principles

- preserve M3 institutional visual language;
- show formula components rather than only final score;
- clearly distinguish catalog from observed occurrence;
- use red only for real critical failure/risk states;
- finalized assessments render as read-only historical records;
- draft vs finalized state must be visually unmistakable;
- empty states explain what is required next;
- no PDF/CSV controls in M4.

---

## 21. Suggested HTTP boundaries

Exact naming may be refined in the implementation plan, but responsibilities should remain separated.

Possible controllers:

- `ExecutionAssessmentController` — create/show/finalize/adjustment;
- `AssessmentCriterionController` — criterion CRUD while draft;
- `AssessmentEvidenceController` — append evidence;
- `CriticalErrorOccurrenceController` — record/remove occurrence while draft;
- `KeyTimeRecordController` — record/remove key time while draft;
- `ExecutionDebriefController` — ensure/load debrief;
- `DebriefEntryController` — structured debrief entries;
- `ActionItemController` — action-plan CRUD while draft.

Controllers remain thin: authorization + validation + service/model delegation.

---

## 22. Scoring calculator boundary

Suggested stateless service:

`AssessmentScoreCalculator`

Inputs:

- criteria collection;
- critical-error occurrences;
- evaluator adjustment;
- threshold.

Output value object/array:

- `base_score`;
- `penalty_points`;
- `evaluator_adjustment`;
- `final_score`;
- `automatic_fail`;
- `result`.

The calculation must be deterministic and covered by unit tests independent of HTTP.

---

## 23. Error handling

Expected domain errors use deterministic validation/domain exceptions and never partially mutate the assessment.

Examples:

- weights do not total 100;
- criterion missing score;
- criterion missing evidence;
- linked timeline event belongs to another execution;
- critical error not in catalog;
- invalid penalty rule/points;
- adjustment outside -10..+10;
- adjustment missing justification;
- finalization before execution completion;
- mutation after finalization;
- cross-org resource reference;
- duplicate assessment for execution.

Finalization is transactional.

---

## 24. Testing strategy

M4 implementation must follow RED → GREEN cycles.

### Unit tests

At least:

- weighted score calculation;
- penalty aggregation;
- clamp to 0..100;
- automatic-fail precedence;
- evaluator adjustment behavior;
- threshold result.

### Feature/domain tests

At least:

- one assessment per execution;
- default rubric seeding from learning objectives;
- exact 100.00 weight distribution;
- manual criteria when no objectives exist;
- finalization rejects incomplete rubric;
- finalization rejects criterion without evidence;
- same-execution event evidence accepted;
- cross-execution evidence rejected;
- catalog error occurrence accepted;
- unknown new error occurrence rejected;
- penalty occurrence affects score;
- automatic fail overrides pass result;
- nonzero adjustment requires justification;
- adjustment bounded to -10..+10;
- key-time elapsed value calculated server-side;
- invalid key-time window rejected;
- fact/interpretation/recommendation required for new assessment finalization;
- legacy unstructured entry cannot be created through public HTTP flow;
- action plan ownership checks;
- `scenarios.view` can read;
- `evaluations.manage` required to mutate;
- `scenarios.manage` alone cannot mutate assessment;
- cross-org assessment read/write blocked;
- finalized aggregate immutable;
- stale concurrent finalization is safe under row lock;
- legacy import preserves score/error strings/debrief text without semantic invention;
- active UI no longer posts to `scenarios.evaluate`.

### Regression gate

Full existing M1-M3 suite must remain green.

---

## 25. Auditability

M4 must leave clear provenance for high-value decisions:

- who finalized;
- when finalized;
- numerical components of final score;
- adjustment justification;
- critical-error rule and penalty snapshot;
- evidence author;
- legacy import marker.

Sensitive free text must not be unnecessarily duplicated into generic logs/audit metadata. Existing repository sanitization conventions remain applicable.

---

## 26. Performance and query boundaries

M4 pages should eager-load the assessment graph needed for rendering and avoid N+1 behavior for:

- criteria/evidence;
- critical-error occurrences/events;
- key times;
- debrief entries/action items;
- responsible people where applicable.

No large analytics query belongs in M4.

---

## 27. Explicit non-goals

Not M4:

- reusable organization-wide rubric template library;
- rubric template versioning;
- PDF/CSV executive reports;
- dashboards/benchmark analytics;
- cohort comparison;
- AI-generated feedback;
- automatic NLP classification of debrief text;
- file/media evidence upload;
- electronic signatures;
- amendment workflow for finalized assessments;
- external API;
- mobile-native application;
- M5 product/reporting work;
- M6 production hardening;
- M7 design-system final audit;
- M8 Wiki overhaul;
- M9 release/tag work.

---

## 28. M4 completion checklist

M4 is complete only when all of the following are demonstrated:

- [ ] assessment belongs to `ScenarioExecution`;
- [ ] one assessment per execution;
- [ ] criteria are normalized and weighted;
- [ ] criterion weights total exactly 100.00 at finalization;
- [ ] all criteria are scored before finalization;
- [ ] evidence exists per criterion;
- [ ] evidence cross-execution reference is blocked;
- [ ] catalog critical errors remain definition data;
- [ ] observed critical errors are execution-assessment records;
- [ ] penalty rule is explicit;
- [ ] automatic fail is explicit;
- [ ] key times are server-derived relative to execution start;
- [ ] hybrid score is deterministic and centralized;
- [ ] evaluator adjustment is limited to -10..+10;
- [ ] nonzero adjustment requires justification;
- [ ] pass threshold is explicit and snapshot at 70.00;
- [ ] debrief separates fact/interpretation/recommendation;
- [ ] action plan exists as structured domain;
- [ ] `evaluations.manage` protects all mutations;
- [ ] `scenarios.manage` alone does not authorize assessment mutation;
- [ ] cross-org access is blocked;
- [ ] public UUID boundary is maintained;
- [ ] finalization requires completed execution;
- [ ] cancelled execution cannot finalize;
- [ ] finalized assessment aggregate is immutable;
- [ ] legacy score is preserved through controlled import;
- [ ] legacy error strings are preserved without inferred penalties;
- [ ] legacy debrief is preserved without semantic reclassification;
- [ ] active UI no longer writes new legacy Scenario assessment fields;
- [ ] old DB columns remain available for rollback compatibility;
- [ ] full PHPUnit suite passes;
- [ ] Pint passes;
- [ ] migrations pass;
- [ ] Vite build passes;
- [ ] `docs/PHASE_M4_AUDIT.md` is created;
- [ ] exact final HEAD receives green CI;
- [ ] PR has no unresolved review threads before integration.

---

## 29. Definition of done

M4 is ready for integration when a completed `ScenarioExecution` can produce a fully structured, explainable and immutable institutional assessment whose numerical result can be reproduced from persisted rubric scores, critical penalties and justified evaluator adjustment; whose evidence and references cannot cross execution/organization boundaries; whose debrief separates facts, interpretations and recommendations; whose action plan is structured; and whose legacy predecessor data has been preserved without inventing semantics.

Only after this state is audited and green should M5 — Institutional Product begin.
