# M6 — Production & Database Hardening Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Make the M1–M5 application production-hardened on PostgreSQL, with authoritative PostgreSQL CI, fail-closed production configuration, database-enforced historical invariants, deterministic concurrency protection, and privacy-safe health/readiness checks.

**Architecture:** Keep Laravel services and domain models as the primary expression of business behavior, then add PostgreSQL as a last-line integrity boundary through narrow constraints/triggers and real row-lock semantics. SQLite remains a regression target; PostgreSQL becomes the authoritative production compatibility target. Production runtime credentials are least-privilege and separate conceptually from migration/owner credentials.

**Tech Stack:** PHP 8.4 CI, Laravel 13, PostgreSQL 16, SQLite, PHPUnit 12.5, GitHub Actions, Blade/Tailwind/Vite, Laravel transactions/Eloquent row locks.

## Global Constraints

- Work only on `feature/m6-production-hardening`; never write directly to `main`.
- PostgreSQL is the supported production database for M6; SQLite remains a fast regression target.
- Preserve `ActiveOrganization` tenant derivation and all M1–M5 authorization contracts.
- Use application-first, database-last-line-of-defense; database logic is limited to structural integrity and immutability.
- Runtime PostgreSQL role must be non-superuser and must not rely on owner/DDL privileges.
- Production must reject `DB_SSLMODE=disable`; `verify-full` is preferred where provider trust material supports it.
- No mandatory Redis, Kubernetes, Terraform, Helm, commercial APM, AI features, M7 redesign, M8 Wiki surface, or M9 release work.
- Every claimed database-level invariant requires a direct SQL bypass test on PostgreSQL.
- Every concurrency claim requires independent connections/processes and assertions against duplicate side effects.
- Do not silently rewrite ambiguous historical data to satisfy a new constraint.
- Exact integration HEAD must pass Composer validation/install, npm ci/build, SQLite migrate/tests, PostgreSQL migrate/tests, and Pint.
- Final merge must be a merge commit protected by the expected branch HEAD SHA after branch-sync and review-thread checks.

---

## File Structure

### Existing files to modify

- `.github/workflows/tests.yml` — keep SQLite regression and add authoritative PostgreSQL service/test job.
- `.env.example` — document safe production-relevant database/session values without secrets.
- `config/database.php` — preserve existing `pgsql` connection and make any M6-specific PostgreSQL options explicit only when required.
- `bootstrap/app.php` — register health routes only if route-file registration is required; do not overload bootstrap with validation logic.
- `routes/web.php` — expose privacy-safe `/health/live` and `/health/ready` endpoints through a dedicated controller.
- `app/Services/ScenarioExecutionManager.php` — retain row-lock transitions; add only race remediation proven necessary by tests.
- `app/Services/ScenarioVersionManager.php` — serialize publish/revise lineage operations and retry only known safe transient races if tests prove a gap.
- `app/Services/ExecutionAssessmentManager.php` — preserve assessment row locking and make duplicate finalization deterministic under separate connections.
- `app/Services/ExecutionInjectManager.php` — preserve inject row locking and prove exactly-one event side effect under concurrent delivery.
- `app/Models/ActionItem.php` — preserve explicit transition matrix while proving stale concurrent transitions cannot overwrite terminal state.

### New focused files

- `config/production.php` — config-cache-safe production requirements sourced from environment variables.
- `app/Support/ProductionConfigurationValidator.php` — pure validation of the resolved production configuration; throws sanitized `LogicException` on unsafe state.
- `app/Console/Commands/ProductionPreflightCommand.php` — explicit `production:preflight` deployment gate.
- `app/Http/Controllers/HealthController.php` — liveness/readiness responses without secrets or exception details.
- `database/migrations/2026_08_08_160000_add_m6_structural_integrity.php` — stable PostgreSQL/portable uniqueness and same-organization reinforcement where justified by the structural audit.
- `database/migrations/2026_08_08_161000_add_m6_postgresql_immutability_guards.php` — PostgreSQL functions/triggers for published scenario versions, finalized assessment truth, and append-only execution events; no-op or portable alternative on SQLite where appropriate.
- `tests/Feature/PostgresDatabaseInvariantTest.php` — PostgreSQL-only direct-SQL corruption attempts.
- `tests/Feature/ProductionConfigurationTest.php` — fail-closed production preflight contract.
- `tests/Feature/HealthReadinessTest.php` — privacy-safe liveness/readiness behavior.
- `tests/Feature/PostgresConcurrencyTest.php` — PostgreSQL-only separate-connection race tests.
- `tests/Support/PostgresRuntimeRole.php` — creates/configures an ephemeral non-owner runtime role for dedicated invariant tests when running in CI PostgreSQL.
- `tests/Support/ConcurrentDatabaseOperation.php` — synchronization/process helper for overlapping PostgreSQL operations.
- `docs/PRODUCTION.md` — deployment/database/preflight/health/rollback/backup responsibility contract.
- `docs/PHASE_M6_AUDIT.md` — final forensic findings and evidence.
- `docs/superpowers/sdd/m6-progress.md` — task ledger and CI evidence.

