# R1 Staging Runbook — Vercel + Neon

Status: PROVIDER REVISION IN EXECUTION

This runbook executes Gate 2 and prepares Gates 3–5. Documentation is not provider evidence: a gate is GREEN only after the real Vercel deployment, Neon database and runtime checks exist.

## Safety rules

- Use only the authenticated Vercel workspace intended for this project.
- Staging never connects to the authoritative production database and never requires production PII.
- `APP_KEY`, `PII_FINGERPRINT_KEY`, database credentials and provider tokens are never committed, pasted into PR comments or baked into container images.
- Staging application/database secrets are unique to staging.
- The existing M9 Docker contract remains authoritative; `Dockerfile.vercel` is a provider adapter, not a new application runtime design.
- No migration command runs automatically in the web container startup command.
- Production is not created or promoted until Gates 1A–7 are GREEN and Gate 8 explicitly approves the provider/plan.

## Target topology

```text
GitHub exact candidate SHA
          |
          v
VERCEL PROJECT: tactical-scenario-lab
          |
          +-- custom staging environment (preferred)
          |      or isolated Preview target
          |
          +-- Dockerfile.vercel container
          +-- managed HTTPS deployment URL
          +-- staging-only environment variables
          +-- private runtime logs
          |
          v
NEON STAGING
  isolated PostgreSQL
  staging-only credentials
  controlled migration path
  restricted runtime role
  recovery/branch drill target
```

## Phase A — Vercel project boundary

1. Create/link a Vercel project named `tactical-scenario-lab` in the authenticated team.
2. Record the Vercel project ID and team ID as secret-safe provider identifiers.
3. Prefer a custom environment named `staging` if the active plan permits it.
4. If a custom environment is unavailable, use an isolated Preview deployment tied to the R1 branch/candidate; do not treat the provider's Production environment as staging.
5. Confirm the project does not reference any existing production database or application secret.

Gate evidence: project exists, environment boundary is identified, and future production remains logically separate.

## Phase B — container and release identity

1. Use repository `Dockerfile.vercel`.
2. Freeze the candidate Git SHA after the inherited M9 matrix passes.
3. Deploy that exact candidate; mutable branch state alone is insufficient evidence.
4. Record Vercel deployment ID/URL together with the Git SHA.
5. Confirm runtime listens on the provider `$PORT` and does not run migrations at web startup.

Repository contract test: `tests/Feature/R1VercelContainerContractTest.php`.

## Phase C — Neon staging database

1. Provision/connect a dedicated Neon PostgreSQL resource through the Vercel Marketplace/integration path or an equivalent authenticated Neon path.
2. Connect the resource only to staging/Preview scope required by this runbook.
3. Configure Laravel for `pgsql` using provider-issued values outside Git.
4. Ensure all database identifiers/URLs recorded in public evidence are secret-safe/redacted.
5. Never reuse a future production Neon database as the staging database.

## Phase D — staging application secrets

Configure outside Git, scoped only to staging/Preview:

- `APP_ENV=production` for production-like validation behavior;
- `APP_DEBUG=false`;
- unique `APP_KEY`;
- unique `PII_FINGERPRINT_KEY`;
- `DB_CONNECTION=pgsql`;
- Neon connection values supplied by the provider/integration;
- secure session/cookie settings required by `production:preflight`;
- any mail/integration values needed for synthetic staging only.

Do not print secret values during verification.

## Phase E — controlled migrations and runtime role

1. Establish a migration-capable database session only for preflight/schema deployment.
2. Run:

```bash
php artisan production:preflight
php artisan migrate --force
php artisan config:cache
php artisan route:cache
```

3. Create/use a restricted runtime role for steady-state web traffic.
4. Grant only required connection, schema usage, table DML and sequence permissions.
5. Runtime must not own schema objects or retain unnecessary DDL privilege.
6. Prove denial:

```sql
CREATE TABLE r1_should_fail(id bigint);
```

Expected under runtime credentials: permission denied.

7. Configure the hosted application to use the restricted runtime identity after migrations.

## Phase F — HTTPS and health admission

Vercel-managed HTTPS is required for the deployed staging URL.

Checks:

```bash
curl -fsS https://STAGING_URL/health/live
curl -fsS https://STAGING_URL/health/ready
```

Acceptance:

- `/health/live` returns HTTP 200 with the minimal liveness contract;
- `/health/ready` returns HTTP 200 only when database readiness is healthy;
- no secret/private connection information is exposed;
- exact Vercel deployment ID and candidate SHA are recorded;
- no plaintext HTTP endpoint is used to serve authenticated staging traffic.

## Phase G — logs and diagnostics

1. Inspect Vercel runtime logs through authenticated project access.
2. Check representative startup, health, login and controlled error paths.
3. Verify there is no `APP_KEY`, `PII_FINGERPRINT_KEY`, raw DB connection string/password or raw PII in captured evidence.
4. Record only summarized secret-safe observations in GitHub.

## Gate 2 acceptance evidence

Gate 2 is GREEN only when all are observed:

- Vercel Tactical Scenario Lab project exists;
- isolated custom staging or Preview target exists;
- dedicated Neon staging PostgreSQL exists;
- staging-only secrets are configured outside Git;
- valid HTTPS deployment is reachable;
- exact Git SHA + Vercel deployment identity are recorded;
- `/health/live` is healthy;
- `/health/ready` is healthy with Neon available;
- no production secret/database reuse is detected.

A Dockerfile, documentation, provider console listing or CI alone is not enough.

## Gate 3 acceptance preparation

Immediately after Gate 2:

1. `production:preflight` succeeds in production-like staging configuration;
2. migrations are run through the controlled migration path;
3. steady-state runtime uses the restricted DB identity;
4. runtime DDL denial succeeds;
5. normal application DML works;
6. logs show no secret leakage.

## Gate 4 recovery preparation

1. Determine the actual recovery/time-travel/branch capability of the provisioned Neon plan.
2. Create a recovery target separate from the source staging branch/database.
3. Recover from a real staging recovery point.
4. Validate migration state and representative application/integrity invariants on that target.
5. Record retention/plan limitations explicitly.

A documented provider capability without an executed recovery drill is not GREEN.

## Production boundary

The free/Hobby staging setup is not automatically a production architecture. Gate 8 re-evaluates provider plan terms, commercial/institutional use, expected load, recovery retention and cost before any real production promotion.
