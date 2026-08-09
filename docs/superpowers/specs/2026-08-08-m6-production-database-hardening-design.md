# M6 — Production & Database Hardening Design

Date: 2026-08-08
Branch: `feature/m6-production-hardening`
Base: `main` at merge commit `b96273edcc057ce9645416dacd497f551a40dee2`
Status: Approved design, implementation not started

## 1. Purpose

M6 turns the M1–M5 application from a green institutional product into a production-hardened system whose critical invariants survive real PostgreSQL behavior, concurrent requests, stale application state, and accidental bypasses of Laravel-level guards.

The milestone is intentionally narrow. It does not redesign the UI, build the Wiki, add AI features, introduce a new product surface, or perform the final release process. Those remain M7, M8, and M9 concerns.

## 2. Current baseline

The M5 baseline is green and integrated into `main` via merge commit `b96273edcc057ce9645416dacd497f551a40dee2`.

The repository already defines a Laravel `pgsql` connection, but the current CI path prepares SQLite and installs only `pdo_sqlite`. The example environment also defaults to SQLite. Production fail-closed rules, PostgreSQL compatibility gates, concurrency tests, and database-level immutability constraints are not yet a first-class milestone.

M6 therefore hardens the existing architecture rather than replacing it.

## 3. Architectural decision

Adopt **application-first, database-last-line-of-defense**.

Laravel services, policies/ability checks, validators, domain transitions, and explicit transactions remain the primary place where business intent is expressed. PostgreSQL adds constraints, indexes, locking semantics, and narrowly scoped triggers only where a critical invariant must remain true even if a future code path bypasses an application guard.

This avoids two opposite failures:

1. application-only integrity, where a missed service path can corrupt production state;
2. trigger-heavy hidden domain logic, where important behavior becomes difficult to understand and test.

Database enforcement is reserved for structural invariants and immutability, not general business workflows.

## 4. Database target

### 4.1 Production database

PostgreSQL becomes the supported production database for M6.

The application must pass migrations and the complete supported test contract against PostgreSQL in CI before M6 can integrate.

### 4.2 SQLite role

SQLite remains a fast development/test compatibility target where useful, but no M6 feature may be considered complete solely because it passes on SQLite.

The final CI must include both:

- SQLite regression coverage;
- PostgreSQL authoritative compatibility/integrity coverage.

Database-specific tests may be explicitly skipped on SQLite only when the behavior being proven is PostgreSQL-specific and an equivalent generic application-level test also exists.

### 4.3 SQL portability

Prefer Laravel schema/query APIs. Raw SQL is allowed only when necessary for PostgreSQL constraints, partial indexes, triggers, advisory/concurrency mechanics, or other capabilities that cannot be expressed reliably through the schema builder.

Every PostgreSQL-specific migration must:

- be isolated and documented;
- have an explicit rollback strategy when safe;
- fail clearly on unsupported assumptions;
- avoid destructive transformations unless the previous data has been validated first.

### 4.4 Production PostgreSQL security posture

The deployment contract must document and validate a least-privilege posture:

- the normal application runtime account must not be a PostgreSQL superuser;
- the runtime account should not own the database/schema or have broad DDL privileges;
- schema migration privileges should be used only by the deployment/migration path, preferably through separate credentials where the hosting model supports it;
- production must reject `DB_SSLMODE=disable`;
- managed/provider deployments should use the strongest certificate-verifying mode they support (`verify-full` preferred when trust material and hostname validation are available);
- credentials and connection URLs must never be logged or committed.

Database-owner or migration credentials are not an application bypass feature. Normal web/queue runtime must exercise the same constraints and triggers that M6 claims as defense in depth.

M6 will not introduce PostgreSQL Row-Level Security as a broad tenant mechanism unless the structural audit identifies a narrowly justified case; current application tenant isolation remains authoritative and is reinforced selectively by relational constraints.

## 5. Critical invariants to enforce

M6 will audit the schema and implement database enforcement only for invariants that are both critical and stable.

### 5.1 Published ScenarioVersion immutability

Once a `ScenarioVersion` is published, fields that define that version must not be altered.

Application-level guards remain. PostgreSQL must reject an update that changes immutable definition fields on a published version even through direct SQL or a future unguarded code path executed with the normal runtime database role.

Allowed operational metadata changes, if any exist, must be explicitly enumerated instead of using a broad allow-all exception.

### 5.2 Finalized ExecutionAssessment immutability

Once an assessment is finalized, its scored/evidentiary content must not be rewritten.

The database protection must cover the finalized assessment and dependent content whose mutation would silently change the historical report: rubric evidence, observed critical errors, key times, and structured debrief content where those records are part of the finalized assessment truth.

Action-item lifecycle transitions remain mutable according to the domain state machine because M4 deliberately allows follow-up work after assessment finalization. M6 must not freeze legitimate action-item status progression.

