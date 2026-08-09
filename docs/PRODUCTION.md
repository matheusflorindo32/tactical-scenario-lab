# Production Operations Contract

This document is the production operating contract for Tactical Scenario Lab. M6 established PostgreSQL/data-integrity hardening; M9 aligns the repository container, CI and release procedure with that production posture. It is not a substitute for provider-specific infrastructure documentation or an incident-response plan.

## 1. Supported production database

Production uses PostgreSQL. SQLite remains a supported local/regression database, not the authoritative production database.

Minimum production posture:

- PostgreSQL 16 is the CI reference version.
- The **migration identity** owns application schema objects and is used only during controlled deploy/migration work.
- The **runtime identity** is a PostgreSQL `LOGIN` role, is not a superuser, does not own application tables or the `public` schema, and has no DDL privileges.
- The runtime identity receives only the table DML and sequence access needed by the application.
- `DB_SSLMODE=disable` is forbidden in production by `php artisan production:preflight`.
- `DB_SSLMODE=verify-full` is preferred when the provider supports CA and hostname verification.
- Provider CA bundles, client certificates, private keys, database passwords and other trust material belong in the deployment platform or secret manager. Do not commit them to this repository.

### Role separation

The exact role names are deployment-specific. The following is an operator template, not a credential-bearing script:

```sql
-- Run as the database/platform administrator and substitute deployment-specific names.
CREATE ROLE <migration_role> LOGIN NOSUPERUSER NOCREATEDB NOCREATEROLE;
CREATE ROLE <runtime_role> LOGIN NOSUPERUSER NOCREATEDB NOCREATEROLE NOINHERIT;

GRANT CONNECT ON DATABASE <database_name> TO <runtime_role>;
GRANT USAGE ON SCHEMA public TO <runtime_role>;
GRANT SELECT, INSERT, UPDATE, DELETE ON ALL TABLES IN SCHEMA public TO <runtime_role>;
GRANT USAGE, SELECT ON ALL SEQUENCES IN SCHEMA public TO <runtime_role>;

ALTER DEFAULT PRIVILEGES FOR ROLE <migration_role> IN SCHEMA public
  GRANT SELECT, INSERT, UPDATE, DELETE ON TABLES TO <runtime_role>;
ALTER DEFAULT PRIVILEGES FOR ROLE <migration_role> IN SCHEMA public
  GRANT USAGE, SELECT ON SEQUENCES TO <runtime_role>;
```

The migration role must be the role whose future object creation/default privileges are being configured. Do not make the runtime role an owner merely to avoid privilege setup.

Operationally verify that the runtime role cannot create tables, alter schema objects, create roles/databases, or bypass the database immutability controls.

## 2. Required production configuration

Secrets and production-specific settings are supplied outside source control. `.env.example` documents names and safe examples only.

Security-critical requirements enforced by `production:preflight` include:

- non-empty `APP_KEY`;
- non-empty `PII_FINGERPRINT_KEY` from the canonical `config/privacy.php` configuration path;
- `APP_DEBUG=false`;
- `DB_CONNECTION=pgsql`;
- `DB_SSLMODE` must not be `disable`;
- secure session cookies when `PRODUCTION_REQUIRE_SECURE_SESSION=true`.

`PII_FINGERPRINT_KEY` is a stable dedicated HMAC secret. Do not rotate it implicitly with `APP_KEY`; a deliberate key-rotation procedure must account for existing fingerprints.

Recommended production posture includes HTTPS-only ingress and:

```text
DB_CONNECTION=pgsql
DB_SSLMODE=verify-full
SESSION_SECURE_COOKIE=true
PRODUCTION_REQUIRE_SECURE_SESSION=true
APP_DEBUG=false
```

Do not copy passwords, private keys, access tokens, CA private material or actual application secrets into tickets, logs, CI output or this document.

## 2.1 M9 reference container contract

The repository `Dockerfile` is a reference application **container** and is tested as part of the M9 release contract.

It must remain aligned with these properties:

- PHP includes `pdo_pgsql`; production does not depend on the SQLite driver or a local SQLite database file.
- Frontend assets are built deterministically in a Node stage with `npm ci` and `npm run build`, then `public/build` is copied into the runtime image.
- PHP production dependencies are installed with optimized autoloading and without dev dependencies.
- The application process runs as the **non-root** user `app`.
- Only required Laravel runtime paths such as `bootstrap/cache` and `storage` are writable by the application user.
- The container startup command serves the application only; it does **not** execute `php artisan migrate --force`.
- Database passwords, TLS material, `APP_KEY`, `PII_FINGERPRINT_KEY` and provider configuration are injected outside the image.

The reference image is the application package/runtime contract, not a complete cloud stack. Production ingress, TLS termination, process supervision and provider networking remain deployment-platform responsibilities. Do not infer that this repository configures a reverse proxy, APM or managed PostgreSQL service.

## 3. Deployment order

Use this order for every production deployment:

1. Build/install the immutable application image/artifact associated with the intended release SHA.
2. Configure production environment and secrets outside source control.
3. Using the migration identity, run `php artisan production:preflight` and stop the deployment on any violation.
4. Run `php artisan migrate --force` using the migration credentials.
5. Warm/cache Laravel configuration and routes where the deployment platform uses those caches (`php artisan config:cache` and `php artisan route:cache`).
6. Remove migration-owner credentials from the web/queue runtime environment and switch processes to the least-privilege runtime identity.
7. Start the application runtime.
8. Verify `GET /health/live`, then `GET /health/ready`.
9. Admit production traffic only after readiness is successful.

