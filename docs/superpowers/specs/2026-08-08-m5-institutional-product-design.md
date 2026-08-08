# M5 — Institutional Product Layer Design

**Project:** Tactical Scenario Lab — Institutional Edition 1.0  
**Date:** 2026-08-08  
**Milestone:** M5 — Institutional Product Layer  
**Branch:** `feature/m5-institutional-product`  
**Base:** `main` after M4 merge (`0f040715cebe17d0893e8898b898eb27f4007ca9`)  
**Status:** design approved; written specification pending final user review

---

## 1. Goal

Turn the completed institutional training domain from M1–M4 into a product that a company, municipality, school, training center or public institution can understand, demonstrate and inspect in a few minutes.

M5 delivers the product layer over the existing source of truth:

`Scenario → ScenarioVersion → ScenarioExecution → ExecutionAssessment → Debrief → Report`

M5 must provide:

- instructor dashboard;
- executive dashboard;
- tenant-safe filters;
- execution history;
- institutional indicators derived from M3/M4, not legacy `Scenario.score` fields;
- downloadable PDF execution report;
- stable CSV execution-summary export;
- reusable scenario templates;
- deterministic fictional institutional demo data;
- a five-minute demonstration path that uses no real PII.

M5 does **not** introduce a data warehouse, asynchronous analytics pipeline, scientific analytics, AI, billing, marketplace, native mobile, production infrastructure or the final visual-system rewrite.

---

## 2. Problems being corrected

### 2.1 Current dashboard reads legacy truth

The current dashboard reads `Scenario.status`, `Scenario.score` and `Scenario.critical_errors`. After M3/M4, those values are no longer the correct source for execution and assessment reporting.

M5 replaces the active dashboard read path with queries based on:

- `ScenarioExecution.status`;
- `ExecutionAssessment.status`;
- `ExecutionAssessment.final_score`;
- `ExecutionAssessment.result`;
- `ExecutionAssessment.automatic_fail`;
- `CriticalErrorOccurrence`;
- `ActionItem`.

Legacy scenario evaluation fields remain migration/history compatibility data and must not drive M5 indicators.

### 2.2 Dashboard logic is embedded in `routes/web.php`

M5 removes institutional dashboard aggregation from route closures. Routes remain routing declarations; read logic moves to dedicated controllers/query services.

### 2.3 Unit attribution is not historically frozen

`ExecutionParticipant` currently records execution, team, person and role, but not the institutional membership/unit represented during that execution. Reading a person's current membership later can rewrite historical reports after a transfer.

M5 introduces a conservative historical attribution snapshot when participants are added.

---

## 3. Chosen architecture

M5 uses a **dedicated institutional read layer over the normalized operational database**.

It deliberately does not create a separate analytics database or materialized reporting warehouse for v1.0.

The main units are:

```text
InstitutionalFilter
        │
        ├── InstructorDashboardQuery
        ├── ExecutiveDashboardQuery
        ├── ExecutionHistoryQuery
        └── ExecutionReportDataBuilder
                    │
                    ├── HTML/PDF renderer
                    └── CSV exporter

ScenarioVersion
        │
        └── ScenarioTemplate
                 │
                 └── ScenarioTemplateManager

DemoSeeder
        └── fictional organization graph
```

Each query unit has one responsibility and is testable independently from Blade templates.

---

## 4. Authorization model

Existing abilities are reused. M5 adds no new ability unless implementation proves an unavoidable authorization gap.

### Instructor dashboard

Requires `scenarios.view` in the active organization.

It may show only operational information already visible under scenario/execution read access.

### Executive dashboard and report exports

Require `reports.view` in the active organization.

`reports.view` never grants mutation rights.

### Templates

- viewing templates requires `scenarios.view`;
- creating, archiving or using a template requires `scenarios.manage`.

### Demo seeding

`DemoSeeder` is CLI/development functionality and is not exposed as an authenticated web endpoint.

---

## 5. Organization boundary

The active organization remains the tenant boundary.

M5 does **not** add an arbitrary `organization_id` URL/query filter to institutional dashboards. A user changes organization through the existing active-organization mechanism.

Every dashboard/report query starts from the organization ID resolved by `ActiveOrganization` and never trusts organization IDs supplied by the browser.

This satisfies the product requirement for organization filtering through the already-established institutional organization switcher without creating a cross-tenant reporting surface.

---

## 6. InstitutionalFilter

M5 centralizes dashboard/history/export filtering in one immutable value object named `InstitutionalFilter`.

Supported fields:

