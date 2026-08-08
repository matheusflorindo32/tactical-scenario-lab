# M4 — Assessment & Debriefing Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Move evaluation from legacy `Scenario` fields into a structured, execution-scoped, explainable and auditable `ExecutionAssessment` domain with weighted criteria, evidence, critical-error occurrences, key times, structured debriefing and action follow-up.

**Architecture:** `ScenarioExecution` owns at most one `ExecutionAssessment`. Scoring is deterministic and centralized in `AssessmentScoreCalculator`; lifecycle/finalization invariants live in `ExecutionAssessmentManager`; thin HTTP controllers enforce `evaluations.manage`, active-organization isolation and UUID boundaries. Finalized assessment content is immutable; only `ActionItem.status` may advance after finalization through a controlled transition service path.

**Tech Stack:** Laravel 13, PHP 8.4, Eloquent, Blade, Tailwind, Alpine, SQLite CI/local, PostgreSQL-targeted relational design, PHPUnit, Laravel Pint, Vite, GitHub Actions.

## Global Constraints

- Work only on `feature/m4-assessment-debriefing`; do not write functional M4 changes directly to `main`.
- Base is `main` after M3 merge commit `c135cd12ef2415d91b7e2ba4636bfbd23dac8759`.
- Preserve all M1–M3 tests and institutional tenant boundaries.
- New M4 writes require `evaluations.manage`; `scenarios.manage` alone is insufficient.
- Assessment read requires `scenarios.view` in the active organization.
- Every externally addressable M4 aggregate uses `HasPublicUuid`; numeric IDs remain internal.
- One assessment per `ScenarioExecution`.
- New M4 assessment threshold snapshot is exactly `70.00`.
- Evaluator adjustment is an integer from `-10` through `+10`; nonzero adjustment requires justification.
- Final numerical score is `clamp(base_score - penalty_points + evaluator_adjustment, 0, 100)`.
- `automatic_fail` changes result to failed without rewriting the numerical score.
- Normal finalization requires execution status `completed`; cancelled execution cannot finalize.
- Normal finalization requires at least one criterion, exact total weight `100.00`, all criteria scored, at least one evidence record per criterion, and at least one `fact`, `interpretation` and `recommendation` debrief entry.
- Finalized assessment content is immutable. Post-finalization action-item content is immutable; only status may advance `open -> in_progress|completed|cancelled` and `in_progress -> completed|cancelled`.
- Legacy import must preserve source semantics and must not invent pass/fail, penalties, or semantic classification of old free text.
- Stop all new application writes to legacy `Scenario.score`, `Scenario.debrief_notes`, and `Scenario.observed_critical_errors` by M4 completion; keep columns for rollback compatibility.
- No M5 PDF/CSV/dashboard work, no M6 production hardening, no Wiki overhaul, no AI/Research Hub/TMA Platform scope.
- Every functional change follows RED -> GREEN: failing test first, prove expected failure in GitHub Actions, minimal implementation, then green CI.
- Exact final HEAD must receive a fresh successful GitHub Actions run before integration.

---

## File Structure

### Domain models to create

- `app/Models/ExecutionAssessment.php` — one assessment aggregate per execution; lifecycle/scoring snapshot and child relations.
- `app/Models/AssessmentCriterion.php` — weighted rubric criterion.
- `app/Models/AssessmentEvidence.php` — criterion evidence with optional same-execution timeline reference.
- `app/Models/CriticalErrorOccurrence.php` — observed critical error and snapshotted rule.
- `app/Models/KeyTimeRecord.php` — assessment-relevant timestamp and server-derived elapsed seconds.
- `app/Models/ExecutionDebrief.php` — one structured debrief container per assessment.
- `app/Models/DebriefEntry.php` — fact/interpretation/recommendation/legacy entry.
- `app/Models/ActionItem.php` — corrective/follow-up action with frozen content and operational status.

### Domain services to create

