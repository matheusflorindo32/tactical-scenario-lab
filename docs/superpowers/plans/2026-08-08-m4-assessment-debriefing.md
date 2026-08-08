# M4 — Assessment & Debriefing Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Move evaluation from legacy `Scenario` fields into a structured, execution-scoped, explainable and auditable `ExecutionAssessment` domain with weighted criteria, evidence, critical-error occurrences, key times, structured debriefing and action follow-up.

**Architecture:** `ScenarioExecution` owns at most one `ExecutionAssessment`. `AssessmentScoreCalculator` is the only scoring formula authority. `ExecutionAssessmentManager` creates and finalizes assessments under transactions/row locks. Thin controllers enforce `evaluations.manage`, active-organization isolation and public UUID boundaries. Finalized assessment content is immutable; only the operational `ActionItem.status` may advance through a dedicated transition method.

**Tech Stack:** Laravel 13, PHP 8.4, Eloquent, Blade, Tailwind, Alpine, SQLite CI/local, PostgreSQL-targeted relational design, PHPUnit, Laravel Pint, Vite, GitHub Actions.

## Global Constraints

- Work only on `feature/m4-assessment-debriefing`; never implement M4 directly on `main`.
- Base is `main` after M3 merge `c135cd12ef2415d91b7e2ba4636bfbd23dac8759`.
- Preserve the full M1–M3 regression suite.
- Read assessment with `scenarios.view`; mutate assessment with `evaluations.manage`. `scenarios.manage` alone is insufficient.
- Every public M4 aggregate uses `HasPublicUuid`; numeric IDs stay internal.
- One assessment per execution.
- New M4 threshold snapshot is `70.00`.
- Evaluator adjustment is integer `-10..+10`; nonzero requires justification.
- `final_score = clamp(base_score - penalty_points + evaluator_adjustment, 0, 100)`.
- `automatic_fail` forces failed result without rewriting the numerical score.
- Normal finalization requires completed execution, exact criterion weight total `100.00`, all criteria scored, evidence for every criterion, and at least one `fact`, `interpretation` and `recommendation` debrief entry.
- Cancelled execution cannot finalize.
- Finalized assessment content is immutable. After finalization only action-item status may advance: `open -> in_progress|completed|cancelled`; `in_progress -> completed|cancelled`; completed/cancelled are terminal.
- Legacy import preserves source values without inventing pass/fail, penalties or semantic debrief categories.
- M4 completion stops new writes to `Scenario.score`, `Scenario.debrief_notes`, `Scenario.observed_critical_errors`; columns remain for rollback compatibility.
- No M5 reports/dashboards, M6 production work, Wiki, AI, external API or broader TMA Platform scope.
- Functional work is TDD: test RED first, confirm expected failure, implement minimal GREEN, run regression, commit.
- Exact final HEAD must receive a fresh successful GitHub Actions run before integration.

## File Map

**Models:** `ExecutionAssessment`, `AssessmentCriterion`, `AssessmentEvidence`, `CriticalErrorOccurrence`, `KeyTimeRecord`, `ExecutionDebrief`, `DebriefEntry`, `ActionItem`.

**Services:** `AssessmentScoreCalculator`, `ExecutionAssessmentManager`, `LegacyAssessmentImporter`.

**Controllers:** `ExecutionAssessmentController`, `AssessmentCriterionController`, `AssessmentEvidenceController`, `CriticalErrorOccurrenceController`, `KeyTimeRecordController`, `DebriefEntryController`, `ActionItemController`.

**Views:** create `resources/views/assessments/show.blade.php`; modify `resources/views/executions/show.blade.php`.

**Migrations:**
- `2026_08_08_140000_create_execution_assessments_table.php`
- `2026_08_08_141000_create_assessment_rubric_tables.php`
- `2026_08_08_142000_create_assessment_observation_tables.php`
- `2026_08_08_143000_create_execution_debrief_tables.php`
- `2026_08_08_149000_import_legacy_scenario_assessments.php`

**Tests:**
- `tests/Feature/ExecutionAssessmentTest.php`
- `tests/Unit/AssessmentScoreCalculatorTest.php`
- `tests/Feature/AssessmentRubricEvidenceTest.php`
- `tests/Feature/AssessmentObservationTest.php`
- `tests/Feature/AssessmentDebriefActionTest.php`
- `tests/Feature/AssessmentFinalizationTest.php`
- `tests/Feature/AssessmentIsolationTest.php`
- `tests/Feature/AssessmentCockpitTest.php`
- `tests/Feature/LegacyAssessmentMigrationTest.php`