- active `organization_id` — server resolved, never client controlled;
- nullable `unit_uuid`;
- nullable `scenario_uuid`;
- nullable `status` appropriate to the target query;
- `date_from`;
- `date_to`.

### Default period

When no period is provided, dashboards use the last 90 calendar days through the end of the current day in the application's configured timezone.

### Period limits

- `date_from <= date_to`;
- interactive dashboard/export period is limited to 366 days in M5 to prevent accidental unbounded institutional scans;
- users can choose another 366-day window;
- full historical bulk analytics is outside M5.

### Unit

`unit_uuid`, when supplied, must belong to the active organization. Cross-org or unknown units are rejected before query execution.

An execution matches a unit filter when **at least one execution participant** has a historical `unit_id_snapshot` equal to the selected unit. The execution appears once even when multiple participants match.

Executions without historical unit attribution do not match a specific unit filter.

### Scenario

`scenario_uuid`, when supplied, must belong to the active organization.

### Status

Each endpoint uses a strict allowlist; arbitrary SQL-oriented filter values are never passed through.

---

## 7. Historical participant attribution

M5 extends `execution_participants` with nullable historical attribution fields:

- `organization_membership_id`;
- `unit_id_snapshot`;
- `unit_name_snapshot`;
- `position_snapshot`.

When a participant is added to an execution:

1. the request identifies the person and, when needed, the active institutional membership being represented;
2. backend verifies membership belongs to the same person and active organization;
3. backend snapshots the membership ID, unit ID/name and position into `ExecutionParticipant`;
4. future transfer, membership closure or unit rename does not silently rewrite the visible historical unit/position label for that execution.

### Existing M3 participants

A migration/backfill is conservative:

- if historical attribution can be resolved unambiguously, snapshot it;
- if more than one plausible membership exists or no safe mapping exists, fields remain null;
- M5 never guesses a historical unit;
- dashboards/reports render these as `Sem unidade histórica` rather than fabricating attribution.

The report/unit-filter layer uses snapshot fields, not a person's current membership.

---

## 8. Instructor dashboard

The existing `/dashboard` route (`dashboard`) becomes the instructor-oriented product dashboard.

It is backed by `InstructorDashboardController` + `InstructorDashboardQuery` rather than a route closure.

### Core cards

Within the active organization and current filter period:

- executions currently running;
- draft executions;
- completed executions awaiting assessment creation;
- draft assessments awaiting finalization;
- open action items;
- overdue action items.

### Work queues

The dashboard includes short prioritized lists:

- running executions;
- recently completed executions without a finalized assessment;
- draft assessments;
- action items due in the next 14 days;
- recently finalized assessments.

### Quick actions

Only actions permitted by current abilities render:

- create scenario;
- open scenarios;
- continue execution;
- continue assessment;
- open executive dashboard when `reports.view` is present.

No metric uses legacy `Scenario.score`.

---

## 9. Executive dashboard

M5 adds exactly:

- `GET /dashboard/executive`
- route name `dashboard.executive`
- controller `ExecutiveDashboardController`
- authorization `reports.view`

It is backed by `ExecutiveDashboardQuery`.

### Required indicators

Within the active organization and filters:

- total executions;
- completed executions;
- finalized M4 assessments;
- average `final_score` among finalized assessments with a numeric final score;
- pass rate among finalized assessments whose `result` is known;
- automatic-fail count;
- top observed critical errors from `CriticalErrorOccurrence`;
- open corrective actions;
- overdue corrective actions.

### Legacy semantics

Legacy-imported assessments may have score but no synthetic result. Therefore:

- their numeric score can participate in numeric score statistics when present;
- they are excluded from pass-rate denominator when `result` is null;
- no pass/fail result is inferred retrospectively.

### Trend

M5 provides one simple monthly execution/finalized-assessment trend for the selected period.

No predictive, risk-scoring or scientific statistical analytics enter M5.

---

## 10. Execution history

M5 adds exactly:

- `GET /history/executions`
- route name `execution-history.index`
- authorization `reports.view`
- controller `ExecutionHistoryController`

The page is backed by `ExecutionHistoryQuery`.

Columns include:

- execution public UUID/sequence;
- scenario title;
- scenario version;
- historical unit summary;
- execution status;
- started/completed timestamps;
- assessment status;
- final score when available;
- result when known;
- automatic-fail marker;
- count of observed critical errors;
- count of open actions.

### Multiple units

An execution may contain participants from multiple historical units. The history UI displays distinct unit names as a compact list. It never selects one arbitrary unit as the execution's single unit.

### Pagination

History is server-side paginated. M5 does not fetch all institutional history into memory for rendering.

### Sorting

Default: most recent execution first.

