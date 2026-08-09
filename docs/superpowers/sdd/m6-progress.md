# M6 — Production & Database Hardening Progress

Branch: `feature/m6-production-hardening`
Base: `main` @ `b96273edcc057ce9645416dacd497f551a40dee2`
Design: `docs/superpowers/specs/2026-08-08-m6-production-database-hardening-design.md`
Plan: `docs/superpowers/plans/2026-08-08-m6-production-database-hardening.md`
PR: `#8 — M6 — Production & Database Hardening` (draft)

## Integration rule

Do not write directly to `main`. M6 integrates only through its own PR after exact-head dual-database CI, forensic audit, branch synchronization, zero unresolved review threads, and merge-commit protection with the expected HEAD SHA.

## Task ledger

| Task | Scope | State | Evidence |
|---|---|---|---|
| 1 | PostgreSQL authoritative CI baseline | GREEN | CI #669: PostgreSQL 16 migrate:fresh + full PHPUnit success; SQLite full PHPUnit success; Pint success; npm build success |
| 2 | Fail-closed production preflight | GREEN | RED #671; output-contract correction #676; GREEN #677 on SQLite + PostgreSQL 16 + Pint/build |
| 3 | Structural integrity + runtime role | GREEN | RED #680 proved cross-org direct SQL was possible; composite tenant FK + restricted runtime role; CI #691 green on PostgreSQL 16 + SQLite + Pint |
| 4 | PostgreSQL database immutability | GREEN | TRUE RED #692; diagnostic #695 isolated PostgreSQL JSON + stale fixture defects; exact clean HEAD `cd0c61cc189865da0a513dd345f33f5f02f149a4`; CI #703 green on PostgreSQL 16 + SQLite + Pint |
| 5 | Deterministic concurrency hardening | GREEN | Real forked-process barrier harness; 7 race contracts; CI #709 repeated all 7 races 3x, then full PostgreSQL suite, SQLite and Pint all green on `7f6853058b664ed004aa1f8bb3b2477d9847ff0c`; no production changes required |
| 6 | Liveness/readiness | PENDING | — |
| 7 | Production operations contract | PENDING | — |
| 8 | Forensic audit + exact-head integration gate | PENDING | — |

## Task 1 evidence — PostgreSQL baseline

- Workflow commit introducing PostgreSQL job: `f0349f2ed9a362d77b5f61508419d0c0fa1933d0`.
- GitHub Actions run: `#669` (`31286290218`).
- `PHPUnit — PHP 8.4 / PostgreSQL 16`: success.
- PostgreSQL service initialization: success.
- PHP setup with `pdo_pgsql`: success.
- `composer validate --strict`: success.
- Composer install: success.
- `npm ci`: success.
- `npm run build`: success.
- PostgreSQL `php artisan migrate:fresh --force`: success.
- Full `php artisan test` on PostgreSQL: success.
- `PHPUnit — PHP 8.4 / SQLite`: success.
- `Lint (Pint)`: success.
- No application portability patch was required: the M1–M5 schema and current suite already execute successfully on PostgreSQL 16.

## Task 2 evidence — production preflight

- RED contract commit: `53b239f1799a4a0b88cda4bfe6857eb1bf52b985`.
- RED CI `#671`: 263 existing tests passed; only the four new production-preflight tests failed because the command/validator did not exist.
- Existing `config/privacy.php` remained the canonical config-cache-safe source for `PII_FINGERPRINT_KEY`; M6 deliberately did not duplicate the secret in a second config key.
- `ProductionConfigurationValidator` rejects unsafe production state for missing `APP_KEY`, missing fingerprint key, `APP_DEBUG=true`, non-PostgreSQL default DB, `DB_SSLMODE=disable`, and insecure session cookie when required.
- `production:preflight` emits individual sanitized diagnostics and never outputs secret values.
- CI `#676` narrowed to one output-format mismatch with 266 tests passing; command output was made one sanitized line per violation.
- GREEN commit: `55a62707d51c02e05a51cbf7a7ce07ad2cfa8e29`.
- GREEN CI `#677`: SQLite full PHPUnit success; PostgreSQL 16 full PHPUnit success; Pint success; Composer validation/install and npm build success.

