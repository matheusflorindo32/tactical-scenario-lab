# M5 — Institutional Product Layer Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Turn M1–M4 into a demonstrable institutional product with trustworthy dashboards, historically stable unit attribution, paginated history, PDF/CSV reporting, reusable scenario templates and deterministic fictional demo data.

**Architecture:** M5 adds a dedicated read/query layer over the normalized operational database. Active organization remains the tenant boundary; participant membership/unit/position are snapshotted at execution time; dashboards/history/reports consume M3/M4 truth; one presentation-safe report builder feeds PDF; CSV has its own fixed row schema; templates clone immutable scenario definition including modeled victims/cohorts but never execution/assessment history.

**Tech Stack:** Laravel 13, PHP 8.4 in CI, Blade, Eloquent, SQLite current CI, PHPUnit 12, Laravel Pint, Vite/Tailwind, `dompdf/dompdf:^3.1`.

## Global Constraints

- No client-controlled `organization_id`; always resolve active organization through `ActiveOrganization`.
- Add no new ability: use only existing `scenarios.view`, `scenarios.manage`, `reports.view` for M5.
- Default reporting window is current day through previous 89 days; maximum inclusive window is 366 days.
- Active reporting truth is `ScenarioExecution` + `ExecutionAssessment` + `CriticalErrorOccurrence` + `ActionItem`, never legacy `Scenario.score` or critical-error catalog counts.
- Historical unit attribution is never guessed. New participant links require one explicit same-org active `OrganizationMembership`; old rows backfill only when exactly one membership matches the deterministic anchor rule.
- Multi-unit executions stay multi-unit; no primary unit is invented.
- PDF exposes only presentation-safe training data, escapes user prose and disables remote resources.
- CSV uses the exact documented 16-column schema, ISO 8601 dates, `0|1` booleans, empty nulls except the explicit unknown-unit label, formula-injection neutralization and streaming/chunking.
- Templates are organization-scoped, source only published versions, archived rather than normally deleted, and never copy executions/assessments/evidence/debrief history.
- `DemoSeeder` refuses `production`, is deterministic enough for deliberate second execution, and uses only clearly fictional/example-domain data.
- No M6 PostgreSQL/Docker/queues, M7 final redesign, M8 Wiki rewrite or M9 release/tagging.
- Every functional task follows RED → GREEN and ends with migrations + PHPUnit + Pint + Vite green on that exact task HEAD.

---

### Task 1: Historical participant attribution

**Files:**
- Create: `database/migrations/2026_08_08_150000_add_historical_attribution_to_execution_participants.php`
- Modify: `app/Models/ExecutionParticipant.php`
- Modify: `app/Http/Controllers/ExecutionParticipantController.php`
- Modify: `resources/views/executions/show.blade.php`
- Test: `tests/Feature/ExecutionParticipantAttributionTest.php`

**Interfaces:**
- Produces fields `organization_membership_id`, `unit_id_snapshot`, `unit_name_snapshot`, `position_snapshot`.
- Produces `ExecutionParticipant::membership(): BelongsTo` and `ExecutionParticipant::unitSnapshot(): BelongsTo`.

- [ ] **Step 1: Write the failing schema and snapshot tests**

```php
public function test_execution_participant_has_historical_attribution_columns(): void
{
    $this->assertTrue(Schema::hasColumns('execution_participants', [
        'organization_membership_id', 'unit_id_snapshot', 'unit_name_snapshot', 'position_snapshot',
    ]));
}

public function test_new_participant_snapshots_explicit_same_org_membership(): void
{
    [$actor, $organization, $execution, $person, $membership, $unit] = $this->fixture();

    $this->actingAs($actor)
        ->withSession(['active_organization_id' => $organization->id])
        ->post(route('execution-participants.store', $execution), [
            'person_uuid' => $person->uuid,
            'organization_membership_uuid' => $membership->uuid,
            'role' => 'Líder',
        ])->assertRedirect();

    $participant = $execution->participants()->firstOrFail();
    $this->assertSame($membership->id, $participant->organization_membership_id);
    $this->assertSame($unit->id, $participant->unit_id_snapshot);
    $this->assertSame($unit->name, $participant->unit_name_snapshot);
    $this->assertSame($membership->position, $participant->position_snapshot);
}
```

