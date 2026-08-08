# PHASE M3 AUDIT — Simulation Engine

**Project:** Tactical Scenario Lab  
**Date:** 2026-08-08  
**Milestone:** M3 — Simulation Engine  
**Branch:** `feature/m3-simulation-engine`  
**PR:** #5 — `M3 — Simulation Engine`  
**Base:** `main` @ `c12ec5777a483d7657c1785c10c336af3b9b640c`  
**Audited functional HEAD before this document:** `f08e93ce8bc21e3a99592483980ff47cbe9ac843`  
**Status:** technically complete; final documentation HEAD still requires its own green CI before integration.

---

## 1. Executive conclusion

M3 establishes a real Simulation Engine boundary between the immutable scenario definition and each concrete training run.

The core domain is now:

```text
Scenario
  └── ScenarioVersion (published definition)
        └── ScenarioExecution (concrete run)
              ├── ExecutionTeam
              ├── ExecutionParticipant
              ├── ExecutionEvent (append-only timeline)
              ├── ExecutionInject
              └── ExecutionResource
```

A published `ScenarioVersion` can now originate multiple independent executions without mutating its historical definition. Execution state, participants, teams, timeline, instructor injects and resource snapshots belong to the execution rather than to the reusable scenario definition.

No structured M4 Assessment/Debriefing domain was introduced in M3. The legacy evaluation flow on `Scenario` remains temporarily compatible and is explicitly treated as debt to be migrated in M4.

---

## 2. Repository gate at the audited functional HEAD

At `f08e93ce8bc21e3a99592483980ff47cbe9ac843`:

- PR #5: open;
- draft: yes;
- mergeable: yes;
- unresolved review threads: 0;
- branch vs `main`: 57 commits ahead, 0 behind;
- changed files: 34;
- additions/deletions at that point: +3375 / -133;
- GitHub Actions run #515: **success**;
- Pint: success;
- frontend build: success;
- migrations: success;
- PHPUnit: success.

This audit document creates a new HEAD and therefore **run #515 is not sufficient as the final merge gate**. The exact documentation HEAD must receive a new successful GitHub Actions run before the PR can be declared `READY FOR INTEGRATION`.

---

## 3. Migrations introduced

### 3.1 `2026_08_08_130000_create_scenario_executions_table.php`

Creates `scenario_executions` with:

- public UUID;
- institutional `organization_id`;
- `scenario_version_id`;
- sequential run number per version;
- lifecycle status: `draft`, `running`, `completed`, `cancelled`;
- `started_at`, `completed_at`, `cancelled_at`;
- uniqueness for `(scenario_version_id, sequence_number)`;
- useful status indexes.

Compatibility backfill:

- pure legacy drafts are not converted into executions;
- legacy scenarios already running/completed (or with `started_at`) receive execution sequence 1;
- backfill points to the highest existing scenario version;
- institutional ownership and relevant timestamps are preserved.

### 3.2 `2026_08_08_131000_create_execution_teams_table.php`

Creates execution-scoped teams with UUID, label and optional description.

### 3.3 `2026_08_08_131100_create_execution_participants_table.php`

Creates execution participants with:

- public UUID;
- execution FK;
- optional team FK;
- required `person_id`;
- contextual role;
- uniqueness of `(scenario_execution_id, person_id)`.

### 3.4 `2026_08_08_132000_create_execution_events_table.php`

Creates the execution timeline with:

- UUID;
- execution FK;
- optional team/participant context;
- allowlisted kind;
- exact `occurred_at`;
- summary;
- optional metadata;
- chronological indexes.

### 3.5 `2026_08_08_133000_create_execution_injects_table.php`

Creates instructor injects with lifecycle:

- `planned`;
- `delivered`;
- `cancelled`;
- optional planned offset;
- delivery/cancellation timestamps.

### 3.6 `2026_08_08_134000_create_execution_resources_table.php`

Creates execution-specific resource snapshots with:

- UUID;
- resource name;
- planned / available / used quantities;
- `available`, `unavailable`, `depleted` status;
- uniqueness by execution + name.

---

## 4. Domain models

### `ScenarioExecution`

Represents one concrete training run and provides explicit state predicates/guards:

- `isDraft()`;
- `isRunning()`;
- `isCompleted()`;
- `isCancelled()`;
- `canStart()`;
- `canComplete()`;
- `canCancel()`;
- `canConfigure()`.

Relations:

- organization;
- scenario version;
- teams;
- participants;
- ordered events;
- injects;
- resources.

### `ExecutionTeam`

Execution-local team aggregate with participants.

### `ExecutionParticipant`

Links an institutional `Person` to one execution and optionally one execution team.

### `ExecutionEvent`

Timeline item. Application-level Eloquent updates and deletes throw:

`Execution timeline events are append-only.`

There is no HTTP edit/delete route for timeline events.

### `ExecutionInject`

Instructor-controlled contextual stimulus with planned/delivered/cancelled lifecycle.

### `ExecutionResource`

Protects the domain invariant at the model boundary:

```text
0 <= used_quantity <= available_quantity <= planned_quantity
```

Invalid direct model writes throw `InvalidArgumentException`, so the invariant is not limited to HTTP validation.

---

## 5. Services and transactional guarantees

### `ScenarioExecutionManager`

#### Creation

- rejects unpublished `ScenarioVersion`;
- executes inside a DB transaction;
- locks the parent scenario row while calculating the next sequence;
- assigns the next sequence number;
- creates execution as `draft`;
- snapshots nonblank distinct resources from the published version in the **same transaction**.

#### Lifecycle

`start`, `complete` and `cancel` were concurrency-hardened during the final audit.

Each transition now:

1. opens a transaction;
2. reloads the persisted execution;
3. applies `lockForUpdate()`;
4. evaluates the transition against the locked persisted state;
5. mutates only when the transition remains valid.

This prevents a stale Eloquent instance from overwriting a transition already committed by another request/session.

### `ExecutionInjectManager`

Delivery:

- locks the inject inside a transaction;
- requires `planned` status;
- requires execution `running`;
- marks inject delivered;
- appends exactly one `ExecutionEvent(kind='inject')` in the same transaction;
- repeated delivery is rejected.

Cancellation:

- locks the inject;
- only planned injects may be cancelled;
- execution must still be configurable.

---

## 6. Institutional authorization and tenant isolation

M3 reuses the institutional access boundary established in M1.

### Read operations

Require `scenarios.view`.

### Mutation operations

Require `scenarios.manage`.

### Tenant enforcement

HTTP controllers never trust an `organization_id` coming from user payloads. The active organization is resolved by `ActiveOrganization`, then ownership is checked before mutation.

Covered resources include:

- scenario version publication;
- execution creation;
- execution show/start/complete/cancel;
- teams;
- participants;
- events;
- injects;
- resources.

Cross-organization crafted requests are covered by tests and rejected without mutation.

---

## 7. Participant integrity

A participant may be attached only if:

- the person exists;
- the person is active;
- the person has an active `OrganizationMembership` in the execution organization;
- the membership is not ended;
- the person is not already attached to that execution;
- any assigned team belongs to that same execution.

The UI uses public person/team UUIDs. The backend retains compatibility with internal IDs for controlled/internal callers while enforcing same-execution and same-organization constraints.

---

## 8. Public UUID boundary

New externally addressable M3 aggregates use the existing `HasPublicUuid` concern:

- `ScenarioExecution`;
- `ExecutionTeam`;
- `ExecutionParticipant`;
- `ExecutionEvent`;
- `ExecutionInject`;
- `ExecutionResource`.

Scenario versions already used the same UUID foundation from M2.

The instructor-facing forms avoid exposing raw numeric person/team IDs by using UUID values.

---

## 9. Timeline integrity and metadata hardening

Timeline events:

- can be appended only while execution is `running`;
- retain their explicit `occurred_at`;
- reject team/participant references belonging to another execution;
- are ordered by `occurred_at`, then `id`;
- cannot be updated/deleted through the Eloquent domain model.

### Audit hardening

The original M3 implementation accepted arbitrary HTTP `metadata` arrays. The final audit identified that as an unnecessary data-governance surface.

The HTTP endpoint now permits only:

```text
metadata.source = instructor | observer | system
```

Unknown/sensitive keys such as `password` are rejected and the event is not persisted.

Internal trusted services may still create controlled system metadata directly, e.g. an inject event with `inject_uuid`.

---

## 10. Instructor inject guarantees

- creation allowed while execution is draft/running;
- delivery only while running;
- exactly one timeline event per successful delivery;
- re-delivery rejected;
- cancelled inject cannot be delivered;
- delivery/cancellation use transaction + row locking;
- tenant/ability checks are enforced at HTTP boundary.

---

## 11. Resource snapshot guarantees

On each new execution:

- the published version's resource names are copied into execution-local resource records;
- blank values are ignored;
- duplicate names are collapsed;
- the scenario version remains unchanged by later execution resource usage;
- each resource starts `planned=1`, `available=1`, `used=0`, `available`;
- quantity consistency is enforced at model boundary;
- HTTP updates require active organization + `scenarios.manage` and a configurable execution.

---

## 12. Instructor cockpit UX

### Scenario launchpad

The scenario detail page now presents the version/execution model explicitly:

- draft latest version → `Publicar versão`;
- published latest version → `Nova execução`;
- execution history per version;
- status cards linking to each concrete execution.

The old legacy `ScenarioController::execute` endpoint remains for compatibility but is no longer presented as the primary M3 execution path.

### Execution cockpit

The execution page brings together:

- lifecycle/status;
- scale and context;
- `Timeline da execução`;
- `Equipes e participantes`;
- `Injects do instrutor`;
- `Recursos`;
- context-sensitive management actions.

The design uses the existing institutional visual language and reserves destructive/emergency styling for meaningful destructive/critical actions.

---

## 13. Legacy compatibility retained until M4

M3 intentionally does **not** delete or migrate the existing assessment/debrief fields from `Scenario`.

The legacy evaluation screen remains available while a legacy scenario is evaluable and still supports:

- score;
- debrief notes;
- `observed_critical_errors[]` selection from the generated critical-error catalog.

The final audit detected that the cockpit refactor had temporarily removed the observed-error checkboxes. A RED test reproduced the regression and the selector was restored before M3 completion.

This preserves behavior without pretending that legacy assessment is the final model.

---

## 14. Tests added for M3

### `ScenarioExecutionTest`

Covers:

- schema;
- multiple executions per version;
- lifecycle predicates;
- unpublished-version rejection;
- sequential execution creation;
- lifecycle transitions;
- stale-model concurrency protection.

### `ExecutionIsolationTest`

Covers:

- protected publication;
- protected execution creation;
- cross-org version/execution attacks;
- cross-org lifecycle mutation rejection;
- view-only access.

### `ExecutionParticipantsTest`

Covers:

- schema;
- teams;
- institutional participants;
- active membership requirement;
- foreign-team rejection;
- duplicate participant protection.

### `ExecutionTimelineTest`

Covers:

- schema;
- event append during running state;
- exact timestamp and controlled metadata;
- non-running write rejection;
- foreign team/participant rejection;
- immutable append-only history;
- sensitive/unapproved HTTP metadata rejection.

### `ExecutionInjectTest`

Covers:

- schema;
- planned creation;
- atomic delivery;
- exactly one timeline event;
- delivery state restrictions;
- cancellation;
- re-delivery protection.

### `ExecutionResourceTest`

Covers:

- schema;
- resource snapshot on execution creation;
- domain quantity invariant;
- authorized update.

### `ExecutionCockpitTest`

Covers:

- version publication CTA;
- new execution CTA/history;
- four cockpit modules;
- preservation of legacy observed critical-error selection until M4.

---

