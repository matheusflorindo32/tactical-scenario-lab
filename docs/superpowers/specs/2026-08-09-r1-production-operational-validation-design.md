# R1 — Production Release & Operational Validation

Date: 2026-08-09
Status: DESIGN APPROVED — WRITTEN SPEC REVIEW
Baseline: `main` after M9 protected merge
Branch: `feature/r1-production-operational-validation`

## Objective

R1 converts the repository-level readiness proven by M1–M9 into **real operational evidence** through a staging-first release process.

R1 adds no new product domain. It validates hosting, TLS, secrets, PostgreSQL identities, backup/recovery, deployment, health admission, authenticated smoke/E2E, observability and production promotion while preserving all M6–M9 security and integrity contracts.

**Primary rule:** production promotion is blocked until staging has passed every applicable R1 promotion gate.

No CI simulation may be represented as a real provider deployment, restore drill, browser session or production result.

## Selected strategy

**Staging-first with explicit production promotion** is the selected model.

Direct-to-production is rejected because it combines first-time provider, database, secret, migration and application risks in the live environment. A provider-neutral rehearsal alone is also insufficient because TLS, managed PostgreSQL, backup/PITR and traffic admission require provider evidence.

## Hard boundaries

- staging and production are separate security boundaries;
- staging never uses the authoritative production database;
- production PII is not required or copied into staging by default;
- staging and production do not share application secrets, DB credentials or provider tokens;
- migration credentials are temporary and are removed from application runtime;
- runtime uses a least-privilege PostgreSQL identity with no schema ownership/DDL capability;
- production PostgreSQL must support PITR before promotion;
- HTTPS is mandatory for hosted authenticated traffic;
- exact Git SHA/image identity is required; branch names and mutable `latest` tags are insufficient;
- unresolved Critical/High findings block promotion;
- failed restore, readiness, authorization or release-critical smoke checks block promotion;
- no provider, restore, TLS, browser or production evidence is fabricated;
- no unrelated feature work, AI/RAG, clinical/tactical automation or broad refactor belongs to R1.

## Provider selection contract

Gate 1 compares candidate stacks only after they satisfy these **blocking requirements**:

1. supported Laravel/PHP or validated Docker execution;
2. managed PostgreSQL compatible with the M6/M9 contract;
3. encrypted application and database transport;
4. external secret management;
5. isolated staging and production environments;
6. health-check/traffic-admission capability or controlled equivalent;
7. private persistent logs/diagnostics;
8. backups with a documented restore path;
9. PITR capability for the production PostgreSQL service;
10. migration/runtime database credential separation;
11. exact deployed SHA/image identification;
12. operator access without committing provider credentials.

Each item is recorded as **PASS**, **FAIL** or **UNKNOWN**. FAIL or UNKNOWN on a blocking requirement prevents provider selection. Cost, region and convenience rank only providers that have passed the blocking safety requirements.

## Environment model

```text
exact candidate SHA / immutable image
              |
       +------+------+
       |             |
       v             v
    STAGING       PRODUCTION
 separate app     separate app
 separate secrets separate secrets
 migration role   migration role
 runtime role     runtime role
 separate PG      separate PG
 HTTPS/TLS        HTTPS/TLS
 private logs     private logs
 restore target   backup + PITR
```

Production does not need to exist before staging Gates 1–7 are green.

## Gate 1 — Provider & Environment Contract

**Goal:** select the hosting/database stack only after mandatory capabilities are verified.

Evidence:

- provider scorecard;
- supported application/container deployment method;
- managed PostgreSQL, backup, restore and PITR capabilities;
- TLS, secrets, environment isolation, health and logging capabilities;
- selected staging architecture;
- explicit unresolved-item list.

GREEN requires zero blocking FAIL/UNKNOWN, confirmed production PITR capability and documented staging/production isolation. Provider credentials remain outside Git.

## Gate 2 — Isolated Staging + TLS

**Goal:** create a real hosted staging environment isolated from production.

Evidence:

- hosted staging application;
- separate staging PostgreSQL;
- staging hostname and valid HTTPS/TLS;
- if plaintext HTTP is exposed, it redirects to HTTPS and never serves authenticated application traffic;
- no production database or secret reuse;
- exact deployed SHA/image is identifiable.

