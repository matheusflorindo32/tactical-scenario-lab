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
| 2 | Fail-closed production preflight | IN PROGRESS | RED test not yet committed |
| 3 | Structural integrity + runtime role | PENDING | — |
| 4 | PostgreSQL database immutability | PENDING | — |
| 5 | Deterministic concurrency hardening | PENDING | — |
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

## Baseline observations

- M5 is integrated and its exact pre-merge HEAD was green on CI #666.
- Laravel already defined a `pgsql` connection; M6 now makes PostgreSQL 16 an explicit CI gate instead of relying only on SQLite.
- `.env.example` remains intentionally local/developer oriented until Task 2 documents production-safe values.
- Existing domain code already provides useful application-level protection: published ScenarioVersion definition guard, finalized ExecutionAssessment guard, append-only ExecutionEvent model hooks, row locks for execution transitions/finalization/injects/action transitions, and unique `(scenario_id, version_number)` versioning.
- M6 will prove and reinforce those contracts instead of replacing them.
