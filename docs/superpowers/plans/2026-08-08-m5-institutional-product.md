# M5 — Institutional Product Layer Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Turn the M1–M4 institutional training domain into a demonstrable product with trustworthy dashboards, historical execution reporting, PDF/CSV exports, scenario templates and deterministic fictional demo data.

**Architecture:** M5 adds a dedicated read/query layer over the existing normalized database, keeping active organization as the tenant boundary. Historical participant attribution is snapshotted at execution time; dashboards/history/reports consume M3/M4 truth rather than legacy `Scenario.score`; one report data builder feeds PDF presentation and stable CSV rows; templates clone only immutable scenario definition; demo data is deterministic and non-production-only.

**Tech Stack:** Laravel 13, PHP 8.4 in CI, Blade, Eloquent, SQLite in current CI, PHPUnit 12, Laravel Pint, Vite/Tailwind, `dompdf/dompdf` 3.1-compatible line for PDF rendering.

## Global Constraints

- Active organization is server-resolved through `ActiveOrganization`; no arbitrary client `organization_id` filter.
- M5 introduces no new ability. Reuse `scenarios.view`, `scenarios.manage` and `reports.view` exactly as specified.
- Interactive dashboard/history/export period is limited to 366 days; default period is the last 90 calendar days through end of current day in application timezone.
- M5 report/export metrics use `ScenarioExecution`, `ExecutionAssessment`, `CriticalErrorOccurrence` and `ActionItem`; legacy `Scenario.score`/catalog fields do not drive active indicators.
- Historical unit attribution is never guessed. New participants snapshot represented membership/unit/position; old participants remain unknown unless exactly one candidate matches the deterministic anchor-date rule.
- Multi-unit executions never acquire an arbitrary primary unit.
- PDF output must not expose unnecessary PII, must escape user prose, and must keep remote asset fetching disabled.
- CSV header order is stable; dates are ISO 8601; booleans are `0|1`; formula-leading text is neutralized; export is streamed/chunked.
- Scenario templates are same-organization, source only published versions, archive instead of normal hard-delete, and never copy executions/assessment history.
- `DemoSeeder` refuses `production`, uses clearly fictional data and does not run from migrations.
- No M6 PostgreSQL/Docker/queue hardening, no M7 redesign, no M8 Wiki rewrite and no M9 release/tag scope enters this branch.
- Every functional change follows RED → GREEN and each task ends with PHPUnit + Pint + migrations + Vite build green on the exact task HEAD.

---

## File structure locked for M5

### Historical attribution
- Create `database/migrations/2026_08_08_150000_add_historical_attribution_to_execution_participants.php` — additive snapshot columns + deterministic conservative backfill.
- Modify `app/Models/ExecutionParticipant.php` — snapshot fillable/casts/relations.
- Modify `app/Http/Controllers/ExecutionParticipantController.php` — choose/validate represented membership and snapshot it.
- Modify `resources/views/executions/show.blade.php` — membership selection when one person has multiple active memberships.
- Test `tests/Feature/ExecutionParticipantAttributionTest.php`.

### Filters/read layer/dashboard/history
- Create `app/Reporting/InstitutionalFilter.php` — immutable validated filter DTO/value object.
- Create `app/Reporting/InstructorDashboardQuery.php` — instructor work queues/operational counts.
- Create `app/Reporting/ExecutiveDashboardQuery.php` — executive indicators/trend.
- Create `app/Reporting/ExecutionHistoryQuery.php` — tenant-safe paginated history query and export cursor source.
- Create `app/Http/Controllers/InstructorDashboardController.php`.
- Create `app/Http/Controllers/ExecutiveDashboardController.php`.
- Create `app/Http/Controllers/ExecutionHistoryController.php`.
- Modify `routes/web.php` — remove dashboard closure and register exact M5 routes.
- Replace `resources/views/dashboard.blade.php` with instructor product dashboard.
- Create `resources/views/dashboard/executive.blade.php`.
- Create `resources/views/history/executions.blade.php`.
- Test `tests/Feature/InstitutionalFilterTest.php`, `InstructorDashboardTest.php`, `ExecutiveDashboardTest.php`, `ExecutionHistoryTest.php`.