Allowlisted sort options only; arbitrary query-column sorting is rejected.

---

## 11. Report data builder

`ExecutionReportDataBuilder` is the single source of report presentation data for one execution.

It loads only data belonging to the active organization and returns a presentation-safe structure containing:

- organization display identity;
- scenario title/version;
- execution identity/status/timestamps;
- participants/teams with historical unit/position snapshots;
- assessment summary;
- rubric criteria and scores;
- evidence;
- observed critical errors and their rules/penalties;
- key times;
- structured debrief entries;
- action plan.

### PII minimization

Reports use public/display names needed for training context but do not include identifiers, CPF, RG, email, phone, WhatsApp or unrelated contact details.

The builder does not expose arbitrary model serialization.

---

## 12. PDF report

M5 adds exactly:

- `GET /reports/executions/{execution}/pdf`
- route name `reports.executions.pdf`
- authorization `reports.view`

`{execution}` uses the existing public UUID route binding.

### Rendering

The implementation uses the current stable `dompdf/dompdf` 3.1-compatible line through a small application adapter so PDF generation is not spread across controllers.

The PDF is rendered from a dedicated server-side Blade/HTML report view.

### Security constraints

- remote asset fetching disabled;
- no user-supplied remote URL is rendered as an image/font/stylesheet;
- styles/assets are local or embedded;
- no arbitrary HTML supplied by users is rendered unescaped;
- report filename is generated by the application, not taken directly from request text.

### Content

The PDF includes:

1. institutional header;
2. scenario/execution context;
3. participant/team summary;
4. assessment score breakdown;
5. rubric and evidence;
6. critical errors observed;
7. key times;
8. fact / interpretation / recommendation debrief sections;
9. corrective-action plan;
10. generation timestamp and public execution reference.

A report for an execution without a finalized assessment still renders execution context and explicitly labels assessment/debriefing as unavailable or pending.

M5 does not implement digital signatures or legally binding document certification.

---

## 13. CSV export

M5 adds exactly:

- `GET /reports/executions.csv`
- route name `reports.executions.csv`
- authorization `reports.view`

The endpoint exports the same filtered execution population defined by `InstitutionalFilter` and `ExecutionHistoryQuery`.

### Stable schema

The first M5 schema is fixed and documented in this order:

1. `execution_uuid`
2. `execution_sequence`
3. `scenario_uuid`
4. `scenario_title`
5. `scenario_version`
6. `unit_uuids`
7. `unit_names`
8. `execution_status`
9. `started_at`
10. `completed_at`
11. `assessment_status`
12. `final_score`
13. `result`
14. `automatic_fail`
15. `critical_error_count`
16. `open_action_count`

For an execution with multiple historical units, `unit_uuids` and `unit_names` contain distinct values joined by `;` in deterministic sorted order. Unknown attribution does not invent a UUID; if every participant lacks attribution, the UUID field is empty and the name field is `Sem unidade histórica`.

Dates use ISO 8601. Boolean uses `0|1`. Other null values remain empty fields.

### CSV injection protection

Text cells beginning with spreadsheet formula-control characters (`=`, `+`, `-`, `@`) are neutralized before output.

The exporter streams records instead of building an unbounded full-file string in memory.

---

## 14. Scenario templates

M5 adds `ScenarioTemplate` as a lightweight institutional reuse layer.

Fields:

- internal ID;
- public UUID;
- `organization_id`;
- `source_scenario_version_id`;
- `name`;
- optional description;
- `status = active|archived`;
- `created_by_user_id`;
- timestamps.

### Web routes

The M5 route family is:

- `GET /scenario-templates` → `scenario-templates.index`;
- `POST /scenario-versions/{scenarioVersion}/templates` → `scenario-templates.store`;
- `POST /scenario-templates/{scenarioTemplate}/use` → `scenario-templates.use`;
- `PATCH /scenario-templates/{scenarioTemplate}/archive` → `scenario-templates.archive`.

All public model parameters use UUID route binding.

### Creation

A template can be created only from a **published** `ScenarioVersion` belonging to the active organization.

The template points to that immutable source version; it does not copy executions or assessments.

### Use template

Using a template creates:

- a new `Scenario` in the same active organization;
- a new draft `ScenarioVersion` initialized from the source version definition;
- no execution;
- no assessment;
- no historical participant/evidence data.

The source template/version remains unchanged.

### Archival

Templates are archived rather than hard-deleted through normal M5 UI.

Archived templates cannot be used to create new scenarios but remain referentially inspectable.

---

## 15. DemoSeeder

M5 introduces a dedicated `Database\Seeders\DemoSeeder`.