---

### Task 1: ExecutionAssessment foundation

**Files:**
- Create `tests/Feature/ExecutionAssessmentTest.php`
- Create `database/migrations/2026_08_08_140000_create_execution_assessments_table.php`
- Create `app/Models/ExecutionAssessment.php`
- Modify `app/Models/ScenarioExecution.php`

**Produces:** `ScenarioExecution::assessment(): HasOne`, `ExecutionAssessment::execution(): BelongsTo`, `isDraft()`, `isFinalized()`.

- [ ] **Step 1: Write RED tests** proving public UUID, organization ownership, casts, lifecycle predicates and unique assessment per execution.

```php
public function test_execution_has_at_most_one_assessment(): void
{
    $execution = $this->completedExecution();

    $first = ExecutionAssessment::create([
        'organization_id' => $execution->organization_id,
        'scenario_execution_id' => $execution->id,
        'source' => 'm4',
        'status' => 'draft',
        'pass_threshold' => 70.00,
    ]);

    $this->assertNotNull($first->uuid);
    $this->assertSame($first->id, $execution->fresh()->assessment->id);

    $this->expectException(QueryException::class);
    ExecutionAssessment::create([
        'organization_id' => $execution->organization_id,
        'scenario_execution_id' => $execution->id,
        'source' => 'm4',
        'status' => 'draft',
        'pass_threshold' => 70.00,
    ]);
}
```

- [ ] **Step 2: Run RED**

```bash
php artisan test tests/Feature/ExecutionAssessmentTest.php
```

Expected failure: class/table absent.

- [ ] **Step 3: Implement migration**

```php
Schema::create('execution_assessments', function (Blueprint $table): void {
    $table->id();
    $table->uuid('uuid')->unique();
    $table->foreignId('organization_id')->constrained()->restrictOnDelete();
    $table->foreignId('scenario_execution_id')->unique()->constrained()->cascadeOnDelete();
    $table->string('source', 16)->default('m4');
    $table->string('status', 16)->default('draft');
    $table->decimal('pass_threshold', 5, 2)->nullable();
    $table->decimal('base_score', 5, 2)->nullable();
    $table->decimal('penalty_points', 6, 2)->nullable();
    $table->smallInteger('evaluator_adjustment')->default(0);
    $table->text('adjustment_justification')->nullable();
    $table->decimal('final_score', 5, 2)->nullable();
    $table->string('result', 16)->nullable();
    $table->boolean('automatic_fail')->default(false);
    $table->timestamp('finalized_at')->nullable();
    $table->foreignId('finalized_by_user_id')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamp('legacy_imported_at')->nullable();
    $table->timestamps();
    $table->index(['organization_id', 'status']);
});
```

- [ ] **Step 4: Implement model and relation** using `HasPublicUuid`, explicit fillable/casts, organization/execution/finalizer relations, and add `assessment()` to `ScenarioExecution`.

- [ ] **Step 5: Run GREEN + regression**

```bash
php artisan test tests/Feature/ExecutionAssessmentTest.php
php artisan test
vendor/bin/pint --test
npm run build
```

- [ ] **Step 6: Commit** `feat: add execution assessment foundation`.

---

### Task 2: Rubric, evidence and score calculator

**Files:**
- Create `tests/Unit/AssessmentScoreCalculatorTest.php`
- Create `tests/Feature/AssessmentRubricEvidenceTest.php`
- Create `database/migrations/2026_08_08_141000_create_assessment_rubric_tables.php`
- Create `app/Models/AssessmentCriterion.php`, `app/Models/AssessmentEvidence.php`
- Create `app/Services/AssessmentScoreCalculator.php`, `app/Services/ExecutionAssessmentManager.php`
- Modify `app/Models/ExecutionAssessment.php`

**Produces:**
- `calculateBase(iterable $criteria): float`
- `calculateFinal(float $base, float $penalties, int $adjustment): float`
- `result(float $finalScore, float $threshold, bool $automaticFail): string`
- `createForExecution(ScenarioExecution $execution): ExecutionAssessment`