- `app/Services/AssessmentScoreCalculator.php` — pure deterministic score calculation.
- `app/Services/ExecutionAssessmentManager.php` — creation/seeding, adjustment validation, finalization, row locking and aggregate invariants.
- `app/Services/LegacyAssessmentImporter.php` — controlled legacy backfill preserving source semantics.

### HTTP controllers to create

- `app/Http/Controllers/ExecutionAssessmentController.php`
- `app/Http/Controllers/AssessmentCriterionController.php`
- `app/Http/Controllers/AssessmentEvidenceController.php`
- `app/Http/Controllers/CriticalErrorOccurrenceController.php`
- `app/Http/Controllers/KeyTimeRecordController.php`
- `app/Http/Controllers/DebriefEntryController.php`
- `app/Http/Controllers/ActionItemController.php`

### Existing files to modify

- `app/Models/ScenarioExecution.php` — add assessment relation.
- `app/Http/Controllers/ScenarioExecutionController.php` — eager-load assessment summary and expose evaluation permissions.
- `app/Http/Controllers/ScenarioController.php` — retire active legacy evaluation write path after migration coverage is green.
- `routes/web.php` — M4 routes and removal of active legacy evaluate route at Task 7.
- `resources/views/executions/show.blade.php` — assessment CTA/status card.
- `resources/views/assessments/show.blade.php` — new dedicated M4 page.

### Migration files to create

- `database/migrations/2026_08_08_140000_create_execution_assessments_table.php`
- `database/migrations/2026_08_08_141000_create_assessment_rubric_tables.php`
- `database/migrations/2026_08_08_142000_create_assessment_observation_tables.php`
- `database/migrations/2026_08_08_143000_create_execution_debrief_tables.php`
- `database/migrations/2026_08_08_149000_import_legacy_scenario_assessments.php`

### Tests to create

- `tests/Feature/ExecutionAssessmentTest.php`
- `tests/Unit/AssessmentScoreCalculatorTest.php`
- `tests/Feature/AssessmentRubricEvidenceTest.php`
- `tests/Feature/AssessmentObservationTest.php`
- `tests/Feature/AssessmentDebriefActionTest.php`
- `tests/Feature/AssessmentFinalizationTest.php`
- `tests/Feature/AssessmentIsolationTest.php`
- `tests/Feature/LegacyAssessmentMigrationTest.php`
- `tests/Feature/AssessmentCockpitTest.php`

---

### Task 1: ExecutionAssessment foundation and one-per-execution invariant

**Files:**
- Create: `tests/Feature/ExecutionAssessmentTest.php`
- Create: `database/migrations/2026_08_08_140000_create_execution_assessments_table.php`
- Create: `app/Models/ExecutionAssessment.php`
- Modify: `app/Models/ScenarioExecution.php`

**Interfaces:**
- Produces: `ScenarioExecution::assessment(): HasOne`
- Produces: `ExecutionAssessment::execution(): BelongsTo`
- Produces: `ExecutionAssessment::isDraft(): bool`
- Produces: `ExecutionAssessment::isFinalized(): bool`
- Produces database uniqueness on `scenario_execution_id`.

- [ ] **Step 1: Write failing foundation tests**

Create tests proving schema, public UUID, organization ownership and one-assessment-per-execution. Core assertions:

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

Also assert casts for decimal score fields, booleans and timestamps.

- [ ] **Step 2: Run RED**

Run:

```bash
php artisan test tests/Feature/ExecutionAssessmentTest.php
```

Expected: FAIL because `execution_assessments` and `ExecutionAssessment` do not exist.

- [ ] **Step 3: Implement schema and model**

Migration must create:

```php
Schema::create('execution_assessments', function (Blueprint $table): void {
    $table->id();
    $table->uuid('uuid')->unique();
    $table->foreignId('organization_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
    $table->foreignId('scenario_execution_id')->unique()->constrained()->cascadeOnUpdate()->cascadeOnDelete();
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

`ExecutionAssessment` uses `HasPublicUuid`, fillable fields listed explicitly, decimal casts to two decimals where appropriate, boolean cast for `automatic_fail`, datetime casts for finalization/import timestamps, and relation to execution/organization/finalizer.

Add to `ScenarioExecution`:

```php
public function assessment(): HasOne
{
    return $this->hasOne(ExecutionAssessment::class);
}
```

- [ ] **Step 4: Run GREEN and full regression**

```bash
php artisan test tests/Feature/ExecutionAssessmentTest.php
php artisan test
vendor/bin/pint --test
npm run build
```

Expected: all PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Models/ExecutionAssessment.php app/Models/ScenarioExecution.php database/migrations/2026_08_08_140000_create_execution_assessments_table.php tests/Feature/ExecutionAssessmentTest.php
git commit -m "feat: add execution assessment foundation"
```

---

### Task 2: Rubric criteria, evidence and deterministic scoring

**Files:**
- Create: `tests/Unit/AssessmentScoreCalculatorTest.php`
- Create: `tests/Feature/AssessmentRubricEvidenceTest.php`
- Create: `database/migrations/2026_08_08_141000_create_assessment_rubric_tables.php`
- Create: `app/Models/AssessmentCriterion.php`
- Create: `app/Models/AssessmentEvidence.php`
- Create: `app/Services/AssessmentScoreCalculator.php`
- Create: `app/Services/ExecutionAssessmentManager.php`
- Modify: `app/Models/ExecutionAssessment.php`

**Interfaces:**
- Produces: `AssessmentScoreCalculator::calculateBase(iterable $criteria): float`
- Produces: `AssessmentScoreCalculator::calculateFinal(float $base, float $penalties, int $adjustment): float`
- Produces: `AssessmentScoreCalculator::result(float $finalScore, float $threshold, bool $automaticFail): string`
- Produces: `ExecutionAssessmentManager::createForExecution(ScenarioExecution $execution): ExecutionAssessment`
- Produces criteria/evidence relations.

- [ ] **Step 1: Write calculator RED tests**

Use exact cases:

```php
public function test_weighted_base_score_is_deterministic(): void
{
    $calculator = new AssessmentScoreCalculator();

    $criteria = [
        ['score' => 80.00, 'weight' => 40.00],
        ['score' => 90.00, 'weight' => 60.00],
    ];

    $this->assertSame(86.00, $calculator->calculateBase($criteria));
    $this->assertSame(81.00, $calculator->calculateFinal(86.00, 7.00, 2));
    $this->assertSame(0.00, $calculator->calculateFinal(5.00, 20.00, -10));
    $this->assertSame(100.00, $calculator->calculateFinal(99.00, 0.00, 10));
    $this->assertSame('failed', $calculator->result(95.00, 70.00, true));
    $this->assertSame('passed', $calculator->result(70.00, 70.00, false));
}
```

- [ ] **Step 2: Run calculator RED**

```bash
php artisan test tests/Unit/AssessmentScoreCalculatorTest.php
```

Expected: FAIL because calculator does not exist.

- [ ] **Step 3: Implement calculator minimal GREEN**

Use centralized rounding:

```php
public function calculateBase(iterable $criteria): float
{
    $sum = collect($criteria)->sum(
        fn ($criterion): float => (float) data_get($criterion, 'score') * (float) data_get($criterion, 'weight') / 100
    );

    return round($sum, 2);
}

public function calculateFinal(float $base, float $penalties, int $adjustment): float
{
    return round(max(0, min(100, $base - $penalties + $adjustment)), 2);
}

public function result(float $finalScore, float $threshold, bool $automaticFail): string
{
    return $automaticFail || $finalScore < $threshold ? 'failed' : 'passed';
}
```

- [ ] **Step 4: Write rubric/evidence RED tests**

Prove:

1. assessment creation seeds one criterion per nonblank `ScenarioVersion.learning_objectives`;
2. equal weights total exactly `100.00`, with last criterion absorbing remainder;
3. no objectives creates empty rubric;
4. evidence may link only to an `ExecutionEvent` from the same execution;
5. evidence timestamp is inside execution window;
6. direct cross-execution event reference is rejected before persistence.

Representative creation assertion:

```php
$assessment = $manager->createForExecution($execution);

$this->assertSame(70.00, (float) $assessment->pass_threshold);
$this->assertSame('m4', $assessment->source);
$this->assertSame(100.00, round((float) $assessment->criteria->sum('weight'), 2));
```

- [ ] **Step 5: Run rubric RED**

```bash
php artisan test tests/Feature/AssessmentRubricEvidenceTest.php
```

Expected: FAIL because rubric tables/models/manager behavior do not exist.

- [ ] **Step 6: Implement rubric schema/models/manager seeding**

Migration creates:

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

`createForExecution()` runs transactionally, rejects duplicate assessment and seeds normalized objectives. Weight helper must compute integer hundredths to avoid float drift:

```php
$totalHundredths = 10000;
$base = intdiv($totalHundredths, $count);
$remainder = $totalHundredths - ($base * $count);
$hundredths = $base + ($index === $count - 1 ? $remainder : 0);
$weight = $hundredths / 100;
```

- [ ] **Step 7: Run GREEN**

```bash
php artisan test tests/Unit/AssessmentScoreCalculatorTest.php tests/Feature/AssessmentRubricEvidenceTest.php
php artisan test
vendor/bin/pint --test
npm run build
```

- [ ] **Step 8: Commit**

```bash
git add app/Models/AssessmentCriterion.php app/Models/AssessmentEvidence.php app/Models/ExecutionAssessment.php app/Services/AssessmentScoreCalculator.php app/Services/ExecutionAssessmentManager.php database/migrations/2026_08_08_141000_create_assessment_rubric_tables.php tests/Unit/AssessmentScoreCalculatorTest.php tests/Feature/AssessmentRubricEvidenceTest.php
git commit -m "feat: add assessment rubric evidence and scoring"
```

---

### Task 3: Critical-error occurrences and key times

**Files:**
- Create: `tests/Feature/AssessmentObservationTest.php`
- Create: `database/migrations/2026_08_08_142000_create_assessment_observation_tables.php`
- Create: `app/Models/CriticalErrorOccurrence.php`
- Create: `app/Models/KeyTimeRecord.php`
- Modify: `app/Models/ExecutionAssessment.php`

**Interfaces:**
- Produces `ExecutionAssessment::criticalErrorOccurrences(): HasMany`.
- Produces `ExecutionAssessment::keyTimes(): HasMany`.
- Critical occurrence `rule` values: `record|penalty|automatic_fail`.
- Key-time authoritative elapsed seconds are backend-derived.

- [ ] **Step 1: Write RED tests for critical rules**

Prove:

```php
// record => penalty 0
// penalty => 0 < penalty_points <= 100
// automatic_fail => assessment outcome flag at finalization, not score rewrite
// source=m4 label must be in ScenarioVersion.critical_errors
// duplicate same catalog label rejected per assessment
// foreign execution event rejected
```

Use an assertion that a crafted unknown catalog label produces validation/domain failure and leaves `critical_error_occurrences` empty.

- [ ] **Step 2: Write RED tests for key times**

Use an execution started at a fixed timestamp and submit `occurred_at` 95 seconds later; assert persisted `elapsed_seconds === 95` even if client payload contains a forged `elapsed_seconds=999999`. Assert before-start and after-completion timestamps are rejected.

- [ ] **Step 3: Run RED**

```bash
php artisan test tests/Feature/AssessmentObservationTest.php
```

Expected: FAIL because models/tables do not exist.

- [ ] **Step 4: Implement observation schema and model invariants**

Migration:

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

