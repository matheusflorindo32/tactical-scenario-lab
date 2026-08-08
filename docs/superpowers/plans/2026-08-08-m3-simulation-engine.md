# M3 — Simulation Engine Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Separar definitivamente a definição versionada de um cenário de cada execução realizada, permitindo múltiplas execuções por `ScenarioVersion` com equipes, participantes, timeline, intervenções, instructor injects e recursos operacionais.

**Architecture:** `ScenarioVersion` permanece como definição histórica. `ScenarioExecution` passa a representar uma realização concreta e possui seus próprios times, participantes, eventos, injects e recursos. O fluxo legado existente em `Scenario` permanece compatível durante M3 e será aposentado apenas em marco posterior; M4 continuará responsável por assessment/debriefing estruturado.

**Tech Stack:** Laravel 13, PHP 8.4, Eloquent, Blade, SQLite no CI/local, PostgreSQL como alvo de produção, PHPUnit, GitHub Actions.

## Global Constraints

- Não implementar M4 Assessment/Debriefing neste plano.
- Não remover campos legados de execução/score/debrief do `Scenario` nesta fase.
- Toda execução pertence a uma `organization_id` e a uma `scenario_version_id`.
- Toda leitura/escrita HTTP deve respeitar a organização ativa.
- Usar UUID público nos novos agregados expostos externamente.
- `scenarios.view` autoriza leitura; `scenarios.manage` autoriza gestão da execução no M3.
- Uma execução só pode nascer de uma `ScenarioVersion` publicada.
- Timeline operacional é append-only depois de concluída/cancelada.
- TDD obrigatório por ciclo RED → GREEN; CI verde no HEAD final.

---

## File Structure

- `app/Models/ScenarioExecution.php` — agregado de uma execução e lifecycle.
- `app/Services/ScenarioExecutionManager.php` — criação e transições transacionais.
- `app/Models/ExecutionTeam.php` — equipes da execução.
- `app/Models/ExecutionParticipant.php` — participantes institucionais da execução.
- `app/Models/ExecutionEvent.php` — timeline cronológica, incluindo ações, observações e intervenções.
- `app/Models/ExecutionInject.php` — injects do instrutor e lifecycle de entrega.
- `app/Models/ExecutionResource.php` — recursos disponíveis/consumidos na execução.
- `app/Http/Controllers/ScenarioExecutionController.php` — listagem, criação, detalhe e lifecycle.
- `app/Http/Controllers/ScenarioVersionController.php` — publicação explícita de versão antes da execução.
- `app/Http/Controllers/ExecutionTeamController.php` — criação de equipe.
- `app/Http/Controllers/ExecutionParticipantController.php` — vínculo de pessoa/equipe.
- `app/Http/Controllers/ExecutionEventController.php` — registro de eventos/intervenções.
- `app/Http/Controllers/ExecutionInjectController.php` — criação/entrega/cancelamento de injects.
- `app/Http/Controllers/ExecutionResourceController.php` — atualização de disponibilidade/uso.
- `resources/views/executions/index.blade.php` — histórico de execuções da versão/cenário.
- `resources/views/executions/show.blade.php` — cockpit da execução.
- `tests/Feature/ScenarioExecutionTest.php` — fundação, lifecycle e múltiplas execuções.
- `tests/Feature/ExecutionIsolationTest.php` — isolamento institucional e crafted requests.
- `tests/Feature/ExecutionParticipantsTest.php` — equipes e participantes.
- `tests/Feature/ExecutionTimelineTest.php` — eventos/intervenções.
- `tests/Feature/ExecutionInjectTest.php` — injects.
- `tests/Feature/ExecutionResourceTest.php` — recursos.

---

### Task 1: ScenarioExecution foundation + compatibilidade legada

**Files:**
- Create: `database/migrations/2026_08_08_130000_create_scenario_executions_table.php`
- Create: `app/Models/ScenarioExecution.php`
- Modify: `app/Models/ScenarioVersion.php`
- Test: `tests/Feature/ScenarioExecutionTest.php`

**Interfaces:**
- Produces: `ScenarioVersion::executions(): HasMany`
- Produces: `ScenarioExecution::{isDraft,isRunning,isCompleted,isCancelled,canStart,canComplete,canCancel}(): bool`

- [ ] **Step 1: Write failing schema/lifecycle tests**