## 15. TDD / CI evidence

Major GREEN gates during M3:

- run #465 — ScenarioExecution foundation;
- run #467 — execution manager lifecycle;
- run #473 — HTTP isolation/publication/lifecycle;
- run #483 — teams and participants;
- run #489 — append-only timeline;
- run #496 — instructor injects;
- run #503 — execution resources;
- run #509 — instructor cockpit.

Forensic hardening cycles:

- run #510 — RED: sensitive metadata accepted;
- run #511 — GREEN: metadata allowlist;
- run #512 — RED: observed critical-error UI regression;
- run #513 — GREEN: legacy selector restored;
- run #514 — RED: stale lifecycle object could overwrite persisted state;
- run #515 — GREEN: lifecycle transitions revalidated under row lock.

The final merge gate must use a newer successful run on the exact HEAD containing this audit document.

---

## 16. Explicit M4 deferrals

The following are intentionally **not M3**:

- structured assessment entities;
- objectives/criteria/evidence scoring model;
- separate observed critical-error records per execution;
- structured key-time assessment;
- debriefing sections;
- action plan (`ação | responsável | prazo | status`);
- evaluator workflow/reporting;
- migration/removal of legacy score/debrief fields from `Scenario`.

These belong to **M4 — Assessment & Debriefing**.

---

## 17. Other explicit deferrals

Not introduced in M3:

- M5 executive reports/export productization;
- M6 PostgreSQL/production-hardening work beyond existing foundations;
- M7 final visual-design-system audit;
- M8 Premium Elite Wiki;
- M9 release audit/tagging;
- AI, Research Hub, marketplace, mobile-native or broader TMA Platform scope.

---

## 18. Known transitional debt

1. Legacy execution/evaluation endpoints and fields still exist on `Scenario` for compatibility. They must be retired only after M4 migration is complete and tested.
2. Application-level append-only protection is implemented for `ExecutionEvent`; database-level immutable-event enforcement is not introduced in M3.
3. The execution resource status and numeric quantities are both stored; deeper derived-status automation can be considered later but is not required by the current M3 contract.
4. Route/controller compatibility accepts numeric internal participant/team IDs in addition to UUIDs for controlled callers, but the instructor UI uses UUIDs and ownership checks prevent cross-execution attachment.

None of the above is a blocker for the scoped M3 deliverable.

---

## 19. Final acceptance checklist

- [x] multiple executions per published scenario version;
- [x] unpublished version cannot create execution;
- [x] execution lifecycle isolated from scenario definition;
- [x] legacy running/completed scenarios receive controlled execution backfill;
- [x] execution teams;
- [x] institutional participants;
- [x] active membership enforcement;
- [x] duplicate participant protection;
- [x] cross-execution team/participant rejection;
- [x] append-only timeline;
- [x] timeline write only while running;
- [x] safe HTTP timeline metadata allowlist;
- [x] instructor inject lifecycle;
- [x] exactly one timeline event per inject delivery;
- [x] execution resource snapshot;
- [x] resource quantity domain invariant;
- [x] public UUID foundation;
- [x] cross-org execution/version protection;
- [x] stale lifecycle concurrency protection;
- [x] scenario execution launchpad;
- [x] instructor cockpit;
- [x] legacy assessment compatibility retained;
- [x] no structured M4 domain introduced;
- [x] PR mergeable at functional audited HEAD;
- [x] zero unresolved review threads at functional audited HEAD;
- [x] branch 0 commits behind `main` at functional audited HEAD;
- [ ] GitHub Actions success on **exact final documentation HEAD**.

---

## 20. Audit verdict

**Functional M3 implementation: PASS.**

The Simulation Engine is technically complete for the approved M3 scope and the final forensic review closed three objective defects through RED → GREEN cycles: unsafe arbitrary timeline metadata, legacy observed-error UI regression, and stale lifecycle concurrency.

**Integration verdict remains `PENDING FINAL HEAD CI` until the commit containing this document receives a successful GitHub Actions run.**