- [ ] **Step 1: Write calculator RED**

```php
$this->assertSame(86.00, $calculator->calculateBase([
    ['score' => 80.00, 'weight' => 40.00],
    ['score' => 90.00, 'weight' => 60.00],
]));
$this->assertSame(81.00, $calculator->calculateFinal(86.00, 7.00, 2));
$this->assertSame(0.00, $calculator->calculateFinal(5.00, 20.00, -10));
$this->assertSame(100.00, $calculator->calculateFinal(99.00, 0.00, 10));
$this->assertSame('failed', $calculator->result(95.00, 70.00, true));
$this->assertSame('passed', $calculator->result(70.00, 70.00, false));
```

- [ ] **Step 2: Run RED**, then implement calculator exactly with centralized `round(..., 2)` and clamping.

```bash
php artisan test tests/Unit/AssessmentScoreCalculatorTest.php
```

- [ ] **Step 3: Write rubric/evidence RED** proving objective seeding, exact 100.00 weight distribution, empty rubric when no objectives, same-execution event evidence, cross-execution rejection and evidence time-window validation.

- [ ] **Step 4: Run RED**

```bash
php artisan test tests/Feature/AssessmentRubricEvidenceTest.php
```

- [ ] **Step 5: Implement rubric migration**

```php
Schema::create('assessment_criteria', function (Blueprint $table): void {
    $table->id();
    $table->uuid('uuid')->unique();
    $table->foreignId('execution_assessment_id')->constrained()->cascadeOnDelete();
    $table->string('code', 80)->nullable();
    $table->string('label', 200);
    $table->text('description')->nullable();
    $table->decimal('weight', 5, 2);
    $table->decimal('score', 5, 2)->nullable();
    $table->text('evaluator_notes')->nullable();
    $table->unsignedInteger('position')->default(1);
    $table->timestamps();
    $table->index(['execution_assessment_id', 'position']);
});

Schema::create('assessment_evidence', function (Blueprint $table): void {
    $table->id();
    $table->uuid('uuid')->unique();
    $table->foreignId('assessment_criterion_id')->constrained('assessment_criteria')->cascadeOnDelete();
    $table->foreignId('execution_event_id')->nullable()->constrained('execution_events')->nullOnDelete();
    $table->text('statement');
    $table->timestamp('observed_at');
    $table->foreignId('created_by_user_id')->constrained('users')->restrictOnDelete();
    $table->timestamps();
});
```

- [ ] **Step 6: Implement seeding** using integer hundredths:

```php
$totalHundredths = 10000;
$baseHundredths = intdiv($totalHundredths, $count);
$remainder = $totalHundredths - ($baseHundredths * $count);
$hundredths = $baseHundredths + ($index === $count - 1 ? $remainder : 0);
$weight = $hundredths / 100;
```

`createForExecution()` uses a transaction, rejects existing assessment, snapshots `70.00`, creates the debrief lazily later, filters blank learning objectives and creates ordered criteria.

- [ ] **Step 7: GREEN + regression** with both test files, full suite, Pint and build.
- [ ] **Step 8: Commit** `feat: add assessment rubric evidence and scoring`.

---

### Task 3: Critical-error occurrences and key times

**Files:**
- Create `tests/Feature/AssessmentObservationTest.php`
- Create `database/migrations/2026_08_08_142000_create_assessment_observation_tables.php`
- Create `app/Models/CriticalErrorOccurrence.php`, `app/Models/KeyTimeRecord.php`
- Modify `app/Models/ExecutionAssessment.php`

- [ ] **Step 1: RED critical-error tests** proving:
  - `record` has zero penalty;
  - `penalty` requires `0 < penalty_points <= 100`;
  - `automatic_fail` has zero inferred numerical penalty and later forces failed result;
  - `source=m4` label must match `ScenarioVersion.critical_errors`;
  - duplicate same label per assessment is rejected;
  - linked event must belong to same execution.

- [ ] **Step 2: RED key-time tests** with fixed execution start: submit time +95 seconds and forged client `elapsed_seconds=999999`; persisted authoritative value must be `95`. Reject before-start and after-completion times.

- [ ] **Step 3: Run RED**

```bash
php artisan test tests/Feature/AssessmentObservationTest.php
```

- [ ] **Step 4: Implement migration**