### Reports
- Create `app/Reporting/ExecutionReportDataBuilder.php` — presentation-safe one-execution report structure, no arbitrary model serialization.
- Create `app/Reporting/ExecutionCsvExporter.php` — fixed schema + streaming row conversion + injection neutralization.
- Create `app/Reporting/Pdf/ExecutionPdfRenderer.php` — Dompdf adapter with remote fetching disabled.
- Create `app/Http/Controllers/ExecutionReportController.php` — PDF endpoint.
- Create `app/Http/Controllers/ExecutionCsvController.php` — streamed CSV endpoint.
- Create `resources/views/reports/execution-pdf.blade.php` — escaped institutional PDF HTML.
- Modify `composer.json` and `composer.lock` for `dompdf/dompdf` 3.1-compatible line.
- Test `tests/Feature/ExecutionReportTest.php`, `ExecutionCsvExportTest.php`.

### Templates
- Create `database/migrations/2026_08_08_151000_create_scenario_templates_table.php`.
- Create `app/Models/ScenarioTemplate.php`.
- Create `app/Services/ScenarioTemplateManager.php`.
- Create `app/Http/Controllers/ScenarioTemplateController.php`.
- Create `resources/views/scenario-templates/index.blade.php`.
- Modify `resources/views/scenarios/show-scalable.blade.php` to expose create-template action only for published versions and `scenarios.manage`.
- Modify `routes/web.php` with exact template routes.
- Test `tests/Feature/ScenarioTemplateTest.php`.

### Demo/docs/audit
- Create `database/seeders/DemoSeeder.php`.
- Keep `DatabaseSeeder` production-safe; do not automatically call `DemoSeeder`.
- Create `docs/REPORTING.md` and `docs/DEMO.md`.
- Create `tests/Feature/DemoSeederTest.php`.
- Create final `docs/PHASE_M5_AUDIT.md` only after full diff/security/performance review.

---

### Task 1: Historical execution participant attribution

**Files:**
- Create: `database/migrations/2026_08_08_150000_add_historical_attribution_to_execution_participants.php`
- Modify: `app/Models/ExecutionParticipant.php`
- Modify: `app/Http/Controllers/ExecutionParticipantController.php`
- Modify: `resources/views/executions/show.blade.php`
- Test: `tests/Feature/ExecutionParticipantAttributionTest.php`

**Interfaces:**
- Consumes: `ScenarioExecution`, `Person`, `OrganizationMembership`, existing `scenarios.manage` boundary.
- Produces: `ExecutionParticipant::membership()`, `unit_id_snapshot`, `unit_uuid_snapshot`, `unit_name_snapshot`, `position_snapshot`; future report queries read only snapshots.

- [ ] **Step 1: Write the failing schema/snapshot tests**

```php
public function test_execution_participant_has_historical_attribution_columns(): void
{
    $this->assertTrue(Schema::hasColumns('execution_participants', [
        'organization_membership_id', 'unit_id_snapshot', 'unit_uuid_snapshot',
        'unit_name_snapshot', 'position_snapshot',
    ]));
}

public function test_adding_participant_snapshots_the_selected_membership(): void
{
    [$actor, $organization, $execution, $person, $membership, $unit] = $this->fixture();

    $this->actingAs($actor)
        ->withSession(['active_organization_id' => $organization->id])
        ->post(route('execution-participants.store', $execution), [
            'person_uuid' => $person->uuid,
            'organization_membership_uuid' => $membership->uuid,
            'role' => 'Líder de equipe',
        ])
        ->assertRedirect();

    $participant = $execution->participants()->firstOrFail();
    $this->assertSame($membership->id, $participant->organization_membership_id);
    $this->assertSame($unit->id, $participant->unit_id_snapshot);
    $this->assertSame($unit->uuid, $participant->unit_uuid_snapshot);
    $this->assertSame($unit->name, $participant->unit_name_snapshot);
    $this->assertSame($membership->position, $participant->position_snapshot);
}
```

- [ ] **Step 2: Run test to verify RED**

Run: `php artisan test tests/Feature/ExecutionParticipantAttributionTest.php`
Expected: FAIL because snapshot columns/validation do not exist.

- [ ] **Step 3: Add additive schema and deterministic backfill**