- [ ] **Step 2: Run RED**

Run: `php artisan test tests/Feature/ExecutionParticipantAttributionTest.php`
Expected: FAIL because the new columns/request field do not exist.

- [ ] **Step 3: Add additive schema + conservative backfill**

```php
Schema::table('execution_participants', function (Blueprint $table): void {
    $table->foreignId('organization_membership_id')->nullable()->after('person_id')
        ->constrained('organization_memberships')->nullOnDelete();
    $table->foreignId('unit_id_snapshot')->nullable()->after('organization_membership_id')
        ->constrained('units')->nullOnDelete();
    $table->string('unit_name_snapshot')->nullable()->after('unit_id_snapshot');
    $table->string('position_snapshot')->nullable()->after('unit_name_snapshot');
    $table->index(['scenario_execution_id', 'unit_id_snapshot']);
});
```

For each existing participant use `anchor = execution.started_at ?? execution.created_at`. Candidate membership must match person + execution organization, `started_at <= anchor`, `ended_at IS NULL OR ended_at >= anchor`, and `deleted_at IS NULL OR deleted_at > anchor`. Update snapshot only when `candidate_count === 1`; otherwise leave all snapshot fields null.

- [ ] **Step 4: Require explicit represented membership for new participant links**

```php
'organization_membership_uuid' => ['required', 'uuid', 'exists:organization_memberships,uuid'],
```

```php
$membership = OrganizationMembership::query()
    ->where('uuid', $validated['organization_membership_uuid'])
    ->where('person_id', $person->id)
    ->where('organization_id', $organizationId)
    ->where('status', 'active')
    ->whereNull('ended_at')
    ->with('unit')
    ->firstOrFail();

$execution->participants()->create([
    'person_id' => $person->id,
    'organization_membership_id' => $membership->id,
    'unit_id_snapshot' => $membership->unit_id,
    'unit_name_snapshot' => $membership->unit?->name,
    'position_snapshot' => $membership->position,
    'execution_team_id' => $team?->id,
    'role' => $validated['role'] ?? null,
]);
```

- [ ] **Step 5: Add regression tests**

```php
public function test_cross_org_membership_cannot_be_snapshotted(): void
{
    [$actor, $organization, $execution, $person] = $this->fixtureWithoutMembership();
    $foreignMembership = $this->foreignMembershipFor($person);

    $this->actingAs($actor)
        ->withSession(['active_organization_id' => $organization->id])
        ->post(route('execution-participants.store', $execution), [
            'person_uuid' => $person->uuid,
            'organization_membership_uuid' => $foreignMembership->uuid,
        ])->assertForbidden();

    $this->assertDatabaseCount('execution_participants', 0);
}

public function test_unit_rename_after_link_does_not_rewrite_historical_label(): void
{
    $participant = $this->createParticipantThroughHttp();
    $original = $participant->unit_name_snapshot;
    $participant->unitSnapshot()->update(['name' => 'Nova Unidade']);
    $this->assertSame($original, $participant->fresh()->unit_name_snapshot);
}
```

Also add migration-level fixtures proving zero and multiple candidates remain null.

- [ ] **Step 6: Run GREEN gate**

Run: `php artisan migrate:fresh --force && php artisan test && vendor/bin/pint --test && npm run build`
Expected: all PASS.

- [ ] **Step 7: Commit**

Commit: `feat(m5): snapshot participant institutional attribution`

---

### Task 2: Central filter + instructor and executive dashboards

