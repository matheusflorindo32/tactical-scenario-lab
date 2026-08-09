# R1 Provider Scorecard — Vercel + Neon Staging

Date: 2026-08-09
Status: GATE 1A CANDIDATE
Baseline: M9 `main` merge `1d77b89ef273e97cc53c7901df2d0f405684df45`

## Decision

**Active R1 staging architecture: Vercel + Neon.**

AWS remains a documented future higher-control option, but it is no longer the active staging target. The provider switch is motivated by cost and operational simplicity during validation; it does not weaken the M6–M9 security/release contracts.

```text
GitHub exact candidate SHA
        |
        v
      Vercel
  container deployment
  preview/staging boundary
  managed HTTPS
  deployment identity
  private runtime logs
        |
        v
       Neon
 isolated PostgreSQL staging
 external connection secret
 recovery/branch capability
```

## Verified session evidence

- Authenticated Vercel team: `team_QHEyDZZUIeF7hGokK8amHy4H` (`matheusflorindo32's projects`).
- Existing Vercel projects were enumerated and no `tactical-scenario-lab` project existed at provider re-selection time.
- Current Vercel documentation confirms root-level `Dockerfile.vercel` container images, container services, deployment environments, environment-scoped variables, managed deployment URLs/HTTPS and runtime logs.
- Current Vercel documentation confirms Neon can be provisioned/connected through the Marketplace integration and scoped to selected environments.

The absence of a project is not Gate 2 evidence; it is the clean starting state. Actual project, database and deployment creation remain provider/runtime gates.

## Blocking staging scorecard

Legend: PASS = capability verified from current first-party Vercel documentation or authenticated provider access; FAIL = incompatible; UNKNOWN = not sufficiently verified. A blocking FAIL/UNKNOWN prevents Gate 1A from becoming GREEN.

| Requirement | Vercel + Neon staging result |
|---|---|
| Authenticated provider workspace | PASS — Vercel workspace is connected in this session |
| Laravel container execution path | PASS — `Dockerfile.vercel` / container services supported |
| PostgreSQL provisioning/connectivity path | PASS — Neon Marketplace integration documented |
| HTTPS for hosted application | PASS — Vercel deployment URLs use managed HTTPS |
| External environment-scoped secrets | PASS — Vercel environment variables/integration-generated variables |
| Staging separated from future production | PASS — preview/custom environment model; custom `staging` used if plan permits, isolated preview otherwise |
| Exact deployment identity | PASS — deployment ID/URL plus Git candidate SHA |
| Private runtime diagnostics | PASS — Vercel runtime logs require workspace/project access |
| Runtime DB least privilege can be tested | PASS — PostgreSQL role/grant model remains application-controlled |
| Recovery drill path exists | PASS for staging design — Neon branching/recovery path must still be executed and qualified at Gate 4 |
| Provider credentials kept out of Git | PASS — authenticated integration/environment model; no token committed |
| Free-tier staging is not silently promoted to production | PASS — Gate 8 requires a new explicit provider/plan decision |

**Gate 1A candidate result: 12 PASS / 0 FAIL / 0 blocking UNKNOWN.**

This scorecard proves provider capability and architecture only. It does not prove a real hosted staging deployment, a real Neon database, recovery, browser behavior or production readiness.

## Container contract

The repository uses the M9 container as the source contract and adds only the provider-specific filename `Dockerfile.vercel`.

Required invariants:

- PHP 8.4 runtime;
- `pdo_pgsql` installed;
- deterministic frontend build from `npm ci` + `npm run build`;
- non-root runtime user;
- runtime listens on Vercel `$PORT`;
- no SQLite production dependency;
- no `php artisan migrate --force` in container startup;
- application/database secrets are injected at runtime and never baked into the image.

`tests/Feature/R1VercelContainerContractTest.php` enforces this repository-side contract with RED → GREEN evidence.

## Environment isolation decision

For current free staging:

1. Prefer a Vercel custom environment named `staging` if the active account permits it.
2. If the account does not permit custom environments, use an isolated Preview deployment tied only to the R1 branch/candidate and connect Neon only to that staging/preview scope.
3. Production is not created or inferred from staging.
4. Staging receives unique `APP_KEY`, `PII_FINGERPRINT_KEY` and DB credentials.
5. Production PII/database credentials are never copied to staging.

The fallback to Preview is not a weakening of data isolation; it changes the provider environment label only. Gate 2 still requires exact deployment identity, distinct database/secrets and HTTPS.

## PostgreSQL security contract

Neon staging must preserve the existing M6/M9 database posture:

- controlled migration session performs schema changes;
- steady-state web runtime uses a restricted PostgreSQL role;
- runtime role receives only required DML, schema usage and sequence privileges;
- runtime `CREATE TABLE r1_should_fail(...)` must fail;
- database connection transport must be encrypted using the provider-supported TLS connection settings;
- no connection string/password appears in GitHub evidence.

## Recovery boundary

Gate 4 must execute a real isolated Neon recovery/branch drill from actual staging state. A product feature or documentation page is not recovery evidence. Free-tier retention/limits are recorded as observed constraints, not represented as production-grade PITR or an SLA.

Before Gate 8, the production database plan must be separately evaluated for required backup/PITR retention and commercial/institutional use.

## Current first-party Vercel references

- Container images: https://vercel.com/docs/functions/container-images
- Services/container runtime: https://vercel.com/docs/services
- Deployment environments: https://vercel.com/docs/deployments/environments
- Environment variables: https://vercel.com/docs/environment-variables
- Neon/Marketplace integration CLI: https://vercel.com/docs/cli/integration
- Runtime logs: https://vercel.com/docs/observability/runtime-logs

## Historical AWS decision

The earlier AWS design remains valid as an enterprise/higher-control option and is retained under `infra/aws/staging/README.md`. It is **superseded for the current R1 staging execution** because ECS/Fargate + ALB + RDS + Secrets Manager + CloudWatch introduced unnecessary cost and operational surface for this validation phase.

## Gate 1A acceptance

- [x] Vercel workspace authenticated.
- [x] Existing projects enumerated; no Tactical Scenario Lab project found.
- [x] Container execution path confirmed.
- [x] PostgreSQL/Neon integration path confirmed.
- [x] HTTPS, environment isolation, external secret and logging capabilities confirmed.
- [x] Exact deployment evidence model defined.
- [x] Runtime least-privilege and recovery requirements preserved.
- [x] AWS retained only as future/historical alternative.
- [x] `Dockerfile.vercel` contract introduced via RED → GREEN TDD.
- [ ] Exact-head M9 matrix after all provider-revision repository changes is GREEN.
- [ ] Real Vercel project/Neon database/deployment exist — belongs to Gate 2, not Gate 1A.

Gate 1A becomes GREEN only after the exact-head repository matrix is green.