```php
Schema::table('execution_participants', function (Blueprint $table): void {
    $table->foreignId('organization_membership_id')->nullable()->after('person_id')
        ->constrained('organization_memberships')->nullOnDelete();
    $table->foreignId('unit_id_snapshot')->nullable()->after('organization_membership_id')
        ->constrained('units')->nullOnDelete();
    $table->uuid('unit_uuid_snapshot')->nullable()->after('unit_id_snapshot');
    $table->string('unit_name_snapshot')->nullable()->after('unit_uuid_snapshot');
    $table->string('position_snapshot')->nullable()->after('unit_name_snapshot');
    $table->index(['scenario_execution_id', 'unit_id_snapshot']);
});
```

Backfill algorithm in the migration:

```php
$participants = DB::table('execution_participants')
    ->join('scenario_executions', 'scenario_executions.id', '=', 'execution_participants.scenario_execution_id')
    ->select('execution_participants.*', 'scenario_executions.organization_id', 'scenario_executions.started_at', 'scenario_executions.created_at as execution_created_at')
    ->orderBy('execution_participants.id')
    ->get();

foreach ($participants as $participant) {
    $anchor = $participant->started_at ?: $participant->execution_created_at;
    $candidates = DB::table('organization_memberships')
        ->where('person_id', $participant->person_id)
        ->where('organization_id', $participant->organization_id)
        ->whereDate('started_at', '<=', $anchor)
        ->where(function ($query) use ($anchor): void {
            $query->whereNull('ended_at')->orWhereDate('ended_at', '>=', $anchor);
        })
        ->where(function ($query) use ($anchor): void {
            $query->whereNull('deleted_at')->orWhere('deleted_at', '>', $anchor);
        })
        ->get();

    if ($candidates->count() !== 1) {
        continue;
    }

    $membership = $candidates->first();
    $unit = $membership->unit_id ? DB::table('units')->find($membership->unit_id) : null;
    DB::table('execution_participants')->where('id', $participant->id)->update([
        'organization_membership_id' => $membership->id,
        'unit_id_snapshot' => $unit?->id,
        'unit_uuid_snapshot' => $unit?->uuid,
        'unit_name_snapshot' => $unit?->name,
        'position_snapshot' => $membership->position,
    ]);
}
```

- [ ] **Step 4: Snapshot the explicitly represented membership on new participant creation**

Controller validation:

```php
'organization_membership_uuid' => ['required', 'uuid', 'exists:organization_memberships,uuid'],
```

Boundary logic:

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
    'unit_uuid_snapshot' => $membership->unit?->uuid,
    'unit_name_snapshot' => $membership->unit?->name,
    'position_snapshot' => $membership->position,
    'execution_team_id' => $team?->id,
    'role' => $validated['role'] ?? null,
]);
```

The execution participant form submits the membership UUID and groups active memberships by person. Do not infer a membership in HTTP when more than one active membership exists.

- [ ] **Step 5: Add boundary/backfill regression tests**

Tests must cover same-org membership required, cross-org membership rejected, transfer/rename after execution does not rewrite snapshot, and zero/multiple historical candidates remain null.

- [ ] **Step 6: Run full task gate**

Run: `php artisan migrate:fresh --force && php artisan test && vendor/bin/pint --test && npm run build`
Expected: all PASS.

- [ ] **Step 7: Commit**

```bash
git add database/migrations/2026_08_08_150000_add_historical_attribution_to_execution_participants.php app/Models/ExecutionParticipant.php app/Http/Controllers/ExecutionParticipantController.php resources/views/executions/show.blade.php tests/Feature/ExecutionParticipantAttributionTest.php
git commit -m "feat(m5): snapshot participant institutional attribution"
```

---

### Task 2: Central filters and instructor/executive dashboard read layer

**Files:**
- Create: `app/Reporting/InstitutionalFilter.php`
- Create: `app/Reporting/InstructorDashboardQuery.php`
- Create: `app/Reporting/ExecutiveDashboardQuery.php`
- Create: `app/Http/Controllers/InstructorDashboardController.php`
- Create: `app/Http/Controllers/ExecutiveDashboardController.php`
- Modify: `routes/web.php`
- Modify: `resources/views/dashboard.blade.php`
- Create: `resources/views/dashboard/executive.blade.php`
- Test: `tests/Feature/InstitutionalFilterTest.php`, `InstructorDashboardTest.php`, `ExecutiveDashboardTest.php`

**Interfaces:**
- `InstitutionalFilter::fromRequest(Request $request, int $organizationId): self`
- `InstructorDashboardQuery::get(InstitutionalFilter $filter): array`
- `ExecutiveDashboardQuery::get(InstitutionalFilter $filter): array`

- [ ] **Step 1: Write failing filter/metric tests**

```php
public function test_default_filter_is_last_ninety_days_and_never_accepts_client_organization_id(): void
{
    Carbon::setTestNow('2026-08-08 12:00:00');
    $request = Request::create('/dashboard', 'GET', ['organization_id' => 999]);
    $filter = InstitutionalFilter::fromRequest($request, 12);

    $this->assertSame(12, $filter->organizationId);
    $this->assertSame('2026-05-11', $filter->dateFrom->toDateString());
    $this->assertSame('2026-08-08', $filter->dateTo->toDateString());
}