Do not run migrations from ordinary web or queue workers. Do not leave migration-owner credentials in the runtime process environment after the migration phase.

For the controlled release decision tree and evidence capture, also follow `docs/RELEASE.md`.

### Probe contract

`GET /health/live`

```json
{"status":"ok"}
```

Liveness does not query the database. Use it to determine whether the application process can answer HTTP.

`GET /health/ready`

Healthy response:

```json
{"status":"ready","database":"ok"}
```

Unavailable response is HTTP 503:

```json
{"status":"unavailable","database":"unavailable"}
```

Readiness validates production configuration when the application is running in production and then performs a minimal database query. The endpoint deliberately does not expose hostnames, usernames, passwords, SQL, SQLSTATE values, stack traces, PII keys or underlying exception text.

Both `/health/live` and `/health/ready` are registered outside Laravel's `web` middleware group. They do not start application sessions and therefore remain infrastructure probes even when production sessions are database-backed and the database is unavailable. Readiness still reports the database outage with HTTP 503; liveness remains independent of it.

The legacy `GET /health` endpoint remains for backward compatibility; infrastructure admission should use `/health/live` and `/health/ready`.

## 4. Migration and data-integrity boundaries

M6 PostgreSQL protections are intentionally narrower than application business logic:

- same-organization relational integrity between executions and assessments;
- published scenario-version definition immutability;
- finalized assessment and historical assessment-content immutability;
- action-item content immutability after finalization while valid status tracking remains allowed;
- append-only execution timeline behavior at the database layer.

The structural-integrity migration performs a precondition check and fails instead of silently rewriting conflicting tenant data. If a precondition fails, stop the deployment and investigate the affected records through an approved remediation process. Never fabricate historical truth to make a migration pass.

## 5. Backup, restore and PITR

Backup and point-in-time recovery are infrastructure responsibilities and must be configured at the PostgreSQL/provider layer.

Before a production migration that changes schema or database guards:

1. Confirm the most recent successful backup or recovery point according to the organization's recovery policy.
2. Confirm the operator knows the target database/cluster and recovery procedure.
3. Prefer a tested restore into an isolated environment for periodic recovery drills.
4. Record the application release/commit and migration state associated with the backup window.
5. Do not treat an untested backup job as proof that recovery is possible.

A restore drill should verify application startup, migration state, tenant relationships, published-version history, finalized assessment history and execution timeline integrity without exposing production secrets or personal data in test logs.

## 6. Rollback policy

Application rollback and schema rollback are separate decisions.

M6 PostgreSQL migrations include `down()` behavior for their own database objects:

- structural-integrity rollback removes the M6 composite assessment/execution foreign key and supporting composite uniqueness constraint;
- immutability rollback removes the M6 triggers and trigger functions.

Those operations do **not** recreate data that was already deleted or altered before protection existed, and removing a guard does not validate or repair historical content. A rollback must never invent replacement history.

Prefer forward remediation when the deployed schema has already accepted production writes that depend on it. Before invoking `php artisan migrate:rollback` in production, confirm that the target application release is schema-compatible and that rollback will not orphan data or re-enable an unsafe write path.

If a deployment fails after migrations but before traffic admission:

1. keep traffic closed;
2. capture the migration/application state without secrets;
3. determine whether a safe application roll-forward is possible;
4. use schema rollback only after compatibility review;
5. restore from the approved backup/PITR boundary when data recovery, not merely schema reversal, is required;
6. rerun `production:preflight`, migrations as applicable, `/health/live` and `/health/ready` before reopening traffic.

`docs/RELEASE.md` gives the operator-facing application rollback / schema rollback / PITR decision tree and requires the release SHA to be recorded.

## 7. Session, cache and queue posture

The current deployment supports database-backed session, cache and queue storage at the intended scale. Redis is optional and is not a production requirement for this release line.

For more than one application instance:

- do not rely on process-local or host-local state for behavior that must be shared across instances;
- use a shared production backing service for session/cache/queue behavior as configured by the deployment;
- run queue workers with the same least-privilege runtime database posture as the web application when they use PostgreSQL;
- restart long-running workers as part of deployments when code/config changes require it.

## 8. Logging and incident diagnostics

Production health failures are intentionally coarse. Readiness logs a category only and does not attach the caught exception. Operators should use protected infrastructure/database observability to investigate connectivity or provider incidents.

Do not increase public health-response detail during an incident. Do not log raw secrets, credentials, PII fingerprint keys, full connection URLs, database passwords or decrypted personal data.

M9 does not fabricate a monitoring vendor integration. A deployment may attach APM/SIEM/log aggregation externally, but the public probe contract remains unchanged.

## 9. Post-deploy verification

After traffic admission, verify at minimum:

- `/health/live` remains HTTP 200;
- `/health/ready` remains HTTP 200;
- normal authenticated access succeeds with an active organization context;
- the runtime database identity is the least-privilege role, not the migration owner;
- no migration credentials remain in runtime workers;
- application logs contain no production secrets;
- queue workers, if enabled, are running the intended release SHA;
- backup/PITR monitoring is healthy according to the infrastructure provider.

A production deployment is not considered complete merely because migration commands returned zero. Readiness, runtime-role separation, release evidence and recovery posture are part of the operating contract.
