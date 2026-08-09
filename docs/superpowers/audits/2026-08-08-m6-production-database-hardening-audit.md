# M6 — Production & Database Hardening Forensic Audit

Date: 2026-08-08
Repository: `matheusflorindo32/tactical-scenario-lab`
Branch: `feature/m6-production-hardening`
Base: `main`
Pull request: `#8 — M6: Production & Database Hardening`

## Audit objective

This audit verifies that M6 is production-hardening work rather than a feature expansion. The review focuses on database authority, tenant integrity, least privilege, historical immutability, deterministic concurrency, fail-closed production configuration, privacy-safe infrastructure probes, operational recovery boundaries, regression safety and exact-head integration discipline.

The closing rule is strict: prior green runs are supporting evidence, but M6 is not integration-ready until the final branch HEAD itself passes SQLite, PostgreSQL 16, PostgreSQL migration rollback/reapply, repeated concurrency invariants and Pint, while the branch remains synchronized with `main`, the PR is mergeable and there are no unresolved review discussions.

## Scope reviewed

The M6 delta includes:

- PostgreSQL 16 CI as the authoritative production database gate;
- fail-closed production configuration validation and `production:preflight`;
- composite database tenant integrity for execution assessments;
- least-privilege PostgreSQL runtime-role validation;
- PostgreSQL database-level historical immutability;
- deterministic multi-process concurrency tests;
- privacy-safe liveness/readiness endpoints;
- production deploy, TLS, backup/PITR and rollback documentation;
- supporting test fixtures and M6 evidence documentation.

No M7 UI expansion, M8 knowledge/wiki scope, M9 release scope, AI feature, TMA feature or unrelated product feature is part of this milestone.

## Gate evidence before final exact-head verification

### Gate 1 — PostgreSQL baseline

GREEN. CI #669 established PostgreSQL 16 `migrate:fresh`, full PHPUnit, SQLite regression and Pint as simultaneous gates.

### Gate 2 — production preflight

GREEN. RED #671 proved the missing contract. CI #677 proved the implemented validator/command on PostgreSQL, SQLite and Pint. Production rejects missing application/fingerprint keys, debug mode, non-PostgreSQL database, `DB_SSLMODE=disable` and insecure sessions when required. Secret values are not printed.

### Gate 3 — structural integrity and runtime-role foundation

GREEN. RED #680 proved a direct-SQL cross-organization assessment relation was possible. The structural migration now refuses ambiguous historical remediation and enforces the stable composite execution/organization relationship. CI #691 proved the initial restricted-role model.

The forensic pass later strengthened this proof: the final CI role is a real PostgreSQL `LOGIN` identity, not merely a privileged owner session using `SET ROLE`. Tests require both `session_user` and `current_user` to equal `tactical_runtime_test`, require non-superuser/non-createdb/non-createrole state, require zero owned public tables and require DDL creation to fail.

### Gate 4 — PostgreSQL immutability

GREEN. TRUE RED #692 proved direct runtime SQL could bypass application model guards. CI #703 validated PostgreSQL triggers for published scenario definition, finalized assessment/history, action-item content and append-only timeline behavior while preserving draft writes and allowed action status tracking.

The forensic pass found one additional bypass: a published version could be changed from `published` back to `draft` and then mutated in a second statement. A new RED contract was added and the trigger now treats publication as terminal once published by rejecting changes to `publication_status` when the old row is published. The draft-to-published transition remains valid.

### Gate 5 — deterministic concurrency

GREEN. The test harness uses `pcntl_fork()` workers, independent database reconnects and an explicit barrier that every child must reach. It does not silently degrade to sequential execution.

The tested races are:

1. start/start on the same execution;
2. complete/cancel on the same running execution;
3. concurrent execution sequence allocation;
4. concurrent scenario revision sequence allocation;
5. duplicate assessment finalization;
6. duplicate inject delivery with exactly one timeline event;
7. stale action-item transition against a newer/terminal transition.

CI #709 ran all seven race contracts three consecutive times before the full PostgreSQL suite. No production-service changes were required because the existing row/aggregate locks, state re-reads and uniqueness constraints satisfied the contracts.

### Gate 6 — liveness/readiness

GREEN after forensic remediation. RED #711 established the endpoint contract. CI #714 validated minimal responses and privacy-safe failure behavior.

The forensic review found that registering the probes in `routes/web.php` inherited Laravel's web/session middleware. With database-backed sessions and an unavailable database, liveness could fail before the health controller executed. RED #720 reproduced this failure. The probes are now registered outside the `web` middleware group in `bootstrap/app.php`; `/health/live` is independent of database-backed session state and `/health/ready` still returns a coarse 503 when the database/configuration is unavailable.

### Gate 7 — production operations contract