public function test_executive_average_uses_m4_final_score_not_legacy_scenario_score(): void
{
    // fixture creates Scenario.score=5 and finalized ExecutionAssessment.final_score=92
    $data = app(ExecutiveDashboardQuery::class)->get($filter);
    $this->assertSame(92.0, $data['average_final_score']);
}
```

Also prove pass-rate excludes `result=null`, top errors use `CriticalErrorOccurrence`, and foreign org rows do not contribute.

- [ ] **Step 2: Run tests and verify RED**

Run: `php artisan test tests/Feature/InstitutionalFilterTest.php tests/Feature/InstructorDashboardTest.php tests/Feature/ExecutiveDashboardTest.php`
Expected: FAIL because reporting classes/controllers do not exist.

- [ ] **Step 3: Implement immutable filter**

```php
final readonly class InstitutionalFilter
{
    public function __construct(
        public int $organizationId,
        public CarbonImmutable $dateFrom,
        public CarbonImmutable $dateTo,
        public ?string $unitUuid = null,
        public ?string $scenarioUuid = null,
        public ?string $status = null,
    ) {}

    public static function fromRequest(Request $request, int $organizationId): self
    {
        $to = CarbonImmutable::parse($request->string('date_to')->toString() ?: now()->toDateString())->endOfDay();
        $from = CarbonImmutable::parse($request->string('date_from')->toString() ?: $to->subDays(89)->toDateString())->startOfDay();
        if ($from->gt($to) || $from->diffInDays($to) > 365) {
            throw ValidationException::withMessages(['date_from' => 'O período deve ter no máximo 366 dias e data inicial não pode superar a final.']);
        }
        return new self($organizationId, $from, $to, $request->string('unit_uuid')->toString() ?: null, $request->string('scenario_uuid')->toString() ?: null, $request->string('status')->toString() ?: null);
    }
}
```

Resolve unit/scenario UUID ownership in the factory before returning the object; reject unknown/cross-org values.

- [ ] **Step 4: Implement bounded SQL-backed dashboard queries**

`ExecutiveDashboardQuery` starts from `ScenarioExecution::query()->where('organization_id', $filter->organizationId)` and applies date/scenario/unit snapshot constraints with `whereHas`. Score/pass/error/action metrics use M4 relations. Instructor query returns only bounded queues (`limit(6)`/`limit(8)`) and counts.

- [ ] **Step 5: Replace dashboard route closure with controllers**

```php
Route::get('/dashboard', InstructorDashboardController::class)
    ->middleware(['auth', 'account.active'])
    ->name('dashboard');
Route::get('/dashboard/executive', ExecutiveDashboardController::class)
    ->middleware(['auth', 'account.active'])
    ->name('dashboard.executive');