**Files:**
- Create: `app/Reporting/InstitutionalFilter.php`
- Create: `app/Reporting/InstructorDashboardQuery.php`
- Create: `app/Reporting/ExecutiveDashboardQuery.php`
- Create: `app/Http/Controllers/InstructorDashboardController.php`
- Create: `app/Http/Controllers/ExecutiveDashboardController.php`
- Modify: `routes/web.php`
- Replace: `resources/views/dashboard.blade.php`
- Create: `resources/views/dashboard/executive.blade.php`
- Test: `tests/Feature/InstitutionalFilterTest.php`, `InstructorDashboardTest.php`, `ExecutiveDashboardTest.php`

**Interfaces:**
- `InstitutionalFilter::fromRequest(Request $request, int $organizationId): self`
- `InstructorDashboardQuery::get(InstitutionalFilter $filter): array`
- `ExecutiveDashboardQuery::get(InstitutionalFilter $filter): array`

- [ ] **Step 1: Write RED filter and metric tests**

```php
public function test_client_organization_id_is_ignored(): void
{
    Carbon::setTestNow('2026-08-08 12:00:00');
    $filter = InstitutionalFilter::fromRequest(
        Request::create('/dashboard', 'GET', ['organization_id' => 999]),
        12,
    );
    $this->assertSame(12, $filter->organizationId);
    $this->assertSame('2026-05-11', $filter->dateFrom->toDateString());
    $this->assertSame('2026-08-08', $filter->dateTo->toDateString());
}

public function test_executive_average_uses_m4_final_score(): void
{
    [$filter] = $this->fixtureWithLegacyScenarioScore(5, finalAssessmentScore: 92);
    $data = app(ExecutiveDashboardQuery::class)->get($filter);
    $this->assertSame(92.0, $data['average_final_score']);
}
```

Add explicit tests: result `null` excluded from pass-rate denominator; observed occurrences, not catalog entries, drive top errors; foreign organization does not contribute; open/overdue action counts are correct.

- [ ] **Step 2: Verify RED**

Run: `php artisan test tests/Feature/InstitutionalFilterTest.php tests/Feature/InstructorDashboardTest.php tests/Feature/ExecutiveDashboardTest.php`
Expected: FAIL because classes/routes do not exist.

- [ ] **Step 3: Implement immutable filter**

```php
final readonly class InstitutionalFilter
{
    public function __construct(
        public int $organizationId,
        public CarbonImmutable $dateFrom,
        public CarbonImmutable $dateTo,
        public ?int $unitId = null,
        public ?int $scenarioId = null,
        public ?string $status = null,
    ) {}
}
```

`fromRequest()` resolves client `unit_uuid` and `scenario_uuid` to same-org internal IDs before construction. Status uses endpoint-specific allowlist. Reject `date_from > date_to` or inclusive windows over 366 days with `ValidationException`.

- [ ] **Step 4: Implement SQL-backed queries**

Instructor query returns bounded counts/queues for running executions, drafts, completed executions lacking assessment, draft assessments, open/overdue actions and recently finalized assessments. Executive query returns total/completed executions, finalized assessments, average `final_score`, pass rate over `result IS NOT NULL`, automatic fail count, observed critical-error frequency, open/overdue actions and one monthly trend. All base queries begin with `organization_id = $filter->organizationId` and apply period/scenario/unit constraints.

- [ ] **Step 5: Replace route closure with exact controllers**

```php
Route::get('/dashboard', InstructorDashboardController::class)
    ->middleware(['auth', 'account.active'])
    ->name('dashboard');
Route::get('/dashboard/executive', ExecutiveDashboardController::class)
    ->middleware(['auth', 'account.active'])
    ->name('dashboard.executive');
```

Instructor requires `scenarios.view`; executive requires `reports.view` through `ActiveOrganization::ensureAbility`.

- [ ] **Step 6: Render dashboards from query output only**

No Blade expression may read legacy `Scenario.score` for institutional assessment metrics. Executive view renders `Sem classificação histórica` where result is null and never infers pass/fail.

- [ ] **Step 7: Full GREEN + commit**

Run full migrate/test/Pint/Vite gate. Commit: `feat(m5): add institutional dashboards and filters`.

---

### Task 3: Paginated execution history

