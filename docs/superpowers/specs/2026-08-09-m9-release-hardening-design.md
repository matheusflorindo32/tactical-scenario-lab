# M9 — Release & Final Product Hardening Design

Date: 2026-08-09
Baseline: `main` at `a093da910c246e5186ea53f0720c7f239713fe95`
Branch: `feature/m9-release-hardening`
Status: SPECIFICATION REVIEW

## 1. Purpose

M9 turns the Tactical Scenario Lab from a feature-complete institutional product into a release-grade, auditable production candidate.

M9 does **not** add a new product domain. It closes release gaps across security posture, deployment parity, CI, dependency governance, reliability, UX/accessibility, observability, operational documentation and final forensic verification.

The milestone succeeds when the repository can demonstrate, through executable checks and synchronized documentation, that the application represented by `main` is the same application operators are instructed to deploy.

## 2. Current verified context

M1–M8 are already integrated. The M8 merge baseline is `a093da910c246e5186ea53f0720c7f239713fe95`.

The current product already has:

- authenticated multi-organization access;
- scenario/version lifecycle;
- execution cockpit with append-only timeline semantics;
- assessment/debrief historical freeze semantics;
- institutional dashboards/reporting;
- PostgreSQL production hardening and least-privilege runtime-role tests;
- production preflight plus liveness/readiness probes;
- M7 operational UI and low-light mode;
- M8 authenticated Knowledge & Documentation Center.

M9 is intentionally a hardening/release milestone rather than a feature-expansion milestone.

## 3. Release gaps found before M9 implementation

The pre-design audit identified concrete release-parity gaps:

1. `SECURITY.md` still describes the application as an MVP and says authentication is planned, which is false for the current product.
2. `.github/workflows/tests.yml` still includes historical `feature/phase-2-*` branch triggers rather than the current branch/PR strategy.
3. `Dockerfile` installs SQLite support only, creates a SQLite file, and executes `php artisan migrate --force` in the application startup command. This conflicts with the M6 production contract requiring PostgreSQL, separated migration/runtime identities and controlled deployment order.
4. `.env.example` still uses `APP_NAME=Laravel` and English locale defaults although the institutional UI is Tactical Scenario Lab in Brazilian Portuguese.
5. `composer.json` still uses Laravel skeleton metadata instead of product metadata.
6. The production runbook is strong but needs final M9 alignment with the actual release container/pipeline and explicit release verification artifacts.

These are M9 scope, because they can make a technically correct application be packaged, described or deployed incorrectly.

## 4. Architectural direction

### Selected approach: release-grade hardening without product-domain expansion

M9 keeps the current Laravel + Blade + Tailwind + Alpine architecture and PostgreSQL production model.

The milestone adds or strengthens **release contracts**, not business-domain features. Every gate must be independently testable and must preserve the M1–M8 invariants.

### Rejected approach A: add more product features before release

Rejected because it increases regression surface after M1–M8 have already completed the planned product lifecycle.

### Rejected approach B: documentation-only release cleanup

Rejected because several discovered gaps are executable/runtime concerns, especially container database parity, startup behavior, dependency checks and CI branch policy.

## 5. Global hard constraints

1. `main` is never written directly during M9 implementation.
2. No M1–M8 historical, authorization, tenant or database immutability invariant may be weakened.
3. PostgreSQL remains the only supported production database. SQLite remains local/regression only.
4. Ordinary web/runtime processes must not require migration-owner credentials.
5. The production application process must not automatically run migrations on every startup.
6. No production secret, token, certificate, password or private key may be committed.
7. M9 must not add autonomous clinical/tactical guidance, AI/RAG, payments, native mobile or another product domain.
8. New dependencies require explicit necessity; prefer existing PHP/Laravel/Node capabilities.
9. All release-critical contracts must be executable in CI where the repository can reasonably prove them.
10. External provider backup/PITR, TLS termination and infrastructure monitoring remain provider/operator responsibilities; the repository must document and verify its side of those contracts without pretending to configure an unavailable provider.
11. No Git tag or hosted release is created merely because M9 code is complete. The repository first reaches a release-ready, exact-head verified `main`; version/tag publication is a separate final release action only if an unambiguous version and supported connector capability are available at that point.
12. Visual claims are limited to what can be tested in this connected environment; do not claim pixel-perfect multi-browser validation without a real browser session.

## 6. Release model

M9 produces a **release-ready main** with:

- deterministic build/install instructions;
- a production-compatible container contract;
- synchronized product/security/operations metadata;
- dependency/security audit gates;
- current CI branch policy;
- final release checklist and rollback/recovery guidance;
- exact-head CI evidence before protected integration.

M9 does not couple the repository to a specific cloud provider.

## 7. Gate 1 — Global M1–M8 Release Baseline Audit

### Goal

Create an executable release-baseline contract that detects stale or contradictory release metadata before deeper hardening begins.

### Required checks

The RED contract must identify at least these current defects:

- `SECURITY.md` must not say authentication is only planned;
- `SECURITY.md` must not describe `main` as an unsupported MVP-only policy;
- `.env.example` must identify the product as Tactical Scenario Lab and use `pt_BR`/Portuguese-oriented application defaults where supported by Laravel configuration;
- `composer.json` description must identify Tactical Scenario Lab rather than Laravel skeleton metadata;
- `Dockerfile` must not create/use `database/database.sqlite` as production runtime storage;
- `Dockerfile` must not run `php artisan migrate --force` in the ordinary runtime `CMD`;
- CI must not retain obsolete `feature/phase-2-*` branch triggers.

### Acceptance

A dedicated M9 baseline test fails on the current branch before fixes and passes only after those contradictions are removed.

No domain/controller/model behavior changes are expected in Gate 1.

## 8. Gate 2 — Security & Dependency Governance

### Goal

Synchronize the security policy with the real product and add deterministic dependency/security checks suitable for CI.

### Security policy contract

`SECURITY.md` must describe:

- current authenticated/multi-organization product posture;
- currently supported release line (`main` until a tagged release policy exists);
- private vulnerability-reporting path;
- coordinated disclosure expectations without promising an unsupported SLA mechanism;
- scope including authorization, tenant isolation, PII exposure, CSRF/XSS/injection, session/auth, dependency vulnerabilities and unsafe knowledge rendering;
- production operator guidance consistent with `docs/PRODUCTION.md`.

It must not claim authentication is future work.

### Dependency checks

CI must execute repository-native package vulnerability checks when lockfiles support them:

- Composer advisory/audit check using the installed Composer version;
- npm audit at a severity threshold appropriate for release blocking.

The implementation must avoid auto-fixing lockfiles in CI.

If the current dependency graph contains a release-blocking advisory, M9 must either upgrade through a separately tested dependency commit or document a narrowly justified non-applicable advisory. Blanket ignore lists are forbidden.

### Acceptance

Security/documentation tests plus dependency audit steps pass in CI without hiding vulnerabilities.

## 9. Gate 3 — Production Container & Deployment Parity

### Goal

Make the repository's container compatible with the M6 production contract instead of silently behaving like the local SQLite setup.

### Container contract

The production image must:

- include the PHP PostgreSQL driver required by production;
- not create `database/database.sqlite` for ordinary production runtime;
- not run migrations from the web process startup command;
- install production PHP dependencies with optimized autoloading;
- build or receive production frontend assets deterministically;
- create only required writable Laravel runtime directories;
- run the application as a non-root user where practical for the selected base image;
- expose the application port without embedding environment-specific credentials;
- keep deployment migrations as an explicit operator/deployment phase.

### Deployment contract

`docs/PRODUCTION.md` must describe the exact container/runtime relationship, including a distinct migration command and runtime command.

A repository-level test must inspect the Dockerfile/entrypoint contract so that SQLite/migrate-on-start regressions fail CI.

### Acceptance

The container contract and production runbook agree on PostgreSQL, migration separation and runtime behavior.

## 10. Gate 4 — CI & Release Pipeline Hardening

### Goal

Make CI reflect the current development/release model and add explicit release-quality gates without coupling to a cloud provider.

### CI branch policy

The primary workflow must:

- run on pull requests targeting `main`;
- run on pushes to `main`;
- remove historical one-off feature branch trigger lists;
- retain SQLite, PostgreSQL 16, Pint, production asset build, fresh migrations, least-privilege runtime-role verification, M6 rollback/reapply and repeated concurrency invariants.

### Release-quality gates

Add deterministic checks for:

- Composer metadata validation;
- dependency audits from Gate 2;
- application production preflight tests/contract;
- release baseline/forensic tests;
- production asset build.

Do not add an automatic production deployment because no deployment provider has been selected in this spec.

### Acceptance

A PR cannot achieve a fully green M9 CI state while the release contracts fail.

## 11. Gate 5 — Reliability & Performance Budget

### Goal

Add conservative, stable release budgets that detect obvious degradation without introducing flaky microbenchmarks.

### Required reliability checks

- production route/config cache commands must complete successfully in a CI-compatible environment;
- the application must boot with cached configuration/routes under supported settings;
- liveness remains database-independent;
- readiness remains coarse, PostgreSQL-aware and secret-safe;
- authenticated core workflow tests continue to pass under both SQLite regression and PostgreSQL production-reference jobs.

### Performance budget philosophy

Do **not** use wall-clock timing thresholds in shared CI.

Prefer deterministic budgets such as:

- route/config cacheability;
- bounded frontend production bundle artifacts if a stable measurable output is available;
- query-count regression tests only for a small number of known, deterministic read surfaces where existing factories make counts stable.

If a proposed metric proves environment-dependent, omit it rather than institutionalizing a flaky release gate.

### Acceptance

Reliability budgets pass consistently and do not weaken existing M6 hardening checks.

