# M6 — Production & Database Hardening Progress

Branch de implementação: `feature/m6-production-hardening`
Base original: `main` @ `b96273edcc057ce9645416dacd497f551a40dee2`
Head auditado da implementação: `a78e0abb404f90be6ca8a77f18bc5ec734e248df`
Merge em `main`: `2689e9892e16d031676878178658dba5a2fa1cf7`
Design: `docs/superpowers/specs/2026-08-08-m6-production-database-hardening-design.md`
Plan: `docs/superpowers/plans/2026-08-08-m6-production-database-hardening.md`
Audit: `docs/superpowers/audits/2026-08-08-m6-production-database-hardening-audit.md`
PR: `#8 — M6 — Production & Database Hardening` — MERGED

## Integration rule

M6 foi desenvolvido fora de `main` e integrado somente por PR após dual-database CI, auditoria forense, branch synchronization, zero itens de revisão pendentes e merge protegido pelo SHA esperado. O merge foi autorizado explicitamente pelo usuário e executado exigindo o head auditado `a78e0abb404f90be6ca8a77f18bc5ec734e248df`.

## Task ledger

| Task | Scope | State | Evidence |
|---|---|---|---|
| 1 | PostgreSQL authoritative CI baseline | GREEN | CI #669: PostgreSQL 16 migrate:fresh + full PHPUnit success; SQLite full PHPUnit success; Pint success; npm build success |
| 2 | Fail-closed production preflight | GREEN | RED #671; output-contract correction #676; GREEN #677 on SQLite + PostgreSQL 16 + Pint/build |
| 3 | Structural integrity + real least-privilege runtime login | GREEN | RED #680 proved cross-org direct SQL was possible; composite tenant FK; forensic hardening replaced owner-session/SET ROLE assurance with an actual `tactical_runtime_test` LOGIN; remediation baseline CI #725 fully green |
| 4 | PostgreSQL database immutability | GREEN | TRUE RED #692; CI #703 initial DB-level guards; forensic RED exposed published→draft bypass; trigger now makes published state terminal; remediation baseline CI #725 fully green |
| 5 | Deterministic concurrency hardening | GREEN | Real forked-process barrier harness; 7 race contracts; CI #709 repeated all 7 races 3x, then full PostgreSQL suite, SQLite and Pint all green on `7f6853058b664ed004aa1f8bb3b2477d9847ff0c`; no production service changes required |
| 6 | Stateless liveness/readiness | GREEN | RED #711 established endpoint contract; forensic RED #720 proved web-session coupling; probes moved outside `web` middleware; remediation baseline CI #725 fully green |
| 7 | Production operations contract | GREEN | `docs/PRODUCTION.md` covers role separation, TLS, preflight, stateless probes, PITR/restore and rollback boundaries; CI #716 initial runbook regression green; forensic update documents stateless probe topology |
| 8 | Forensic audit + exact-head integration gate | GREEN | Final implementation HEAD `a78e0abb404f90be6ca8a77f18bc5ec734e248df`; CI #729 fully green; branch 0 behind; no PR discussion/review items; protected merge accepted; `main` at `2689e9892e16d031676878178658dba5a2fa1cf7` |

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
- No application portability patch was required: the M1–M5 schema and suite already executed successfully on PostgreSQL 16.

## Task 2 evidence — production preflight

- RED contract commit: `53b239f1799a4a0b88cda4bfe6857eb1bf52b985`.
- RED CI `#671`: 263 existing tests passed; only the four new production-preflight tests failed because the command/validator did not exist.
- Existing `config/privacy.php` remained the canonical config-cache-safe source for `PII_FINGERPRINT_KEY`; M6 deliberately did not duplicate the secret in a second config key.
- `ProductionConfigurationValidator` rejects unsafe production state for missing `APP_KEY`, missing fingerprint key, `APP_DEBUG=true`, non-PostgreSQL default DB, `DB_SSLMODE=disable`, and insecure session cookie when required.
- `production:preflight` emits individual sanitized diagnostics and never outputs secret values.
- CI `#676` narrowed to one output-format mismatch with 266 tests passing; command output was made one sanitized line per violation.
- GREEN commit: `55a62707d51c02e05a51cbf7a7ce07ad2cfa8e29`.
- GREEN CI `#677`: SQLite full PHPUnit success; PostgreSQL 16 full PHPUnit success; Pint success; Composer validation/install and npm build success.

