# R1 — Production Release & Operational Validation — Design Specification

Date: 2026-08-09
Status: DESIGN APPROVED — WRITTEN SPEC REVIEW
Baseline: `main` after M9 protected merge
Branch: `feature/r1-production-operational-validation`

## 1. Objective

R1 converts the repository-level release readiness proven by M1–M9 into real operational evidence using a **staging-first** promotion model.

R1 does not add a new product domain. It validates infrastructure, deployment, database identities, recovery, health admission, authenticated smoke/E2E behavior, observability boundaries and production promotion using the exact application contracts already established by M6–M9.

The principal safety rule is:

> Production promotion is prohibited until an isolated staging environment has passed every promotion-blocking R1 gate that can be exercised before production.

R1 must never represent a simulated CI result as a real provider deployment, TLS validation, backup/PITR restore, browser session or production health result.

## 2. Selected operating strategy

The selected strategy is **staging-first with explicit production promotion**.

Rejected alternatives:

1. **Direct-to-production first deployment** — rejected because the first live infrastructure exercise would also become the first release exercise, unnecessarily combining provider, database, secrets, migration, health and application risks.
2. **Provider-neutral rehearsal only** — useful as preparation but insufficient as final operational evidence because TLS, managed PostgreSQL, backup/PITR and traffic admission are provider-specific in practice.

Staging-first is selected because failures occur in an isolated environment before production identities, production data or public traffic are exposed.

## 3. R1 scope

R1 includes:

- provider/environment selection evidence;
- isolated staging infrastructure;
- TLS and HTTPS validation;
- external secret injection;
- migration/runtime PostgreSQL identity separation;
- managed PostgreSQL staging database;
- backup/PITR configuration evidence where supported by the selected provider;
- restore drill into an isolated recovery target;
- deployment of an immutable application SHA/image;
- `production:preflight`, migrations and Laravel cache warmup under controlled identity;
- runtime startup under least-privilege identity;
- liveness/readiness traffic-admission checks;
- authenticated smoke/E2E tests in the hosted staging environment;
- provider/private observability checks;
- failure/recovery drills that do not risk production data;
- controlled production promotion only after staging acceptance;
- production smoke/health closeout;
- an explicit version/tag decision rather than an invented version.

R1 does **not** include:

- new clinical or tactical functionality;
- AI/RAG/vector features;
- feature redesign unrelated to deployment defects;
- production data copied into staging by default;
- exposing secrets/PII in logs or test artifacts;
- weakening M6 PostgreSQL guards to simplify deployment;
- weakening M7/M8 UX/security contracts;
- broad infrastructure-as-code migration unless a RED operational requirement proves it necessary;
- fabricating provider evidence when the required provider account/credentials are not available.

## 4. Safety model

### 4.1 Environment isolation

At minimum, staging and production are distinct security boundaries.

They must not share:

- PostgreSQL database/cluster credentials;
- `APP_KEY`;
- `PII_FINGERPRINT_KEY`;
- session secrets;
- migration-role password;
- runtime-role password;
- provider service tokens;
- TLS private keys when operator-managed;
- backup restore targets.

Staging must not use the authoritative production database.

Synthetic or explicitly approved non-sensitive seed data is preferred for staging. Production PII is not copied into staging as an R1 default.

### 4.2 Deployment identity separation

The deployment sequence preserves the M6/M9 distinction:

1. a controlled **migration identity** executes preflight/migrations;
2. migration credentials are removed from web/worker runtime;
3. the **runtime identity** is least-privilege and cannot own or alter schema;
4. health and authenticated application checks execute only after runtime identity is active.

A deployment that leaves migration-owner credentials available to runtime fails R1.

### 4.3 Immutable release identity

Every staging or production candidate is identified by an exact Git commit SHA and, where the platform uses a built image, by the corresponding immutable image digest/tag.

A branch name, “latest”, mutable container tag or dashboard timestamp is not sufficient release identity.

### 4.4 Promotion rule

A release may be promoted to production only if:

- all repository CI gates for the exact candidate SHA are green;
- all staging Gates 1–7 required for promotion are green;
- no unresolved Critical/High operational finding exists;
- backup/recovery posture is known;
- the production migration/runtime identity split is configured;
- a rollback/recovery decision owner is identified;
- production secrets are external to Git/source artifacts.

## 5. Provider selection contract

R1 does not select a provider by popularity or convenience alone.

The selected provider/stack must demonstrate, through documentation and/or account configuration evidence, the following **blocking requirements**:

1. supported execution model for the Laravel/PHP application or its validated Docker image;
2. managed PostgreSQL suitable for the application's M6/M9 contract;
3. encrypted transport/TLS support;
4. external secret/environment-variable management;
5. separate staging and production environments/projects/services;
6. health-check/traffic-admission capability or an equivalent controlled routing mechanism;
7. persistent/private application logs without requiring secrets in public health responses;
8. backup capability and a documented restore path;
9. PITR capability when required by the selected production recovery policy;
10. ability to separate migration credentials from runtime credentials;
11. ability to identify the exact deployed SHA/image;
12. an operator-access model that does not require committing provider credentials.

### 5.1 Provider scorecard

Gate 1 records each candidate as:

- PASS — blocking requirement demonstrated;
- FAIL — blocking requirement not available;
- UNKNOWN — not yet verified.

A provider with any unresolved FAIL on a blocking requirement cannot be selected for production.

UNKNOWN items block provider selection until resolved.

Cost, region, convenience and vendor preference may rank providers only **after** blocking safety requirements are satisfied.

## 6. Environment architecture

R1 targets the following logical separation:

```text
GitHub / exact candidate SHA
          |
          v
   immutable build/image
          |
     +----+------------------+
     |                       |
     v                       v
STAGING                    PRODUCTION
isolated secrets           isolated secrets
staging migration role     production migration role
staging runtime role       production runtime role
staging PostgreSQL         production PostgreSQL
staging TLS/domain         production TLS/domain
staging logs               production logs
staging recovery target    production backup/PITR policy
```

Production is not required to exist before staging Gates 1–7 are proven.

## 7. Gate model

R1 uses eight gates.

### Gate 1 — Provider & Environment Contract

Goal: select a provider/stack only after proving mandatory operational capabilities.

Evidence:

- provider scorecard;
- supported Laravel/container deployment method;
- PostgreSQL/backup/PITR capabilities;
- TLS/secrets/environment isolation capabilities;
- health/logging capabilities;
- selected staging architecture;
- explicit unresolved-item list.

Acceptance:

- no FAIL/UNKNOWN on production-blocking requirements needed for the selected design;
- staging and production isolation model documented;
- provider credentials remain outside the repository.

### Gate 2 — Isolated Staging + TLS

Goal: create a real hosted staging environment without production trust/data reuse.

Evidence:

- staging service/application exists;
- separate staging PostgreSQL exists;
- staging hostname resolves;
- valid HTTPS/TLS is observed;
- HTTP-to-HTTPS policy is confirmed where applicable;
- staging is not connected to production DB;
- deployed artifact can be mapped to exact SHA/image.

Acceptance:

- staging is independently addressable over HTTPS;
- no production secret/database reuse is detected;
- provider shows exact release identity.

### Gate 3 — Secrets + Migration/Runtime Identity Separation

Goal: prove production-style secret and database authorization boundaries in staging.

Evidence:

- secrets injected externally;
- staging `APP_KEY` and `PII_FINGERPRINT_KEY` are not repository values;
- migration role can run required schema operations;
- runtime role can perform application DML but cannot perform DDL/schema ownership operations;
- migration credentials are absent from running application/worker environment after deploy.

Acceptance:

- `production:preflight` passes with staging production-like settings;
- runtime DDL-denial test passes;
- application operation under runtime identity passes;
- no secret is committed/logged/artifacted.

### Gate 4 — PostgreSQL + Backup/PITR + Restore Drill

Goal: prove that staging database recovery is actionable rather than assumed.

Evidence:

- managed PostgreSQL version/connection posture;
- encrypted DB transport appropriate to provider;
- backup/recovery-point evidence;
- PITR configuration/evidence when the selected recovery policy requires PITR;
- restore to a **separate isolated recovery target**;
- restored application/schema/invariants validated without exposing production data.

Acceptance:

- restore completes into an isolated target;
- migration state is coherent;
- application can connect using recovery-target credentials;
- M6 historical/tenant integrity checks remain valid;
- original staging DB is not overwritten as the first restore drill.

A configured backup without a successful restore drill does not satisfy Gate 4.

### Gate 5 — Real Deployment + Health Admission

Goal: prove the real deployment sequence and traffic-admission contract.

Sequence:

1. exact candidate SHA/image selected;
2. repository CI for that candidate confirmed green;
3. migration identity loaded;
4. `php artisan production:preflight` passes;
5. migrations execute successfully;
6. config/route cache warmup executes as applicable;
7. migration credentials removed from runtime;
8. application starts under runtime identity;
9. `GET /health/live` returns expected healthy response;
10. `GET /health/ready` returns expected ready/database response;
11. only then is staging considered admitted for functional QA.

Acceptance:

- no migrate-on-web-startup behavior;
- probes are secret-safe;
- readiness reflects database loss appropriately;
- release SHA/image identity is recorded.

### Gate 6 — Authenticated Smoke/E2E + Browser QA