```php
Schema::create('critical_error_occurrences', function (Blueprint $table): void {
    $table->id();
    $table->uuid('uuid')->unique();
    $table->foreignId('execution_assessment_id')->constrained()->cascadeOnDelete();
    $table->string('catalog_label_snapshot', 500);
    $table->string('rule', 24)->default('record');
    $table->decimal('penalty_points', 6, 2)->default(0);
    $table->foreignId('execution_event_id')->nullable()->constrained('execution_events')->nullOnDelete();
    $table->timestamp('observed_at');
    $table->text('notes')->nullable();
    $table->string('source', 16)->default('m4');
    $table->timestamps();
    $table->unique(['execution_assessment_id', 'catalog_label_snapshot'], 'assessment_critical_error_unique');
});

Schema::create('key_time_records', function (Blueprint $table): void {
    $table->id();
    $table->uuid('uuid')->unique();
    $table->foreignId('execution_assessment_id')->constrained()->cascadeOnDelete();
    $table->string('label', 200);
    $table->timestamp('occurred_at');
    $table->unsignedInteger('elapsed_seconds');
    $table->unsignedInteger('reference_seconds')->nullable();
    $table->text('notes')->nullable();
    $table->timestamps();
});
```

- [ ] **Step 5: Implement model guards** and server-derived elapsed calculation using `execution.started_at->diffInSeconds($occurredAt)` after range checks.
- [ ] **Step 6: GREEN + regression**, then commit `feat: add critical error observations and key times`.

---

### Task 4: Structured debrief and action plan

**Files:**
- Create `tests/Feature/AssessmentDebriefActionTest.php`
- Create `database/migrations/2026_08_08_143000_create_execution_debrief_tables.php`
- Create `app/Models/ExecutionDebrief.php`, `DebriefEntry.php`, `ActionItem.php`
- Modify `app/Models/ExecutionAssessment.php`

- [ ] **Step 1: RED debrief tests** proving one debrief per assessment and normal M4 creation allows only `fact|interpretation|recommendation`; `legacy_unstructured` is rejected outside importer.
- [ ] **Step 2: RED action tests** proving required action, required due date and at least one responsible target; same-organization person validation; initial `open` status; exact transition matrix.
- [ ] **Step 3: Run RED**

```bash
php artisan test tests/Feature/AssessmentDebriefActionTest.php
```

- [ ] **Step 4: Implement migration**

```php
Schema::create('execution_debriefs', function (Blueprint $table): void {
    $table->id();
    $table->uuid('uuid')->unique();
    $table->foreignId('execution_assessment_id')->unique()->constrained()->cascadeOnDelete();
    $table->timestamps();
});
Schema::create('debrief_entries', function (Blueprint $table): void {
    $table->id();
    $table->uuid('uuid')->unique();
    $table->foreignId('execution_debrief_id')->constrained()->cascadeOnDelete();
    $table->string('kind', 32);
    $table->text('content');
    $table->unsignedInteger('position')->default(1);
    $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamps();
});
Schema::create('action_items', function (Blueprint $table): void {
    $table->id();
    $table->uuid('uuid')->unique();
    $table->foreignId('execution_debrief_id')->constrained()->cascadeOnDelete();
    $table->text('action');
    $table->foreignId('responsible_person_id')->nullable()->constrained('people')->nullOnDelete();
    $table->string('responsible_label', 200)->nullable();
    $table->date('due_date');
    $table->string('status', 24)->default('open');
    $table->text('notes')->nullable();
    $table->timestamp('status_changed_at')->nullable();
    $table->foreignId('status_changed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamps();
});
```

- [ ] **Step 5: Implement `ActionItem::transitionTo(string $nextStatus, User $actor): void`** with explicit map:

```php
$allowed = [
    'open' => ['in_progress', 'completed', 'cancelled'],
    'in_progress' => ['completed', 'cancelled'],
    'completed' => [],
    'cancelled' => [],
];
```

Reject any target outside the current state's list. Persist status, actor and timestamp atomically.

- [ ] **Step 6: GREEN + regression**, then commit `feat: add structured debrief and action plan`.

---

### Task 5: Finalization, hybrid scoring, immutability and concurrency

