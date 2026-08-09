# R1 — Staging-First Production Release & Operational Validation Design

Date: 2026-08-09
Baseline: `main` at M9 merge `1d77b89ef273e97cc53c7901df2d0f405684df45`
Branch: `feature/r1-staging-production-validation`
Status: DESIGN REVIEW

## 1. Objective

R1 converts the M1–M9 release-ready repository into evidence of safe hosted operation. It does not add a new product domain. The strategy is **staging-first**: production promotion is blocked until an isolated hosted staging environment proves deployment, least-privilege runtime, health admission, authenticated smoke/E2E behavior, backup/PITR recovery, observability and rollback/recovery procedures.

The central rule is simple: **repository readiness is not production readiness**. M9 proved the application package and release contracts. R1 proves that a real hosted environment satisfies them.

## 2. Safety model

R1 follows these non-negotiable constraints:

1. Staging and production are separate environments with separate databases and secrets.
2. Staging must not use a live production database or production credentials.
3. Migration credentials and runtime credentials are separate identities.
4. Runtime must remain least-privilege and must not own schema objects or receive DDL permissions merely for convenience.
5. Ordinary web/queue startup must not run migrations.
6. Production traffic cannot be admitted until staging gates 1–7 are green and the production candidate is frozen.
7. Backup existence is not accepted as recovery evidence; a successful isolated restore/PITR drill is required before production promotion.
8. Health endpoints remain minimal and secret-safe; detailed diagnostics belong in protected observability.
9. No secrets, access tokens, private keys, database passwords, provider credentials or PII are committed to Git.
10. No production tag/version is fabricated. Versioning is decided only at the final promotion gate from an exact tested SHA.
11. No destructive operation against production is performed as part of staging validation.
12. No production claim is made for actions that cannot be executed or independently verified in the connected environment.

## 3. Provider selection principle

R1 does not preselect a hosting vendor in the design spec. Provider choice is Gate 1 and must be evidence-driven.

A provider/architecture is eligible only if it supports the release contract without weakening it:

- immutable or SHA-identifiable application deployment;
- container or otherwise equivalent Laravel/PHP runtime support;
- managed PostgreSQL compatible with the supported release line;
- encrypted network transport / TLS;
- external secret injection;
- separate staging and production environments;
- health checks / traffic admission controls;
- protected logs and deploy history;
- backup and point-in-time recovery capability appropriate to the selected database tier;
- restore to an isolated target or equivalent safe recovery drill;
- rollback/redeploy of a previous application artifact;
- ability to keep migration and runtime database credentials separate;
- auditability sufficient to associate environment state with a release SHA.

If no available provider satisfies these controls on an acceptable tier, R1 stops at Gate 1 instead of relaxing the product's security posture.

## 4. Environment topology

### 4.1 Staging

Staging is a real hosted environment used for release validation.

Required separation:

- dedicated application environment;
- dedicated PostgreSQL database/cluster or isolated database instance as supported by the selected provider;
- dedicated `APP_KEY`;
- dedicated `PII_FINGERPRINT_KEY`;
- dedicated migration credential;
- dedicated runtime credential;
- non-production hostname;
- valid TLS;
- staging-only test users and organizations;
- no copied production PII unless a separately approved, sanitized data process exists.

Synthetic/demo data is preferred.

### 4.2 Production

Production remains unprovisioned or traffic-blocked until staging Gates 1–7 pass. Its credentials, database, hostnames and secrets are independent from staging.

## 5. Deployment identity model

### Migration identity

Used only for controlled preflight/migration work. It may hold the schema privileges required by Laravel migrations, but it must not remain in web/queue runtime after deployment.

### Runtime identity

Used by web/queue processes after migration. It remains least-privilege as defined by M6/M9:

- no superuser;
- no role/database creation;
- no schema ownership;
- no DDL;
- only required DML/sequence access;
- database immutability guards remain effective.

R1 must verify the identities operationally rather than relying only on configuration text.

## 6. Release artifact identity

Every staging and production deployment must record an exact Git SHA. A branch name or "latest" image is not a release identity.

The preferred artifact flow is:

1. exact candidate SHA;
2. M9 CI green on that SHA or an R1-required successor CI green on the exact candidate;
3. application image/artifact built from that SHA;
4. staging deploy associated with the same SHA;
5. staging evidence ledger;
6. frozen production candidate;
7. production promotion from the same immutable artifact/SHA where provider capabilities allow.

If the provider rebuilds from source rather than accepting a prebuilt image, R1 must still verify the deployed revision equals the intended SHA.

## 7. Gates

### Gate 1 — Provider & Environment Contract

Objective: select the safest feasible hosting/data architecture and document the controls available on the actual tier/account.

Evidence must include:

