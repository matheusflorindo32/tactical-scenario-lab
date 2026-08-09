# R1 — Production Release & Operational Validation

Date: 2026-08-09
Status: DESIGN APPROVED — REVISED WRITTEN SPEC REVIEW
Baseline: `main` after M9 protected merge
Branch: `feature/r1-production-operational-validation`
Selected staging stack: **Vercel + Neon**

## Objective

R1 converts the repository-level readiness proven by M1–M9 into real operational evidence through a **staging-first, fail-closed** release process.

R1 adds no new product domain. It validates hosting, TLS, secrets, PostgreSQL identities, recovery, deployment, health admission, authenticated smoke/E2E, observability and eventual production promotion while preserving all M6–M9 security and integrity contracts.

**Primary rule:** production promotion is blocked until staging has passed every applicable R1 promotion gate.

No CI simulation may be represented as a real provider deployment, restore drill, browser session or production result.

## Selected provider architecture

For the current validation phase, R1 uses:

```text
GitHub exact candidate SHA
          |
          v
       Vercel
 custom staging environment
 OCI/Docker container runtime
 HTTPS / deployment identity
 provider-private logs
          |
          v
        Neon
 isolated PostgreSQL staging
 external connection secret
 recovery/restore capability
```

### Why Vercel + Neon

The stack is selected because it minimizes current operating cost and provider complexity while preserving the essential release contracts:

- Vercel supports container services/OCI images and `Dockerfile.vercel` patterns;
- Vercel supports preview/custom environments and environment-scoped variables;
- Vercel provides hosted deployment URLs and HTTPS;
- Vercel exposes deployment identity and runtime logs;
- Neon integrates with Vercel as managed PostgreSQL;
- staging can be created independently from any future production environment;
- the existing Laravel/PHP 8.4/PostgreSQL container contract remains the baseline rather than rewriting the application for a provider-specific framework runtime.

AWS remains documented as a future higher-control option, not the active R1 staging provider. It is rejected for the current phase because ECS/Fargate + ALB + RDS + Secrets Manager + CloudWatch adds unnecessary cost and operational surface before real user load/SLA requirements justify it.

## Hard boundaries

- staging and production are separate security boundaries;
- staging never uses the authoritative production database;
- production PII is not copied into staging by default;
- staging and production do not share application secrets or DB credentials;
- database credentials are injected through provider environment/secrets, never committed;
- migration capability and runtime application capability remain logically separated;
- runtime must not require schema-owner/DDL capability for normal requests;
- HTTPS is mandatory for hosted authenticated traffic;
- exact deployed Git SHA/deployment identity is required;
- unresolved Critical/High findings block promotion;
- failed restore, readiness, authorization or release-critical smoke checks block promotion;
- free-tier limitations are acceptable for staging but are not represented as a production SLA;
- no provider, restore, TLS, browser or production evidence is fabricated;
- no unrelated feature work, AI/RAG, clinical/tactical automation or broad refactor belongs to R1.

## Provider evidence contract

The active Vercel + Neon stack must prove these blocking capabilities before Gate 2 can become GREEN:

1. validated execution of the Laravel container through Vercel container runtime;
2. isolated Neon PostgreSQL compatible with the existing PostgreSQL 16 contracts;
3. HTTPS on the hosted application endpoint;
4. environment-scoped secret injection outside Git;
5. staging separated from future production;
6. exact Vercel deployment identity tied to the candidate SHA;
7. private runtime logs/diagnostics;
8. documented Neon recovery/restore path sufficient for the staging drill;
9. runtime database permissions restricted from unnecessary DDL;
10. no provider credentials committed to the repository.

Each item is recorded as PASS, FAIL or UNKNOWN. FAIL/UNKNOWN blocks the applicable gate.

## Environment model

```text
exact candidate SHA
       |
       v
VERCEL PROJECT: tactical-scenario-lab
       |
       +-- staging environment
       |      |
       |      +-- staging-only APP_KEY / secrets
       |      +-- container deployment
       |      +-- HTTPS endpoint
       |      +-- private logs
       |      |
       |      v
       |   NEON STAGING
       |   isolated PostgreSQL
       |   disposable test data
       |   recovery drill target
       |
       +-- production environment (NOT required yet)
              separate secrets/database
              only after Gates 1–7
```

Production does not need to exist before staging Gates 1–7 are green.

## Gate 1A — Provider Re-selection & Environment Contract

**Goal:** replace the earlier AWS staging design with the approved zero/near-zero-cost Vercel + Neon design without weakening any M9 release contract.

Evidence:

- Vercel workspace authenticated;
- absence/existence of an existing Tactical Scenario Lab Vercel project recorded;
- official container/custom-environment capability confirmed;
- Neon integration path documented;
- AWS retained only as historical/future alternative;
- no production or paid resource is created implicitly.

