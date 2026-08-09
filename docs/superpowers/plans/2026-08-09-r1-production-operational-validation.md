# R1 Production Release & Operational Validation Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Convert the M1–M9 release-ready repository into verified Vercel + Neon staging evidence using a staging-first, fail-closed promotion model.

**Architecture:** Vercel is the active R1 staging compute/HTTPS/logging layer and Neon is the isolated managed PostgreSQL layer. The existing Laravel/PHP 8.4 Docker contract remains authoritative; provider adaptation is limited to `Dockerfile.vercel` and narrowly-scoped Vercel configuration. Production is intentionally out of scope until Gates 1A–7 are GREEN and provider/plan terms are explicitly re-approved.

**Tech Stack:** Laravel/PHP 8.4, Docker/OCI, PostgreSQL 16, Vercel custom/container runtime, Vercel staging environment, Neon PostgreSQL, GitHub Actions.

## Global Constraints

- Production promotion is blocked until staging passes every applicable R1 promotion gate.
- Vercel Hobby/free resources are staging/validation only; they are not treated as a commercial production SLA.
- Staging never uses the authoritative production database or production PII by default.
- Staging and production do not share `APP_KEY`, `PII_FINGERPRINT_KEY`, DB credentials or provider tokens.
- HTTPS is mandatory for hosted authenticated traffic.
- Runtime PostgreSQL identity must not require schema ownership/DDL for normal requests.
- Migration capability must be controlled and must not remain in steady-state web runtime unnecessarily.
- Exact Git SHA + Vercel deployment identity are required release evidence.
- Any unresolved Critical/High finding or blocking UNKNOWN/FAIL prevents promotion.
- Recovery claims require an executed isolated recovery drill; provider marketing is not evidence.
- M9 Security, Container, SQLite, PostgreSQL and Pint matrix remains mandatory after repository-file changes.
- No provider/deploy/restore/browser/production evidence may be fabricated.

---

### Task 1: Provider Revision — Vercel + Neon

**Files:**
- Modify: `docs/R1_PROVIDER_SCORECARD.md`
- Modify: `docs/superpowers/sdd/r1-progress.md`
- Modify: PR #13 metadata only after repository HEAD is frozen.

**Produces:** Gate 1A evidence replacing AWS as active staging provider while preserving AWS as a future alternative.

- [ ] Record authenticated Vercel workspace and absence of an existing Tactical Scenario Lab project.
- [ ] Confirm current Vercel container/custom-environment/HTTPS/logging capabilities from official Vercel documentation.
- [ ] Confirm Neon integration/provisioning path and staging recovery capability.
- [ ] Score blocking criteria PASS/FAIL/UNKNOWN.
- [ ] Mark Gate 1A GREEN only with zero blocking UNKNOWN for staging.

### Task 2: Provider Container Contract

**Files:**
- Create: `Dockerfile.vercel`
- Create: `tests/Feature/R1VercelContainerContractTest.php`
- Modify only if required: `vercel.json`

**Produces:** a Vercel-specific container entrypoint that preserves the M9 production container contract.

- [ ] Write a failing repository contract test requiring `Dockerfile.vercel` to use PHP 8.4, `pdo_pgsql`, non-root runtime, built frontend assets, `$PORT`, and no `migrate` command in startup.
- [ ] Run the test and confirm RED because `Dockerfile.vercel` does not yet exist.
- [ ] Add the minimal `Dockerfile.vercel` based on the validated root `Dockerfile`.
- [ ] Run the focused test and confirm GREEN.
- [ ] Run the full M9 CI matrix on the exact resulting HEAD.

### Task 3: Vercel Project + Isolated Staging Environment

**Provider actions:**
- Create/link project `tactical-scenario-lab` in authenticated Vercel workspace.
- Create/use custom environment `staging` or an isolated preview equivalent if custom environments are unavailable on the active plan.

**Produces:** real Vercel staging project boundary.

- [ ] Confirm project ID and team ID.
- [ ] Confirm staging environment target.
- [ ] Confirm HTTPS deployment URL is provider-managed.
- [ ] Record exact candidate SHA/deployment ID without recording secrets.

### Task 4: Neon Staging PostgreSQL + Secrets

**Provider actions:**
- Provision a dedicated Neon database for staging through the Vercel marketplace/integration path when available.
- Scope database/application environment variables to staging only.

**Produces:** isolated managed PostgreSQL and external secret injection.

- [ ] Create a staging-only Neon PostgreSQL database.
- [ ] Configure `DB_CONNECTION=pgsql` and provider-issued connection parameters outside Git.
- [ ] Configure staging-only `APP_KEY` and `PII_FINGERPRINT_KEY` outside Git.
- [ ] Ensure no production database or secret is referenced.
- [ ] Record Neon project/database identifiers only when secret-safe.