GREEN. `docs/PRODUCTION.md` documents PostgreSQL/TLS posture, migration/runtime identity separation, deploy ordering, preflight, stateless probes, backup/PITR responsibility, restore drills, rollback boundaries and post-deploy verification. It explicitly states that schema rollback cannot recreate lost or corrupted historical truth.

## Forensic RED findings and remediation

### F-01 — published-version downgrade bypass

Severity: High before remediation.

Finding: the initial published-version trigger compared definition columns but did not freeze `publication_status`. Direct SQL could downgrade a published row and then modify its definition.

Remediation: include `publication_status` in the terminal published-row guard. Add a direct runtime-SQL test expecting database rejection.

Validation baseline: CI #725 passed PostgreSQL 16, repeated concurrency, full PostgreSQL suite, SQLite and Pint on remediation HEAD `c332ffc20e9d41918ed1c7b8be51dc7960996b39`.

Status: remediated.

### F-02 — privileged-session runtime-role proof

Severity: High as an assurance gap before remediation.

Finding: the original helper cloned the PostgreSQL owner credentials and used `SET ROLE`. That validates role permissions but not the actual production property that a runtime connection cannot recover owner privileges.

Remediation: CI now creates `tactical_runtime_test` as a real `LOGIN` role with test-only credentials. The runtime helper authenticates with that role. Tests assert both `session_user` and `current_user`, lack of elevated role attributes, lack of table ownership and inability to create tables.

Validation baseline: CI #725 passed the complete PostgreSQL gate after real-login provisioning.

Status: remediated.

### F-03 — liveness coupled to database-backed web session

Severity: High for availability semantics before remediation.

Finding: `/health/live` and `/health/ready` were initially declared in `routes/web.php`, inheriting `StartSession`. RED #720 demonstrated that an unavailable database could cause liveness to return 500 before the health controller ran when sessions were database-backed.

Remediation: register infrastructure probes outside Laravel's `web` middleware group. Add a test that deliberately enables database-backed sessions, makes the database unavailable, and requires liveness 200 plus readiness 503.

Validation baseline: CI #725 passed both database suites and Pint after the routing correction.

Status: remediated.

## Database migration reversibility

The final PostgreSQL CI adds an explicit path-scoped rollback/reapply verification for the two M6 database migrations:

1. rollback immutability guards;
2. rollback structural integrity;
3. reapply structural integrity;
4. reapply immutability guards;
5. continue with repeated concurrency tests and the full PostgreSQL suite.

This avoids assuming the M6 migrations are globally last and verifies their real `down()`/`up()` paths without rolling back unrelated migrations. Passing this step on the final HEAD is a Gate 8 closing condition.

## Security and privacy review

No production secret values are added to repository configuration. CI-only PostgreSQL credentials are explicit test fixtures and are not production credentials. `PII_FINGERPRINT_KEY` remains sourced through the existing privacy configuration. Production health responses are deliberately coarse, and readiness logging does not attach caught exception objects or connection details.

The runtime role is intended to hold DML and sequence privileges only. Migration-owner credentials are documented as deployment-phase credentials and must not remain in web/queue runtime processes.

## Tenant and historical-truth review

The structural migration enforces the stable assessment/execution organization relationship and refuses ambiguous auto-repair. Historical protections live both at the application layer and, for PostgreSQL production, at the database layer. Published scenario truth, finalized assessment truth and execution timeline history cannot be rewritten through the tested runtime path.

## Concurrency review

The race harness proves overlap rather than inferring it. Seven critical races are tested, and the PostgreSQL CI repeats the complete race file three times before running the complete suite. No retry loop was introduced to conceal authorization, validation, tenant or immutable-state failures.

## Operations and recovery review

The documented deployment sequence requires preflight before migrations, migrations under a migration identity, runtime identity separation, then liveness/readiness before traffic admission. Backup/PITR remains an infrastructure responsibility; rollback is described as schema reversal, not data recovery.

## Regression and scope review

The branch retains SQLite regression coverage and Pint. PostgreSQL remains an added production authority rather than a replacement for fast local SQLite tests. The reviewed M6 delta does not intentionally introduce M7/M8/M9 or unrelated product scope.

## Remaining Gate 8 closing conditions

No unresolved Critical/High findings are identified after the remediations above. Final integration readiness still requires all of the following on the final, unchanged branch HEAD:

- PostgreSQL 16 service and `migrate:fresh` success;
- actual runtime-login provisioning success;
- path-scoped rollback/reapply of both M6 migrations success;
- all seven concurrency races repeated three times successfully;
- complete PostgreSQL PHPUnit success;
- complete SQLite PHPUnit success;
- Pint success;
- branch `behind_by = 0` relative to `main`;
- PR mergeable;
- zero unresolved PR review/comment threads;
- final changed-file inventory contains no unintended milestone leakage.

Once those conditions are proven, M6 is technically 8/8 complete and ready for the user's explicit merge decision. The audit itself does not authorize merging the PR.