**Files:**
- Create `tests/Feature/AssessmentFinalizationTest.php`
- Modify `app/Services/ExecutionAssessmentManager.php`
- Modify all M4 models to enforce finalized-content immutability.

- [ ] **Step 1: RED prerequisite tests**: completed execution required; cancelled rejected; criteria required; exact 100.00 weight; all scores 0..100; evidence per criterion; fact+interpretation+recommendation; adjustment range; nonzero justification.
- [ ] **Step 2: RED outcome tests**: base 86, penalties 7, adjustment +2 => final 81 and passed; numerical 95 plus automatic fail => final 95 and failed.
- [ ] **Step 3: RED stale-finalization test**: load two model instances, finalize first, stale second must throw and not rewrite snapshot/finalizer.
- [ ] **Step 4: Run RED**

```bash
php artisan test tests/Feature/AssessmentFinalizationTest.php
```

- [ ] **Step 5: Implement `setAdjustment()`**

```php
if ($adjustment < -10 || $adjustment > 10) {
    throw new InvalidArgumentException('Evaluator adjustment must be between -10 and 10.');
}
if ($adjustment !== 0 && trim((string) $justification) === '') {
    throw new InvalidArgumentException('A nonzero evaluator adjustment requires justification.');
}
if ($assessment->isFinalized()) {
    throw new LogicException('Finalized assessment content is immutable.');
}
$assessment->update([
    'evaluator_adjustment' => $adjustment,
    'adjustment_justification' => $adjustment === 0 ? null : trim((string) $justification),
]);
```

- [ ] **Step 6: Implement `finalize()` with no duplicate formula**

```php
return DB::transaction(function () use ($assessment, $evaluator): ExecutionAssessment {
    $locked = ExecutionAssessment::query()
        ->with([
            'execution.scenarioVersion',
            'criteria.evidence',
            'criticalErrorOccurrences',
            'debrief.entries',
        ])
        ->lockForUpdate()
        ->findOrFail($assessment->id);

    if ($locked->isFinalized()) {
        throw new LogicException('Assessment is already finalized.');
    }
    if (! $locked->execution->isCompleted()) {
        throw new LogicException('Only a completed execution can be finalized.');
    }
    if ($locked->criteria->isEmpty()) {
        throw new LogicException('At least one assessment criterion is required.');
    }
    if (round((float) $locked->criteria->sum('weight'), 2) !== 100.00) {
        throw new LogicException('Criterion weights must total exactly 100.00.');
    }
    if ($locked->criteria->contains(fn ($criterion): bool => $criterion->score === null || (float) $criterion->score < 0 || (float) $criterion->score > 100)) {
        throw new LogicException('Every criterion must have a score between 0 and 100.');
    }
    if ($locked->criteria->contains(fn ($criterion): bool => $criterion->evidence->isEmpty())) {
        throw new LogicException('Every criterion requires evidence.');
    }

    $kinds = $locked->debrief?->entries?->pluck('kind')->all() ?? [];
    foreach (['fact', 'interpretation', 'recommendation'] as $requiredKind) {
        if (! in_array($requiredKind, $kinds, true)) {
            throw new LogicException('Structured debrief requires fact, interpretation and recommendation.');
        }
    }

    if ($locked->evaluator_adjustment < -10 || $locked->evaluator_adjustment > 10) {
        throw new LogicException('Evaluator adjustment is outside the allowed range.');
    }
    if ($locked->evaluator_adjustment !== 0 && trim((string) $locked->adjustment_justification) === '') {
        throw new LogicException('Nonzero evaluator adjustment requires justification.');
    }

    $base = $this->calculator->calculateBase($locked->criteria);
    $penalties = round((float) $locked->criticalErrorOccurrences
        ->where('rule', 'penalty')
        ->sum('penalty_points'), 2);
    $automaticFail = $locked->criticalErrorOccurrences->contains('rule', 'automatic_fail');
    $final = $this->calculator->calculateFinal($base, $penalties, $locked->evaluator_adjustment);
    $result = $this->calculator->result($final, (float) $locked->pass_threshold, $automaticFail);

    $locked->update([
        'base_score' => $base,
        'penalty_points' => $penalties,
        'automatic_fail' => $automaticFail,
        'final_score' => $final,
        'result' => $result,
        'status' => 'finalized',
        'finalized_at' => now(),
        'finalized_by_user_id' => $evaluator->id,
    ]);

    return $locked->fresh();
});
```