## Task 3 evidence — structural integrity + runtime identity

- RED CI `#680` proved direct SQL could attach an assessment to an execution from another organization.
- `2026_08_08_160000_add_m6_structural_integrity.php` fails closed if historical mismatches exist and adds a composite `(id, organization_id)` uniqueness/FK path rather than rewriting data.
- Default privileges preserve runtime DML and sequence access when Laravel test migrations recreate tables.
- Initial CI role assurance used `SET ROLE`; the Gate 8 forensic audit classified that as an assurance gap because the session still originated as the owner identity.
- Final CI provisioning creates `tactical_runtime_test` as `LOGIN NOSUPERUSER NOCREATEDB NOCREATEROLE NOINHERIT` with test-only credentials, DML/sequence access, no table ownership and no schema DDL privilege.
- The runtime connection authenticates as that role; tests require both `session_user` and `current_user` to be `tactical_runtime_test`, verify the restricted role attributes/ownership and require `CREATE TABLE` to fail.
- Remediation baseline CI `#725` (`31291994554`) passed PostgreSQL 16, 21 repeated concurrency races, the full PostgreSQL suite, SQLite and Pint on HEAD `c332ffc20e9d41918ed1c7b8be51dc7960996b39`.

## Task 4 evidence — PostgreSQL database immutability

- TRUE RED CI `#692` proved the restricted runtime path could still perform intended direct-SQL corruption attempts before database guards existed.
- PostgreSQL trigger guards freeze published scenario definition fields, finalized assessment rows and historical assessment content while keeping draft content mutable.
- Definition JSON fields are compared as `jsonb`, preserving semantic equality and avoiding PostgreSQL's lack of `json = json`.
- Assessment criteria/evidence, observed critical errors, key times, debrief entries and action-item content are immutable after finalization; legitimate action-item status tracking remains allowed.
- Execution timeline rows reject UPDATE/DELETE at the database layer.
- A stale dashboard fixture was corrected to record observed critical errors before finalization; the guard was not weakened.
- Initial clean guard HEAD `cd0c61cc189865da0a513dd345f33f5f02f149a4` passed CI #703.
- Gate 8 forensic review found a second-order bypass: direct SQL could set a published version back to `draft` and then mutate its definition in a later statement.
- A direct-SQL regression test now requires published→draft to fail; the trigger compares `publication_status` whenever the OLD row is published. Draft→published remains allowed.
- Remediation baseline CI #725 fully passed the strengthened contract on HEAD `c332ffc20e9d41918ed1c7b8be51dc7960996b39`.

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

## Task 6 evidence — privacy-safe stateless liveness/readiness

- RED commit: `9deb86e488db6a6208b795da807a7c0af1e10062`.
- RED CI `#711`: all 267 pre-existing tests passed and exactly the four new health contracts failed with 404 before routes/controller existed.
- `GET /health/live` is public and minimal: HTTP 200 with only `status=ok`; it performs no database check.
- `GET /health/ready` is public and minimal: it runs production preflight only in production, then a minimal `select 1` against the default database.
- Readiness failure returns HTTP 503 with only `status=unavailable` and `database=unavailable`; host, credentials, SQL, SQLSTATE, PII key names and exception messages are not returned.
- Readiness logging is coarse (`readiness_unavailable`) and does not attach the caught exception.
- Initial endpoint implementation passed CI #714.
- Gate 8 forensic RED #720 proved `/health/live` inherited `StartSession` from `routes/web.php`, so database-backed sessions could make liveness fail with 500 during a database outage before the controller ran.
- Probes are now registered outside the `web` middleware group in `bootstrap/app.php`. A regression test sets database-backed sessions plus an unavailable database and requires live=200 while ready=503.
- Remediation baseline CI #725 fully passed the stateless probe contract.

## Task 7 evidence — production operations contract