GREEN requires HTTPS staging, isolated data/trust boundaries and immutable release identity evidence.

## Gate 3 — Secrets + Migration/Runtime Identity Separation

**Goal:** prove production-style authorization boundaries in staging.

Evidence:

- application secrets injected outside Git/artifacts;
- migration role can perform required migration operations;
- runtime role supports normal application DML but cannot own/alter schema;
- migration credentials are absent from running web/worker runtime after deployment;
- `production:preflight` succeeds with production-like staging settings.

GREEN requires a runtime DDL-denial test, successful normal application access using the runtime identity and no exposed secret material.

## Gate 4 — PostgreSQL + Backup/PITR + Restore Drill

**Goal:** prove recoverability instead of merely proving backup configuration.

Evidence:

- managed PostgreSQL connection/TLS posture;
- backup/recovery point evidence;
- production PostgreSQL PITR capability/configuration evidence;
- restore into a **separate isolated recovery target**;
- migration state and application/integrity validation on the restored target.

GREEN requires a successful isolated restore drill. The original staging database is not overwritten for the first recovery drill. The production PostgreSQL option must have PITR enabled or ready to be enabled before Gate 8.

A configured backup without a successful restore drill is not GREEN.

## Gate 5 — Real Deployment + Health Admission

**Goal:** prove the real release sequence in staging.

Required order:

1. select exact candidate SHA/image;
2. confirm repository CI green on that candidate;
3. load migration identity;
4. run `production:preflight`;
5. apply migrations;
6. warm Laravel config/route caches where applicable;
7. remove migration credentials from runtime;
8. start application under the runtime identity;
9. verify `/health/live`;
10. verify `/health/ready`;
11. only then admit staging for functional QA.

GREEN requires no migration-on-web-startup behavior, secret-safe probes, correct readiness behavior and recorded release identity.

## Gate 6 — Authenticated Smoke/E2E + Browser QA

**Goal:** exercise the hosted application as real users using disposable staging data.

Minimum flow coverage:

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

Browser qualification requires one Chromium-family desktop browser plus one independent engine. If the second engine cannot be automated, a manual check is required and recorded before production promotion.

No production PII is used.

## Gate 7 — Observability + Failure/Recovery Drill

**Goal:** prove that predictable failures can be detected and recovered safely in staging.

Required evidence:

- provider/application logs are private;
- inspected logs contain no exposed secrets or raw sensitive payloads;
- database-unavailable behavior is safely exercised where the provider permits;
- liveness remains process-oriented while readiness fails closed;
- restart/redeploy of the same candidate returns staging to health;
- schema-compatible application rollback is rehearsed, or an approved roll-forward rehearsal is used when rollback is unsafe;
- Gate 4 recovery evidence is still valid;
- ownership/decision path for application rollback vs schema rollback vs PITR is documented.

GREEN requires observable failure without public diagnostic leakage and successful return to a healthy staging state.

## Gate 8 — Production Promotion + Release Closeout

**Goal:** promote only a staging-qualified immutable candidate.

Preconditions:

- Gates 1–7 GREEN;
- exact candidate CI remains GREEN;
- zero unresolved Critical/High findings;
- production secrets/database/roles are independent from staging;
- production PostgreSQL PITR is enabled;
- an appropriate production recovery point is confirmed.

Production sequence:

1. freeze candidate identity;
2. execute preflight/migrations using production migration identity;
3. remove migration credentials;
4. start runtime under least privilege;
5. verify production liveness/readiness;
6. admit traffic;
7. run minimal non-destructive authenticated smoke checks;
8. inspect private logs/alerts;
9. record deployed SHA/image, migration state and health evidence;
10. make an explicit version/tag decision.

A semantic version is created only when an explicit versioning policy/version decision exists. Otherwise the exact deployed SHA remains the release identity.

## Evidence model

Evidence is classified as:

- **repository:** Git SHA, PR, CI, tests and documentation;
- **provider:** environment, deployment identity, TLS, PostgreSQL, backup/restore and private logs;
- **runtime:** preflight, role behavior, probes and authenticated smoke;
- **manual:** required checks where authenticated automation/tool access is unavailable.

Evidence must be secret-safe. Tokens, passwords, PII, private connection strings and sensitive restored records do not belong in GitHub artifacts or public logs.