---

### Task 1: PostgreSQL authoritative CI baseline

**Files:**
- Modify: `.github/workflows/tests.yml`
- Create: `docs/superpowers/sdd/m6-progress.md`

**Interfaces:**
- Consumes: current Laravel `pgsql` connection from `config/database.php`.
- Produces: a `phpunit-postgres` GitHub Actions job using PostgreSQL 16 and `pdo_pgsql`; later M6 PostgreSQL-only tests rely on `DB_CONNECTION=pgsql`.

- [ ] **Step 1: Add the PostgreSQL test harness before changing application code**

Add a service-backed job alongside the existing SQLite job:

```yaml
  phpunit-postgres:
    name: PHPUnit — PHP 8.4 / PostgreSQL 16
    runs-on: ubuntu-latest
    services:
      postgres:
        image: postgres:16
        env:
          POSTGRES_DB: tactical_scenario_test
          POSTGRES_USER: postgres
          POSTGRES_PASSWORD: postgres
        ports:
          - 5432:5432
        options: >-
          --health-cmd="pg_isready -U postgres -d tactical_scenario_test"
          --health-interval=5s
          --health-timeout=5s
          --health-retries=10
    env:
      APP_ENV: testing
      DB_CONNECTION: pgsql
      DB_HOST: 127.0.0.1
      DB_PORT: 5432
      DB_DATABASE: tactical_scenario_test
      DB_USERNAME: postgres
      DB_PASSWORD: postgres
      DB_SSLMODE: disable
      PII_FINGERPRINT_KEY: ci-only-m6-fingerprint-key-not-for-production
```

Use PHP 8.4 with `pdo_pgsql`, run `composer validate --strict`, `composer install --no-interaction --prefer-dist --no-progress`, `npm ci`, `npm run build`, prepare `.env`, generate `APP_KEY`, run `php artisan migrate:fresh --force`, then `php artisan test`.

Keep the existing SQLite job and Pint job; do not weaken either.

- [ ] **Step 2: Push the harness and inspect the first PostgreSQL run**

Expected gate: either the full suite is green immediately or the PostgreSQL job produces a concrete portability failure. Do not change product code unless the PostgreSQL run proves a defect.

- [ ] **Step 3: If PostgreSQL is RED, diagnose one root cause at a time**

For each failure, inspect the exact job log, reproduce the violated database assumption from the migration/query, and make the smallest portability correction. Do not replace a real constraint with looser behavior just to make PostgreSQL pass.

- [ ] **Step 4: Verify dual-database baseline**

Expected: SQLite PHPUnit green, PostgreSQL PHPUnit green, Pint green, Vite build green.

- [ ] **Step 5: Record evidence and commit**

Update `docs/superpowers/sdd/m6-progress.md` with the exact workflow run number and any portability defects fixed.

```bash
git add .github/workflows/tests.yml docs/superpowers/sdd/m6-progress.md
git commit -m "ci(m6): validate full suite on PostgreSQL"
```

---

### Task 2: Fail-closed production preflight

**Files:**
- Create: `config/production.php`
- Create: `app/Support/ProductionConfigurationValidator.php`
- Create: `app/Console/Commands/ProductionPreflightCommand.php`
- Modify: `.env.example`
- Test: `tests/Feature/ProductionConfigurationTest.php`