## Task 3 evidence — structural integrity + runtime role

- RED CI `#680` proved direct SQL could attach an assessment to an execution from another organization.
- `2026_08_08_160000_add_m6_structural_integrity.php` fails closed if historical mismatches exist and adds a composite `(id, organization_id)` uniqueness/FK path rather than rewriting data.
- CI provisions `tactical_runtime_test` as `NOLOGIN NOSUPERUSER NOCREATEDB NOCREATEROLE NOINHERIT`, with only runtime DML/table and sequence privileges and no table ownership or schema DDL.
- Default privileges preserve the same runtime DML contract when Laravel test migrations recreate tables.
- GREEN structural/style commit: `587856681b815ae1dc21dd9c384f4236598741b4`.
- GREEN CI `#691`: PostgreSQL 16, SQLite and Pint all passed.

## Task 4 evidence — PostgreSQL database immutability

- TRUE RED CI `#692` proved the restricted runtime role could still perform the intended direct-SQL corruption attempts before DB guards existed.
- PostgreSQL trigger guards freeze published scenario definition fields, finalized assessment rows and historical assessment content, while keeping draft content mutable.
- Definition JSON fields are compared as `jsonb`, preserving semantic equality and avoiding PostgreSQL's lack of `json = json`.
- Assessment criteria/evidence, observed critical errors, key times, debrief entries and action-item content are immutable after finalization; legitimate action-item status tracking remains allowed.
- Execution timeline rows reject UPDATE/DELETE at the database layer.
- A stale dashboard fixture was corrected to record observed critical errors before finalization; the guard was not weakened.
- Temporary JUnit/Pint diagnostic instrumentation was removed before the gate closed.
- Exact clean HEAD: `cd0c61cc189865da0a513dd345f33f5f02f149a4`.
- GREEN CI `#703` (`31290814309`): PostgreSQL 16 full PHPUnit success, SQLite full PHPUnit success and Pint success on the same SHA.

## Task 5 evidence — deterministic PostgreSQL concurrency

- `tests/Support/ConcurrentDatabaseOperation.php` uses `pcntl_fork()` workers with independent purged/reconnected database connections and an explicit filesystem barrier; every worker must prove it reached the barrier or the test fails.
- `tests/Feature/PostgresConcurrencyTest.php` is PostgreSQL-only and uses committed fixture state visible across processes; cleanup returns the schema to a clean migrated state after each test.
- Proven races: start/start, complete/cancel, concurrent execution sequencing, concurrent scenario revision sequencing, duplicate assessment finalization, duplicate inject delivery with exactly one timeline event, and stale action-item status transition against a terminal winner.
- CI `#707` proved all seven real races passed but exposed test-fixture contamination after the file; this was corrected in the test harness only, not production behavior.
- CI `#708` proved the cleanup fix: full PostgreSQL, SQLite and Pint were green.
- CI workflow then added a dedicated stability gate that runs the seven PostgreSQL race tests three consecutive times before the full PostgreSQL suite.
- Exact Task 5 HEAD: `7f6853058b664ed004aa1f8bb3b2477d9847ff0c`.
- GREEN CI `#709` (`31291231683`): 21 repeated concurrency executions passed, followed by the complete PostgreSQL suite; SQLite and Pint also passed on the same HEAD.
- No application-service remediation was necessary: the existing row locks, aggregate locks, state re-reads and uniqueness constraints already satisfied all tested race contracts.

## Baseline observations

- M5 is integrated and its exact pre-merge HEAD was green on CI #666.
- Laravel already defined a `pgsql` connection; M6 now makes PostgreSQL 16 an explicit CI gate instead of relying only on SQLite.
- `.env.example` retains developer-friendly SQLite defaults and now documents the PostgreSQL/TLS/session production posture without real secrets.
- Existing domain code already provides useful application-level protection: published ScenarioVersion definition guard, finalized ExecutionAssessment guard, append-only ExecutionEvent model hooks, row locks for execution transitions/finalization/injects/action transitions, and unique `(scenario_id, version_number)` versioning.
- M6 proves and reinforces those contracts instead of replacing them.