## Failure policy

R1 is fail-closed:

- Critical/High finding → promotion blocked;
- provider FAIL/UNKNOWN → selection blocked;
- failed restore drill → production blocked;
- missing PITR capability → production blocked;
- runtime least-privilege failure → production blocked;
- readiness failure → traffic admission blocked;
- release-critical authenticated smoke failure → promotion blocked;
- unverifiable provider claim → UNKNOWN, never PASS.

Failed gates are fixed and rerun; acceptance wording is not weakened to make a failure pass.

## Repository-change policy

R1 may add only operational-validation artifacts or targeted fixes justified by observed staging failures:

- R1 spec/plan/ledger/audit;
- staging/release smoke or E2E tests;
- narrowly justified provider/deployment configuration;
- secret-safe CI integrations;
- targeted defects fixed via RED → GREEN.

Every repository-file change reruns the complete M9 release matrix: dependency security, real container build/runtime contract, SQLite, PostgreSQL 16 including M6 hardening/concurrency, and Pint.

## Progress model

R1 progress is evidence-based:

- approved architecture/design: **5%**;
- written spec review + implementation/operations plan: next **5%**;
- Gates 1–7: **10% each**;
- Gate 8 + production promotion/closeout: final **20%**.

A gate contributes only after its defined evidence is GREEN. Planning does not substitute for provider/runtime evidence.

## Acceptance checklist

### Design/spec
- [x] Staging-first strategy selected.
- [x] Production promotion block defined.
- [x] Eight gates defined.
- [x] Environment/security boundaries defined.
- [x] Spec self-review completed.
- [ ] Written spec approved by user.
- [ ] Detailed implementation/operations plan committed.

### Gate 1
- [ ] Provider scorecard complete.
- [ ] Provider selected with zero blocking FAIL/UNKNOWN.
- [ ] Production PostgreSQL PITR capability confirmed.
- [ ] Staging architecture recorded.

### Gate 2
- [ ] Hosted isolated staging exists.
- [ ] Separate staging PostgreSQL exists.
- [ ] HTTPS/TLS verified.
- [ ] Plaintext HTTP does not serve authenticated traffic.
- [ ] Exact deployed artifact identified.

### Gate 3
- [ ] External staging secrets configured.
- [ ] Migration/runtime roles separated.
- [ ] Runtime DDL denial proven.
- [ ] Migration credentials absent from runtime.

### Gate 4
- [ ] Backup/recovery point verified.
- [ ] Production PITR capability/configuration verified.
- [ ] Isolated restore target created.
- [ ] Restore drill completed.
- [ ] Restored integrity/application checks pass.

### Gate 5
- [ ] Exact candidate CI green.
- [ ] Staging preflight green.
- [ ] Migrations executed under migration identity.
- [ ] Runtime starts under least privilege.
- [ ] Liveness green.
- [ ] Readiness green.

### Gate 6
- [ ] Authenticated smoke/E2E green.
- [ ] Restricted-user authorization checks green.
- [ ] Logout/session check green.
- [ ] Accessibility/low-light sanity green.
- [ ] Browser matrix evidence recorded.

### Gate 7
- [ ] Private observability evidence captured.
- [ ] Secret/PII log inspection green.
- [ ] DB/readiness failure behavior validated.
- [ ] Restart/redeploy recovery green.
- [ ] Rollback/roll-forward rehearsal complete.
- [ ] Recovery decision ownership recorded.

### Gate 8
- [ ] Gates 1–7 GREEN.
- [ ] Production independently configured.
- [ ] Production migration/runtime roles separated.
- [ ] Production PITR enabled.
- [ ] Production recovery point confirmed.
- [ ] Production preflight/migrations green.
- [ ] Production liveness/readiness green.
- [ ] Minimal production smoke green.
- [ ] Release SHA/image and migration state recorded.
- [ ] Version/tag decision explicitly recorded.
- [ ] R1 closeout audit complete.

## Completion definition

R1 is complete only when a staging-qualified immutable candidate has been promoted to production under this policy; production health and minimal smoke evidence are captured; least-privilege and recovery controls remain intact; an isolated restore drill succeeded before promotion; production PITR is enabled; no unresolved Critical/High finding remains; release identity/migration state are recorded; and no external operational result was fabricated.
