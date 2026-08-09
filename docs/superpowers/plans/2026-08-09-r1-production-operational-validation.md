# R1 Production Release & Operational Validation Implementation Plan

**Goal:** convert the M1–M9 release-ready repository into verified Vercel + Neon staging evidence using a staging-first, fail-closed promotion model.

**Corrected architecture:** Vercel is the active R1 HTTPS/serverless compute/logging layer using `vercel.json`, `api/index.php` and `vercel-php@0.8.0` (PHP 8.4). Neon is the isolated PostgreSQL layer. The root M9 Dockerfile remains the provider-neutral release/container reference validated in CI; it is not executed by Vercel.

**Tech stack:** Laravel/PHP 8.4, Blade/Vite, Vercel PHP community runtime, Neon PostgreSQL, root Docker reference, GitHub Actions.

## Global constraints

- production promotion is blocked until staging passes the applicable R1 gates;
- Preview/free resources are staging/validation only and are not represented as a production SLA;
- staging never uses production PII/database/secrets;
- secrets remain outside Git/chat;
- migration and steady-state runtime DB identities remain separated;
- runtime PostgreSQL identity must not require schema ownership/DDL;
- exact Git SHA + Vercel deployment identity are required evidence;
- HTTP health output must be corroborated by runtime logs;
- any unresolved Critical/High finding or blocking failure prevents promotion;
- every repository write invalidates earlier exact-head CI as final evidence.

---

### Task 1 — Correct the Vercel execution model

**Files:**
- Create: `vercel.json`
- Create: `api/index.php`
- Modify: `package.json`
- Modify: `tests/Feature/R1VercelContainerContractTest.php`
- Delete: obsolete `Dockerfile.vercel`
- Modify: `.github/workflows/tests.yml`

**Observed RED:** initial Vercel import built Vite successfully but failed because the project was misclassified as a static Vite app expecting `dist`.

**Required implementation:**

- [x] disable Vite framework preset with `framework: null`;
- [x] keep frontend build via `npm run build`;
- [x] publish Laravel static boundary from `public`;
- [x] execute Laravel through `api/index.php` and `vercel-php@0.8.0`;
- [x] redirect serverless writable cache/view paths to `/tmp`;
- [x] pin Node to 22.x;
- [x] remove false Vercel-Docker runtime contract while preserving root Docker CI;
- [x] add repository regression contract requiring the real adapter.

### Task 2 — Revalidate Gate 1A documentation

**Files:**
- `docs/R1_PROVIDER_SCORECARD.md`
- `docs/R1_STAGING_RUNBOOK.md`
- `infra/vercel/staging/README.md`
- R1 spec/plan/ledger
- PR #13 body after evidence is frozen

- [x] remove claims that Vercel executes `Dockerfile.vercel`;
- [x] document the observed PHP 8.4 serverless path;
- [x] preserve root M9 Dockerfile as a separate provider-neutral release contract;
- [ ] require exact corrected HEAD CI GREEN before Gate 1A is final GREEN.

### Task 3 — Real Vercel project + isolated Preview

Observed provider evidence:

- [x] project `tactical-scenario-lab` exists;
- [x] team/project IDs recorded secret-safely;
- [x] R1 branch produces isolated Preview deployments;
- [x] managed HTTPS path observed;
- [x] exact Git SHA + deployment identity available.

No Production deployment is used as R1 staging evidence.

### Task 4 — Neon staging PostgreSQL + external secrets

Observed:

- [x] dedicated project `tactical-scenario-lab-staging` exists;
- [x] current provider PostgreSQL 18.4 observed;
- [x] pre-migration schema confirmed empty;
- [ ] configure Vercel Preview `APP_ENV=production`, `APP_DEBUG=false`;
- [ ] configure unique `APP_KEY` and `PII_FINGERPRINT_KEY` outside Git/chat;
- [ ] configure PostgreSQL runtime connection values outside Git/chat;
- [ ] configure secure session and encrypted DB connection settings;
- [ ] prove no production resource reuse.