GREEN requires a written, internally consistent provider design and no blocking UNKNOWN about container execution, staging isolation, HTTPS, secret injection or PostgreSQL connectivity.

## Gate 2 — Real Isolated Staging + TLS

**Goal:** create a real hosted staging environment isolated from production.

Required sequence:

1. create or link the Tactical Scenario Lab Vercel project;
2. introduce only the provider configuration required for container execution (for example `Dockerfile.vercel` and narrowly-scoped Vercel config if required);
3. create/use a custom `staging` environment or equivalent isolated preview target;
4. provision/link a Neon staging database;
5. configure staging-only application/database environment values outside Git;
6. deploy the exact candidate SHA;
7. verify HTTPS and deployment identity;
8. verify that no production secret/database is referenced.

GREEN requires a reachable HTTPS staging deployment, isolated Neon database and exact release identity evidence.

## Gate 3 — Secrets + Database Runtime Least Privilege

**Goal:** prove production-style authorization boundaries in staging.

Evidence:

- staging secrets injected through Vercel environment configuration/integration;
- no secret material committed or echoed to public logs;
- Laravel `production:preflight` succeeds with staging production-like settings;
- schema/migrations are applied through a controlled migration step;
- the web runtime connects using a role that supports normal application DML but cannot perform unnecessary schema DDL;
- DDL-denial test succeeds under runtime credentials.

If Neon/provider limitations make two persistent identities impractical on the free staging tier, a controlled migration session followed by a restricted runtime role is required; the design must not silently fall back to schema-owner credentials for normal web runtime.

## Gate 4 — PostgreSQL Recovery / Restore Drill

**Goal:** prove recoverability instead of merely proving that a provider advertises backups.

Evidence:

- Neon staging connection/TLS posture;
- recovery point/history capability visible for the selected plan;
- restore/branch/recovery performed into a separate isolated target where the provider supports it;
- migration state and application/integrity validation on the recovery target;
- any free-tier retention limitation recorded explicitly.

GREEN requires an actual isolated recovery drill. A documentation claim without executing recovery is not GREEN.

Before eventual production promotion, the selected paid/free production database must provide an acceptable PITR/backup policy for the real risk level. A staging free-tier recovery window is not automatically considered production-grade PITR.

## Gate 5 — Real Deployment + Health Admission

**Goal:** prove the real release sequence in Vercel staging.

Required order:

1. freeze exact candidate SHA;
2. confirm repository CI green;
3. run production preflight;
4. apply migrations through the controlled migration path;
5. deploy/start the Vercel container under runtime DB credentials;
6. verify `/health/live`;
7. verify `/health/ready`;
8. verify runtime logs contain no secret leakage;
9. only then admit staging for functional QA.

GREEN requires no migration-on-web-startup behavior, secret-safe probes and recorded Vercel deployment identity.

## Gate 6 — Authenticated Smoke/E2E + Browser QA

**Goal:** exercise the hosted application as real users using disposable staging data.

Minimum coverage:

- login and active organization context;
- dashboard;
- scenario/version workspace;
- execution cockpit;
- assessment/debrief;
- history/report access;
- Knowledge Center/contextual help;
- people/organization/access surfaces for an authorized test administrator;
- forbidden behavior for a restricted test user;
- logout/session invalidation;
- low-light persistence;
- keyboard, focus and skip-link sanity.

Browser qualification requires one Chromium-family desktop browser plus one independent engine before production promotion. No production PII is used.

## Gate 7 — Observability + Failure/Recovery Drill

**Goal:** prove predictable failures can be detected and recovered safely in staging.

Evidence:

- Vercel runtime logs are private to the workspace;
- inspected logs contain no secrets or raw sensitive payloads;
- database-unavailable/readiness failure behavior is safely exercised where feasible;
- liveness remains process-oriented while readiness fails closed;
- redeploy/restart of the same candidate returns staging to health;
- Gate 4 recovery evidence remains valid;
- rollback/roll-forward decision path is documented.

GREEN requires observable failure without public diagnostic leakage and successful return to a healthy staging state.

## Gate 8 — Production Promotion + Release Closeout

**Goal:** promote only a staging-qualified immutable candidate.

Preconditions:

- Gates 1A–7 GREEN;
- exact candidate CI remains GREEN;
- zero unresolved Critical/High findings;
- production plan/tier is explicitly approved for commercial/institutional use;
- production secrets/database are independent from staging;
- production recovery/PITR policy is adequate for the accepted risk level.

The Vercel Hobby/free staging setup is **not automatically promoted to commercial production**. Before Gate 8, provider-plan terms, expected load, uptime needs, recovery requirements and costs are re-evaluated. Production may remain on Vercel + Neon with appropriate plans or move to another provider without changing the application-domain design.