**Interfaces:**
- Produces: `ProductionConfigurationValidator::violations(): array`, `ProductionConfigurationValidator::assertSafe(): void`, Artisan command `production:preflight` returning `SUCCESS` only for a safe production-like configuration.
- Consumes: resolved config values, never raw secret output.

- [ ] **Step 1: Write failing preflight tests**

Test the validator/command against resolved config overrides. Required RED cases:

```php
public function test_production_preflight_rejects_debug_sqlite_disabled_tls_and_missing_keys(): void
{
    config([
        'app.env' => 'production',
        'app.debug' => true,
        'app.key' => '',
        'database.default' => 'sqlite',
        'database.connections.pgsql.sslmode' => 'disable',
        'production.pii_fingerprint_key' => '',
        'session.secure' => false,
    ]);

    $this->artisan('production:preflight')->assertFailed();
}
```

Also prove a safe configuration passes, and prove command output contains field names/reasons but not supplied secret values.

- [ ] **Step 2: Run only the new test and verify RED**

Run: `php artisan test tests/Feature/ProductionConfigurationTest.php`
Expected: FAIL because config/validator/command do not exist.

- [ ] **Step 3: Add config-cache-safe production settings**

`config/production.php`:

```php
<?php

return [
    'pii_fingerprint_key' => env('PII_FINGERPRINT_KEY'),
    'require_secure_session' => env('PRODUCTION_REQUIRE_SECURE_SESSION', true),
];
```

Do not echo these values anywhere.

- [ ] **Step 4: Implement the validator with sanitized messages**

Use exact checks:

```php
final class ProductionConfigurationValidator
{
    public function violations(): array
    {
        if (config('app.env') !== 'production') {
            return [];
        }

        $violations = [];

        if (blank(config('app.key'))) $violations[] = 'APP_KEY is required.';
        if (blank(config('production.pii_fingerprint_key'))) $violations[] = 'PII_FINGERPRINT_KEY is required.';
        if ((bool) config('app.debug')) $violations[] = 'APP_DEBUG must be false.';
        if (config('database.default') !== 'pgsql') $violations[] = 'DB_CONNECTION must be pgsql.';
        if (config('database.connections.pgsql.sslmode') === 'disable') $violations[] = 'DB_SSLMODE must not be disable.';
        if ((bool) config('production.require_secure_session') && ! (bool) config('session.secure')) $violations[] = 'SESSION_SECURE_COOKIE must be true.';

        return $violations;
    }

    public function assertSafe(): void
    {
        $violations = $this->violations();
        if ($violations !== []) {
            throw new LogicException('Unsafe production configuration: '.implode(' ', $violations));
        }
    }
}
```

Use normal braces/formatting in the real implementation; the compact example only fixes the contract.

- [ ] **Step 5: Implement `production:preflight`**

The command calls `assertSafe()`, prints a generic success line on success, catches `LogicException`, prints only its sanitized message, and returns `self::FAILURE` on unsafe configuration.

- [ ] **Step 6: Update `.env.example` without production secrets**

Keep local SQLite developer defaults, but add commented production examples for `DB_CONNECTION=pgsql`, `DB_PORT=5432`, `DB_SSLMODE=verify-full`, `SESSION_SECURE_COOKIE=true`, and the separate `PII_FINGERPRINT_KEY` requirement. Never provide a real secret.

- [ ] **Step 7: Verify RED→GREEN and full regression**

Run the focused test, then the complete suite on both database CI jobs.

- [ ] **Step 8: Commit**

```bash
git add config/production.php app/Support/ProductionConfigurationValidator.php app/Console/Commands/ProductionPreflightCommand.php .env.example tests/Feature/ProductionConfigurationTest.php
git commit -m "feat(m6): fail closed on unsafe production configuration"
```

---

### Task 3: Structural integrity and runtime-role foundation

**Files:**
- Create: `database/migrations/2026_08_08_160000_add_m6_structural_integrity.php`
- Create: `tests/Support/PostgresRuntimeRole.php`
- Create: `tests/Feature/PostgresDatabaseInvariantTest.php`
- Inspect/modify only if required: `database/migrations/2026_08_08_110000_create_scenario_versions_table.php`, `database/migrations/2026_08_08_130000_create_scenario_executions_table.php`, `database/migrations/2026_08_08_140000_create_execution_assessments_table.php`