- application hosting capability;
- PostgreSQL service/version posture;
- TLS/ingress capability;
- secret management/injection;
- environment isolation;
- deploy revision visibility;
- health/readiness integration;
- backup/PITR availability;
- isolated restore capability or safe equivalent;
- logging/observability baseline;
- rollback/redeploy capability;
- cost/tier limitations that affect security or recovery.

Gate fails if a required production control is unavailable and cannot be supplied safely by another component.

### Gate 2 — Isolated Staging + TLS

Objective: provision a non-production hosted environment with strict separation from production.

Required proof:

- staging hostname resolves to the intended environment;
- HTTPS/TLS is valid;
- application environment is not `local`/debug;
- staging database is PostgreSQL;
- staging has its own secrets and credentials;
- no production database or production secret is present;
- public health endpoints are reachable without exposing diagnostic internals.

No normal user traffic is sent to staging until preflight/migrations and readiness pass.

### Gate 3 — Secrets + Migration/Runtime Separation

Objective: prove operational least privilege.

Required proof:

- migration secret exists only in the controlled migration phase;
- runtime processes use the runtime database identity;
- runtime identity cannot create/alter/drop schema objects;
- runtime identity cannot create roles/databases;
- application CRUD paths required for normal operation still work;
- migration credentials are absent from web/queue runtime after deployment;
- secret values are not printed into logs, PRs or CI output.

### Gate 4 — PostgreSQL Backup/PITR + Recovery Drill

Objective: prove recoverability before production promotion.

Required proof:

- backup/PITR is enabled on the actual staging database tier or equivalent recovery mechanism is documented and tested;
- recovery point/window is known;
- restore is performed to an isolated target, never over the active staging database as the first drill;
- restored application/database state can start and pass health/readiness;
- tenant relationships, published scenario versions, finalized assessments and append-only execution timeline invariants remain consistent after restore;
- drill evidence records timestamps/recovery point without exposing secrets or PII.

A configured but untested backup does not pass this gate.

### Gate 5 — Real Staging Deployment + Traffic Admission

Objective: execute the repository release sequence in hosted staging.

Required order:

1. identify exact release SHA;
2. inject staging config/secrets externally;
3. execute `php artisan production:preflight` with migration identity;
4. execute `php artisan migrate --force` with migration identity;
5. warm release caches where applicable;
6. remove migration credentials from application runtime;
7. start/restart the candidate with runtime identity;
8. verify `GET /health/live`;
9. verify `GET /health/ready`;
10. only then admit staging traffic.

Failure before admission keeps traffic closed and follows the documented recovery decision tree.

### Gate 6 — Authenticated Smoke/E2E + Browser QA

Objective: prove the actual hosted application, not only unit/feature tests.

Minimum authenticated smoke path:

- login;
- active organization context;
- dashboard shell/navigation;
- scenario/version read and allowed lifecycle path using staging-only data;
- execution/cockpit path using staging-only data;
- assessment/debrief path appropriate to test data;
- historical/read-only path;
- Knowledge Center and contextual help;
- management surface appropriate to the test role;
- logout;
- authorization denial for an intentionally unauthorized path;
- low-light/theme persistence contract;
- responsive/keyboard/focus sanity checks on representative desktop and mobile viewport classes.

Automation may be introduced when it produces deterministic evidence and does not require committing user credentials. Browser automation credentials must be injected as secrets.

R1 does not claim exhaustive pixel-perfect multi-browser equivalence unless such browsers are actually executed.

### Gate 7 — Observability + Failure/Recovery Drill

Objective: prove the operator can detect and react to failures without exposing sensitive data.

Required evidence:

- protected application logs available;
- deploy/revision history available;
- health/readiness status visible to the platform/operator;
- database connectivity failure causes readiness failure while liveness remains process-oriented where the platform permits the drill;
- application restart/redeploy procedure proven;
- previous compatible artifact redeploy/rollback procedure proven in staging;
- logs/telemetry reviewed for secrets, raw credentials and avoidable PII;
- alerting/notification capability documented if the chosen platform supports it;
- no public probe is expanded with stack traces, SQLSTATE, hostnames or credentials.

A destructive database failure is not required merely to prove monitoring. Use reversible/provider-safe drills.

### Gate 8 — Production Promotion + Release Closeout

Objective: promote only a proven candidate.

Preconditions:

- Gates 1–7 green;
- no unresolved Critical/High finding;
- exact production candidate SHA frozen;
- repository CI green on the exact candidate;
- staging is on the intended candidate artifact/revision;
- production secrets/database are separate from staging;
- production backup/PITR policy confirmed before schema change;
- rollback/recovery decision tree available to the operator.

Production sequence mirrors Gate 5 with production-specific credentials and traffic admission.

Post-deploy closeout records:

- version/tag decision;
- exact release SHA;
- CI run evidence;
- deployment/revision identifier;
- migration state;
- runtime identity verification;
- health/readiness results;
- authenticated smoke result using safe production verification steps;
- backup/PITR status;
- anomalies and remediation.

Production is not considered complete merely because deployment tooling reports success.

## 8. Data and privacy boundaries

R1 uses staging-only synthetic/demo records wherever possible. Production exports or personal records are not copied into staging by default.

If a future need requires production-derived test data, that becomes a separate approved data-sanitization design and is outside this R1 spec.

Secrets must never be embedded in browser test code, repository fixtures, screenshots, audit Markdown, PR comments or CI logs.

## 9. Failure policy

R1 is fail-closed.

- Provider lacks required recovery/security control -> Gate 1 blocked.
- TLS/secret/database isolation uncertain -> staging not admitted.
- preflight/migration failure -> traffic remains closed.
- runtime identity has excess privileges -> Gate 3 blocked.
- restore drill cannot be completed -> production promotion blocked.
- readiness fails -> traffic not admitted.
- authenticated smoke reveals authorization/tenant regression -> production promotion blocked.
- Critical/High dependency or operational finding -> promotion blocked.
- deployed revision cannot be tied to exact source SHA -> promotion blocked.

No gate is marked green by prose assertion alone when direct provider/runtime evidence is expected.

## 10. Evidence ledger

R1 will maintain a versioned evidence ledger under `docs/superpowers/sdd/` containing only non-secret metadata:

- gate status;
- tested SHA;
- CI/run IDs where applicable;
- provider/deployment revision identifiers that are safe to publish;
- health/readiness result summaries;
- restore drill summary and recovery point metadata safe for source control;
- smoke/E2E result summary;
- known limitations;
- rejected candidates and why they were rejected.

Provider screenshots containing account identifiers, secrets or private infrastructure details are not required in Git.

## 11. Testing strategy

R1 combines repository tests with hosted evidence.

Repository-side gates continue to require the M9 matrix:

- Composer/npm security audits;
- real container build/runtime contract;
- SQLite suite;
- PostgreSQL 16 suite;
- M6 least-privilege/rollback/concurrency;
- Pint;
- release cacheability.

Hosted gates add:

- TLS/hostname validation;
- staging preflight/migrations;
- runtime-role verification;
- live/ready probes;
- authenticated smoke/E2E;
- authorization negative path;
- recovery drill;
- rollback/redeploy drill;
- log/privacy inspection.

Tests that require provider credentials use external secret storage. Credentials are never added to repository files.

## 12. Observability strategy

R1 uses the selected provider's protected telemetry rather than creating a vendor abstraction layer inside the product unless a concrete product requirement later justifies one.

Minimum operational signals:

- deployment/revision state;
- application process state;
- health/readiness;
- protected application logs;
- database service health;
- backup/PITR status where exposed;
- restart/redeploy history.

Alert rules are provider-specific and are documented in the R1 operational closeout when available.

## 13. Versioning and release policy

R1 does not assume a semantic version in advance.

At Gate 8, versioning is decided from repository history and release intent. If a new hosted release/tag is created, it must point to the exact production candidate or resulting merge/release commit according to the chosen release workflow.

No tag is created merely to make the checklist look complete.

## 14. Scope exclusions

R1 does not include:

- new tactical/clinical guidance;
- AI/RAG/agents;
- billing;
- native mobile app;
- new product-domain tables/features;
- analytics product development;
- production-derived staging data pipeline;
- multi-region HA unless selected provider/tier and actual requirements justify it;
- Kubernetes solely for architectural prestige;
- self-hosted PostgreSQL when a safer managed service is available within the chosen constraints;
- fabricated cloud, restore, TLS, monitoring or browser-test evidence.

## 15. Acceptance criteria

R1 is complete only when:

1. an eligible provider/architecture has been selected by explicit controls;
2. isolated TLS staging exists;
3. staging secrets and DB identities are separated from production and from each other as required;
4. least-privilege runtime is operationally proven;
5. backup/PITR recovery has been successfully drilled to an isolated target;
6. the exact candidate is deployed to staging through the documented release order;
7. liveness/readiness admission is green;
8. authenticated smoke/E2E and authorization negative path are green;
9. observability and reversible failure/rollback procedures are proven;
10. no unresolved Critical/High issue remains;
11. production promotion uses a frozen, tested exact SHA;
12. production health/smoke/identity/recovery posture is verified and recorded;
13. no claim exceeds the evidence actually gathered.

## 16. Progress model

R1 progress is evidence-based:

- approved architecture + written design/spec: 5%;
- reviewed spec + technical execution plan: next 5%;
- Gates 1–7: 10% each = 70%;
- Gate 8 production promotion, forensic closeout and release evidence: final 20%.

Planning artifacts do not count as completed operational gates. A gate becomes complete only after its defined evidence passes.