### 5.3 Timeline append-only behavior

Operational timeline/history rows that are defined as append-only must not be updated or deleted by the normal runtime database role after insertion.

Legitimate schema/data maintenance is performed through the controlled migration/maintenance credential path, not through a web-accessible bypass and not by weakening runtime constraints.

### 5.4 Tenant relational integrity

Where the current schema can structurally encode same-organization relationships, M6 should do so with composite unique keys/foreign keys or equivalent constraints.

Do not introduce a large schema rewrite merely to encode every tenant rule in SQL. Cross-tenant protections that are more safely expressed in services remain application-enforced, with focused database reinforcement for the highest-risk relations.

### 5.5 Lifecycle uniqueness and sequencing

Audit existing unique constraints and sequence-generation paths for races. Any identifier/sequence that must be unique under concurrent creation must rely on database-enforced uniqueness and retry-safe application behavior rather than check-then-insert logic alone.

## 6. Concurrency model

M6 must prove behavior under genuinely separate database connections/processes, not only two operations inside one transaction on one connection.

Priority race scenarios:

1. two requests attempting to start the same execution;
2. competing complete/cancel transitions;
3. duplicate finalization attempts for the same assessment;
4. concurrent publication/revision operations on the same scenario/version lineage;
5. concurrent creation where an execution or version sequence must remain unique;
6. duplicate instructor inject delivery;
7. concurrent action-item transitions where stale state could otherwise overwrite a newer state.

The preferred mechanisms are row-level locks (`SELECT ... FOR UPDATE` through Laravel), database uniqueness, explicit transaction boundaries, and bounded retries for serialization/deadlock/unique-race cases where retry is safe.

Advisory locks are permitted only if a race cannot be modeled reliably with row locks/unique constraints and the lock key can be derived deterministically from a stable aggregate identity.

No global application mutexes or Redis dependency will be introduced solely for M6.

## 7. Transaction and retry policy

Critical state transitions must execute in transactions whose locked rows are re-read inside the transaction.

Retry behavior must be narrow:

- retry only known transient PostgreSQL concurrency errors or a known unique-race case;
- use bounded attempts;
- never retry validation, authorization, tenant, or invariant violations;
- preserve idempotency when a client repeats the same operation.

The implementation plan must identify which services require retry handling instead of adding a generic blanket retry around all database work.

## 8. Production fail-closed configuration

M6 introduces a production configuration guard that validates security-critical assumptions during application boot or an explicit production preflight command.

At minimum, production must reject unsafe/missing values for:

- `APP_KEY`;
- `PII_FINGERPRINT_KEY`;
- `APP_DEBUG=true`;
- unsupported production database driver;
- insecure placeholder database configuration where detectable;
- `DB_SSLMODE=disable`;
- session/cookie transport settings that contradict HTTPS deployment expectations;
- any other secret/critical variable already required by existing security code.

The guard must not leak secret values into logs or exception messages.

Local/test environments remain developer-friendly and must not require production-only infrastructure.

## 9. Session, cache, queue, and runtime posture

M6 will document and validate a production-safe default posture but will not add Redis as a hard dependency.

Database-backed session/cache/queue may remain supported if they are correct for the current deployment scale. The implementation should ensure these tables/migrations work on PostgreSQL and that the documented deployment model does not accidentally use local in-memory/file state for shared production behavior.

A future scale milestone may move these concerns to Redis or managed equivalents without changing M6's domain contract.

## 10. Health and readiness

Provide separate semantics for:

- **liveness**: the application process can respond;
- **readiness**: the application can serve traffic safely, including a successful database check and required production configuration state.

Health endpoints must:

- expose no credentials, stack traces, SQL, PII, tenant data, or internal configuration values;
- be cheap enough for infrastructure polling;
- return meaningful HTTP status codes;
- be testable without production-only services.

Readiness may report coarse component labels such as `database: ok|unavailable` but not connection strings or exception text.

## 11. CI design

The final workflow must include PostgreSQL as a GitHub Actions service or equivalent isolated service and run the authoritative PHP test suite against it.

Required final gates on the exact integration HEAD:

1. `composer validate --strict`;
2. clean install of Composer dependencies;
3. `npm ci`;
4. `npm run build`;
5. SQLite `php artisan migrate:fresh --force`;
6. SQLite `php artisan test`;
7. PostgreSQL `php artisan migrate:fresh --force`;
8. PostgreSQL `php artisan test`;
9. `vendor/bin/pint --test`.

The PostgreSQL job must install `pdo_pgsql` and use explicit CI-only credentials.

CI secrets must not be required for ordinary pull requests; the service database should use ephemeral non-production credentials declared in the workflow.

## 12. Test strategy

Implementation follows TDD.

### 12.1 Compatibility tests

Prove all migrations build from zero on PostgreSQL and the existing suite remains green.

### 12.2 Database-invariant tests

