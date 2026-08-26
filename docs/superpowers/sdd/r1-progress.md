# R1 Progress — Production Release & Operational Validation

Branch: `feature/r1-production-operational-validation`
PR: #13 — draft
Baseline: M9 `main` merge `1d77b89ef273e97cc53c7901df2d0f405684df45`
Spec: `docs/superpowers/specs/2026-08-09-r1-production-operational-validation-design.md`
Plan: `docs/superpowers/plans/2026-08-09-r1-production-operational-validation.md`
Status: REAL VERCEL + NEON STAGING BOOTSTRAP — SECRETS/MIGRATIONS BLOCKED

## Progress model

Current earned R1 progress remains **20%** until Gate 2 is fully GREEN. Real provider bootstrap evidence does not justify partial percentage inflation.

## Gates

- [~] Gate 1A — Vercel + Neon Provider & Environment Contract — architecture corrected; exact corrected-head CI still required.
- [~] Gate 2 — Real Isolated Staging + TLS — real project/Preview/HTTPS/Neon exist; secrets, migrations, runtime role and readiness remain blocked.
- [ ] Gate 3 — Secrets + Database Runtime Least Privilege.
- [ ] Gate 4 — PostgreSQL Recovery / Restore Drill.
- [ ] Gate 5 — Real Deployment + Health Admission.
- [ ] Gate 6 — Authenticated Smoke/E2E + Browser QA.
- [ ] Gate 7 — Observability + Failure/Recovery Drill.
- [ ] Gate 8 — Production Decision + Release Closeout.

## Architecture correction

The earlier R1 branch incorrectly treated Vercel as if it would execute `Dockerfile.vercel` as an OCI application container. The real provider import disproved that assumption: Vercel detected the root Vite build, generated Laravel assets successfully, then failed because it expected the static Vite output directory `dist`.

The corrected provider adapter is:

- `vercel.json` with `framework: null`;
- frontend `npm run build` with static boundary `public`;
- `api/index.php` Laravel serverless entrypoint;
- `vercel-php@0.8.0` for PHP 8.4;
- Laravel writable serverless paths redirected to `/tmp`;
- Node pinned to 22.x;
- obsolete `Dockerfile.vercel` deleted;
- root M9 Dockerfile still built/inspected in CI as a separate provider-neutral release contract.

The regression test retains the historical filename `R1VercelContainerContractTest` but now explicitly rejects the obsolete Dockerfile adapter and requires the real Vercel PHP configuration.

## TDD / debugging evidence

- Initial production import of `main` failed after a successful Vite build with `No Output Directory named "dist"`.
- Root-cause analysis identified that merely pointing Vercel at `public/build` would deploy only frontend assets and not the Laravel backend.
- RED contract commit: `f7b9ad3a349a3ebd9744a3e03786a9ddda5fa478` required the real PHP/serverless adapter while the obsolete Dockerfile still existed and `vercel.json`/`api/index.php` were absent.
- `vercel.json` commit: `9bd8426ce182af97ea78ebb8fb29e57b1900f124`.
- Laravel entrypoint commit: `29628ad8639410c7abf98257c9912beaf1098c39`.
- Node 22 pin commit: `315b5e591869d06570d8051d94f888c2b34c0404`.
- obsolete Vercel Dockerfile deletion: `7cafbfa7c8de6e64687437315c2d69020dd98850`.
- workflow correction preserving root Docker CI only: `49b0129598188b30b3f88ae43243345a0c35fd7c`.

## Real Vercel evidence

Authenticated boundary:

- team ID: `team_QHEyDZZUIeF7hGokK8amHy4H`;
- project ID: `prj_GK7BQot3xOYCKYA09AKMffesiSgj`;
- project name: `tactical-scenario-lab`.

Corrected Preview evidence on commit `49b0129598188b30b3f88ae43243345a0c35fd7c`:

- deployment ID: `dpl_DjWdeF2ZmwN7GyfHwEyu7eFwuzhs`;
- deployment state observed: `READY`;
- branch: `feature/r1-production-operational-validation`;
- `GET /health/live` returned HTTP 200 with `{"status":"ok"}` over Vercel HTTPS;
- response identified PHP 8.4.14;
- runtime logs for the same request recorded `MissingAppKeyException`.

Therefore the original `dist` blocker is closed, but health admission is **not** GREEN: provider-side application secrets remain absent and the HTTP 200 must not override the fatal runtime-log evidence.

## Real Neon evidence

Dedicated staging project observed:

- name: `tactical-scenario-lab-staging`;
- project ID: `curly-moon-55089444`;
- database: `neondb`;
- PostgreSQL provider version: 18.4;
- public application tables at inspection: 0;
- current inspected identity: owner/migration-capable role, not an approved steady-state runtime role.

The empty schema is expected because controlled Laravel migrations have not yet run. No migration is claimed as applied.

CI uses PostgreSQL 16 as the inherited reference. Because the provider target is PostgreSQL 18.4, R1 requires real migration/readiness/smoke qualification before provider compatibility becomes GREEN.

## Current blocking boundary

The remaining Gate 2 path is now narrow and concrete:

1. configure Vercel Preview environment values outside Git/chat, including unique `APP_KEY`, unique `PII_FINGERPRINT_KEY`, production-like safe Laravel settings and Neon connectivity;
2. run `php artisan production:preflight` and Laravel migrations through a trusted migration-capable environment/session;
3. provision/verify a restricted Neon runtime identity;
4. switch Vercel steady-state DB credentials to that restricted identity;
5. redeploy/freeze an exact candidate SHA;
6. verify `/health/live` 200 with clean runtime logs;
7. verify `/health/ready` 200 with database healthy;
8. capture exact deployment identity and exact-head CI.

The current Vercel connector exposes project/deployment/log inspection but no safe environment-variable mutation action. Secrets therefore remain a provider-UI/operator boundary. No token/API key should be sent through chat to bypass it.

## Exact-head policy

Every repository-file write invalidates earlier exact-head CI as final evidence. The branch must be frozen after the documentation/config correction and the complete inherited M9 matrix must succeed on that exact final SHA before Gate 1A or Gate 2 can be promoted.

Required matrix:

- dependency security;
- root release container build/runtime contract;
- SQLite suite;
- PostgreSQL reference suite including least privilege, rollback/reapply and M6 concurrency;
- Pint.

## Process integrity

- `main` has not been modified by this R1 correction;
- PR #13 remains draft;
- no application secret, Neon password, connection string or Vercel token has been committed;
- no production promotion is implied by successful Preview bootstrap.