**Interfaces:**
- Produces: deterministic PostgreSQL runtime test connection `pgsql_runtime` with non-superuser DML privileges; database uniqueness/relational invariants proven by direct SQL.
- Consumes: existing unique `scenario_versions(scenario_id, version_number)` and domain ownership columns.

- [ ] **Step 1: Write PostgreSQL-only direct SQL tests first**

Skip with an explicit reason when `DB::getDriverName() !== 'pgsql'`.

At minimum assert:

```php
$this->assertSame('pgsql', DB::getDriverName());
$this->expectException(QueryException::class);
DB::table('scenario_versions')->insert($duplicateScenarioVersionNumber);
```

Add direct-SQL tests for every new structural invariant selected by the audit; use real IDs from factories/seed setup rather than magic constants.

- [ ] **Step 2: Verify the tests expose only genuine missing database protections**

Run under PostgreSQL CI. Existing unique constraints that already reject corruption are recorded as already-satisfied evidence, not duplicated with a second redundant index.

- [ ] **Step 3: Add only missing stable constraints**

Before adding each constraint, run a precondition query. If violations exist and cannot be repaired unambiguously, throw `RuntimeException` from the migration with a count/table diagnostic but no PII values.

Use composite keys/foreign keys only when both sides already carry the stable organization identity; do not denormalize the entire schema solely for SQL tenant enforcement.

- [ ] **Step 4: Create the ephemeral runtime role helper for PostgreSQL tests**

The helper executes as the CI owner connection:

```sql
DROP ROLE IF EXISTS tactical_runtime_test;
CREATE ROLE tactical_runtime_test LOGIN PASSWORD 'runtime-test-only';
GRANT CONNECT ON DATABASE tactical_scenario_test TO tactical_runtime_test;
GRANT USAGE ON SCHEMA public TO tactical_runtime_test;
GRANT SELECT, INSERT, UPDATE, DELETE ON ALL TABLES IN SCHEMA public TO tactical_runtime_test;
GRANT USAGE, SELECT ON ALL SEQUENCES IN SCHEMA public TO tactical_runtime_test;
```

Then register an in-memory Laravel connection named `pgsql_runtime` using the same host/database and runtime credentials. Assert `rolsuper = false` and that the role does not own application tables.

- [ ] **Step 5: Verify migration rollback where safe and fresh build on both databases**

PostgreSQL must migrate fresh from zero; SQLite must remain green.

- [ ] **Step 6: Commit**

```bash
git add database/migrations/2026_08_08_160000_add_m6_structural_integrity.php tests/Support/PostgresRuntimeRole.php tests/Feature/PostgresDatabaseInvariantTest.php
git commit -m "feat(m6): reinforce structural database integrity"
```

---

### Task 4: PostgreSQL database immutability guards

**Files:**
- Create: `database/migrations/2026_08_08_161000_add_m6_postgresql_immutability_guards.php`
- Modify: `tests/Feature/PostgresDatabaseInvariantTest.php`
- Reference: `app/Models/ScenarioVersion.php`, `app/Models/ExecutionAssessment.php`, `app/Models/ExecutionEvent.php`, `app/Models/ActionItem.php`

**Interfaces:**
- Produces: PostgreSQL trigger functions that reject direct runtime-role mutation of historical truth while preserving valid draft writes and action status transitions.

- [ ] **Step 1: Write failing bypass tests before creating triggers**

Using `DB::connection('pgsql_runtime')`, prove these currently succeed or are not DB-blocked, then assert they must throw `QueryException`:

1. change a `ScenarioVersion::DEFINITION_FIELDS` column after publication;
2. update/delete a finalized `execution_assessments` row;
3. mutate/delete finalized assessment criterion/evidence/critical-error/key-time/debrief truth;
4. update/delete an `execution_events` timeline row.

Also write positive controls proving:

- a draft version definition can still change;
- draft assessment content can still change;
- finalized action item `status`, `status_changed_at`, `status_changed_by_user_id` can change through the existing state-machine path while action text/responsibility/deadline/notes remain immutable.

- [ ] **Step 2: Run PostgreSQL invariant tests and verify RED for the intended bypasses**