- [ ] **Step 7: Implement finalized-content guards** on criterion/evidence/error/key-time/debrief-entry/action content update/delete. `ActionItem::transitionTo()` remains the only post-finalization write path and may change only status fields.
- [ ] **Step 8: GREEN + regression**, then commit `feat: finalize assessments with immutable hybrid scoring`.

---

### Task 6: HTTP authorization, tenant isolation and UX

**Files:**
- Create `tests/Feature/AssessmentIsolationTest.php`, `tests/Feature/AssessmentCockpitTest.php`
- Create seven M4 controllers listed in File Map
- Create `resources/views/assessments/show.blade.php`
- Modify `resources/views/executions/show.blade.php`, `app/Http/Controllers/ScenarioExecutionController.php`, `routes/web.php`

- [ ] **Step 1: RED authorization tests** proving `scenarios.view` can GET, `scenarios.manage` without `evaluations.manage` gets 403 on mutation, `evaluations.manage` mutates, cross-org read/write is 403, foreign event/person references are rejected.
- [ ] **Step 2: RED UX tests** proving cockpit has `Avaliação & Debriefing` and assessment page has `Nota-base`, `Penalidades`, `Ajuste do avaliador`, `Nota final`, `Rubrica`, `Erros críticos observados`, `Tempos-chave`, `Fatos`, `Interpretações`, `Recomendações`, `Plano de ação`. Finalized page must not render content-edit forms.
- [ ] **Step 3: Run RED**

```bash
php artisan test tests/Feature/AssessmentIsolationTest.php tests/Feature/AssessmentCockpitTest.php
```

- [ ] **Step 4: Add exact routes**

```php
Route::post('/executions/{execution}/assessment', [ExecutionAssessmentController::class, 'store'])->name('assessments.store');
Route::get('/assessments/{assessment}', [ExecutionAssessmentController::class, 'show'])->name('assessments.show');
Route::patch('/assessments/{assessment}/adjustment', [ExecutionAssessmentController::class, 'adjustment'])->name('assessments.adjustment');
Route::patch('/assessments/{assessment}/finalize', [ExecutionAssessmentController::class, 'finalize'])->name('assessments.finalize');
Route::post('/assessments/{assessment}/criteria', [AssessmentCriterionController::class, 'store'])->name('assessment-criteria.store');
Route::patch('/assessment-criteria/{criterion}', [AssessmentCriterionController::class, 'update'])->name('assessment-criteria.update');
Route::delete('/assessment-criteria/{criterion}', [AssessmentCriterionController::class, 'destroy'])->name('assessment-criteria.destroy');
Route::post('/assessment-criteria/{criterion}/evidence', [AssessmentEvidenceController::class, 'store'])->name('assessment-evidence.store');
Route::delete('/assessment-evidence/{evidence}', [AssessmentEvidenceController::class, 'destroy'])->name('assessment-evidence.destroy');
Route::post('/assessments/{assessment}/critical-errors', [CriticalErrorOccurrenceController::class, 'store'])->name('critical-error-occurrences.store');
Route::delete('/critical-error-occurrences/{occurrence}', [CriticalErrorOccurrenceController::class, 'destroy'])->name('critical-error-occurrences.destroy');
Route::post('/assessments/{assessment}/key-times', [KeyTimeRecordController::class, 'store'])->name('key-times.store');
Route::delete('/key-times/{keyTime}', [KeyTimeRecordController::class, 'destroy'])->name('key-times.destroy');
Route::post('/assessments/{assessment}/debrief-entries', [DebriefEntryController::class, 'store'])->name('debrief-entries.store');
Route::patch('/debrief-entries/{entry}', [DebriefEntryController::class, 'update'])->name('debrief-entries.update');
Route::delete('/debrief-entries/{entry}', [DebriefEntryController::class, 'destroy'])->name('debrief-entries.destroy');
Route::post('/assessments/{assessment}/action-items', [ActionItemController::class, 'store'])->name('action-items.store');
Route::patch('/action-items/{actionItem}', [ActionItemController::class, 'update'])->name('action-items.update');
Route::delete('/action-items/{actionItem}', [ActionItemController::class, 'destroy'])->name('action-items.destroy');
Route::patch('/action-items/{actionItem}/status', [ActionItemController::class, 'transition'])->name('action-items.transition');
```