### Task 5 — Controlled Laravel migrations + runtime least privilege

Use the canonical Laravel migration code, not ad-hoc reconstruction from generated SQL.

- [ ] establish trusted migration-capable environment/session;
- [ ] run `php artisan production:preflight` with production-like staging settings;
- [ ] run `php artisan migrate --force` against Neon staging;
- [ ] provision/reuse restricted steady-state runtime role;
- [ ] grant only required schema usage, table DML and sequence access;
- [ ] prove runtime DDL denial;
- [ ] configure Vercel to use only restricted runtime credentials after migrations.

Because Neon currently exposes PostgreSQL 18.4 while CI's reference is PostgreSQL 16, successful real migrations/readiness are mandatory provider-version qualification.

### Task 6 — Real deployment + health admission

Existing partial evidence:

- [x] corrected adapter produced a READY Preview;
- [x] HTTPS request reached Laravel under PHP 8.4.14;
- [x] `/health/live` returned the expected JSON body;
- [x] runtime logs were inspected;
- [x] logs exposed the current missing-secret blocker (`MissingAppKeyException`) without exposing a secret value.

Admission remains blocked until:

- [ ] exact final repository candidate CI is GREEN;
- [ ] required provider secrets are configured;
- [ ] controlled migrations are current;
- [ ] Vercel uses restricted runtime DB credentials;
- [ ] `/health/live` is HTTP 200 with no fatal runtime error in logs;
- [ ] `/health/ready` is HTTP 200 with database healthy;
- [ ] exact final deployment ID/URL + Git SHA are recorded.

### Task 7 — Recovery drill

**Create:** `docs/R1_RECOVERY_DRILL.md`

- [ ] record actual history-retention/recovery capability of the active Neon project;
- [ ] create an isolated recovery/branch target from real staging state;
- [ ] validate migration state and representative M6 integrity invariants;
- [ ] record observed limits without inventing SLA/PITR guarantees;
- [ ] mark Gate 4 GREEN only after the real drill succeeds.

### Task 8 — Authenticated smoke/E2E + browser QA

Use disposable synthetic staging data only.

- [ ] login + active organization;
- [ ] dashboard;
- [ ] scenario/version workspace;
- [ ] execution cockpit;
- [ ] assessment/debrief;
- [ ] history/reports;
- [ ] Knowledge Center;
- [ ] people/organization/access for authorized test admin;
- [ ] forbidden paths for restricted identity;
- [ ] logout/session invalidation;
- [ ] keyboard/focus/skip-link/low-light sanity;
- [ ] Chromium plus one independent engine before production promotion.

### Task 9 — Observability + failure/recovery drill

- [ ] inspect Vercel runtime errors/logs without exporting sensitive values;
- [ ] prove no secret/PII leakage;
- [ ] safely exercise database-unavailable/readiness failure;
- [ ] recover by same-candidate redeploy/runtime restoration;
- [ ] document rollback/roll-forward decision path.

### Task 10 — Production decision + closeout

- [ ] require Gates 1A–7 GREEN;
- [ ] explicitly approve provider/plan for commercial/institutional use;
- [ ] keep production secrets/database independent;
- [ ] approve production recovery/PITR posture;
- [ ] record exact production release identity, migrations, health and minimal smoke;
- [ ] create semantic tag only if an explicit version decision exists.

## Verification policy

The exact final R1 repository HEAD must pass the inherited M9 matrix:

- Composer/npm security audits;
- root Docker image build + PostgreSQL extension + non-root + built assets;
- PHPUnit SQLite;
- PHPUnit PostgreSQL reference suite including least privilege, rollback/reapply and concurrency;
- Pint.

Operational gates follow: observe failure → identify root cause → smallest safe change → rerun → capture secret-safe evidence. Failed checks are never reworded into PASS.