Expected: application model guards are bypassed by query builder/direct SQL, demonstrating why database enforcement is required.

- [ ] **Step 3: Add a published-version trigger with explicit immutable columns**

Create a PostgreSQL trigger function equivalent to:

```sql
IF OLD.publication_status = 'published' AND (
    NEW.environment IS DISTINCT FROM OLD.environment OR
    NEW.threat_level IS DISTINCT FROM OLD.threat_level OR
    NEW.mechanism IS DISTINCT FROM OLD.mechanism OR
    NEW.estimated_casualty_count IS DISTINCT FROM OLD.estimated_casualty_count OR
    NEW.resources IS DISTINCT FROM OLD.resources OR
    NEW.learning_objectives IS DISTINCT FROM OLD.learning_objectives OR
    NEW.expected_actions IS DISTINCT FROM OLD.expected_actions OR
    NEW.critical_errors IS DISTINCT FROM OLD.critical_errors
) THEN
    RAISE EXCEPTION 'published scenario version definition is immutable';
END IF;
```

Do not block the draft→published status transition itself.

- [ ] **Step 4: Add finalized-assessment truth guards**

Use narrow trigger functions that consult the parent assessment status. Once finalized, reject UPDATE/DELETE of the assessment and historical content tables. For action items, reject changes to content columns after finalization but allow only status tracking columns to change.

- [ ] **Step 5: Add append-only execution-event guard**

Reject UPDATE/DELETE on `execution_events` for the runtime path. The migration/owner role can still remove schema objects during controlled rollback; there is no web-accessible bypass flag.

- [ ] **Step 6: Make SQLite behavior explicit**

If driver is not PostgreSQL, the migration returns without installing PostgreSQL functions/triggers. Existing Eloquent guards remain the SQLite regression protection. PostgreSQL-only tests skip explicitly on SQLite.

- [ ] **Step 7: Run direct bypass tests, normal M2–M5 suites, migrate fresh, and rollback checks**

Expected: corruption attempts fail at the database; valid domain workflows remain green.

- [ ] **Step 8: Commit**

```bash
git add database/migrations/2026_08_08_161000_add_m6_postgresql_immutability_guards.php tests/Feature/PostgresDatabaseInvariantTest.php
git commit -m "feat(m6): enforce historical immutability in PostgreSQL"
```

---

### Task 5: Deterministic PostgreSQL concurrency hardening

**Files:**
- Create: `tests/Support/ConcurrentDatabaseOperation.php`
- Create: `tests/Feature/PostgresConcurrencyTest.php`
- Modify if RED proves necessary: `app/Services/ScenarioExecutionManager.php`
- Modify if RED proves necessary: `app/Services/ScenarioVersionManager.php`
- Modify if RED proves necessary: `app/Services/ExecutionAssessmentManager.php`
- Modify if RED proves necessary: `app/Services/ExecutionInjectManager.php`
- Modify if RED proves necessary: `app/Models/ActionItem.php`

**Interfaces:**
- Produces: repeatable overlap harness using independent PostgreSQL connections/processes; race-safe state transitions with exactly-once side effects.

- [ ] **Step 1: Build the concurrency test helper without production behavior changes**

The helper must create two independent database connections/processes, synchronize them at an explicit barrier, execute closures/commands, collect exit/result state, and close connections after each test. It must fail rather than silently falling back to sequential execution.

- [ ] **Step 2: Add race tests one invariant at a time**

Required tests:

```text
start/start same execution -> one running state, one rejected operation
complete/cancel same running execution -> exactly one terminal state wins
duplicate assessment finalize -> one finalization, no duplicated side effect
revise/revise same published version -> unique sequential version_number values
create/create execution same version -> unique sequential sequence_number values
deliver/deliver same inject -> one delivered inject and exactly one timeline inject event
action transition from stale open state -> terminal/newer transition is not overwritten
```

Each test asserts final database state and duplicate-side-effect counts.

- [ ] **Step 3: Run the new PostgreSQL test file and classify failures**

A test that passes because operations did not overlap is invalid; confirm the barrier was reached by both workers.

- [ ] **Step 4: Apply the smallest race remediation for each proven failure**

Preferred order:

1. re-read target row under `lockForUpdate()` inside the transaction;
2. rely on existing unique constraints;
3. add bounded retry only for a known SQLSTATE such as serialization/deadlock/unique-race where repeating the operation is safe;
4. use a deterministic PostgreSQL advisory lock only if row locks cannot represent the aggregate race.

Never retry authorization, validation, tenant, or immutable-state exceptions.

- [ ] **Step 5: Run each race repeatedly under PostgreSQL**

Run the focused concurrency test multiple times in CI or loop the relevant test method enough to expose flakiness without turning CI into a load test. Expected: deterministic final state and no duplicate critical events.

- [ ] **Step 6: Run the full PostgreSQL and SQLite suites**

Existing behavior must remain green; no new PostgreSQL dependency may break local SQLite regression.

- [ ] **Step 7: Commit**

```bash
git add tests/Support/ConcurrentDatabaseOperation.php tests/Feature/PostgresConcurrencyTest.php app/Services/ScenarioExecutionManager.php app/Services/ScenarioVersionManager.php app/Services/ExecutionAssessmentManager.php app/Services/ExecutionInjectManager.php app/Models/ActionItem.php
git commit -m "fix(m6): harden critical transitions against database races"
```

Only include production files actually changed by proven RED tests.

---

### Task 6: Privacy-safe liveness and readiness

**Files:**
- Create: `app/Http/Controllers/HealthController.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/HealthReadinessTest.php`

**Interfaces:**
- Produces: `GET /health/live` and `GET /health/ready` JSON contracts.
- Consumes: `ProductionConfigurationValidator` and the default database connection.

- [ ] **Step 1: Write failing endpoint tests**

Expected contracts:

```json
GET /health/live -> 200 {"status":"ok"}
GET /health/ready -> 200 {"status":"ready","database":"ok"}
```

For a deliberately unavailable database connection, readiness returns HTTP 503 with only coarse values such as:

```json
{"status":"unavailable","database":"unavailable"}
```

Assert response text does not contain DB host, username, password, connection URL, SQL, stack trace, PII keys, or underlying exception message.

- [ ] **Step 2: Run the focused test and verify RED**

Run: `php artisan test tests/Feature/HealthReadinessTest.php`
Expected: 404/controller missing.

- [ ] **Step 3: Implement a final `HealthController`**

`live()` performs no database query and returns `200` with `status=ok`.

`ready()`:

1. runs `ProductionConfigurationValidator::assertSafe()` only when `app()->environment('production')`;
2. executes a minimal `DB::select('select 1')`;
3. returns 200 ready on success;
4. catches `Throwable`, logs only a coarse structured readiness failure category, and returns 503 without exception text.

- [ ] **Step 4: Register routes without authentication/tenant data**

Use explicit route names `health.live` and `health.ready`. Do not attach application session/account middleware that would make infrastructure polling dependent on a user login.

- [ ] **Step 5: Verify privacy and regression**