**Files:**
- Create: `app/Reporting/ExecutionHistoryQuery.php`
- Create: `app/Http/Controllers/ExecutionHistoryController.php`
- Create: `resources/views/history/executions.blade.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/ExecutionHistoryTest.php`

**Interfaces:**
- `ExecutionHistoryQuery::paginate(InstitutionalFilter $filter, int $perPage = 25): LengthAwarePaginator`
- `ExecutionHistoryQuery::cursor(InstitutionalFilter $filter): LazyCollection`

- [ ] **Step 1: Write RED tests**

```php
public function test_history_requires_reports_view(): void
{
    [$user, $organization] = $this->userWithoutReportsView();
    $this->actingAs($user)->withSession(['active_organization_id' => $organization->id])
        ->get(route('execution-history.index'))->assertForbidden();
}

public function test_matching_two_participants_in_same_unit_returns_execution_once(): void
{
    [$user, $organization, $execution, $unit] = $this->executionWithTwoParticipantsSameUnit();
    $response = $this->actingAs($user)->withSession(['active_organization_id' => $organization->id])
        ->get(route('execution-history.index', ['unit_uuid' => $unit->uuid]));
    $response->assertOk()->assertViewHas('executions', fn ($page) => $page->total() === 1);
}
```

Add multi-unit sorted-label and cross-org filter tests.

- [ ] **Step 2: Verify RED**

Run: `php artisan test tests/Feature/ExecutionHistoryTest.php`
Expected: FAIL because route/query/controller do not exist.

- [ ] **Step 3: Implement query**

Base on `ScenarioExecution` same org, period over `COALESCE(started_at, created_at)`, eager load scenario/version/assessment/participant snapshots and calculate critical-error/open-action counts with SQL subqueries/`withCount`. Unit filter uses participant `unit_id_snapshot`, so multiple matching participants do not duplicate the execution row. Default order is newest first; sort/status inputs use strict allowlists.

- [ ] **Step 4: Register exact route + view**

```php
Route::get('/history/executions', ExecutionHistoryController::class)
    ->name('execution-history.index');
```

Controller requires `reports.view`, constructs filter and calls `paginate()`.

- [ ] **Step 5: GREEN + commit**

Run full gate. Commit: `feat(m5): add paginated execution history`.

---

### Task 4: Stable streamed CSV export

**Files:**
- Create: `app/Reporting/ExecutionCsvExporter.php`
- Create: `app/Http/Controllers/ExecutionCsvController.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/ExecutionCsvExportTest.php`

**Interfaces:**
- `ExecutionCsvExporter::headers(): array`
- `ExecutionCsvExporter::row(ScenarioExecution $execution): array`
- `ExecutionCsvExporter::stream(InstitutionalFilter $filter): StreamedResponse`

- [ ] **Step 1: Write RED header/security/serialization tests**

```php
public function test_csv_header_order_is_stable(): void
{
    $this->assertSame([
        'execution_uuid','execution_sequence','scenario_uuid','scenario_title','scenario_version',
        'unit_uuids','unit_names','execution_status','started_at','completed_at','assessment_status',
        'final_score','result','automatic_fail','critical_error_count','open_action_count',
    ], app(ExecutionCsvExporter::class)->headers());
}

public function test_formula_leading_text_is_neutralized(): void
{
    $this->assertSame("'=cmd", app(ExecutionCsvExporter::class)->neutralizeForSpreadsheet('=cmd'));
}
```

Add `reports.view`, same-org, ISO date, null, multi-unit deterministic order and controller-not-materializing-full-history tests.

- [ ] **Step 2: Verify RED**

Run: `php artisan test tests/Feature/ExecutionCsvExportTest.php`
Expected: FAIL because exporter/route do not exist.

- [ ] **Step 3: Implement stable row mapping + streaming**

`unit_uuids` derives from distinct `unit_id_snapshot` units resolved within active organization and sorted by UUID; `unit_names` uses distinct snapshot labels sorted lexically. If all participants lack attribution, UUID field is empty and name is exactly `Sem unidade histórica`.

