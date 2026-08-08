# M4 — Assessment & Debriefing Design

**Project:** Tactical Scenario Lab — Institutional Edition 1.0  
**Date:** 2026-08-08  
**Milestone:** M4 — Assessment & Debriefing  
**Branch:** `feature/m4-assessment-debriefing`  
**Base:** `main` after M3 merge (`c135cd12ef2415d91b7e2ba4636bfbd23dac8759`)  
**Status:** design approved; written specification pending final user review

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
- structured action plan;
- immutable finalized assessment content;
- controlled migration of legacy evaluation data without inventing semantics.

M4 ends when the active application no longer writes new assessment/debriefing data to the legacy fields on `Scenario`.

---

## 2. Architectural boundary

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

Assessment mutation never changes scenario definition or execution history.

---

## 3. Chosen approach

M4 uses a **normalized relational assessment domain**, not one opaque JSON document.

This is preferred because it gives stronger referential integrity, tenant isolation, reproducible calculations, explicit immutable finalization and a clean foundation for M5 reporting.

A reusable organization-wide rubric-template engine is deliberately deferred. M4 works with a concrete rubric snapshot for one execution.

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
3. criteria may be prepared before execution starts;
4. evidence, critical-error occurrences and key times may be collected while execution is `running` or after it is `completed`;
5. normal M4 finalization is allowed only when execution is `completed`;
6. cancelled execution cannot be finalized;
7. finalized assessment content is immutable;
8. no reopening/amendment workflow is introduced in M4.

Legacy import is a controlled migration path and does not use the normal M4 finalization rules to reinterpret old data.

---

## 5. ExecutionAssessment

Fields:

- internal `id`;
- public `uuid`;
- `organization_id`;
- unique `scenario_execution_id`;
- `source` = `m4|legacy`;
- `status` = `draft|finalized`;
- nullable `pass_threshold` decimal;
- nullable `base_score` decimal;
- nullable `penalty_points` decimal;
- `evaluator_adjustment` integer default 0;
- nullable `adjustment_justification`;
- nullable `final_score` decimal;
- nullable `result` = `passed|failed`;
- `automatic_fail` boolean default false;
- nullable `finalized_at`;
- nullable `finalized_by_user_id`;
- nullable `legacy_imported_at`;
- timestamps.

Rules:

- one assessment per execution;
- organization must match execution organization;
- new M4 assessments use `source=m4` and snapshot `pass_threshold=70.00`;
- legacy imports use `source=legacy`; threshold/result remain null unless they were explicitly represented by legacy source data, which the current legacy model does not provide;
- finalized scoring snapshot cannot be rewritten.

---

## 6. Rubric criteria

`AssessmentCriterion` fields:

- public UUID;
- `execution_assessment_id`;
- optional `code`;
- `label`;
- nullable `description`;
- `weight` decimal percentage;
- nullable `score` decimal 0..100;
- nullable `evaluator_notes`;
- `position` integer;
- timestamps.

### Initialization

When a new M4 assessment is created:

- each nonblank `ScenarioVersion.learning_objectives` entry seeds one criterion;
- weights are distributed evenly to two decimals;
- the last criterion absorbs rounding remainder so total is exactly `100.00`;
- if no learning objectives exist, assessment starts with no criteria and the evaluator must add them.

### Draft editing

With `evaluations.manage`, while assessment is draft, evaluator may add/edit/remove/reorder criteria and set scores.

### Normal M4 finalization requires

- at least one criterion;
- every criterion scored;
- score in 0..100;
- every weight > 0;
- total weight exactly `100.00`;
- at least one evidence record per criterion.

---

## 7. Evidence

`AssessmentEvidence` fields:

- public UUID;
- `assessment_criterion_id`;
- optional `execution_event_id`;
- required objective `statement`;
- `observed_at`;
- `created_by_user_id`;
- timestamps.

Rules:

- evidence belongs to the same execution as its assessment;
- linked `ExecutionEvent`, if present, must belong to that same execution;
- cross-execution references are rejected before mutation;
- statement is required and length-limited;
- new evidence can be added only while assessment is draft;
- when execution has started, `observed_at` cannot precede `started_at`;
- when completed, `observed_at` cannot exceed `completed_at`;
- finalized evidence is immutable.

File/media attachments are outside M4.

---

## 8. Weighted score

Base score:

```text
base_score = Σ(criterion_score × criterion_weight) / 100
```

The score is rounded to two decimals by one centralized `AssessmentScoreCalculator`.

Controllers and Blade views do not duplicate the formula.

---

## 9. Critical-error catalog vs occurrence

`ScenarioVersion.critical_errors` remains the immutable monitoring catalog.

`CriticalErrorOccurrence` records what actually happened in one execution assessment.

Fields:

- public UUID;
- `execution_assessment_id`;
- `catalog_label_snapshot`;
- `rule` = `record|penalty|automatic_fail`;
- `penalty_points` decimal default 0;
- optional `execution_event_id`;
- `observed_at`;
- nullable `notes`;
- `source` = `m4|legacy`;
- timestamps.

For new M4 records:

- label must exist in the execution version's critical-error catalog;
- optional event must belong to same execution;
- `record` has zero penalty;
- `penalty` requires points > 0 and <= 100;
- `automatic_fail` forces failed result without rewriting numerical score;
- duplicate occurrence of the same catalog item is rejected in M4.

Legacy import preserves old observed strings even when catalog drift exists. Such records use `source=legacy`, `rule=record`, zero penalty and no inferred meaning.

---

## 10. Hybrid scoring model

### Base

Weighted rubric produces `base_score` in 0..100.

### Penalties

`penalty_points` is the sum of occurrences with `rule=penalty`.

### Evaluator adjustment

Evaluator may apply integer adjustment from `-10` through `+10`.

- zero requires no justification;
- nonzero requires a length-limited justification;
- adjustment and justification freeze at finalization.

### Numerical result

```text
final_score = clamp(base_score - penalty_points + evaluator_adjustment, 0, 100)
```

### Pass/fail for new M4 assessments

```text
if any automatic_fail occurrence:
    result = failed
else if final_score >= pass_threshold:
    result = passed
else:
    result = failed
```

For new M4 assessments, `pass_threshold=70.00`.

An automatic fail changes the result but preserves the numerical score for transparency.

Legacy imports do not receive a synthetic threshold or pass/fail classification.

---

## 11. Key times

`KeyTimeRecord` fields:

- public UUID;
- `execution_assessment_id`;
- `label`;
- `occurred_at`;
- server-derived `elapsed_seconds`;
- optional `reference_seconds`;
- optional `notes`;
- timestamps.

Rules:

- execution must have `started_at`;
- client never supplies authoritative elapsed value;
- backend derives elapsed seconds from execution start;
- time cannot precede start;
- completed execution time cannot exceed completion;
- reference is optional and nonnegative;
- finalized key times are immutable.

Advanced SLA/statistical analytics are outside M4.

---

## 12. Structured debrief

Each assessment has at most one `ExecutionDebrief`.

`ExecutionDebrief`:

- public UUID;
- unique `execution_assessment_id`;
- timestamps.

`DebriefEntry`:

- public UUID;
- `execution_debrief_id`;
- `kind` = `fact|interpretation|recommendation|legacy_unstructured`;
- `content`;
- `position`;
- `created_by_user_id`;
- timestamps.

Public M4 HTTP flows allow only:

- `fact` — what objectively happened;
- `interpretation` — what that means;
- `recommendation` — what should be maintained or changed.

`legacy_unstructured` is migration-only.

Normal M4 finalization requires at least one fact, one interpretation and one recommendation.

The system never automatically classifies evaluator prose between these categories.

---

## 13. Action plan

`ActionItem` fields:

- public UUID;
- `execution_debrief_id`;
- required `action`;
- optional `responsible_person_id`;
- optional `responsible_label`;
- required `due_date`;
- `status` = `open|in_progress|completed|cancelled`;
- nullable `notes`;
- nullable `status_changed_at`;
- nullable `status_changed_by_user_id`;
- timestamps.