```

Instructor controller requires `scenarios.view`; executive controller requires `reports.view` through `ActiveOrganization::ensureAbility`.

- [ ] **Step 6: Build views from returned arrays only**

The instructor view shows running/draft/pending assessment/action queues. Executive view shows execution/assessment score/result/error/action indicators and one monthly trend; neither references `Scenario.score`.

- [ ] **Step 7: Run full task gate and commit**

Run: `php artisan migrate:fresh --force && php artisan test && vendor/bin/pint --test && npm run build`
Expected: PASS.

Commit:

```bash
git add app/Reporting app/Http/Controllers/InstructorDashboardController.php app/Http/Controllers/ExecutiveDashboardController.php routes/web.php resources/views/dashboard.blade.php resources/views/dashboard/executive.blade.php tests/Feature/InstitutionalFilterTest.php tests/Feature/InstructorDashboardTest.php tests/Feature/ExecutiveDashboardTest.php
git commit -m "feat(m5): add institutional dashboards and filters"
```

---

### Task 3: Paginated execution history with multi-unit semantics

**Files:**
- Create: `app/Reporting/ExecutionHistoryQuery.php`
- Create: `app/Http/Controllers/ExecutionHistoryController.php`
- Create: `resources/views/history/executions.blade.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/ExecutionHistoryTest.php`

**Interfaces:**
- `ExecutionHistoryQuery::paginate(InstitutionalFilter $filter, int $perPage = 25): LengthAwarePaginator`
- `ExecutionHistoryQuery::cursor(InstitutionalFilter $filter): LazyCollection`

- [ ] **Step 1: Write failing history tests**

Prove route requires `reports.view`, page is server-paginated, default order newest first, unit filter matches an execution once even if multiple participants match, multi-unit labels are distinct/sorted, and cross-org scenario/unit UUID cannot leak rows.

- [ ] **Step 2: Verify RED**

Run: `php artisan test tests/Feature/ExecutionHistoryTest.php`
Expected: FAIL because route/query/controller do not exist.

- [ ] **Step 3: Implement query with allowlisted sorting/status**

```php
$query = ScenarioExecution::query()
    ->where('organization_id', $filter->organizationId)
    ->whereBetween(DB::raw('COALESCE(started_at, created_at)'), [$filter->dateFrom, $filter->dateTo])
    ->with([
        'scenarioVersion.scenario:id,uuid,title',
        'assessment:id,uuid,scenario_execution_id,status,final_score,result,automatic_fail',
        'participants:id,scenario_execution_id,unit_uuid_snapshot,unit_name_snapshot',
    ])
    ->withCount([
        'assessment as critical_error_count' => fn ($q) => $q->whereHas('criticalErrorOccurrences'),
    ]);
```

Use explicit subqueries/`withCount` for critical errors/open actions rather than PHP N+1 loops. Unit filter uses `whereHas('participants', fn ($q) => $q->where('unit_uuid_snapshot', $filter->unitUuid))`, which keeps one execution row.

- [ ] **Step 4: Register exact route/controller/view**

```php
Route::get('/history/executions', ExecutionHistoryController::class)
    ->name('execution-history.index');
```

Controller resolves `reports.view`, builds `InstitutionalFilter`, calls `paginate`, returns the history view.

- [ ] **Step 5: Full gate and commit**

Run: `php artisan migrate:fresh --force && php artisan test && vendor/bin/pint --test && npm run build`
Expected: PASS.

Commit message: `feat(m5): add paginated execution history`.

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

- [ ] **Step 1: Write failing CSV tests**

Assert exact header order:

```php
[
 'execution_uuid','execution_sequence','scenario_uuid','scenario_title','scenario_version',
 'unit_uuids','unit_names','execution_status','started_at','completed_at','assessment_status',
 'final_score','result','automatic_fail','critical_error_count','open_action_count',
]
```

Assert multi-unit values are distinct/sorted/joined by `;`, formula-leading text is prefixed with `'`, dates are ISO 8601, nulls empty, and `reports.view`/tenant/filter gates apply.

- [ ] **Step 2: Verify RED**

Run: `php artisan test tests/Feature/ExecutionCsvExportTest.php`
Expected: FAIL because exporter/route do not exist.

- [ ] **Step 3: Implement neutralizer and streaming**

```php
private function neutralize(?string $value): string
{
    $value ??= '';
    return preg_match('/^[=+\-@]/u', $value) === 1 ? "'{$value}" : $value;
}
```

`stream()` returns `response()->streamDownload(...)` and writes `headers()` then chunked/cursor rows with `fputcsv`; controller never materializes all rows.

- [ ] **Step 4: Register exact route**