```php
public function neutralizeForSpreadsheet(?string $value): string
{
    $value ??= '';
    return preg_match('/^[=+\-@]/u', $value) === 1 ? "'{$value}" : $value;
}
```

Use `response()->streamDownload()` + `fputcsv()` over `ExecutionHistoryQuery::cursor()`; do not call `get()`/`all()` on the full result set.

- [ ] **Step 4: Register exact route**

```php
Route::get('/reports/executions.csv', ExecutionCsvController::class)
    ->name('reports.executions.csv');
```

Controller requires `reports.view`.

- [ ] **Step 5: GREEN + commit**

Run full gate. Commit: `feat(m5): add stable streamed execution CSV`.

---

### Task 5: PII-minimized institutional PDF report

**Files:**
- Modify: `composer.json`, `composer.lock`
- Create: `app/Reporting/ExecutionReportDataBuilder.php`
- Create: `app/Reporting/Pdf/ExecutionPdfRenderer.php`
- Create: `app/Http/Controllers/ExecutionReportController.php`
- Create: `resources/views/reports/execution-pdf.blade.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/ExecutionReportTest.php`

**Interfaces:**
- `ExecutionReportDataBuilder::build(ScenarioExecution $execution, int $organizationId): array`
- `ExecutionPdfRenderer::render(array $report): string`

- [ ] **Step 1: Write RED authorization/privacy tests**

```php
public function test_report_builder_omits_sensitive_person_fields(): void
{
    [$execution, $organization] = $this->executionWithPersonContactsAndIdentifiers();
    $json = json_encode(app(ExecutionReportDataBuilder::class)->build($execution, $organization->id));
    $this->assertStringNotContainsString('cpf', strtolower($json));
    $this->assertStringNotContainsString('@example.test', $json);
    $this->assertStringNotContainsString('whatsapp', strtolower($json));
}
```

Add `reports.view`, cross-org block, M4 score/debrief/action content and execution-without-final-assessment tests.

- [ ] **Step 2: Verify RED**

Run: `php artisan test tests/Feature/ExecutionReportTest.php`
Expected: FAIL because report classes/routes do not exist.

- [ ] **Step 3: Add Composer-produced Dompdf lock update**

Run `composer require dompdf/dompdf:^3.1 --no-interaction` in an isolated environment. Commit both Composer files. If the local harness cannot reach Packagist, temporarily let CI generate the Composer-produced lockfile as an artifact; inspect and commit that lockfile, then restore normal `composer install` CI behavior before completing this task. Never hand-author package hashes or transitive dependency versions.

- [ ] **Step 4: Implement explicit presentation builder**

```php
'participants' => $execution->participants->map(fn ($participant) => [
    'name' => $participant->person->preferredName(),
    'role' => $participant->role,
    'unit' => $participant->unit_name_snapshot ?: 'Sem unidade histórica',
    'position' => $participant->position_snapshot,
])->all(),
```

Map scenario/execution, assessment score components, criteria/evidence, observed errors, key times, structured debrief and action plan explicitly. Never serialize raw Person/User/contact/identifier models.

- [ ] **Step 5: Implement Dompdf adapter**

```php
$options = new Options;
$options->set('isRemoteEnabled', false);
$dompdf = new Dompdf($options);
$dompdf->loadHtml(view('reports.execution-pdf', ['report' => $report])->render(), 'UTF-8');
$dompdf->setPaper('a4', 'portrait');
$dompdf->render();
return $dompdf->output();
```

- [ ] **Step 6: Exact route/controller**

```php
Route::get('/reports/executions/{execution}/pdf', ExecutionReportController::class)
    ->name('reports.executions.pdf');
```

Controller requires `reports.view`, checks same org, generates filename `execution-<uuid>.pdf`, returns `application/pdf`.

- [ ] **Step 7: GREEN + commit**

Run `composer validate --strict` plus full gate. Commit: `feat(m5): add institutional execution PDF`.