Model/service validation must reject invalid rule/penalty pairs and compute elapsed seconds from execution timestamps.

- [ ] **Step 5: Run GREEN and regression**

```bash
php artisan test tests/Feature/AssessmentObservationTest.php
php artisan test
vendor/bin/pint --test
npm run build
```

- [ ] **Step 6: Commit**

```bash
git add app/Models/CriticalErrorOccurrence.php app/Models/KeyTimeRecord.php app/Models/ExecutionAssessment.php database/migrations/2026_08_08_142000_create_assessment_observation_tables.php tests/Feature/AssessmentObservationTest.php
git commit -m "feat: add critical error observations and key times"
```

---

### Task 4: Structured debrief and operational action plan

**Files:**
- Create: `tests/Feature/AssessmentDebriefActionTest.php`
- Create: `database/migrations/2026_08_08_143000_create_execution_debrief_tables.php`
- Create: `app/Models/ExecutionDebrief.php`
- Create: `app/Models/DebriefEntry.php`
- Create: `app/Models/ActionItem.php`
- Modify: `app/Models/ExecutionAssessment.php`

**Interfaces:**
- One debrief per assessment.
- Public kinds for new entries: `fact|interpretation|recommendation`; `legacy_unstructured` is migration-only.
- Action status transition API: `ActionItem::transitionTo(string $status, User $actor): void` or manager-equivalent.

- [ ] **Step 1: Write RED tests for structured debrief**

Prove exactly one debrief container per assessment and that public-domain creation rejects `legacy_unstructured` for normal M4 records.

- [ ] **Step 2: Write RED tests for action plan**

Prove action item requires:

- nonblank action;
- responsible person from same organization **or** nonblank responsible label;
- required due date;
- initial status `open`.

Then prove transition matrix:

```text
open -> in_progress | completed | cancelled
in_progress -> completed | cancelled
completed -> no transitions
cancelled -> no transitions
```

After assessment finalization, content changes are rejected but a valid status transition persists `status_changed_at` and `status_changed_by_user_id`.

- [ ] **Step 3: Run RED**

```bash
php artisan test tests/Feature/AssessmentDebriefActionTest.php
```

- [ ] **Step 4: Implement debrief/action schema and models**

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

Use application guards to freeze content after finalization and allow only explicit operational status transitions.

- [ ] **Step 5: Run GREEN**

```bash
php artisan test tests/Feature/AssessmentDebriefActionTest.php
php artisan test
vendor/bin/pint --test
npm run build
```

- [ ] **Step 6: Commit**

```bash
git add app/Models/ExecutionDebrief.php app/Models/DebriefEntry.php app/Models/ActionItem.php app/Models/ExecutionAssessment.php database/migrations/2026_08_08_143000_create_execution_debrief_tables.php tests/Feature/AssessmentDebriefActionTest.php
git commit -m "feat: add structured debrief and action plan"
```

---

### Task 5: Finalization, hybrid scoring, immutability and concurrency

**Files:**
- Create: `tests/Feature/AssessmentFinalizationTest.php`
- Modify: `app/Services/ExecutionAssessmentManager.php`
- Modify: all M4 child models as needed for immutable finalized content.

**Interfaces:**
- Consumes `AssessmentScoreCalculator`.
- Produces `ExecutionAssessmentManager::setAdjustment(ExecutionAssessment $assessment, int $adjustment, ?string $justification): ExecutionAssessment`.
- Produces `ExecutionAssessmentManager::finalize(ExecutionAssessment $assessment, User $evaluator): ExecutionAssessment`.

- [ ] **Step 1: Write RED tests for finalization prerequisites**

One test per rule:

- execution must be completed;
- cancelled cannot finalize;
- one or more criteria required;
- total weight exactly 100.00;
- all criteria scored 0..100;
- each criterion has evidence;
- at least one fact, interpretation and recommendation;
- adjustment outside range rejected;
- nonzero adjustment without justification rejected.