Rules:

- every action item must have a responsible party: either a same-organization person or a responsible free-text label;
- every action item has a deadline;
- before assessment finalization, action-plan content may be edited by `evaluations.manage`;
- after finalization, historical content (`action`, responsible, deadline, notes) is immutable;
- after finalization, **only operational status** may transition with `evaluations.manage`;
- post-finalization status changes update actor/time and must follow repository audit/sanitization conventions;
- allowed simple transitions: `open -> in_progress|completed|cancelled`, `in_progress -> completed|cancelled`;
- completed/cancelled are terminal in M4.

This is the sole deliberate exception to full aggregate immutability: evaluation content remains frozen while action follow-up can progress operationally.

Action items are available but not mandatory for finalization, because an exercise may legitimately yield no corrective action.

---

## 14. Authorization

Existing abilities are reused.

### Read

`scenarios.view` may view assessment/debriefing within active organization.

### Mutate assessment/debrief

`evaluations.manage` is required for:

- assessment creation;
- criterion CRUD/scoring;
- evidence;
- critical-error occurrence;
- key time;
- debrief entries;
- action-item content before finalization;
- evaluator adjustment;
- finalization;
- post-finalization action-item status transitions.

`scenarios.manage` alone is insufficient.

### Tenant isolation

Every mutation:

1. resolves active organization through `ActiveOrganization`;
2. requires `evaluations.manage`;
3. verifies assessment/execution organization matches active organization;
4. validates referenced event/person belongs to same allowed context;
5. ignores/rejects organization identifiers supplied by clients.

---

## 15. Public identifiers

All new externally addressable M4 aggregates use `HasPublicUuid`:

- `ExecutionAssessment`;
- `AssessmentCriterion`;
- `AssessmentEvidence`;
- `CriticalErrorOccurrence`;
- `KeyTimeRecord`;
- `ExecutionDebrief`;
- `DebriefEntry`;
- `ActionItem`.

Instructor-facing routes/forms use UUIDs. Numeric IDs stay internal.

---

## 16. Finalization service

Finalization belongs in `ExecutionAssessmentManager`, not a controller.

`finalize(ExecutionAssessment $assessment, User $evaluator)` runs in one transaction and:

1. reloads assessment with row lock;
2. rejects already-finalized assessment;
3. reloads execution and requires `completed`;
4. rejects cancelled/noncompleted execution;
5. validates rubric completeness and exact total weight;
6. validates evidence requirement;
7. validates structured debrief requirement;
8. validates adjustment/justification;
9. calculates base score;
10. calculates penalties;
11. detects automatic fail;
12. calculates final score;
13. derives pass/fail;
14. persists all score components/result;
15. persists finalizer and timestamp;
16. returns fresh finalized assessment.

This is the only normal M4 finalization path.

---

## 17. Immutability

After finalization, application-domain update/delete is blocked for:

- assessment scoring fields;
- criteria;
- evidence;
- critical-error occurrences;
- key times;
- debrief entries;
- action-item historical content.

The only allowed post-finalization mutation is the controlled operational `ActionItem.status` transition described above.

No HTTP route may reopen or rewrite finalized assessment content.

Database-level immutable-history enforcement may be considered in M6; M4 requires strong application guards and tests.

---

## 18. Legacy migration

Legacy `Scenario` fields include:

- `score`;
- `debrief_notes`;
- `observed_critical_errors`;
- historical scenario lifecycle fields.

For each scenario with legacy assessment data:

1. resolve historical/backfilled execution sequence 1;
2. if mapping is absent, do not guess; leave source untouched and report case in M4 audit;
3. if mapped and no M4 assessment exists, create `ExecutionAssessment(source=legacy, status=finalized, legacy_imported_at=...)`;
4. do **not** assign a synthetic `pass_threshold` or `result`;
5. if legacy score exists, preserve it as `base_score` and `final_score` and create one 100%-weight criterion `Avaliação legada importada` with that score;
6. if score exists, create provenance evidence stating only that the numerical value was imported from the legacy assessment record;
7. import each legacy observed-error string as `CriticalErrorOccurrence(source=legacy, rule=record, penalty_points=0)`;
8. import nonblank legacy debrief text as one `DebriefEntry(kind=legacy_unstructured)`;
9. never reclassify legacy prose into fact/interpretation/recommendation;
10. preserve legacy database columns for rollback/audit compatibility;
11. stop all new application writes to those legacy assessment fields after M4 activation.