```php
Route::get('/reports/executions.csv', ExecutionCsvController::class)
    ->name('reports.executions.csv');
```

- [ ] **Step 5: Full gate and commit**

Run full suite/lint/build/migrations. Commit: `feat(m5): add stable streamed execution CSV`.

---

### Task 5: PII-minimized execution PDF report

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
- `ExecutionPdfRenderer::render(array $reportData): string`

- [ ] **Step 1: Write failing builder/report authorization tests**

Tests assert `reports.view`, cross-org 403/404, builder output has display names but no identifier/contact/email/phone keys, M4 scoring/debrief/action content is present, and execution with no finalized assessment produces a valid pending-assessment report structure.

- [ ] **Step 2: Verify RED**

Run: `php artisan test tests/Feature/ExecutionReportTest.php`
Expected: FAIL because report classes/routes do not exist.

- [ ] **Step 3: Add Dompdf dependency with lockfile generated by Composer**

Run in an isolated dependency update environment:

```bash
composer require dompdf/dompdf:^3.1 --no-interaction
```

Do not hand-edit dependency versions. Commit both `composer.json` and `composer.lock`. If the local harness cannot reach Packagist, generate the lockfile in CI as an artifact, inspect it, then commit the Composer-produced lockfile before continuing.

- [ ] **Step 4: Implement report data builder**

The builder eagerly loads only approved relations and maps to explicit arrays. Example participant projection:

```php
'participants' => $execution->participants->map(fn ($participant) => [
    'name' => $participant->person->preferredName(),
    'role' => $participant->role,
    'unit' => $participant->unit_name_snapshot ?: 'Sem unidade histórica',
    'position' => $participant->position_snapshot,
])->all(),
```

Never return `Person::toArray()`, identifiers, contacts or raw model serialization.

- [ ] **Step 5: Implement Dompdf adapter with remote disabled**

```php
$options = new Options;
$options->set('isRemoteEnabled', false);
$options->set('isHtml5ParserEnabled', true);
$dompdf = new Dompdf($options);
$dompdf->loadHtml(view('reports.execution-pdf', ['report' => $reportData])->render(), 'UTF-8');
$dompdf->setPaper('a4', 'portrait');
$dompdf->render();
return $dompdf->output();
```

- [ ] **Step 6: Exact route/controller**

```php
Route::get('/reports/executions/{execution}/pdf', ExecutionReportController::class)
    ->name('reports.executions.pdf');
```

Controller validates `reports.view`, same organization, uses application-generated filename `execution-<uuid>.pdf`, and returns `application/pdf`.

- [ ] **Step 7: Full gate and commit**

Run full suite/lint/build/migrations and `composer validate --strict`. Commit: `feat(m5): add institutional execution PDF`.

---

### Task 6: Scenario templates from immutable published versions

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

- [ ] **Step 1: Write failing schema/domain/HTTP tests**

Prove only published same-org source versions are accepted; `scenarios.manage` required for create/use/archive; `scenarios.view` can list; use creates a new Scenario + version 1 draft with definition copied; no executions/assessments copied; source remains unchanged; archived template cannot be used.

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

- [ ] **Step 4: Implement manager transactionally**

`use()` locks/reloads template, rejects archived, loads immutable source, creates `Scenario` and its version 1 draft in one transaction, copying only definition fields listed by `ScenarioVersion::DEFINITION_FIELDS` plus title/name conventions. It never copies victims/executions/assessment history unless the spec explicitly names definition fields; victims/cohorts remain outside M5 template copy.

- [ ] **Step 5: Register exact routes**

```php
Route::get('/scenario-templates', [ScenarioTemplateController::class, 'index'])->name('scenario-templates.index');
Route::post('/scenario-versions/{scenarioVersion}/templates', [ScenarioTemplateController::class, 'store'])->name('scenario-templates.store');
Route::post('/scenario-templates/{scenarioTemplate}/use', [ScenarioTemplateController::class, 'use'])->name('scenario-templates.use');
Route::patch('/scenario-templates/{scenarioTemplate}/archive', [ScenarioTemplateController::class, 'archive'])->name('scenario-templates.archive');
```

- [ ] **Step 6: Full gate and commit**

Run full suite/lint/build/migrations. Commit: `feat(m5): add institutional scenario templates`.