- [ ] **Step 2: Write RED scoring outcome tests**

Example:

```php
// weighted base 86
// penalties 7
// adjustment +2
// final 81 => passed
$this->assertSame(86.00, (float) $assessment->base_score);
$this->assertSame(7.00, (float) $assessment->penalty_points);
$this->assertSame(81.00, (float) $assessment->final_score);
$this->assertSame('passed', $assessment->result);
```

Add automatic fail case with numerical score 95 and expected result `failed` while `final_score` remains 95.

- [ ] **Step 3: Write stale concurrent finalization RED test**

Load the same assessment twice. Finalize one instance. Attempt finalization with the stale instance. Expected: deterministic `LogicException`/domain exception and no rewritten finalizer/snapshot.

- [ ] **Step 4: Run RED**

```bash
php artisan test tests/Feature/AssessmentFinalizationTest.php
```

- [ ] **Step 5: Implement transactional finalization**

`finalize()` must:

```php
return DB::transaction(function () use ($assessment, $evaluator): ExecutionAssessment {
    $locked = ExecutionAssessment::query()
        ->with(['execution', 'criteria.evidence', 'criticalErrorOccurrences', 'debrief.entries'])
        ->lockForUpdate()
        ->findOrFail($assessment->id);

    if ($locked->isFinalized()) {
        throw new LogicException('Assessment is already finalized.');
    }

    // validate completed execution + all invariants
    // calculate score only through AssessmentScoreCalculator
    // persist immutable snapshot + finalizer + finalized_at

    return $locked->fresh();
});
```

Child update/delete guards consult parent assessment status and throw if finalized. `ActionItem` permits only its dedicated status-transition path after finalization.

- [ ] **Step 6: Run GREEN and full regression**

```bash
php artisan test tests/Feature/AssessmentFinalizationTest.php
php artisan test
vendor/bin/pint --test
npm run build
```

- [ ] **Step 7: Commit**

```bash
git add app/Services/ExecutionAssessmentManager.php app/Models/ExecutionAssessment.php app/Models/AssessmentCriterion.php app/Models/AssessmentEvidence.php app/Models/CriticalErrorOccurrence.php app/Models/KeyTimeRecord.php app/Models/DebriefEntry.php app/Models/ActionItem.php tests/Feature/AssessmentFinalizationTest.php
git commit -m "feat: finalize assessments with immutable hybrid scoring"
```

---

### Task 6: HTTP authorization, tenant isolation and assessment page

**Files:**
- Create: `tests/Feature/AssessmentIsolationTest.php`
- Create: `tests/Feature/AssessmentCockpitTest.php`
- Create controllers listed in File Structure.
- Create: `resources/views/assessments/show.blade.php`
- Modify: `resources/views/executions/show.blade.php`
- Modify: `app/Http/Controllers/ScenarioExecutionController.php`
- Modify: `routes/web.php`

**Interfaces:**
- `POST /executions/{execution}/assessment` creates/opens assessment.
- `GET /assessments/{assessment}` displays assessment.
- All M4 mutation routes require `evaluations.manage` and same active organization.
- All public M4 route/model references resolve by UUID via `HasPublicUuid`.

- [ ] **Step 1: Write RED authorization/isolation tests**

Prove:

```text
scenarios.view                        -> GET assessment allowed
scenarios.manage without eval.manage -> M4 mutation 403
evaluations.manage                   -> M4 mutation allowed
cross-org assessment                 -> read/write 403
foreign execution event              -> rejected
foreign responsible person           -> rejected
```

Use crafted POST/PATCH requests, not only service calls.

- [ ] **Step 2: Write RED cockpit/UX contracts**

Prove execution cockpit contains `Avaliação & Debriefing`, assessment summary page contains labels:

```text
Nota-base
Penalidades
Ajuste do avaliador
Nota final
Rubrica
Erros críticos observados
Tempos-chave
Fatos
Interpretações
Recomendações
Plano de ação
```

