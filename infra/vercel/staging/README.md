# Vercel + Neon Staging Infrastructure Contract

This directory defines the active R1 staging provider contract. It contains no provider token, database password, connection URL, application secret or production identifier.

## Boundary

The staging boundary is:

```text
Vercel project: tactical-scenario-lab
  -> custom staging environment when available
     OR isolated Preview deployment tied to the R1 candidate
  -> Dockerfile.vercel container
  -> provider-managed HTTPS
  -> staging-only environment variables
  -> private runtime logs
  -> dedicated Neon PostgreSQL staging database
```

Production is a separate future boundary and is not inferred from this configuration.

## Release identity

Every qualified candidate is recorded as:

```text
exact Git commit SHA + Vercel deployment ID + Vercel deployment URL
```

A branch name alone is not release identity.

## Container invariant

`Dockerfile.vercel` must preserve the root M9 Docker contract:

- PHP 8.4;
- `pdo_pgsql`;
- deterministic frontend build;
- production Composer dependencies;
- non-root runtime;
- provider `$PORT` listener;
- no SQLite production dependency;
- no migration in web-container startup.

This is enforced by `tests/Feature/R1VercelContainerContractTest.php`.

## Secret invariant

Staging-only values such as `APP_KEY`, `PII_FINGERPRINT_KEY` and PostgreSQL credentials are injected through Vercel environment configuration/Marketplace integration. They are never committed or copied into PR evidence.

## Database invariant

The Neon staging database is not a production database. Schema changes use a controlled migration-capable session. Steady-state web runtime uses a restricted PostgreSQL identity that can perform required DML but cannot create/alter/drop schema objects.

## Gate 2 provider evidence

Repository preparation alone is insufficient. Gate 2 requires observed evidence that:

- Vercel project exists;
- staging/Preview boundary is isolated;
- dedicated Neon staging database exists;
- staging-only secrets are externally configured;
- HTTPS deployment is reachable;
- exact Git SHA/deployment identity is recorded;
- `/health/live` and `/health/ready` are healthy;
- no production credential/database reuse exists.

Execution order is maintained in `docs/R1_STAGING_RUNBOOK.md`.
