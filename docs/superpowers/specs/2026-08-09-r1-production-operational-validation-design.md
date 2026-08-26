# R1 — Production Release & Operational Validation

Date: 2026-08-09
Status: ACTIVE — PROVIDER MODEL CORRECTED FROM REAL DEPLOYMENT EVIDENCE
Baseline: `main` after M9 protected merge
Branch: `feature/r1-production-operational-validation`
Selected staging stack: **Vercel + Neon**

## Objective

R1 converts the repository-level readiness proven by M1–M9 into real operational evidence through a staging-first, fail-closed release process.

R1 adds no new product domain. It validates hosting, TLS, secrets, PostgreSQL identities, recovery, deployment, health admission, authenticated smoke/E2E, observability and eventual production promotion while preserving the M6–M9 security/integrity contracts.

**Primary rule:** production promotion is blocked until staging has passed every applicable R1 gate. CI simulation is never represented as provider, restore, browser or production evidence.

## Selected provider architecture

The real Vercel import proved that this repository must not be treated as a static Vite SPA and that Vercel staging does not execute `Dockerfile.vercel` as the application runtime.

The corrected architecture is:

```text
GitHub exact candidate SHA
          |
          v
       Vercel
 isolated Preview boundary
 vercel.json + api/index.php
 vercel-php@0.8.0 / PHP 8.4
 Laravel + static public/build assets
 HTTPS / deployment identity / private logs
          |
          v
        Neon
 isolated PostgreSQL staging
 controlled migration identity/session
 restricted steady-state runtime identity
 recovery/branch target
```

The root M9 `Dockerfile` remains the provider-neutral release/container reference and is still built/inspected in CI. It is not represented as the Vercel runtime.

## Why Vercel + Neon remains selected

The provider choice remains valid for R1 staging because the corrected adapter has already demonstrated a real HTTPS Preview executing Laravel under PHP 8.4, while Neon provides an isolated PostgreSQL staging resource. Provider-specific configuration remains narrow and does not rewrite the application domain.

AWS remains documented as a future higher-control option, not the active R1 staging provider.

## Hard boundaries

- staging and production are separate security boundaries;
- staging never uses an authoritative production database or production PII by default;
- staging and production do not share application/database secrets;
- secrets are injected through provider configuration, never committed or pasted into chat;
- migration capability and runtime capability remain separated;
- normal runtime must not require schema-owner/DDL capability;
- HTTPS is mandatory for hosted authenticated traffic;
- exact Git SHA + Vercel deployment identity are required evidence;
- runtime HTTP responses must be corroborated by provider logs before health is marked GREEN;
- unresolved Critical/High findings, failed readiness, failed recovery or least-privilege failures block promotion;
- free-tier staging limitations are not represented as a production SLA;
- no unrelated feature, AI/RAG or clinical/tactical automation belongs to R1.

## Provider evidence already observed

### Vercel

- authenticated team and real project `tactical-scenario-lab` exist;
- initial `main` import built Vite assets successfully but failed because Vercel expected `dist`;
- corrected adapter (`framework: null`, `outputDirectory: public`, `api/index.php`, `vercel-php@0.8.0`) produced a real READY Preview;
- `/health/live` reached Laravel over HTTPS under PHP 8.4.14;
- runtime logs also recorded `MissingAppKeyException`, so the application is not yet health-admitted.

### Neon

- dedicated project `tactical-scenario-lab-staging` exists;
- provider PostgreSQL 18.4 was observed;
- the database is intentionally pre-migration with zero public application tables.

These observations prove bootstrap progress, not Gate 2 completion.

## Gate 1A — Provider & Environment Contract

**Goal:** define and prove a viable Vercel + Neon staging path without weakening M9.

GREEN requires:

- corrected Vercel PHP/serverless execution model documented;
- real provider project boundaries understood;
- Neon isolation path defined;
- no blocking architectural UNKNOWN about HTTPS, Laravel execution, external secrets or PostgreSQL connectivity;
- exact corrected repository HEAD passes the inherited M9 matrix.

The earlier Docker-specific evidence is historical and superseded; it must not be used as proof of Vercel runtime behavior.

## Gate 2 — Real Isolated Staging + TLS

**Goal:** establish a real hosted staging boundary.

Required evidence:

1. Vercel project exists;
2. isolated R1 Preview/staging target exists;
3. dedicated Neon staging database exists;
4. staging-only `APP_KEY`, `PII_FINGERPRINT_KEY` and DB settings are configured outside Git;
5. controlled Laravel migrations initialize the staging schema;
6. steady-state Vercel runtime uses restricted DB credentials;
7. exact candidate SHA/deployment identity is recorded;
8. HTTPS is reachable;
9. `/health/live` is clean in response and runtime logs;
10. `/health/ready` returns HTTP 200 with database readiness healthy.

Current state: **PARTIAL**. Items 1–3, 7 and HTTPS path are real; secrets, migrations, restricted runtime role and clean readiness remain open.

## Gate 3 — Secrets + Database Runtime Least Privilege

**Goal:** prove production-style configuration and database authorization boundaries.

Evidence:

- `APP_ENV=production`, `APP_DEBUG=false`, unique secrets and encrypted PostgreSQL settings are provider-scoped;
- Laravel `production:preflight` succeeds;
- schema changes are applied through a controlled migration-capable session;
- web runtime uses a separate role restricted to required DML/schema usage/sequence access;
- runtime DDL denial succeeds;
- no secret value appears in public or provider evidence.

## PostgreSQL version qualification

CI's historical production reference is PostgreSQL 16. The provisioned Neon project currently exposes PostgreSQL 18.4. R1 therefore requires the actual hosted migration, readiness and smoke gates to qualify the provider version; version compatibility is not inferred solely from CI 16.

## Gate 4 — PostgreSQL Recovery / Restore Drill

GREEN requires an executed isolated Neon branch/recovery drill from real staging state, followed by migration/application/integrity validation. Provider feature availability alone is not recovery evidence.

## Gate 5 — Real Deployment + Health Admission

Required order:

1. freeze exact candidate SHA;
2. require exact-head repository CI GREEN;
3. configure production-like staging secrets;
4. run production preflight through a trusted migration/operator path;
5. apply migrations with migration identity;
6. ensure Vercel runtime has only restricted DB credentials;
7. verify `/health/live` and `/health/ready`;
8. inspect runtime logs for exceptions/secrets;
9. only then admit staging to functional QA.

No migration runs from normal HTTP/serverless startup.

## Gate 6 — Authenticated Smoke/E2E + Browser QA

Use only disposable synthetic staging tenants/users/data. Cover login, active organization, dashboard, scenario/version, execution, assessment/debrief, history/reports, Knowledge Center, people/access, forbidden restricted-user behavior, logout/session behavior and accessibility/low-light sanity.

Before production promotion, qualify one Chromium-family browser plus one independent browser engine.

## Gate 7 — Observability + Failure/Recovery Drill

Prove that provider logs remain private/secret-safe, database-unavailable readiness fails closed, failures are diagnosable without public detail leakage, and redeployment/recovery of the exact candidate returns staging to health.

## Gate 8 — Production Promotion + Release Closeout

Preconditions:

- Gates 1A–7 GREEN;
- exact candidate CI GREEN;
- zero unresolved Critical/High findings;
- production provider/plan explicitly approved for commercial/institutional use;
- production secrets/database independent from staging;
- production recovery/PITR policy adequate for accepted risk.

The current Preview/free staging setup is not automatically promoted to production.

## Repository-change policy

R1 may add only targeted operational/provider artifacts justified by real staging evidence:

- spec/plan/ledger/scorecard/runbooks;
- `vercel.json` and `api/index.php`;
- targeted staging/health/E2E tests;
- narrowly-scoped defects fixed via RED → GREEN.

Every repository-file change reruns the inherited M9 matrix: security audits, root Docker build/runtime checks, SQLite, PostgreSQL reference suite and Pint.

## Evidence model

- **repository:** exact SHA, PR, CI, tests, docs;
- **provider:** Vercel project/deployment/HTTPS/logs, Neon project/recovery;
- **runtime:** production preflight, migrations, DB-role behavior, probes and authenticated smoke;
- **manual:** only where safe provider automation is unavailable.

Tokens, passwords, PII, connection strings and secret values never belong in GitHub evidence.

## Failure policy

R1 is fail-closed. A provider capability failure/unknown, readiness failure, fatal runtime log, least-privilege failure, recovery failure or release-critical smoke failure remains BLOCKED until corrected and re-verified. Acceptance wording is never weakened to make a failure pass.

## Completion definition

R1 is complete only after a staging-qualified immutable candidate has passed the operational gates and been promoted to an explicitly approved production environment with health, minimal smoke, least-privilege, recovery and release-identity evidence intact.
