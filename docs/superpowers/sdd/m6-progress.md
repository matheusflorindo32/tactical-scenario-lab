# M6 — Production & Database Hardening Progress

Branch: `feature/m6-production-hardening`
Base: `main` @ `b96273edcc057ce9645416dacd497f551a40dee2`
Design: `docs/superpowers/specs/2026-08-08-m6-production-database-hardening-design.md`
Plan: `docs/superpowers/plans/2026-08-08-m6-production-database-hardening.md`

## Integration rule

Do not write directly to `main`. M6 integrates only through its own PR after exact-head dual-database CI, forensic audit, branch synchronization, zero unresolved review threads, and merge-commit protection with the expected HEAD SHA.

## Task ledger

| Task | Scope | State | Evidence |
|---|---|---|---|
| 1 | PostgreSQL authoritative CI baseline | IN PROGRESS | PostgreSQL service/CI gate not yet committed |
| 2 | Fail-closed production preflight | PENDING | — |
| 3 | Structural integrity + runtime role | PENDING | — |
| 4 | PostgreSQL database immutability | PENDING | — |
| 5 | Deterministic concurrency hardening | PENDING | — |
| 6 | Liveness/readiness | PENDING | — |
| 7 | Production operations contract | PENDING | — |
| 8 | Forensic audit + exact-head integration gate | PENDING | — |

## Baseline observations

- M5 is integrated and its exact pre-merge HEAD was green on CI #666.
- Laravel already defines a `pgsql` connection, but the existing CI runs PHPUnit only with SQLite and installs only `pdo_sqlite` in that job.
- `.env.example` is intentionally local/developer oriented and defaults to SQLite; production hardening is an M6 concern.
- Existing domain code already provides useful application-level protection: published ScenarioVersion definition guard, finalized ExecutionAssessment guard, append-only ExecutionEvent model hooks, row locks for execution transitions/finalization/injects/action transitions, and unique `(scenario_id, version_number)` versioning.
- M6 will prove and reinforce those contracts instead of replacing them.