```php
public function test_same_published_version_can_have_multiple_execution_records(): void
{
    [$scenario, $version] = $this->publishedVersion();

    ScenarioExecution::create([
        'organization_id' => $scenario->organization_id,
        'scenario_version_id' => $version->id,
        'sequence_number' => 1,
        'status' => 'draft',
    ]);
    ScenarioExecution::create([
        'organization_id' => $scenario->organization_id,
        'scenario_version_id' => $version->id,
        'sequence_number' => 2,
        'status' => 'draft',
    ]);

    $this->assertCount(2, $version->fresh()->executions);
}
```

Also assert columns `uuid`, `organization_id`, `scenario_version_id`, `sequence_number`, `status`, `started_at`, `completed_at`, `cancelled_at` exist and `(scenario_version_id, sequence_number)` is unique by attempting a duplicate.

- [ ] **Step 2: Run test to verify RED**

Run: `php artisan test --filter=ScenarioExecutionTest`
Expected: FAIL because `scenario_executions` / `ScenarioExecution` do not exist.

- [ ] **Step 3: Implement migration/model**

Create `scenario_executions` using BIGINT FKs, UUID unique, unsigned integer `sequence_number`, string status default `draft`, nullable timestamps and unique pair `(scenario_version_id, sequence_number)`.

Migration backfill rule: for every legacy `Scenario` whose `status` is `running`/`completed` or `started_at` is non-null, locate its highest `scenario_versions.version_number` and insert execution sequence 1 preserving `organization_id`, mapped status, `started_at` and `completed_at`. Do not backfill pure drafts.

- [ ] **Step 4: Run focused + full suite**

Run: `php artisan test --filter=ScenarioExecutionTest && php artisan test`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add database/migrations/2026_08_08_130000_create_scenario_executions_table.php app/Models/ScenarioExecution.php app/Models/ScenarioVersion.php tests/Feature/ScenarioExecutionTest.php
git commit -m "feat: add scenario execution foundation"
```

---

### Task 2: Execution manager, publication gate and HTTP isolation

**Files:**
- Create: `app/Services/ScenarioExecutionManager.php`
- Create: `app/Http/Controllers/ScenarioExecutionController.php`
- Create: `app/Http/Controllers/ScenarioVersionController.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/ScenarioExecutionTest.php`
- Test: `tests/Feature/ExecutionIsolationTest.php`

**Interfaces:**
- Produces: `ScenarioExecutionManager::create(ScenarioVersion $version): ScenarioExecution`
- Produces: `ScenarioExecutionManager::start(ScenarioExecution $execution): ScenarioExecution`
- Produces: `ScenarioExecutionManager::complete(ScenarioExecution $execution): ScenarioExecution`
- Produces: `ScenarioExecutionManager::cancel(ScenarioExecution $execution): ScenarioExecution`

- [ ] **Step 1: Write failing lifecycle/publication tests**

```php
public function test_execution_requires_published_version(): void
{
    [, $draftVersion] = $this->scenarioWithVersion();

    $this->expectException(LogicException::class);
    app(ScenarioExecutionManager::class)->create($draftVersion);
}