Also prove finalized assessment page exposes no content-edit form actions.

- [ ] **Step 3: Run RED**

```bash
php artisan test tests/Feature/AssessmentIsolationTest.php tests/Feature/AssessmentCockpitTest.php
```

- [ ] **Step 4: Implement thin controllers and routes**

Controller pattern for every mutation:

```php
$organizationId = $activeOrganization->ensureAbility($request, AccessAbility::EVALUATIONS_MANAGE);
abort_unless($assessment->organization_id === $organizationId, 403);
```

Never accept `organization_id` from payload. Resolve linked event/person by UUID, then verify same execution/organization before mutation.

Route family should use explicit names, e.g.:

```php
Route::post('/executions/{execution}/assessment', [ExecutionAssessmentController::class, 'store'])
    ->name('assessments.store');
Route::get('/assessments/{assessment}', [ExecutionAssessmentController::class, 'show'])
    ->name('assessments.show');
Route::patch('/assessments/{assessment}/adjustment', [ExecutionAssessmentController::class, 'adjustment'])
    ->name('assessments.adjustment');
Route::patch('/assessments/{assessment}/finalize', [ExecutionAssessmentController::class, 'finalize'])
    ->name('assessments.finalize');
```

Add analogous criterion/evidence/error/key-time/debrief/action routes with UUID-bound models and draft/finalized guards.

- [ ] **Step 5: Implement assessment page and cockpit CTA**

Render calculation components separately. Red is reserved for actual critical/failure states. Empty states tell evaluator what remains before finalization. For action items on finalized assessments, render only the valid operational status controls, not content-edit fields.

- [ ] **Step 6: Run GREEN and regression**

```bash
php artisan test tests/Feature/AssessmentIsolationTest.php tests/Feature/AssessmentCockpitTest.php
php artisan test
vendor/bin/pint --test
npm run build
```

- [ ] **Step 7: Commit**

```bash
git add app/Http/Controllers app/Http/Controllers/ScenarioExecutionController.php resources/views/assessments/show.blade.php resources/views/executions/show.blade.php routes/web.php tests/Feature/AssessmentIsolationTest.php tests/Feature/AssessmentCockpitTest.php
git commit -m "feat: add institutional assessment workflow"
```

---

### Task 7: Legacy import and retirement of active Scenario evaluation writes

**Files:**
- Create: `tests/Feature/LegacyAssessmentMigrationTest.php`
- Create: `database/migrations/2026_08_08_149000_import_legacy_scenario_assessments.php`
- Create: `app/Services/LegacyAssessmentImporter.php`
- Modify: `app/Http/Controllers/ScenarioController.php`
- Modify: `routes/web.php`
- Modify: any legacy Blade block that posts to `scenarios.evaluate`.

**Interfaces:**
- Import maps legacy assessment data only to historical/backfilled execution sequence 1.
- If no execution can be mapped, importer does not guess and leaves source data untouched.
- Legacy imported assessment is `source=legacy`, `status=finalized`, `pass_threshold=null`, `result=null`.

- [ ] **Step 1: Write RED legacy migration tests**

Cases:

1. legacy score `82`, debrief text and observed errors map to execution #1;
2. imported assessment preserves `base_score=82`, `final_score=82`, but `pass_threshold` and `result` remain null;
3. observed strings become `source=legacy`, `rule=record`, penalty zero;
4. free text becomes one `legacy_unstructured` entry, never auto-classified;
5. scenario with legacy data but no execution is not guessed into another execution;
6. rerunning importer is idempotent;
7. active UI/controller no longer writes legacy score/debrief/error fields.

- [ ] **Step 2: Run RED**

```bash
php artisan test tests/Feature/LegacyAssessmentMigrationTest.php
```

- [ ] **Step 3: Implement importer and migration**