- `docs/PRODUCTION.md` defines PostgreSQL as the production database and PostgreSQL 16 as the CI reference version while retaining SQLite only for local/regression use.
- Migration and runtime identities are separated; runtime is documented as LOGIN/non-superuser/non-owner/no-DDL with table DML and sequence usage only.
- TLS posture matches code/config: `DB_SSLMODE=disable` is forbidden in production and `verify-full` is preferred when provider trust material is available.
- Deployment order matches the M6 plan: immutable build, external secrets, `production:preflight`, migration credentials, cache/warm step, runtime credentials, liveness, readiness, traffic admission.
- Backup/PITR and restore drills are explicitly infrastructure responsibilities; rollback text states that trigger/constraint removal cannot recreate deleted or corrupted history and forbids fabricating historical truth.
- Database-backed session/cache/queue remain supported; Redis is optional/future rather than an M6 dependency.
- `.env.example` was audited and already matched the documented names/posture, so it was intentionally left unchanged after Task 2.
- Initial runbook HEAD `f9209af3d73a7dc38ca864253ca0718de4db196e` passed CI #716.
- Gate 8 updated the runbook to state explicitly that infrastructure probes are outside web/session middleware and remain meaningful during a database outage.

## Task 8 evidence — forensic audit and protected integration

- Versioned audit: `docs/superpowers/audits/2026-08-08-m6-production-database-hardening-audit.md`.
- RED CI #720 exposed the stateless-liveness defect and accompanied direct contracts for published downgrade and real runtime-login assurance.
- Three findings were remediated without feature-scope expansion: published state terminal at DB layer; actual least-privilege runtime LOGIN; stateless infrastructure probes.
- Remediation baseline HEAD `c332ffc20e9d41918ed1c7b8be51dc7960996b39` passed CI #725 completely.
- Final implementation HEAD `a78e0abb404f90be6ca8a77f18bc5ec734e248df` passed CI #729 (`31292237441`).
- CI #729 proved SQLite full suite, Pint, PostgreSQL 16 `migrate:fresh`, real runtime LOGIN provisioning, path-scoped rollback/reapply of both M6 migrations, all seven concurrency contracts repeated three times, and the complete PostgreSQL suite (303 tests / 1335 assertions).
- Immediately before merge, `feature/m6-production-hardening` was 0 commits behind `main`, PR #8 was mergeable and had zero comments/review items.
- PR #8 was marked ready and its body was updated without changing the implementation HEAD.
- Merge was executed with `expected_head_sha=a78e0abb404f90be6ca8a77f18bc5ec734e248df`; GitHub accepted the protected merge and created `2689e9892e16d031676878178658dba5a2fa1cf7`.
- `main` was then compared against `2689e9892e16d031676878178658dba5a2fa1cf7` and returned `identical`, proving the branch tip of `main` was the merge commit.
- The green GitHub PR merge ref `c0d56cb24189b5705db144a48b881b5537f42b45` and the actual merge commit `2689e9892e16d031676878178658dba5a2fa1cf7` were compared and returned zero changed files. They differ only in merge-commit identity/metadata, not repository content.
- No unresolved Critical/High finding remained at integration.

## Final verification result

M6 is **8/8 GREEN and integrated**.

Final evidence:

- SQLite full PHPUnit: GREEN;
- Pint: GREEN;
- PostgreSQL 16 service + `migrate:fresh`: GREEN;
- real `tactical_runtime_test` LOGIN: GREEN;
- path-scoped rollback/reapply of `160000` and `161000`: GREEN;
- seven concurrency contracts × three runs: GREEN;
- full PostgreSQL PHPUnit: 303 tests / 1335 assertions GREEN;
- final implementation branch: 0 behind `main` before merge;
- PR #8: mergeable before integration, zero discussion/review items;
- changed-file inventory: M6-only, no M7/M8/M9 leakage;
- protected merge: accepted using exact expected head SHA;
- `main`: integrated at `2689e9892e16d031676878178658dba5a2fa1cf7`;
- tested merge tree vs actual merge tree: zero file differences.

This document is the post-integration closeout record for M6.