- [ ] **Step 5: Implement controller authorization pattern**: reads call `ensureAbility(...SCENARIOS_VIEW)`; every mutation calls `ensureAbility(...EVALUATIONS_MANAGE)`, verifies `assessment/execution.organization_id` equals active organization, then validates referenced event/person belongs to same execution/organization before writing. No controller accepts `organization_id` from request data.
- [ ] **Step 6: Implement dedicated assessment page and execution cockpit CTA**. Show score components separately, use red only for actual critical/failure states, show required-step empty states, and on finalized assessment render action-status controls only.
- [ ] **Step 7: GREEN + full regression**, then commit `feat: add institutional assessment workflow`.

---

### Task 7: Legacy import and retirement of legacy write path

**Files:**
- Create `tests/Feature/LegacyAssessmentMigrationTest.php`
- Create `database/migrations/2026_08_08_149000_import_legacy_scenario_assessments.php`
- Create `app/Services/LegacyAssessmentImporter.php`
- Modify `app/Http/Controllers/ScenarioController.php`, `routes/web.php`, legacy Blade assessment block.

- [ ] **Step 1: RED migration tests** proving score `82` maps to execution #1 with `source=legacy`, `status=finalized`, `base_score=82`, `final_score=82`, `pass_threshold=null`, `result=null`; observed errors become `rule=record` with zero penalty; debrief becomes one `legacy_unstructured`; absent execution is not guessed; importer is idempotent; active UI/controller no longer writes legacy fields.
- [ ] **Step 2: Run RED**

```bash
php artisan test tests/Feature/LegacyAssessmentMigrationTest.php
```

- [ ] **Step 3: Implement importer**. It must resolve only the historical/backfilled execution with `sequence_number=1`, create nothing when mapping is absent, and when score exists create one 100%-weight criterion named `Avaliação legada importada` plus one provenance evidence statement exactly `Valor numérico importado do registro de avaliação legado do cenário.`. It must never infer threshold/result/penalty/debrief category.
- [ ] **Step 4: Implement migration `up()`** by invoking the importer algorithm idempotently after all M4 tables exist. `down()` deletes only M4 rows with `source=legacy` that were created by import and never clears original legacy scenario columns.
- [ ] **Step 5: Remove `ScenarioController::evaluate`, route `scenarios.evaluate`, and active legacy evaluation form** only after migration tests are green. Preserve database columns.
- [ ] **Step 6: GREEN + full regression**, then commit `feat: migrate legacy assessments to execution domain`.

---

### Task 8: Forensic audit and final PR gate

**Files:** create `docs/PHASE_M4_AUDIT.md`; modify production files only after a newly added regression test proves an audit defect.

- [ ] **Step 1: Review full diff against `main`** for scope contamination, legacy writes, UUID boundaries, `evaluations.manage`, tenant checks, cross-execution references, duplicate scoring formulas, row-lock finalization, immutable children, action-status exception, legacy semantic preservation, eager-loading/N+1 and unnecessary PII/free-text logging.
- [ ] **Step 2: For each objective defect found, write RED regression**, confirm RED, minimally fix, confirm GREEN. Do not make untested functional audit patches.
- [ ] **Step 3: Run pre-document gate**

```bash
php artisan test
vendor/bin/pint --test
npm run build
php artisan migrate:fresh --force
```

- [ ] **Step 4: Create `docs/PHASE_M4_AUDIT.md`** with sections: Executive conclusion; Repository/PR gate; Schema/migrations; Domain model; Scoring guarantees; Critical errors; Key times; Debrief/action plan; Authorization/tenant isolation; UUID boundary; Finalization/immutability/concurrency; Legacy migration; UX; Tests/TDD evidence; Known debt; Explicit deferrals; Acceptance checklist; Verdict.
- [ ] **Step 5: Require fresh CI on the exact documentation HEAD**. Prior runs are not final evidence.
- [ ] **Step 6: Reconcile PR**: base `main`; mergeable; zero unresolved review threads; branch 0 behind; exact final HEAD documented; final CI success.
- [ ] **Step 7: Stop before merge and report** exact HEAD, final CI run, PR state, changed files, M4 percentage, deferrals and recommended integration action. Do not start M5 before deliberate M4 integration.
