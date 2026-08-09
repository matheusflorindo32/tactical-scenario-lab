# R1 Provider Scorecard — Vercel + Neon Staging

Date: 2026-08-09
Status: GATE 1A REVALIDATION AFTER REAL DEPLOYMENT
Baseline: M9 `main` merge `1d77b89ef273e97cc53c7901df2d0f405684df45`

## Decision

**Active R1 staging architecture: Vercel + Neon.**

AWS remains a documented future higher-control option. The active Vercel adapter is serverless Laravel/PHP, not a Docker/OCI deployment.

```text
GitHub exact candidate SHA
        |
        v
      Vercel
 isolated Preview
 vercel.json + api/index.php
 vercel-php PHP 8.4 runtime
 static public/ assets
 managed HTTPS + deployment identity
 private runtime logs
        |
        v
       Neon
 isolated PostgreSQL staging
 controlled migration identity
 restricted runtime identity required
```

The root M9 `Dockerfile` remains a provider-neutral release/container reference validated by CI. Vercel is not claimed to execute it.

## Observed provider evidence

Vercel:

- authenticated team: `team_QHEyDZZUIeF7hGokK8amHy4H`;
- project exists: `tactical-scenario-lab`, ID `prj_GK7BQot3xOYCKYA09AKMffesiSgj`;
- initial `main` import was misdetected as Vite and failed after a successful frontend build because Vercel expected `dist`;
- the corrected R1 adapter uses `framework: null`, `outputDirectory: public`, `api/index.php` and `vercel-php@0.8.0`;
- Preview deployment for commit `49b0129598188b30b3f88ae43243345a0c35fd7c` reached `READY` as deployment `dpl_DjWdeF2ZmwN7GyfHwEyu7eFwuzhs`;
- `GET /health/live` reached Laravel over HTTPS and returned the minimal JSON response under PHP 8.4.14;
- runtime logs for that probe also recorded `MissingAppKeyException`, proving provider-side application secrets are not yet configured and health admission is not complete.

Neon:

- dedicated project exists: `tactical-scenario-lab-staging`, ID `curly-moon-55089444`;
- current provider PostgreSQL version observed: 18.4;
- pre-migration inspection found zero public application tables;
- this is a staging-only resource and is not represented as production.

No secret value, database password, connection URL or API token is recorded in this document.

## Corrected blocking scorecard

Legend: PASS = directly observed or documented capability sufficient for the current gate; PARTIAL = real progress exists but acceptance is incomplete; BLOCKED = prerequisite is absent.

| Requirement | Current result |
|---|---|
| Authenticated Vercel workspace | PASS |
| Vercel project boundary | PASS — real project exists |
| Laravel/PHP execution path | PASS — Preview executed PHP 8.4.14 and Laravel health route |
| Vite static assets path | PASS — `public`/`public/build` adapter replaces erroneous `dist` expectation |
| HTTPS deployment | PASS — provider-managed HTTPS observed |
| Exact deployment identity | PASS — Git SHA + deployment ID/URL available |
| Private runtime diagnostics | PASS — runtime log inspection available |
| Dedicated Neon staging project | PASS |
| PostgreSQL schema initialized | BLOCKED — migrations intentionally not applied yet |
| Staging application secrets | BLOCKED — APP_KEY and remaining required values not yet configured in Vercel |
| Readiness against Neon | BLOCKED — secrets/migrations/runtime DB identity not complete |
| Runtime DB least privilege | BLOCKED — restricted steady-state role not yet proven |
| Recovery drill | BLOCKED — belongs to Gate 4 |
| Production isolation | PASS at resource-design level; production remains uncreated/unpromoted |

## Runtime adapter contract

Repository-side Vercel adaptation is intentionally narrow:

- `vercel.json` disables static Vite framework detection;
- Vercel runs `npm run build` and serves static files from `public`;
- `api/index.php` starts Laravel through the PHP community runtime;
- writable Laravel serverless paths are redirected to `/tmp`;
- no migrations run during HTTP function startup;
- Node is pinned to 22.x to match CI/release assumptions;
- root `Dockerfile` continues to be built and inspected separately in the M9 CI matrix.

`tests/Feature/R1VercelContainerContractTest.php` now tests this real provider adapter and explicitly rejects the obsolete `Dockerfile.vercel` assumption.

## Environment isolation decision

For R1 staging, an isolated Vercel Preview tied to the R1 branch is acceptable. Production remains a separate future environment and receives independent secrets/database only after the promotion gates.

Staging must receive unique values for at least:

- `APP_KEY`;
- `PII_FINGERPRINT_KEY`;
- PostgreSQL runtime credentials;
- required secure production-like Laravel settings.

Secrets are configured only in the provider UI/integration and are never pasted into chat or committed.

## PostgreSQL security contract

Neon staging must preserve the M6/M9 posture:

- a migration-capable identity/session performs schema changes;
- steady-state HTTP runtime uses a restricted role;
- runtime receives only required DML, schema usage and sequence privileges;
- DDL denial must be proved under the runtime role;
- transport encryption must use the provider-supported TLS posture;
- the real PostgreSQL 18 provider target must pass migrations and hosted readiness even though CI's reference database remains PostgreSQL 16.

## Gate decisions

### Gate 1A

The original provider choice remains **Vercel + Neon**, but the implementation model was corrected from an invalid Docker assumption to the actually observed Vercel PHP runtime model. Gate 1A can be considered GREEN only after the exact corrected repository HEAD passes the inherited M9 matrix.

### Gate 2

Gate 2 is **PARTIAL**. Real Vercel project, isolated Preview, HTTPS, exact deployment identity and real Neon staging now exist. It remains blocked on provider-side application/database secrets, controlled migrations, restricted runtime DB credentials and `/health/ready` admission.

## Production boundary

Free/Preview staging is not automatically a commercial/institutional production architecture. Gate 8 must explicitly re-evaluate provider plan terms, expected load, recovery retention, operational requirements and cost before production promotion.