public function test_execution_lifecycle_is_draft_running_completed(): void
{
    [, $version] = $this->publishedVersion();
    $manager = app(ScenarioExecutionManager::class);

    $execution = $manager->create($version);
    $this->assertSame('draft', $execution->status);
    $manager->start($execution);
    $this->assertSame('running', $execution->fresh()->status);
    $manager->complete($execution->fresh());
    $this->assertSame('completed', $execution->fresh()->status);
}
```

- [ ] **Step 2: Verify RED**

Run: `php artisan test --filter=ScenarioExecutionTest`
Expected: FAIL because manager/controller/routes do not exist.

- [ ] **Step 3: Implement service + routes/controllers**

Routes:

```php
Route::patch('/scenario-versions/{scenarioVersion}/publish', [ScenarioVersionController::class, 'publish'])->name('scenario-versions.publish');
Route::post('/scenario-versions/{scenarioVersion}/executions', [ScenarioExecutionController::class, 'store'])->name('executions.store');
Route::get('/executions/{execution}', [ScenarioExecutionController::class, 'show'])->name('executions.show');
Route::patch('/executions/{execution}/start', [ScenarioExecutionController::class, 'start'])->name('executions.start');
Route::patch('/executions/{execution}/complete', [ScenarioExecutionController::class, 'complete'])->name('executions.complete');
Route::patch('/executions/{execution}/cancel', [ScenarioExecutionController::class, 'cancel'])->name('executions.cancel');
```

`store/start/complete/cancel` require `scenarios.manage`; `show` requires `scenarios.view`. Every controller verifies `execution.organization_id === ActiveOrganization::id(...)` or the version's scenario organization before mutation.

`create()` computes next `sequence_number = max + 1` inside `DB::transaction()` and rejects any version whose `publication_status !== 'published'`.

- [ ] **Step 4: Add crafted cross-org tests**

Assert user in Organization A receives 403 when reading, starting, completing or cancelling execution from Organization B, even with a valid UUID/route model binding.

- [ ] **Step 5: Run focused + full suite and commit**

Run: `php artisan test --filter='ScenarioExecutionTest|ExecutionIsolationTest' && php artisan test`
Expected: PASS.

Commit: `feat: add execution lifecycle and institutional isolation`.

---

### Task 3: Teams and institutional participants

**Files:**
- Create: `database/migrations/2026_08_08_131000_create_execution_teams_table.php`
- Create: `database/migrations/2026_08_08_131100_create_execution_participants_table.php`
- Create: `app/Models/ExecutionTeam.php`
- Create: `app/Models/ExecutionParticipant.php`
- Create: `app/Http/Controllers/ExecutionTeamController.php`
- Create: `app/Http/Controllers/ExecutionParticipantController.php`
- Modify: `app/Models/ScenarioExecution.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/ExecutionParticipantsTest.php`

**Interfaces:**
- `ScenarioExecution::teams(): HasMany`
- `ScenarioExecution::participants(): HasMany`
- `ExecutionParticipant` requires `person_id`; `team_id` is nullable.

- [ ] **Step 1: Write RED tests**

Assert an execution can have multiple teams and participants, a person cannot be duplicated within the same execution, and a participant can only reference a `Person` with an active `OrganizationMembership` in the execution organization.

- [ ] **Step 2: Verify RED**

Run: `php artisan test --filter=ExecutionParticipantsTest`
Expected: FAIL because schema/models do not exist.

- [ ] **Step 3: Implement schema/models/controllers**

`execution_teams`: UUID, execution FK, label max 100, optional description max 500.

`execution_participants`: UUID, execution FK, nullable team FK, required person FK, role max 80; unique `(scenario_execution_id, person_id)`.

Controller rejects team from another execution and person without membership where `organization_id` matches, `status='active'`, and `ended_at IS NULL`.

- [ ] **Step 4: Run focused/full tests and commit**

Commit: `feat: add execution teams and participants`.

---

### Task 4: Append-only timeline and interventions

**Files:**
- Create: `database/migrations/2026_08_08_132000_create_execution_events_table.php`
- Create: `app/Models/ExecutionEvent.php`
- Create: `app/Http/Controllers/ExecutionEventController.php`
- Modify: `app/Models/ScenarioExecution.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/ExecutionTimelineTest.php`

**Interfaces:**
- Allowed kinds: `observation`, `action`, `intervention`, `system`, `inject`, `resource`.
- `ScenarioExecution::events(): HasMany` ordered by `occurred_at`, then `id`.

- [ ] **Step 1: Write RED tests**

Assert events can only be appended while execution is `running`; events preserve exact `occurred_at`; participant/team references must belong to the same execution; completed/cancelled executions reject new timeline writes.

- [ ] **Step 2: Verify RED**

Run: `php artisan test --filter=ExecutionTimelineTest`
Expected: FAIL.

- [ ] **Step 3: Implement event storage/controller**

Columns: UUID, execution FK, nullable team FK, nullable participant FK, kind, `occurred_at`, `summary` max 500, nullable JSON `metadata`.

Controller whitelist-validates kind and never accepts `organization_id` from payload.

- [ ] **Step 4: Run focused/full tests and commit**

Commit: `feat: add append-only execution timeline`.

---

### Task 5: Instructor injects with delivery trace

**Files:**
- Create: `database/migrations/2026_08_08_133000_create_execution_injects_table.php`
- Create: `app/Models/ExecutionInject.php`
- Create: `app/Services/ExecutionInjectManager.php`
- Create: `app/Http/Controllers/ExecutionInjectController.php`
- Modify: `app/Models/ScenarioExecution.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/ExecutionInjectTest.php`

**Interfaces:**
- Statuses: `planned`, `delivered`, `cancelled`.
- `ExecutionInjectManager::deliver()` atomically marks delivered and appends an `ExecutionEvent(kind='inject')`.

- [ ] **Step 1: Write RED tests**

Assert planned inject can be created in draft/running execution, delivered only while running, delivery is idempotency-protected, cancelled inject cannot be delivered, and delivery creates exactly one timeline event.

- [ ] **Step 2: Verify RED**

Run: `php artisan test --filter=ExecutionInjectTest`
Expected: FAIL.

- [ ] **Step 3: Implement inject schema/model/manager/controller**

Columns: UUID, execution FK, label max 150, content text, nullable `planned_offset_seconds`, status default `planned`, nullable `delivered_at`, nullable `cancelled_at`.

- [ ] **Step 4: Run focused/full tests and commit**

Commit: `feat: add instructor inject lifecycle`.

---

### Task 6: Execution resource snapshot and usage

**Files:**
- Create: `database/migrations/2026_08_08_134000_create_execution_resources_table.php`
- Create: `app/Models/ExecutionResource.php`
- Create: `app/Http/Controllers/ExecutionResourceController.php`
- Modify: `app/Services/ScenarioExecutionManager.php`
- Modify: `app/Models/ScenarioExecution.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/ExecutionResourceTest.php`

**Interfaces:**
- Every string in `ScenarioVersion.resources` becomes an execution resource snapshot on execution creation.
- Invariant: `0 <= used_quantity <= available_quantity <= planned_quantity`.

- [ ] **Step 1: Write RED tests**

Create published version with `['Kit IFAK', 'Rádio']`; create execution; assert exactly two resources are snapshotted. Assert invalid negative/excess usage is rejected.

- [ ] **Step 2: Verify RED**

Run: `php artisan test --filter=ExecutionResourceTest`
Expected: FAIL.

- [ ] **Step 3: Implement resources**

Columns: UUID, execution FK, name max 120, unsigned `planned_quantity` default 1, `available_quantity` default 1, `used_quantity` default 0, status `available|unavailable|depleted`.

- [ ] **Step 4: Run focused/full tests and commit**

Commit: `feat: snapshot and track execution resources`.

---

### Task 7: Instructor cockpit UX

**Files:**
- Create: `resources/views/executions/show.blade.php`
- Modify: `resources/views/scenarios/show-scalable.blade.php`
- Modify: `app/Http/Controllers/ScenarioExecutionController.php`
- Test: `tests/Feature/ScenarioExecutionTest.php`

**Interfaces:**
- Scenario page exposes publication state and execution history.
- Execution cockpit shows status, version, teams, participants, resources, injects and timeline without exposing numeric internal IDs.

- [ ] **Step 1: Write RED view tests**

Assert scenario page shows `Publicar versão` for a draft latest version and `Nova execução` for a published version. Assert execution page renders `Timeline da execução`, `Equipes e participantes`, `Injects do instrutor` and `Recursos`.

- [ ] **Step 2: Verify RED**

Run: `php artisan test --filter=ScenarioExecutionTest`
Expected: FAIL before views/controllers are wired.

- [ ] **Step 3: Implement accessible Blade UX**

Use existing design-system components and semantic headings. Keep critical/destructive actions visually distinct; do not use red decoratively. Preserve legacy evaluation UI but stop presenting the old `ScenarioController::execute` action as the primary execution path.

- [ ] **Step 4: Run build/full tests and commit**

Run: `npm run build && php artisan test && vendor/bin/pint --test`
Expected: PASS.

Commit: `feat: add instructor execution cockpit`.

---

### Task 8: M3 audit and final gate

**Files:**
- Create: `docs/PHASE_M3_AUDIT.md`
- Modify only if objectively required by audit: M3 files above.

- [ ] **Step 1: Audit scope and security**

Verify: multiple executions per version; published-version gate; lifecycle; cross-org direct URL/crafted POST; team/participant ownership; active membership; timeline append-only; inject delivery trace; resource invariants; no new M4 behavior; no PII copied into audit metadata.

- [ ] **Step 2: Run final CI-equivalent suite**

Run: `npm ci && npm run build && php artisan migrate:fresh --force && php artisan test && vendor/bin/pint --test`
Expected: PASS.

- [ ] **Step 3: Create `docs/PHASE_M3_AUDIT.md`**

Record exact HEAD, migrations, models, routes, security guarantees, tests, CI run number and explicit limitations deferred to M4/M5.

- [ ] **Step 4: GitHub gate**

Require PR mergeable, zero unresolved review threads, branch zero commits behind `main`, and GitHub Actions success on final HEAD.

- [ ] **Step 5: Stop before merge**

Do not merge until the final gate is reported and integration is explicitly selected.