Goal: exercise the deployed application through real authenticated browser/HTTP behavior.

Required smoke coverage:

- login;
- active organization context;
- dashboard;
- scenario workspace and version navigation;
- execution cockpit read/create behavior appropriate to test data;
- assessment/debrief flow appropriate to disposable staging data;
- history/report access;
- Knowledge Center and contextual help;
- people/organization/access surfaces for an authorized test administrator;
- unauthorized/forbidden behavior for a restricted test identity;
- logout/session invalidation;
- low-light mode persistence/local behavior;
- keyboard/focus/skip-link sanity checks.

Browser matrix minimum for staging release qualification:

- one Chromium-family desktop browser;
- one additional independent engine when tooling/environment permits.

If the environment cannot automate the second engine, the limitation is recorded and a manual check is required before production promotion.

No test uses real production PII.

### Gate 7 — Observability + Failure/Recovery Drill

Goal: prove the operator can detect and safely respond to predictable failures.

Required exercises in staging:

- confirm application/provider logs are private/protected;
- confirm no secrets or raw sensitive payloads appear in inspected logs;
- simulate or safely induce a database-unavailable condition where provider capabilities permit;
- verify liveness remains process-oriented and readiness fails closed;
- restart/redeploy the same candidate and confirm stable recovery;
- exercise an application rollback to a known schema-compatible candidate **or** document why the current schema transition makes rollback inappropriate and perform the approved roll-forward rehearsal instead;
- validate the Gate 4 recovery target/drill evidence;
- record ownership and decision path for application rollback vs schema rollback vs PITR.

Acceptance:

- observable failure is detected without exposing diagnostics publicly;
- recovery procedure restores a healthy staging state;
- no production data is used as a drill target.

### Gate 8 — Production Promotion + Release Closeout

Goal: promote only a staging-qualified immutable candidate and capture production evidence.

Preconditions:

- Gates 1–7 GREEN;
- exact candidate SHA still matches the intended production artifact;
- repository CI still GREEN on candidate;
- zero unresolved Critical/High findings;
- production secrets/roles/database configured separately from staging;
- backup/recovery point appropriate to the production migration window confirmed.

Production sequence:

1. freeze candidate identity;
2. execute production preflight/migration under production migration identity;
3. remove migration credentials;
4. start production runtime under least privilege;
5. verify production liveness/readiness;
6. admit traffic;
7. execute minimal non-destructive authenticated smoke checks;
8. inspect logs/alerts for release anomalies;
9. record deployed SHA/image, migration state and production health evidence;
10. make an explicit version/tag decision.

Version/tag rule:

- if a semantic version is explicitly selected under a documented versioning policy, create/tag that exact deployed commit;
- otherwise record the exact SHA as the release identity and leave semantic tagging pending;
- never invent a version merely to make R1 look complete.

## 8. Evidence model

Every gate must distinguish:

- **repository evidence** — Git SHA, PR, CI, tests, docs;
- **provider evidence** — service/environment configuration, deploy identity, TLS, PostgreSQL, backup/restore, private logs;
- **runtime evidence** — probes, preflight, role behavior, authenticated smoke;
- **manual evidence** — only where automation/tool access cannot perform the check.

Evidence must avoid secret material. Screenshots/log excerpts used as evidence must redact tokens, passwords, private URLs where sensitive, PII and database connection strings.

## 9. Failure handling

R1 is fail-closed.

- Any unresolved Critical/High security or operational finding blocks promotion.
- Any provider blocking requirement marked FAIL/UNKNOWN blocks provider selection.
- Failed restore drill blocks production promotion.
- Failed runtime least-privilege test blocks production promotion.
- Failed readiness blocks traffic admission.
- Failed authenticated smoke on a release-critical flow blocks production promotion.
- Provider evidence that cannot be independently verified is recorded as UNKNOWN, not PASS.

A failed gate is fixed and rerun; it is not waived by changing the checklist wording.

## 10. Data and privacy rules

- No production PII is required for R1 staging qualification.
- Staging uses synthetic/disposable tenant/user/scenario data unless an explicit privacy-approved dataset is provided.
- Test credentials are environment-specific and never committed.
- Database dumps containing sensitive production records are not stored in GitHub artifacts.
- Health probes remain minimal and never expose connection metadata or exception details.
- Recovery evidence reports integrity outcomes without publishing sensitive restored records.

## 11. Automation boundaries

Automation is preferred for deterministic checks, but R1 does not require inventing automation where provider/tool access is unavailable.

Good automation candidates:

- provider deployment status checks where an authenticated connector/API exists;
- health probe verification;
- release SHA verification;
- authenticated staging smoke/E2E using dedicated synthetic accounts;
- runtime role-denial tests;
- CI/repository checks.