---

### Task 7: Deterministic fictional DemoSeeder and five-minute walkthrough

**Files:**
- Create: `database/seeders/DemoSeeder.php`
- Create: `tests/Feature/DemoSeederTest.php`
- Create: `docs/DEMO.md`

**Interfaces:**
- `DemoSeeder::run(): void`
- No web endpoint; run only via `php artisan db:seed --class=Database\\Seeders\\DemoSeeder` outside production.

- [ ] **Step 1: Write failing safety/completeness tests**

Tests must switch environment to `production` and assert the seeder throws `LogicException`; on fresh testing DB assert one deterministic demo organization, at least two units, role/access profiles, people/memberships, at least three scenarios, published versions, multiple executions, finalized + draft assessments, errors/key times/debrief/actions and at least one template; second run does not create a second institutional graph.

- [ ] **Step 2: Verify RED**

Run: `php artisan test tests/Feature/DemoSeederTest.php`
Expected: FAIL because `DemoSeeder` does not exist.

- [ ] **Step 3: Implement production guard and deterministic natural keys**

```php
if (app()->environment('production')) {
    throw new LogicException('DemoSeeder cannot run in production.');
}

$organization = Organization::firstOrCreate(
    ['name' => 'Instituto Fictício Horizonte'],
    ['kind' => 'training_center', 'status' => 'active'],
);
```

Use reserved `example.test` email domains and obviously fictional names. Use existing domain managers where they enforce lifecycle invariants rather than raw inserts that bypass M2–M4 rules.

- [ ] **Step 4: Write `docs/DEMO.md`**

Document exact command, fictional accounts, five-minute walkthrough and explicit safety warning that demo seeding is not for production.

- [ ] **Step 5: Full gate and commit**

Run full suite/lint/build/migrations. Commit: `feat(m5): add deterministic institutional demo`.

---

### Task 8: Reporting documentation, forensic review and final integration gate

**Files:**
- Create: `docs/REPORTING.md`
- Create: `docs/PHASE_M5_AUDIT.md`
- Review: every M5 file and PR diff.

**Interfaces:** none; this task validates rather than expands product behavior.

- [ ] **Step 1: Write `docs/REPORTING.md` from implemented truth**

Document source-of-truth tables, exact metric denominators, default/maximum period, unit snapshot semantics, legacy-result exclusion from pass-rate, exact 16-column CSV schema, formula-neutralization rule and PDF PII boundary.

- [ ] **Step 2: Run forensic review before audit doc**

Check:
- dashboard no longer references `Scenario.score` for assessment metrics;
- no query trusts client organization ID;
- all report endpoints require `reports.view`;
- templates enforce same-org/published/archived state;
- participant snapshots cannot use cross-org membership;
- CSV streaming does not call `->get()`/`->all()` on full history;
- PDF builder exposes no identifiers/contacts/raw user HTML/remote resources;
- demo refuses production and uses fictional/reserved values;
- migrations are additive/conservative;
- no N+1 obvious in bounded dashboards/history;
- no M6–M9 scope drift;
- PR has no unresolved threads.

Any finding rated critical/high/meaningful functional gap returns to RED→GREEN before writing the final audit document.

- [ ] **Step 3: Run complete verification on functional HEAD**

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

- [ ] **Step 4: Create `docs/PHASE_M5_AUDIT.md`**

Record architecture delivered, security/tenant proof, historical attribution, metric semantics, report privacy, CSV injection defense, template invariants, demo safety, deliberate limitations, TDD evidence and final checklist.

- [ ] **Step 5: Require CI on exact audit-document HEAD**

Do not reuse a run from the previous commit. GitHub Actions must be green on the SHA containing `PHASE_M5_AUDIT.md`.

- [ ] **Step 6: Reconcile PR before integration**

Require:
- `behind_by = 0` versus `main`;
- PR mergeable;
- zero unresolved review threads;
- exact-head CI green;
- no known critical/high security issue;
- audit document present.

- [ ] **Step 7: Mark PR READY FOR INTEGRATION**

Update PR body with final SHA, CI run, delivered scope and deferred M6–M9 limitations. Integration uses merge commit with `expected_head_sha` to prevent racing a moved branch.
