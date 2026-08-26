# Vercel + Neon Staging Infrastructure Contract

This directory defines the active R1 staging provider contract. It contains no provider token, database password, connection URL, application secret or production identifier.

## Boundary

The observed staging boundary is:

```text
GitHub R1 candidate SHA
  -> Vercel project: tactical-scenario-lab
  -> isolated Preview deployment
  -> vercel-php community runtime (PHP 8.4)
  -> Laravel serverless entrypoint: api/index.php
  -> static Laravel/Vite assets from public/
  -> provider-managed HTTPS + runtime logs
  -> staging-only environment variables
  -> dedicated Neon PostgreSQL staging project
```

Production is a separate future boundary and is not inferred from this configuration.

## Vercel runtime invariant

Vercel does not execute the repository Dockerfile for this staging path. The provider adapter is `vercel.json` + `api/index.php`:

- `framework: null` prevents the repository from being misclassified as a static Vite SPA;
- `npm run build` generates Laravel Vite assets under `public/build`;
- `public` is the static output boundary;
- `api/index.php` is executed through `vercel-php@0.8.0` (PHP 8.4);
- Laravel writable cache/view paths used by the serverless runtime are redirected to `/tmp`;
- application requests are routed to the Laravel entrypoint while `/build/*` remains static;
- no migration is executed from normal HTTP startup.

The root `Dockerfile` remains the M9 provider-neutral container/release reference and continues to be built in CI. It is not represented as the Vercel runtime.

`tests/Feature/R1VercelContainerContractTest.php` preserves this adapter contract despite its historical filename.

## Release identity

Every qualified candidate is recorded as:

```text
exact Git commit SHA + Vercel deployment ID + Vercel deployment URL
```

A branch name alone is not release identity.

## Secret invariant

Staging-only values such as `APP_KEY`, `PII_FINGERPRINT_KEY` and PostgreSQL credentials are injected through Vercel environment configuration. They are never committed or copied into PR evidence.

A deployment that builds but logs `MissingAppKeyException` is not health-admitted and must remain blocked until provider-side secrets are configured.

## Database invariant

The dedicated Neon project is `tactical-scenario-lab-staging`. At the latest observed provider check it existed independently from Vercel application secrets and contained no application tables yet, which is the expected pre-migration state.

Schema changes use a controlled migration-capable identity/session. Steady-state web runtime must use a restricted PostgreSQL identity that can perform required DML but cannot create, alter or drop schema objects.

The provider currently exposes PostgreSQL 18 for this project while CI uses PostgreSQL 16 as its historical reference. R1 therefore requires the real hosted migration/test gate before treating provider compatibility as proven.

## Gate 2 provider evidence

Repository preparation alone is insufficient. Gate 2 requires observed evidence that:

- Vercel project exists;
- isolated Preview/staging boundary exists;
- dedicated Neon staging PostgreSQL exists;
- staging-only secrets are externally configured;
- exact candidate deployment is reachable over HTTPS;
- exact Git SHA/deployment identity is recorded;
- `/health/live` answers successfully without fatal runtime errors;
- `/health/ready` is HTTP 200 with the Neon database available;
- no production credential/database reuse exists.

Execution order is maintained in `docs/R1_STAGING_RUNBOOK.md`.