---

### Task 6: Scenario templates

**Files:**
- Create: `database/migrations/2026_08_08_151000_create_scenario_templates_table.php`
- Create: `app/Models/ScenarioTemplate.php`
- Create: `app/Services/ScenarioTemplateManager.php`
- Create: `app/Http/Controllers/ScenarioTemplateController.php`
- Create: `resources/views/scenario-templates/index.blade.php`
- Modify: `resources/views/scenarios/show-scalable.blade.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/ScenarioTemplateTest.php`

**Interfaces:**
- `ScenarioTemplateManager::create(ScenarioVersion $version, int $organizationId, User $actor, string $name, ?string $description): ScenarioTemplate`
- `ScenarioTemplateManager::use(ScenarioTemplate $template, User $actor): Scenario`
- `ScenarioTemplateManager::archive(ScenarioTemplate $template): ScenarioTemplate`

- [ ] **Step 1: Write RED domain/HTTP tests**

```php
public function test_using_template_creates_new_draft_definition_without_history(): void
{
    [$actor, $organization, $sourceVersion, $template] = $this->publishedTemplateFixture();
    $sourceVersion->victims()->create(['code' => 'V1']);
    $sourceVersion->cohorts()->create(['label' => 'Grupo A', 'quantity' => 12]);

    $scenario = app(ScenarioTemplateManager::class)->use($template, $actor);
    $version = $scenario->versions()->sole();

    $this->assertSame('draft', $version->publication_status);
    $this->assertCount(1, $version->victims);
    $this->assertCount(1, $version->cohorts);
    $this->assertCount(0, $scenario->versions()->first()->scenario->executions ?? collect());
}
```

Also prove unpublished/cross-org source rejected, archived use rejected, `scenarios.manage` required for create/use/archive and source remains unchanged.

- [ ] **Step 2: Verify RED**

Run: `php artisan test tests/Feature/ScenarioTemplateTest.php`
Expected: FAIL because schema/model/routes do not exist.

- [ ] **Step 3: Add schema/model**

```php
Schema::create('scenario_templates', function (Blueprint $table): void {
    $table->id();
    $table->uuid('uuid')->unique();
    $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
    $table->foreignId('source_scenario_version_id')->constrained('scenario_versions')->restrictOnDelete();
    $table->string('name', 150);
    $table->text('description')->nullable();
    $table->string('status', 20)->default('active');
    $table->foreignId('created_by_user_id')->constrained('users')->restrictOnDelete();
    $table->timestamps();
    $table->index(['organization_id', 'status']);
});
```

- [ ] **Step 4: Implement transactional manager**

`create()` requires source `publication_status === 'published'` and same org. `use()` lock/reloads active template and source, creates new Scenario + version 1 draft, copies `ScenarioVersion::DEFINITION_FIELDS`, clones `ScenarioVictim` and `VictimCohort` definition rows, and copies no executions/assessment/evidence/debrief/action data. `archive()` changes active → archived and archived is terminal in M5.

- [ ] **Step 5: Register exact routes**

```php
Route::get('/scenario-templates', [ScenarioTemplateController::class, 'index'])->name('scenario-templates.index');
Route::post('/scenario-versions/{scenarioVersion}/templates', [ScenarioTemplateController::class, 'store'])->name('scenario-templates.store');
Route::post('/scenario-templates/{scenarioTemplate}/use', [ScenarioTemplateController::class, 'use'])->name('scenario-templates.use');
Route::patch('/scenario-templates/{scenarioTemplate}/archive', [ScenarioTemplateController::class, 'archive'])->name('scenario-templates.archive');
```

- [ ] **Step 6: GREEN + commit**

Run full gate. Commit: `feat(m5): add institutional scenario templates`.

---

### Task 7: Deterministic fictional DemoSeeder

**Files:**
- Create: `database/seeders/DemoSeeder.php`
- Create: `tests/Feature/DemoSeederTest.php`
- Create: `docs/DEMO.md`