Run focused tests, then dual-database suites.

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/HealthController.php routes/web.php tests/Feature/HealthReadinessTest.php
git commit -m "feat(m6): add privacy-safe health and readiness checks"
```

---

### Task 7: Production operations contract

**Files:**
- Create: `docs/PRODUCTION.md`
- Modify: `.env.example` only if Task 2 documentation needs reconciliation
- Modify: `docs/superpowers/sdd/m6-progress.md`

**Interfaces:**
- Produces: a deployment operator contract that matches the tested code; no runtime behavior change.

- [ ] **Step 1: Document database roles and TLS**

State explicitly:

```text
migration role: schema owner / DDL only during deploy
runtime role: LOGIN, non-superuser, DML + sequence usage, no schema ownership
DB_SSLMODE: verify-full preferred; disable forbidden in production
```

Include provider-specific trust material as an operator responsibility without committing CA/private key files.

- [ ] **Step 2: Document deployment order**

Exact order:

```text
1. install immutable application build
2. configure production environment/secrets outside source control
3. run php artisan production:preflight
4. run php artisan migrate --force with migration credentials
5. warm/cache Laravel configuration/routes where deployment platform uses them
6. switch to runtime database credentials for web/queue processes
7. verify /health/live then /health/ready
8. admit traffic
```

- [ ] **Step 3: Document rollback and backup boundaries**

Explain which M6 migrations are reversible, that trigger removal does not restore already-deleted data, that backups/PITR are infrastructure responsibilities, and that historical truth must not be fabricated during remediation.

- [ ] **Step 4: Document session/cache/queue posture**

Database-backed session/cache/queue are supported for current scale; Redis is optional/future. Shared production workers must not use local-only state for cross-instance behavior.

- [ ] **Step 5: Verify docs against tested names/commands**

Search for stale route/command/env names; ensure no example contains a real credential.

- [ ] **Step 6: Commit**

```bash
git add docs/PRODUCTION.md .env.example docs/superpowers/sdd/m6-progress.md
git commit -m "docs(m6): define production operating contract"
```

---

### Task 8: Forensic M6 audit and exact-head integration gate

**Files:**
- Create: `docs/PHASE_M6_AUDIT.md`
- Modify: `docs/superpowers/sdd/m6-progress.md`
- Modify: `.github/workflows/tests.yml` only if the audit finds a missing verification gate

**Interfaces:**
- Produces: evidence-backed integration verdict; no merge until every Critical/High finding is remediated and reverified.

- [ ] **Step 1: Audit the full M6 delta against `main`**

Inspect:

```text
PostgreSQL fresh migration compatibility
SQLite regression
runtime role privilege level
TLS/fail-closed production checks
secret leakage
DB-level direct bypass resistance
published version immutability
finalized assessment/debrief/evidence immutability
action status exception to immutability
append-only timeline
same-org relational constraints selected by Task 3
sequence uniqueness
real concurrency overlap and duplicate side effects
health/readiness privacy
migration rollback/preconditions
M7/M8/M9 scope drift
```

- [ ] **Step 2: Convert every Critical/High finding into a RED test or verification gate**

Do not close a finding with prose. Add a failing test/check, prove RED, implement the minimal correction, and prove GREEN.

- [ ] **Step 3: Run the exact final verification commands on the final HEAD**

Required CI evidence:

```bash
composer validate --strict
composer install --no-interaction --prefer-dist --no-progress
npm ci
npm run build
# SQLite job
php artisan migrate:fresh --force
php artisan test
# PostgreSQL 16 job
php artisan migrate:fresh --force
php artisan test
vendor/bin/pint --test
```

- [ ] **Step 4: Reconcile branch with `main`**

Confirm merge base is current `main`, branch is zero commits behind, and the exact final HEAD is the SHA that passed the final CI. If `main` advanced, merge/reconcile deliberately and rerun the entire exact-head gate.

- [ ] **Step 5: Review the pull request**

Confirm PR is mergeable, zero unresolved review threads, no unexpected changed files, and no Critical/High audit finding remains.

- [ ] **Step 6: Finalize audit and ledger**

`docs/PHASE_M6_AUDIT.md` must list findings, RED/GREEN evidence, deliberate limitations, exact final SHA, CI run number, and integration verdict.

- [ ] **Step 7: Merge only with expected HEAD SHA protection**

Use GitHub merge method `merge`; pass the exact reread branch HEAD SHA as `expected_head_sha`. After merge, compare `main` with the returned merge commit and require `identical` / zero ahead/behind.

---

## Plan Self-Review

- Spec coverage: Tasks 1–8 cover PostgreSQL, dual DB CI, fail-closed production configuration, runtime least privilege/TLS, structural constraints, DB immutability, real concurrency, readiness/liveness, operations docs, forensic audit, and protected integration.
- Scope: no M7 design, M8 Wiki surface, M9 release packaging, AI, TMA, mandatory Redis, Kubernetes/Terraform/Helm, or unrelated refactor is included.
- Placeholder scan: no TBD/TODO/"implement later" requirements remain.
- Type/interface consistency: `ProductionConfigurationValidator::violations(): array` and `assertSafe(): void` are consumed consistently by preflight and readiness; PostgreSQL-only invariant/concurrency tests consume the `pgsql_runtime`/independent-connection infrastructure defined before them.
- Domain consistency: existing `ScenarioVersion::DEFINITION_FIELDS`, `ScenarioExecutionManager` row locking, `ExecutionAssessmentManager` finalization lock, `ExecutionInjectManager` inject lock, and `ActionItem::transitionTo()` state machine are preserved rather than replaced.