It creates a deterministic fictional organization graph sufficient to demonstrate the product end-to-end.

### Safety

- refuses to run when application environment is `production`;
- uses clearly fictional names and synthetic contact/identifier data only when such fields are required;
- does not read or transform real production records;
- is designed for a fresh/demo database and uses deterministic natural keys/known emails to avoid uncontrolled duplicates on an intentional second run;
- is never automatically run by production migrations.

### Demo organization

The demo includes at least:

- one fictional organization;
- at least two units;
- administrator, instructor and evaluator user/access profiles;
- fictional participants with memberships;
- at least three scenarios;
- published versions;
- multiple executions in different states;
- teams and participants;
- execution events and instructor injects;
- finalized and draft assessments;
- rubric/evidence;
- observed critical errors;
- key times;
- structured debriefing;
- open, in-progress and completed action items;
- at least one scenario template.

### Demonstration path

The seeded dataset must support this five-minute walkthrough:

1. login to fictional institutional account;
2. see instructor dashboard with meaningful work queues;
3. open a scenario and execution;
4. inspect a finalized assessment/debrief;
5. open executive dashboard;
6. filter history;
7. download PDF report;
8. export CSV;
9. create a new draft scenario from a template.

---

## 16. Dashboard/query performance rules

M5 stays within the current single-database architecture but must avoid obvious performance debt.

Rules:

- aggregate counts/sums/averages are performed in SQL where practical;
- no dashboard loops that issue one query per row;
- relationships used in lists are eager loaded deliberately;
- history is paginated;
- CSV is streamed/chunked;
- dashboard top lists are bounded;
- no `Model::all()` over institutional execution/assessment history;
- indexes are added only where the new query patterns demonstrably require them.

Production-level PostgreSQL query tuning remains M6.

---

## 17. Error handling

M5 preserves existing HTTP/domain conventions.

- invalid filters return validation errors;
- cross-org UUIDs return 403/404 according to the existing resource-boundary convention and never leak foreign data;
- report for execution without assessment still renders available execution context and explicitly indicates absence of finalized assessment;
- CSV cells with missing values remain empty except the explicit historical-unit label rule above;
- ambiguous historical-unit backfill remains unknown, never guessed;
- archived template use is rejected;
- PDF rendering failures produce a controlled application error and do not expose stack traces in production configuration.

---

## 18. UI design boundary

M5 improves information architecture enough to demonstrate the product but does not perform the final M7 visual-system rewrite.

The UI reuses existing components, colors, typography and spacing tokens.

New/changed pages are exactly:

- instructor dashboard refresh at `/dashboard`;
- executive dashboard at `/dashboard/executive`;
- execution history at `/history/executions`;
- scenario template list and actions under `/scenario-templates`;
- print-specific execution report Blade view used by the PDF adapter.

### UX principles

- decision-relevant numbers first;
- no vanity metrics;
- filters remain visible and reversible;
- empty states explain the next action;
- result labels always accompany numeric score where known;
- legacy result `null` is displayed as `Sem classificação histórica`, never inferred;
- unknown historical unit displays `Sem unidade histórica`;
- reports/export controls require `reports.view` and do not appear otherwise.

---

## 19. Testing strategy

M5 implementation remains TDD-driven.

### Query/metric tests

Must prove:

- active-organization isolation;
- date filtering;
- unit filtering through snapshots;
- multi-unit execution appears only once under a matching unit filter;
- scenario filtering;
- M4 `final_score` drives averages rather than `Scenario.score`;
- pass-rate denominator excludes unknown legacy results;
- top critical errors use observed occurrences rather than catalog entries;
- open/overdue action metrics are correct;
- no foreign organization contributes to any metric.

### Historical attribution tests

Must prove:

- adding participant snapshots membership/unit/position;
- transfer after execution does not change execution's historical unit label;
- ambiguous legacy participant attribution remains null;
- cross-org membership cannot be snapshotted.

### PDF tests

Must prove:

- reports require `reports.view`;
- cross-org execution report is blocked;
- PDF response has expected content type and generated filename;
- generated data omits sensitive identifiers/contact fields;
- report data comes from M4 aggregate;
- remote/user-controlled asset fetching is not enabled.

### CSV tests

Must prove:

- stable header order;
- multi-unit aggregation is deterministic;
- tenant/filter isolation;
- ISO date serialization;
- null representation;
- spreadsheet-formula neutralization;
- large-enough dataset is streamed rather than converted into one unbounded in-memory collection by controller code.

### Template tests

Must prove:

- only published same-org source version can become template;
- using template creates new draft scenario/version;
- source version remains immutable;
- executions/assessments are not copied;
- archived template cannot be used;
- tenant isolation.

### Demo tests

Must prove:

- seeder refuses production;
- fresh database receives complete fictitious demo graph;
- second intentional demo seed does not create uncontrolled duplicate institutional graphs;
- key walkthrough routes can render from seeded data;
- fixtures use reserved/example domains and obviously fictional identities rather than real-person PII.

---

## 20. Migration strategy

M5 migrations are additive and conservative.

Expected schema changes:

1. historical attribution columns on `execution_participants`;
2. `scenario_templates` table;
3. targeted indexes justified by institutional dashboard/history queries.

No M5 migration drops legacy M4 compatibility fields or rewrites historical assessment semantics.

Rollback removes only M5-owned schema when safe under normal migration reversal.

---

## 21. Documentation delivered inside M5

M5 adds concise technical documentation needed to keep exports/demo deterministic:

- `docs/REPORTING.md` — metric definitions, CSV schema, legacy-result behavior;
- `docs/DEMO.md` — safe demo seeding and five-minute walkthrough.

Full end-user/instructor/admin documentation and Wiki polish remain M8.

---

## 22. Explicitly out of scope

M5 does not include:

- PostgreSQL production migration/hardening;
- Docker/Compose production stack;
- Redis/queues for report jobs;
- scheduled reports;
- email distribution;
- BI warehouse;
- organization-comparison across tenant boundaries;
- predictive analytics;
- scientific inferential statistics;
- AI-generated recommendations;
- external media/evidence attachments;
- legally certified signatures;
- final M7 redesign;
- M8 Wiki rewrite;
- M9 release/tagging.

If a PDF later becomes too large for synchronous generation, job queues are evaluated in M6 rather than silently expanding M5.

---

## 23. Acceptance checklist

M5 is complete only when all are true:

- [ ] active `/dashboard` no longer derives assessment metrics from legacy `Scenario.score`;
- [ ] instructor dashboard uses dedicated controller/query layer;
- [ ] executive dashboard exists at `/dashboard/executive` and requires `reports.view`;
- [ ] execution history exists at `/history/executions` with server pagination;
- [ ] common period/scenario/unit filters are centralized in `InstitutionalFilter`;
- [ ] active organization is never accepted as arbitrary client filter;
- [ ] execution participants snapshot institutional attribution for new links;
- [ ] ambiguous historical attribution is not guessed;
- [ ] finalized assessment score metrics use M4 data;
- [ ] pass rate excludes legacy assessments without result;
- [ ] top errors use `CriticalErrorOccurrence` observations;
- [ ] open/overdue action indicators work;
- [ ] execution PDF exists at `/reports/executions/{execution}/pdf`;
- [ ] PDF requires `reports.view` and tenant match;
- [ ] PDF does not expose unnecessary PII;
- [ ] PDF remote asset fetching is disabled;
- [ ] filtered CSV exists at `/reports/executions.csv`;
- [ ] CSV schema/order is stable and documented;
- [ ] CSV handles multiple execution units without inventing a primary unit;
- [ ] CSV formula injection is neutralized;
- [ ] CSV export is streamed;
- [ ] scenario templates can be created from published versions;
- [ ] template use creates a new draft scenario/version without copying history;
- [ ] template archival works;
- [ ] `DemoSeeder` refuses production;
- [ ] demo data is fictional and supports the five-minute walkthrough;
- [ ] dashboard/history/report/template endpoints preserve ability checks and tenant isolation;
- [ ] no M6/M7/M8/M9 scope is mixed in;
- [ ] `docs/REPORTING.md` exists;
- [ ] `docs/DEMO.md` exists;
- [ ] PHPUnit complete suite is green;
- [ ] Laravel Pint is green;
- [ ] migrations are green;
- [ ] Vite build is green;
- [ ] `docs/PHASE_M5_AUDIT.md` records final forensic review;
- [ ] GitHub Actions is green on the exact final M5 HEAD;
- [ ] PR is mergeable, current with `main` and has no unresolved review threads.

---

## 24. Definition of done

M5 is **READY FOR INTEGRATION** only when the application can be demonstrated end-to-end from fictional institutional data and a reviewer can answer, within a few minutes:

- what scenarios exist;
- what is running or pending;
- how completed exercises performed;
- what critical errors were actually observed;
- what corrective actions remain open;
- how to inspect one execution report;
- how to export stable data;
- how to reuse a published scenario safely through a template.

The product must reach that point without weakening M1 tenant/access guarantees, M2 version immutability, M3 execution history or M4 assessment semantics.