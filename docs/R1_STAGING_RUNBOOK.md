# R1 Staging Runbook — Vercel + Neon

Status: REAL STAGING BOOTSTRAP IN PROGRESS

This runbook executes Gate 2 and prepares Gates 3–5. A gate is GREEN only after real provider/runtime evidence exists.

## Safety rules

- Use only the authenticated Vercel and Neon workspaces intended for this project.
- Staging never connects to an authoritative production database and never requires production PII.
- `APP_KEY`, `PII_FINGERPRINT_KEY`, database credentials and provider tokens are never committed or pasted into chat/PR evidence.
- Staging secrets are unique to staging.
- The root M9 `Dockerfile` remains the provider-neutral container reference, but Vercel staging uses `vercel.json` + `api/index.php` + `vercel-php`; do not describe Vercel as executing the Dockerfile.
- No migration command runs from normal HTTP/serverless startup.
- Production remains blocked until Gates 1A–7 are GREEN and Gate 8 explicitly approves the provider/plan.

## Target topology

```text
GitHub exact candidate SHA
          |
          v
VERCEL PROJECT: tactical-scenario-lab
          |
          +-- isolated Preview target for R1
          +-- vercel.json: framework=null, build -> public
          +-- api/index.php -> PHP 8.4 community runtime
          +-- managed HTTPS deployment URL
          +-- staging-only environment variables
          +-- private runtime logs
          |
          v
NEON: tactical-scenario-lab-staging
  isolated PostgreSQL
  controlled migration identity/session
  restricted steady-state runtime role
  recovery/branch drill target
```

## Phase A — Vercel project boundary — OBSERVED

Observed identifiers:

- team: `team_QHEyDZZUIeF7hGokK8amHy4H`;
- project: `prj_GK7BQot3xOYCKYA09AKMffesiSgj` (`tactical-scenario-lab`).

The R1 branch is deploying to isolated Preview targets. Do not use the imported `main` Production deployment as staging evidence.

## Phase B — Vercel runtime adapter — IMPLEMENTED

Repository adapter:

- `vercel.json` disables incorrect Vite SPA detection with `framework: null`;
- `buildCommand` remains `npm run build`;
- static output is `public`, containing Laravel public files and `public/build` assets;
- `api/index.php` is the Laravel serverless entrypoint;
- `vercel-php@0.8.0` provides PHP 8.4;
- Laravel cache/view paths that require writes are redirected to `/tmp`;
- no migration runs in the HTTP bootstrap path.

Regression contract: `tests/Feature/R1VercelContainerContractTest.php`.

The original Vercel import failed because the provider detected Vite and expected `dist`. That diagnosis is closed only by the full-stack adapter above, not by publishing `public/build` as a standalone SPA.

## Phase C — Neon staging database — OBSERVED / PRE-MIGRATION

Observed staging resource:

- project name: `tactical-scenario-lab-staging`;
- project ID: `curly-moon-55089444`;
- database observed: `neondb`;
- provider PostgreSQL observed: 18.4;
- current public application tables: 0.

The empty schema is intentional until the controlled migration step. Do not apply Laravel migrations by copying generated SQL manually unless that migration path has been explicitly audited; the canonical schema source remains Laravel migrations.

## Phase D — staging application secrets — CURRENT BLOCKER

Configure in Vercel Preview/environment settings, never in Git/chat:

- `APP_ENV=production`;
- `APP_DEBUG=false`;
- unique `APP_KEY`;
- unique `PII_FINGERPRINT_KEY`;
- `DB_CONNECTION=pgsql`;
- Neon host/port/database/runtime username/runtime password;
- provider-supported encrypted PostgreSQL connection setting (`DB_SSLMODE` must not be `disable`);
- `SESSION_SECURE_COOKIE=true`;
- `PRODUCTION_REQUIRE_SECURE_SESSION=true`.

Do not print secret values during verification.

Current evidence: the Preview runtime log recorded `MissingAppKeyException`, so this phase is not complete.

## Phase E — controlled migrations and runtime role

1. Use a migration-capable Neon identity/session only for schema deployment.
2. Run the canonical Laravel release checks/migrations from a trusted operator/CI environment that has PHP/Composer and staging migration credentials:

```bash
php artisan production:preflight
php artisan migrate --force
```

3. Provision/use a separate restricted runtime role for Vercel steady-state traffic.
4. Grant only required connection, schema usage, table DML and sequence access.
5. Ensure the runtime role does not own application tables/schema and cannot create/alter/drop schema objects.
6. Prove DDL denial under the runtime role using a harmless disposable attempt after migrations.
7. Replace migration-owner credentials in Vercel with the restricted runtime credentials before health admission.

Do not leave migration-owner credentials in the web runtime.

## Phase F — exact deployment + HTTPS + health admission

A qualifying deployment records:

```text
Git SHA + Vercel deployment ID + HTTPS deployment URL
```

Current corrected-adapter evidence includes a real READY Preview and successful HTTPS routing into PHP/Laravel, but it is not yet admitted because secrets/database readiness are incomplete.

Acceptance checks:

```bash
GET https://STAGING_URL/health/live
GET https://STAGING_URL/health/ready
```

GREEN requires:

- `/health/live` HTTP 200 with no fatal runtime error in provider logs;
- `/health/ready` HTTP 200 with `database=ok`;
- runtime logs contain no secret values;
- exact deployment identity is recorded;
- steady-state DB connection uses the restricted runtime identity.

A browser/proxy status alone is insufficient if runtime logs show a fatal exception.

## Phase G — logs and diagnostics

Inspect Vercel runtime logs through authenticated access after every health/smoke gate. Record only secret-safe summaries.

Specifically reject admission if logs expose or report:

- missing/unsafe production configuration;
- uncaught exceptions;
- raw connection strings/passwords;
- `APP_KEY` or `PII_FINGERPRINT_KEY` values;
- raw PII.

## Gate 2 acceptance evidence

Gate 2 is GREEN only when all are observed:

- [x] Vercel Tactical Scenario Lab project exists;
- [x] isolated R1 Preview target exists;
- [x] dedicated Neon staging PostgreSQL exists;
- [ ] staging-only secrets configured outside Git;
- [x] HTTPS deployment path is real;
- [x] exact deployment identity can be recorded;
- [ ] `/health/live` is clean in both HTTP response and runtime logs;
- [ ] `/health/ready` is healthy against Neon;
- [ ] controlled migration has initialized the staging schema;
- [ ] steady-state runtime role is restricted;
- [x] no production resource reuse has been asserted or observed.

## Gate 3 preparation

Immediately after Gate 2:

1. `production:preflight` succeeds under production-like staging configuration;
2. migrations are current;
3. Vercel uses restricted DB credentials;
4. runtime DDL denial is proven;
5. representative normal DML works;
6. logs show no secret leakage.

## Gate 4 recovery preparation

The active Neon project currently exposes a finite history-retention window. Gate 4 must execute an actual isolated branch/recovery drill and validate application/migration integrity there. Provider capability alone is not GREEN.

## Production boundary

The current Preview/free staging setup is not automatically a production architecture. Gate 8 re-evaluates plan terms, commercial/institutional use, expected load, recovery retention and cost before real production promotion.