### Task 5: Controlled Migration + Runtime Least Privilege

**Files:**
- Create if required: `scripts/ops/r1/verify-runtime-role.sh`
- Modify: `docs/R1_STAGING_RUNBOOK.md`

**Produces:** controlled schema migration and restricted steady-state runtime database role.

- [ ] Run `php artisan production:preflight` with staging production-like settings.
- [ ] Apply migrations through a controlled migration session.
- [ ] Create/use a runtime database role limited to required DML/sequence/schema-usage capabilities.
- [ ] Prove runtime DDL denial with `CREATE TABLE r1_should_fail(...)` returning permission denied.
- [ ] Ensure steady-state Vercel runtime uses restricted credentials.

### Task 6: Real Vercel Staging Deployment + Health Admission

**Produces:** a reachable exact-candidate HTTPS staging deployment.

- [ ] Deploy the exact candidate SHA to Vercel staging.
- [ ] Verify `/health/live` returns 200.
- [ ] Verify `/health/ready` returns 200 and database readiness is healthy.
- [ ] Confirm runtime logs are private and do not expose secret values.
- [ ] Record Vercel deployment ID/URL + Git SHA as release evidence.
- [ ] Mark Gate 2 GREEN only when HTTPS + isolated DB + exact identity are all proven.

### Task 7: Recovery Drill

**Files:**
- Create: `docs/R1_RECOVERY_DRILL.md`

**Produces:** executed Neon staging recovery evidence.

- [ ] Record the recovery/time-travel capability available on the active Neon plan.
- [ ] Create an isolated recovery branch/target from an actual staging recovery point.
- [ ] Validate migration state and representative M6 integrity invariants on the recovery target.
- [ ] Record observed recovery behavior and free-tier retention limits without inventing an SLA.
- [ ] Mark Gate 4 GREEN only after the recovery target is validated.

### Task 8: Authenticated Smoke/E2E + Browser QA

**Files:**
- Create if justified: `tests/e2e/r1-staging.spec.*`
- Create: `docs/R1_BROWSER_QA.md`

**Produces:** hosted authenticated/authorization/accessibility evidence.

- [ ] Use disposable synthetic staging tenant/users/data only.
- [ ] Cover login, organization context, dashboard, scenario/version, execution, assessment/debrief, history/report, Knowledge Center, people/access and logout.
- [ ] Verify restricted identity receives expected forbidden behavior.
- [ ] Verify skip link, keyboard/focus, reduced-motion and low-light persistence.
- [ ] Qualify Chromium plus one independent engine before production promotion.

### Task 9: Observability + Failure/Recovery Drill

**Files:**
- Create: `docs/R1_FAILURE_DRILL.md`

**Produces:** operator detection/recovery evidence.

- [ ] Inspect Vercel runtime errors/logs without exporting sensitive payloads to Git.
- [ ] Confirm no secret, private DB URL, `APP_KEY`, `PII_FINGERPRINT_KEY` or raw PII appears in captured evidence.
- [ ] Safely exercise DB-unavailable/readiness failure behavior where provider controls permit.
- [ ] Re-deploy the same exact candidate and confirm stable recovery.
- [ ] Record rollback/roll-forward decision path.

### Task 10: Production Decision + R1 Closeout

**Files:**
- Create: `docs/R1_PRODUCTION_CLOSEOUT.md`

**Produces:** explicit production-provider decision and eventual production release evidence.

- [ ] Require Gates 1A–7 GREEN.
- [ ] Re-evaluate Vercel/Neon plan terms, expected load, commercial/institutional use, recovery needs and cost.
- [ ] Select/approve the production provider/plan explicitly; free staging is not automatically promoted.
- [ ] Keep production secrets/database independent from staging.
- [ ] Require production recovery/PITR policy adequate for accepted risk.
- [ ] Record exact release identity, migration state, health and smoke evidence.
- [ ] Create a semantic tag only if an explicit versioning policy/version decision exists.

## Verification policy

Any repository-file change during R1 requires the inherited M9 matrix on the exact candidate HEAD:

- Security — `composer audit --locked` + `npm audit --audit-level=high`;
- real Docker image build/runtime contract;
- PHPUnit SQLite;
- PHPUnit PostgreSQL 16 including least-privilege, rollback/reapply and concurrency;
- Pint.

Operational gates use observe-not-green → smallest configuration/fix → rerun → secret-safe evidence. Failed checks are never reworded into PASS.