`LegacyAssessmentImporter` uses explicit queries and transactions. It may create provenance evidence only when a legacy score exists:

```text
Valor numérico importado do registro de avaliação legado do cenário.
```

Do not assert that this is an observed field event or infer any pass/fail semantics.

Migration `up()` invokes importer logic safely or mirrors its DB-safe algorithm; `down()` removes only `source=legacy` M4 records created by import and never clears original `Scenario` fields.

- [ ] **Step 4: Retire active legacy write path**

Remove `ScenarioController::evaluate` and route `scenarios.evaluate` after the migration tests are green. Remove active legacy assessment forms/POSTs. Keep old DB columns and read compatibility as needed for rollback/audit.

- [ ] **Step 5: Run GREEN and full regression**

```bash
php artisan test tests/Feature/LegacyAssessmentMigrationTest.php
php artisan test
vendor/bin/pint --test
npm run build
```

- [ ] **Step 6: Commit**

```bash
git add app/Services/LegacyAssessmentImporter.php app/Http/Controllers/ScenarioController.php database/migrations/2026_08_08_149000_import_legacy_scenario_assessments.php routes/web.php resources/views tests/Feature/LegacyAssessmentMigrationTest.php
git commit -m "feat: migrate legacy assessments to execution domain"
```

---

### Task 8: Forensic audit, cleanup and final PR gate

**Files:**
- Create: `docs/PHASE_M4_AUDIT.md`
- Modify only files required by defects found during audit, each defect preceded by a failing regression test.

**Interfaces:**
- Produces final M4 integration evidence, not new product scope.

- [ ] **Step 1: Review complete branch diff against `main`**

Check:

```text
- no M5/M6/M8/M9 scope contamination
- no new writes to Scenario.score/debrief_notes/observed_critical_errors
- all public M4 resources use UUID
- every mutation uses evaluations.manage + active-org ownership
- no cross-execution event/person attachment path
- scoring formula exists only in AssessmentScoreCalculator
- finalization uses transaction + lockForUpdate
- finalized child content mutation is blocked
- action-item post-finalization status is the only deliberate mutable exception
- legacy import invents no pass/fail, penalties, or semantic categories
- assessment page eager-loads graph without obvious N+1 loops
- no arbitrary metadata/PII duplication into generic audit payloads
```

- [ ] **Step 2: For every objective gap found, add RED regression first**

Do not patch production code directly. Create a failing test naming the observed defect, confirm RED, then minimally fix and confirm GREEN.

- [ ] **Step 3: Run authoritative pre-document gate**

```bash
php artisan test
vendor/bin/pint --test
npm run build
php artisan migrate:fresh --force
```

GitHub Actions must show PHPUnit, migrations, build and Pint green for the exact functional HEAD.

- [ ] **Step 4: Write `docs/PHASE_M4_AUDIT.md`**

Required sections:

```text
Executive conclusion
Repository/PR gate
Schema and migrations
Domain model
Scoring guarantees
Critical-error semantics
Key-time semantics
Debrief/action-plan semantics
Authorization and tenant isolation
Public UUID boundary
Finalization/immutability/concurrency
Legacy migration
UX
Tests and TDD evidence
Known transitional debt
Explicit deferrals
Final acceptance checklist
Verdict
```

- [ ] **Step 5: Run a fresh final CI on the documentation HEAD**

The audit commit creates a new HEAD. Do not cite a prior run as final evidence. Require a fresh GitHub Actions success on the exact audit HEAD.

- [ ] **Step 6: Reconcile PR**

Confirm:

```text
PR base = main
mergeable = true
unresolved review threads = 0
branch behind main = 0
exact final HEAD = documented
final CI = success
```

Keep PR draft until deliberate integration gate.

- [ ] **Step 7: Stop before merge and report**

Report M4 percentage, exact HEAD, CI run, PR state, changed files, known deferrals and recommended next action. Do not begin M5 until M4 is deliberately integrated into `main`.