**Interfaces:**
- Run command: `php artisan db:seed --class=Database\\Seeders\\DemoSeeder`

- [ ] **Step 1: Write RED safety/completeness tests**

```php
public function test_demo_seeder_refuses_production(): void
{
    app()->detectEnvironment(fn () => 'production');
    $this->expectException(LogicException::class);
    $this->seed(DemoSeeder::class);
}

public function test_demo_seed_is_deterministic_and_complete(): void
{
    $this->seed(DemoSeeder::class);
    $this->seed(DemoSeeder::class);
    $this->assertDatabaseCount('organizations', 1);
    $this->assertGreaterThanOrEqual(2, Unit::count());
    $this->assertGreaterThanOrEqual(3, Scenario::count());
    $this->assertGreaterThanOrEqual(1, ScenarioTemplate::count());
    $this->assertTrue(ExecutionAssessment::where('status', 'finalized')->exists());
    $this->assertTrue(ActionItem::exists());
}
```

- [ ] **Step 2: Verify RED**

Run: `php artisan test tests/Feature/DemoSeederTest.php`
Expected: FAIL because DemoSeeder does not exist.

- [ ] **Step 3: Implement guard + deterministic graph**

```php
if (app()->environment('production')) {
    throw new LogicException('DemoSeeder cannot run in production.');
}

$organization = Organization::firstOrCreate(
    ['name' => 'Instituto Fictício Horizonte'],
    ['kind' => 'company', 'status' => 'active'],
);
```

Use stable emails under `example.test`; stable organization/unit/scenario names; domain managers for publication/execution/assessment/template transitions; create at least two units, three scenarios, multiple execution states, finalized + draft assessments, observed errors, key times, structured debrief, action statuses and one template.

- [ ] **Step 4: Write exact walkthrough doc**

`docs/DEMO.md` contains the seed command, fictional account names/emails, expected pages and the nine-step five-minute walkthrough; it explicitly says the seeder refuses production.

- [ ] **Step 5: GREEN + commit**

Run full gate. Commit: `feat(m5): add deterministic institutional demo`.

---

### Task 8: Reporting docs, forensic audit and integration gate

**Files:**
- Create: `docs/REPORTING.md`
- Create: `docs/PHASE_M5_AUDIT.md`
- Review: complete PR diff.

- [ ] **Step 1: Write reporting truth**

`docs/REPORTING.md` documents exact M4 metric sources/denominators, default and max period, historical-unit anchor/candidate rule, multi-unit semantics, 16-column CSV order, injection neutralization and PDF PII boundary.

- [ ] **Step 2: Forensic review before audit doc**

Inspect the diff and prove: no active metric reads legacy `Scenario.score`; no client org filter; all report endpoints require `reports.view`; snapshot memberships are same-org; CSV full history is not materialized; PDF builder contains no identifiers/contacts/raw HTML/remote fetch; template source is same-org published and archived use fails; demo refuses production; no M6–M9 scope drift. Any meaningful gap returns to a new RED→GREEN cycle before Step 3.

- [ ] **Step 3: Complete functional verification**

Run:

```bash
composer validate --strict
php artisan migrate:fresh --force
php artisan test
vendor/bin/pint --test
npm ci
npm run build
```

Expected: all PASS.

- [ ] **Step 4: Write `docs/PHASE_M5_AUDIT.md`**

Record delivered architecture, tenant/security proof, attribution semantics, metric semantics, PDF privacy, CSV injection defense/streaming, template invariants, demo safety, TDD evidence, deliberate limitations and checklist.

- [ ] **Step 5: Exact-head CI gate**

Require GitHub Actions green on the commit containing the audit document; do not reuse a prior run.

- [ ] **Step 6: PR reconciliation**

Require `behind_by=0`, mergeable PR, zero unresolved review threads, exact-head CI green and no known critical/high issue.

- [ ] **Step 7: READY FOR INTEGRATION**

Update PR body with final SHA/run and integrate only by merge commit with `expected_head_sha` after the user-authorized safest-path policy; never force-push.