## 12. Gate 6 — UX, Localization & Accessibility Finalization

### Goal

Close release-level presentation inconsistencies without redesigning M7.

### Required checks

- product name is Tactical Scenario Lab in environment/example metadata;
- Brazilian Portuguese is the intended default application locale where application behavior depends on locale;
- document/page titles identify the product consistently;
- no canonical authenticated navigation item is a dead placeholder;
- M8 Knowledge links remain authenticated and contextual;
- WCAG-oriented contracts already established in M7 remain intact: skip link, focus visibility, semantic controls, accessible labels/current state and reduced-motion support;
- low-light remains local to the browser and does not create backend preference state.

### Acceptance

Final UI contract tests pass without changing domain rules or creating a frontend rewrite.

## 13. Gate 7 — Observability, Recovery & Release Documentation

### Goal

Make operator-facing release documentation internally consistent and sufficient for a controlled release/recovery exercise.

### Required artifacts

Create or synchronize:

- `docs/RELEASE.md` — release procedure, verification and rollback decision tree;
- `docs/PRODUCTION.md` — container/runtime/migration parity;
- `SECURITY.md` — current security policy;
- `README.md` — release-grade setup and product architecture summary;
- `CHANGELOG.md` — human-readable milestone summary for the first release-ready line without inventing historical dates or semantic-version tags that do not exist;
- M9 progress ledger and final audit artifact.

### Recovery checklist

The release documentation must explicitly distinguish:

- application rollback;
- schema rollback;
- backup/PITR restore;
- traffic admission/readiness;
- migration identity vs runtime identity;
- release commit/SHA evidence.

### Observability boundaries

Public health responses remain minimal and secret-safe. Repository documentation may tell operators where to inspect protected platform/database telemetry, but M9 must not fabricate integrations with an unavailable APM/logging provider.

### Acceptance

Documentation contract tests prevent stale MVP/auth/container claims from reappearing.

## 14. Gate 8 — Final Forensic Audit & Protected Integration

### Goal

Prove that the exact M9 candidate reviewed is the exact content integrated into `main`.

### Required audit

Before merge:

1. compare branch against current `main`;
2. confirm no unexpected migration/model/domain-service changes;
3. confirm all M9 gates and inherited M6/M7/M8 contracts are green;
4. confirm PR is mergeable and branch is not behind `main`;
5. inspect PR comments/review threads;
6. freeze the candidate SHA;
7. execute a fresh exact-head CI on that SHA;
8. make no file commits after the exact-head run;
9. merge with `expected_head_sha` using the exact verified SHA;
10. confirm the PR is closed/merged and `main` is identical to the merge commit;
11. compare the tested synthetic merge ref and actual merge tree where available.

### Final release artifact

Record exact-head run ID, relevant job IDs, verified SHA and merge commit in the PR closeout discussion so evidence does not move the tested tree.

A hosted release/tag may be created only after this protected integration if versioning is unambiguous and connector support is available. Absence of a tag does not make M9 incomplete; release readiness is defined by the tested repository state.

## 15. Testing strategy

Every implementation gate follows:

1. write a narrowly scoped failing contract test;
2. prove RED in CI with existing legacy tests remaining healthy;
3. implement the minimum release-hardening change;
4. prove GREEN locally/through the relevant job;
5. run the full SQLite + PostgreSQL 16 + Pint/build/hardening matrix for the gate candidate;
6. record SHA and CI evidence in `docs/superpowers/sdd/m9-progress.md`.

Gate 8 repeats a final exact-head matrix regardless of previous green runs.

## 16. Proposed gate weights

Progress is reported from completed evidence, not commit count:

- Architecture/specification: 5%
- Approved implementation plan: 5%
- Gate 1: 10%
- Gate 2: 10%
- Gate 3: 10%
- Gate 4: 10%
- Gate 5: 10%
- Gate 6: 10%
- Gate 7: 10%
- Gate 8 + exact-head protected integration: 20%

M9 is 100% only after Gate 8 protected integration and post-merge verification.

## 17. Explicitly out of scope

- new scenario/assessment domain features;
- AI/RAG/autonomous clinical or tactical guidance;
- native mobile applications;
- payment/subscription systems;
- provider-specific infrastructure provisioning;
- a monitoring vendor integration chosen without an operator requirement;
- redesigning the M7 Operational Command Center;
- CMS/editor expansion of M8;
- fabricating semantic-version history or release tags.

## 18. Success criteria

M9 is complete when:

- all eight gates are GREEN;
- Docker/runtime behavior matches `docs/PRODUCTION.md`;
- security and repository metadata describe the real product;
- CI represents the current branch/release policy;
- dependency/security checks are explicit and non-suppressive;
- release/recovery documentation is synchronized;
- no M1–M8 invariant has regressed;
- a fresh exact-head CI passes on the final candidate;
- protected merge succeeds using that exact SHA;
- post-merge verification proves `main` contains the audited tree.