## Repository-change policy

R1 may add only operational validation artifacts or targeted provider changes justified by Vercel staging:

- revised R1 spec/plan/ledger/scorecard;
- `Dockerfile.vercel` or narrowly-scoped provider configuration;
- staging/release smoke/E2E tests;
- secret-safe deployment configuration;
- targeted defects fixed via RED → GREEN.

Every repository-file change reruns the complete M9 release matrix: dependency security, real container build/runtime contract, SQLite, PostgreSQL 16 including M6 hardening/concurrency, and Pint.

## Evidence model

Evidence is classified as:

- **repository:** Git SHA, PR, CI, tests and documentation;
- **provider:** Vercel project/environment/deployment identity, HTTPS, Neon database/recovery and private logs;
- **runtime:** preflight, DB-role behavior, probes and authenticated smoke;
- **manual:** required checks where automation/tool access is unavailable.

Evidence must be secret-safe. Tokens, passwords, PII, connection strings and sensitive restored records do not belong in GitHub artifacts or public logs.

## Failure policy

R1 remains fail-closed:

- Critical/High finding → promotion blocked;
- provider capability FAIL/UNKNOWN → applicable gate blocked;
- failed recovery drill → production blocked;
- runtime least-privilege failure → production blocked;
- readiness failure → functional admission blocked;
- release-critical authenticated smoke failure → promotion blocked;
- unverifiable provider claim → UNKNOWN, never PASS.

Failed gates are fixed and rerun; acceptance wording is not weakened to make a failure pass.

## Progress model

Existing R1 progress remains evidence-based. Changing provider does not retroactively award percentage.

- approved R1 staging-first architecture/spec/plan work already earned remains recorded;
- Gate 1A replaces the AWS provider choice and must become GREEN before Gate 2;
- Gate 2 and later contribute only after real provider/runtime evidence exists;
- no percentage is awarded merely for choosing a free provider.

## Acceptance checklist

### Provider revision / Gate 1A
- [x] Vercel + Neon selected for current staging phase.
- [x] Vercel workspace connection confirmed.
- [x] Existing Vercel projects enumerated; no existing Tactical Scenario Lab project found.
- [x] Container/custom-environment capability confirmed from current Vercel documentation.
- [x] Neon integration path confirmed from current Vercel documentation.
- [x] AWS retained only as historical/future alternative.
- [x] Revised spec self-review completed.
- [ ] Revised written spec approved by user.

### Gate 2
- [ ] Tactical Scenario Lab Vercel project exists.
- [ ] Provider container config is committed and CI-green.
- [ ] Isolated staging environment exists.
- [ ] Isolated Neon staging database exists.
- [ ] Staging-only secrets are configured outside Git.
- [ ] HTTPS deployment is reachable.
- [ ] Exact candidate/deployment identity recorded.

### Gate 3
- [ ] `production:preflight` green in hosted staging.
- [ ] Migration path controlled.
- [ ] Runtime DB credentials restricted.
- [ ] Runtime DDL denial proven.
- [ ] No secret leakage found.

### Gate 4
- [ ] Provider recovery capability recorded.
- [ ] Isolated recovery target created.
- [ ] Recovery drill completed.
- [ ] Restored migration/application integrity passes.

### Gate 5
- [ ] Exact candidate CI green.
- [ ] Hosted deployment green.
- [ ] `/health/live` green.
- [ ] `/health/ready` green.
- [ ] Runtime logs inspected.

### Gate 6
- [ ] Authenticated smoke/E2E green.
- [ ] Restricted-user authorization checks green.
- [ ] Logout/session check green.
- [ ] Accessibility/low-light sanity green.
- [ ] Browser matrix evidence recorded.

### Gate 7
- [ ] Private observability evidence captured.
- [ ] Failure/readiness behavior validated.
- [ ] Same-candidate redeploy/recovery green.
- [ ] Rollback/roll-forward path recorded.

### Gate 8
- [ ] Gates 1A–7 GREEN.
- [ ] Production provider/plan terms explicitly approved.
- [ ] Production secrets/database independent.
- [ ] Production recovery/PITR policy approved.
- [ ] Production preflight/deploy/health green.
- [ ] Minimal production smoke green.
- [ ] Release identity/migration state recorded.
- [ ] Version/tag decision recorded.
- [ ] R1 closeout audit complete.

## Completion definition

R1 is complete only when a staging-qualified immutable candidate has been promoted to an explicitly approved production environment; production health and minimal smoke evidence are captured; least-privilege and recovery controls remain intact; a real staging recovery drill succeeded before promotion; no unresolved Critical/High finding remains; release identity/migration state are recorded; and no external operational result was fabricated.