Legacy imported assessments are read-only historical snapshots and are exempt from new-M4 rubric/debrief finalization requirements because imposing those rules would invent missing historical semantics.

---

## 19. Legacy endpoint retirement

`ScenarioController::evaluate` and `scenarios.evaluate` are transitional debt.

M4 completion requires:

- execution cockpit links to the M4 assessment page;
- active assessment writes target `ScenarioExecution`/`ExecutionAssessment`;
- no active UI posts new data to `scenarios.evaluate`;
- legacy route is removed once migration/regression tests prove safe compatibility;
- legacy columns remain in the database during M4.

Column removal is deferred to later release hardening.

---

## 20. UX

M4 adds a dedicated **Avaliação & Debriefing** page linked from the execution cockpit.

Hierarchy:

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
  - action
  - responsible
  - deadline
  - status
```

Principles:

- preserve M3 institutional visual language;
- show calculation components, not only final score;
- distinguish critical-error catalog from observed occurrence;
- use red only for actual critical/failure states;
- finalized assessment is visibly read-only;
- action-plan operational status remains visibly followable;
- empty states explain next required step;
- no PDF/CSV in M4.

---

## 21. HTTP/service boundaries

Suggested thin controllers:

- `ExecutionAssessmentController` — create/show/finalize/adjustment;
- `AssessmentCriterionController` — draft criterion CRUD;
- `AssessmentEvidenceController` — evidence append/remove while draft;
- `CriticalErrorOccurrenceController` — observed error add/remove while draft;
- `KeyTimeRecordController` — key time add/remove while draft;
- `DebriefEntryController` — structured debrief CRUD while draft;
- `ActionItemController` — content CRUD while draft + controlled status transition after finalization.

Suggested services:

- `AssessmentScoreCalculator` — deterministic scoring;
- `ExecutionAssessmentManager` — assessment creation/finalization/lifecycle invariants;
- migration/backfill logic isolated from public HTTP paths.

---

## 22. Error handling

Deterministic validation/domain errors include:

- duplicate assessment per execution;
- weights not totaling 100;
- unscored criterion;
- criterion without evidence;
- foreign execution event reference;
- unknown new critical-error label;
- invalid penalty rule/value;
- adjustment outside -10..+10;
- nonzero adjustment without justification;
- key time outside execution window;
- missing fact/interpretation/recommendation;
- finalization before completed execution;
- finalization of cancelled execution;
- mutation of finalized historical content;
- invalid post-finalization action status transition;
- cross-org execution/event/person reference.

Finalization is transactional.

---

## 23. Testing strategy

Implementation follows TDD RED -> GREEN.

### Unit tests

- weighted score;
- penalty aggregation;
- clamp to 0..100;
- automatic-fail precedence;
- adjustment behavior;
- threshold result.

### Feature/domain tests

- one assessment per execution;
- rubric seeding from learning objectives;
- exact 100.00 weight distribution;
- manual criteria when no objectives;
- incomplete rubric cannot finalize;
- criterion without evidence cannot finalize;
- same-execution event evidence accepted;
- cross-execution evidence rejected;
- catalog error accepted;
- unknown new error rejected;
- penalty changes final score;
- automatic fail overrides numerical pass;
- nonzero adjustment requires justification;
- adjustment limited to -10..+10;
- server-derived key-time elapsed value;
- invalid time window rejected;
- structured debrief categories required;
- `legacy_unstructured` cannot be created by public HTTP;
- action item requires responsible party and deadline;
- post-finalization action content immutable;
- valid action status transition after finalization;
- terminal action status cannot reopen in M4;
- `scenarios.view` reads;
- `evaluations.manage` mutates;
- `scenarios.manage` alone cannot mutate;
- cross-org read/write blocked;
- finalized assessment content immutable;
- stale concurrent finalization safe under row lock;
- legacy import preserves numeric score without synthetic pass/fail;
- legacy error strings preserved without inferred penalty;
- legacy debrief preserved without semantic reclassification;
- active UI no longer posts to `scenarios.evaluate`.

Full M1-M3 regression suite must remain green.

---

## 24. Auditability and privacy

M4 preserves provenance for:

- who finalized;
- when finalized;
- score components;
- adjustment justification;
- critical-error rule/penalty snapshot;
- evidence author;
- legacy import marker;
- post-finalization action status actor/time.

Free-text evidence/debrief content must not be unnecessarily duplicated into generic logs. Existing sanitization conventions remain applicable.

---

## 25. Performance

Assessment page should eager-load the graph needed to render:

- criteria/evidence;
- critical-error occurrences/events;
- key times;
- debrief entries/action items;
- responsible people where applicable.

Avoid N+1 queries. Advanced analytics belong to M5.

---

## 26. Explicit non-goals

Not M4:

- reusable rubric-template library;
- rubric template versioning;
- PDF/CSV reports;
- executive dashboards/benchmark analytics;
- AI-generated feedback;
- NLP debrief classification;
- evidence file uploads;
- electronic signatures;
- amendment/reopen workflow for finalized assessment;
- external API;
- mobile-native app;
- M5 product/reporting work;
- M6 production hardening;
- M7 final design-system audit;
- M8 Wiki overhaul;
- M9 release/tag work.

---

## 27. Completion checklist

M4 is complete only when:

- [ ] assessment belongs to `ScenarioExecution`;
- [ ] one assessment per execution;
- [ ] criteria normalized and weighted;
- [ ] weights total exactly 100.00 at normal finalization;
- [ ] all normal-M4 criteria scored;
- [ ] evidence exists per normal-M4 criterion;
- [ ] cross-execution evidence blocked;
- [ ] catalog remains definition data;
- [ ] observed critical errors are assessment records;
- [ ] penalty rule explicit;
- [ ] automatic fail explicit;
- [ ] key times server-derived from execution start;
- [ ] scoring deterministic and centralized;
- [ ] adjustment limited to -10..+10;
- [ ] nonzero adjustment justified;
- [ ] new M4 threshold snapshot is 70.00;
- [ ] debrief separates fact/interpretation/recommendation;
- [ ] action plan has action/responsible/deadline/status;
- [ ] finalized action content immutable while status remains operationally followable;
- [ ] `evaluations.manage` protects mutations;
- [ ] `scenarios.manage` alone does not authorize assessment mutation;
- [ ] cross-org access blocked;
- [ ] public UUID boundary maintained;
- [ ] normal finalization requires completed execution;
- [ ] cancelled execution cannot finalize;
- [ ] finalized historical assessment content immutable;
- [ ] legacy score preserved without invented pass/fail;
- [ ] legacy error strings preserved without invented penalties;
- [ ] legacy debrief preserved without semantic reclassification;
- [ ] active UI stops writing legacy Scenario assessment fields;
- [ ] old DB columns remain for rollback compatibility;
- [ ] PHPUnit full suite passes;
- [ ] Pint passes;
- [ ] migrations pass;
- [ ] Vite build passes;
- [ ] `docs/PHASE_M4_AUDIT.md` exists;
- [ ] exact final HEAD receives green CI;
- [ ] PR has no unresolved review threads before integration.

---

## 28. Definition of done

M4 is ready for integration when a completed `ScenarioExecution` can produce a structured, explainable and immutable institutional assessment whose numerical result is reproducible from rubric scores, critical penalties and justified evaluator adjustment; whose evidence and references cannot cross execution/organization boundaries; whose debrief separates facts, interpretations and recommendations; whose action plan preserves historical content while allowing controlled operational status follow-up; and whose legacy predecessor data has been preserved without invented semantics.

Only after this state is audited and green should M5 — Institutional Product begin.