Add direct-database mutation tests that intentionally bypass model/service protections and verify PostgreSQL rejects forbidden mutations using the same privilege class expected for normal runtime.

These are required for any invariant claimed to be database-enforced.

### 12.3 Concurrency tests

Use independent connections/processes and synchronization barriers so two operations overlap predictably. A test that merely calls two service methods sequentially is not a concurrency test.

Tests must assert both final state and absence of duplicated side effects such as duplicate timeline events or duplicate inject delivery.

### 12.4 Production-guard tests

Prove unsafe production configuration fails closed without exposing secret values, while valid production-like configuration passes.

### 12.5 Health tests

Prove liveness remains available independently of DB readiness where architecture permits, and readiness becomes unavailable when the database cannot be reached or production preflight fails.

## 13. Observability boundary

M6 may add structured logging around concurrency retries, readiness failures, and rejected production preflight conditions, but it will not add a commercial APM/monitoring vendor, tracing platform, or metrics backend as a hard dependency.

Logs must preserve the existing sanitization posture and never include credentials, PII values, decrypted identifiers, or sensitive free-form content.

## 14. Migration safety

Before any constraint/trigger is enabled against existing data, a precondition query or migration guard must establish that current rows satisfy the invariant.

If legacy data violates a new invariant, M6 must not silently rewrite historical truth. The implementation must either:

- perform a deterministic, documented repair when correctness is unambiguous; or
- fail the migration with an actionable diagnostic and provide an explicit remediation command/process.

## 15. Scope exclusions

M6 explicitly excludes:

- final visual redesign or design-system overhaul (M7);
- Wiki/documentation product surface (M8);
- final release packaging/forensic release process (M9);
- AI agents or generated tactical recommendations;
- TMA Platform integration;
- Kubernetes, Terraform, Helm, or cloud-provider lock-in;
- mandatory Redis;
- broad performance optimization unrelated to measured M6 bottlenecks;
- unrelated refactors.

## 16. Proposed delivery slices

### Task 1 — PostgreSQL baseline

Add PostgreSQL CI service, `pdo_pgsql`, environment wiring, and clean migration/test execution on PostgreSQL. Fix only real portability defects exposed by this gate.

### Task 2 — Production configuration preflight

Implement and test fail-closed production configuration validation plus safe `.env.example`/deployment documentation, including TLS and least-privilege database guidance.

### Task 3 — Structural integrity audit

Inventory high-risk uniqueness, tenant relationships, and sequence paths; add stable constraints/indexes where justified.

### Task 4 — Database immutability

Protect published ScenarioVersion definitions, finalized assessment historical content, and append-only timeline/history records at the database layer with narrow PostgreSQL mechanisms and direct-SQL tests.

### Task 5 — Concurrency hardening

Add true concurrent tests and remediate lifecycle, sequence, publication, finalization, inject, and action transition races using locks/constraints/retry-safe logic.

### Task 6 — Health/readiness

Add privacy-safe liveness/readiness behavior and tests.

### Task 7 — Production operations documentation

Document PostgreSQL/TLS posture, runtime vs migration privilege boundaries, migrations, preflight, deployment sequence, rollback expectations, backup responsibility boundaries, and health checks. No secrets in repository examples.

### Task 8 — Forensic M6 audit and final gate

Audit tenant isolation, immutability, concurrency, migration reversibility/safety, secret handling, database privileges/TLS, CI parity, and scope boundaries. Resolve all Critical/High findings before integration.

## 17. Acceptance criteria

M6 is complete only when all of the following are true:

- a fresh PostgreSQL database can migrate and run the full supported suite in CI;
- SQLite regression coverage remains green;
- every claimed database-level invariant has a direct bypass test proving the database rejects corruption;
- priority races have deterministic concurrent tests and correct final state;
- no duplicate critical side effects occur in tested races;
- production configuration fails closed for defined unsafe states without leaking secrets;
- production contract rejects disabled PostgreSQL TLS and documents least-privilege runtime credentials;
- liveness/readiness endpoints expose no sensitive data;
- no M7/M8/M9 product scope is mixed into the branch;
- final exact-HEAD CI is green;
- branch is synchronized with `main` before integration;
- unresolved review threads are zero;
- final integration uses merge commit with expected HEAD SHA protection.

## 18. Integration rule

M6 must remain isolated on `feature/m6-production-hardening` and integrate only through its own pull request.

Do not write directly to `main`.

Before merge:

1. re-read PR HEAD SHA;
2. confirm exact-HEAD final CI green;
3. confirm branch is zero commits behind `main`;
4. confirm no unresolved review threads;
5. perform forensic M6 audit;
6. merge with a merge commit protected by the expected HEAD SHA.

## 19. Deferred follow-up

After M6 integration, preserve the green production-hardened baseline and open a new isolated branch for M7 — final design and presentation layer. M7 must consume M6 contracts rather than weakening or bypassing them.