Manual/provider-console evidence may remain necessary for:

- initial billing/account/domain ownership;
- certain backup/PITR controls;
- DNS/TLS ownership challenges;
- recovery target creation depending on provider;
- second-browser validation if automation is unavailable.

Manual does not mean optional: the gate remains blocked until evidence is captured.

## 12. Repository changes allowed in R1

R1 may add only what operational validation proves necessary, for example:

- R1 specification/plan/ledger/audit docs;
- staging/release smoke scripts or E2E tests;
- provider-neutral deployment manifests/configuration when justified;
- CI checks for staging release evidence where credentials can be safely referenced as GitHub/provider secrets;
- targeted application fixes revealed by staging, each with its own RED→GREEN proof.

R1 must not perform unrelated feature work or broad refactoring.

Any product/runtime code defect found in staging is fixed on the R1 branch using TDD and rerun through the full repository matrix before redeployment.

## 13. Testing strategy

Repository changes follow RED → GREEN → full CI.

Operational gates follow:

1. define observable acceptance check;
2. observe failing/not-yet-satisfied state;
3. configure/fix the smallest required surface;
4. rerun check;
5. capture secret-safe evidence;
6. do not promote until green.

The M9 exact-head matrix remains a prerequisite for every candidate that changes repository files:

- Security — Composer/npm audit;
- real container build/runtime contract;
- PHPUnit SQLite;
- PHPUnit PostgreSQL 16 with M6 least-privilege, rollback/reapply and concurrency;
- Pint.

## 14. R1 progress model

R1 progress is evidence-based:

- approved architecture/design: 5%;
- written spec review + implementation/operations plan: next 5%;
- Gates 1–7: 10% each = 70%;
- Gate 8 + production promotion/closeout: final 20%.

A gate contributes its percentage only after its defined evidence is GREEN.

Planning text does not substitute for real provider/runtime evidence.

## 15. Initial acceptance checklist

### Design/spec
- [x] Staging-first strategy selected.
- [x] Production promotion block defined.
- [x] Eight R1 gates defined.
- [x] Environment/security boundaries defined.
- [ ] Written spec approved by user.
- [ ] Detailed implementation/operations plan committed.

### Gate 1
- [ ] Provider scorecard completed.
- [ ] Provider selected with no blocking FAIL/UNKNOWN.
- [ ] Staging architecture documented.

### Gate 2
- [ ] Isolated hosted staging exists.
- [ ] Separate staging database exists.
- [ ] HTTPS/TLS verified.
- [ ] Exact deployed artifact identified.

### Gate 3
- [ ] External staging secrets configured.
- [ ] Migration/runtime roles separated.
- [ ] Runtime DDL denial proven.
- [ ] Migration credentials absent from runtime.

### Gate 4
- [ ] Backup/recovery point verified.
- [ ] PITR posture verified as required.
- [ ] Isolated restore target created.
- [ ] Restore drill completed.
- [ ] Restored integrity/application checks pass.

### Gate 5
- [ ] Exact candidate CI green.
- [ ] `production:preflight` green in staging.
- [ ] Migrations completed under migration identity.
- [ ] Runtime starts under least privilege.
- [ ] Liveness green.
- [ ] Readiness green.

### Gate 6
- [ ] Authenticated smoke/E2E flows green.
- [ ] Restricted-user authorization checks green.
- [ ] Logout/session check green.
- [ ] Accessibility/low-light sanity checks green.
- [ ] Browser matrix evidence recorded.

### Gate 7
- [ ] Private logging/observability evidence captured.
- [ ] Secret/PII log inspection passes.
- [ ] DB/readiness failure behavior validated.
- [ ] Restart/redeploy recovery green.
- [ ] Rollback/roll-forward rehearsal completed.
- [ ] Recovery decision ownership recorded.

### Gate 8
- [ ] Gates 1–7 GREEN.
- [ ] Production environment independently configured.
- [ ] Production migration/runtime roles separated.
- [ ] Production recovery point confirmed.
- [ ] Production preflight/migrations green.
- [ ] Production liveness/readiness green.
- [ ] Minimal production smoke green.
- [ ] Release SHA/image and migration state recorded.
- [ ] Version/tag decision explicitly recorded.
- [ ] R1 closeout audit completed.

## 16. Completion definition

R1 is complete only when:

1. a staging-qualified immutable candidate has been promoted under the defined gate policy;
2. production health/admission and minimal smoke evidence are captured;
3. migration/runtime identities remain separated;
4. recovery posture includes a successful isolated restore drill before promotion;
5. no unresolved Critical/High R1 finding remains;
6. release identity and migration state are recorded;
7. repository evidence and provider/runtime evidence are clearly distinguished;
8. no provider, restore, browser or production result is fabricated.
