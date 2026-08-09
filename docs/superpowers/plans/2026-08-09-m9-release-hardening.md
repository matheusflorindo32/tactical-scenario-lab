# M9 — Release & Final Product Hardening Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Turn the M1–M8 feature-complete Tactical Scenario Lab into an auditable, release-ready production candidate without adding a new product domain.

**Architecture:** Keep the current Laravel 13 + Blade + Tailwind + Alpine application and PostgreSQL production model. M9 adds executable release contracts around repository metadata, security/dependencies, container/deploy parity, CI, reliability, UX/localization, observability/recovery documentation and exact-head protected integration. Business-domain behavior and M1–M8 invariants remain unchanged.

**Tech Stack:** PHP 8.4 in CI; Laravel 13; PHPUnit 12; PostgreSQL 16; SQLite regression; Composer 2; Node 22; npm; Vite 7; Tailwind CSS 4; Alpine.js 3; Docker.

## Global Constraints

- `main` is never written directly during M9 implementation.
- PostgreSQL is the only supported production database; SQLite remains local/regression only.
- Runtime processes do not require migration-owner credentials and do not execute migrations automatically.
- No production secret, token, certificate, password or private key is committed.
- No M1–M8 authorization, tenant, historical or database immutability invariant may be weakened.
- No new product domain, AI/RAG, autonomous clinical/tactical guidance, payments or native mobile scope.
- Prefer existing dependencies; no new dependency unless a gate cannot be satisfied safely without it.
- `APP_LOCALE=pt_BR`, `APP_FALLBACK_LOCALE=pt_BR`, `APP_FAKER_LOCALE=pt_BR` are the repository defaults for the institutional product.
- Dependency release blocking uses `composer audit` and `npm audit --audit-level=high`; CI never auto-fixes lockfiles.
- The production application process runs as a non-root user and has writable access only to Laravel runtime directories it needs.
- No hosted tag/release is fabricated; release readiness is the tested repository state unless an unambiguous version and supported connector capability exist after protected integration.
- Do not claim pixel-perfect browser validation without a real authenticated browser session.

---

## File Structure

### New files
- `tests/Feature/M9ReleaseBaselineTest.php` — executable stale-release-metadata contract.
- `tests/Feature/M9SecurityGovernanceTest.php` — security policy and dependency-audit workflow contract.
- `tests/Feature/M9ContainerContractTest.php` — Docker/runtime/migration separation contract.
- `tests/Feature/M9CiReleaseContractTest.php` — current CI branch and release-quality gate contract.
- `tests/Feature/M9ReliabilityContractTest.php` — route/config caching and health contract assertions.
- `tests/Feature/M9FinalUiContractTest.php` — product naming/localization/accessibility regression contract.
- `tests/Feature/M9DocumentationContractTest.php` — release/recovery/observability documentation contract.
- `tests/Feature/M9ForensicReleaseContractTest.php` — final repository forensic contract.
- `docs/RELEASE.md` — controlled release, verification and rollback decision tree.
- `CHANGELOG.md` — milestone-oriented release-ready history without fabricated semantic versions.
- `docs/PHASE_M9_AUDIT.md` — final forensic evidence and limitations.
- `docs/superpowers/sdd/m9-progress.md` — RED/GREEN SHA and CI evidence ledger.

### Existing files expected to change
- `SECURITY.md` — current security posture and disclosure policy.
- `.env.example` — product identity and pt_BR defaults.
- `composer.json` — product metadata; scripts only if needed by release checks.
- `Dockerfile` — PostgreSQL-capable, migration-free runtime startup, non-root runtime, deterministic assets.
- `.github/workflows/tests.yml` — current branch policy + dependency/release gates while preserving M6 matrix.
- `docs/PRODUCTION.md` — exact container migration/runtime relationship and release verification.
- `README.md` — release-grade product/setup/operations summary.
- UI/layout files only if Gate 6 tests expose current naming/accessibility inconsistencies.

---

### Task 1: Gate 1 — Global M1–M8 Release Baseline Audit

**Files:**
- Create: `tests/Feature/M9ReleaseBaselineTest.php`
- Modify: `SECURITY.md`
- Modify: `.env.example`
- Modify: `composer.json`
- Modify: `Dockerfile`
- Modify: `.github/workflows/tests.yml`
- Create: `docs/superpowers/sdd/m9-progress.md`

**Interfaces:**
- Consumes: repository text/config files only.
- Produces: `M9ReleaseBaselineTest` as the canonical release-metadata baseline contract used again by Gate 8.

- [ ] **Step 1: Write the failing baseline test**

Create tests that read repository files and assert:

```php
public function test_release_metadata_matches_current_product(): void
{
    $security = file_get_contents(base_path('SECURITY.md'));
    $env = file_get_contents(base_path('.env.example'));
    $composer = json_decode(file_get_contents(base_path('composer.json')), true, flags: JSON_THROW_ON_ERROR);
    $dockerfile = file_get_contents(base_path('Dockerfile'));
    $workflow = file_get_contents(base_path('.github/workflows/tests.yml'));

    $this->assertStringNotContainsString('autenticação (planejada', mb_strtolower($security));
    $this->assertStringContainsString('APP_NAME="Tactical Scenario Lab"', $env);
    $this->assertStringContainsString('APP_LOCALE=pt_BR', $env);
    $this->assertStringContainsString('Tactical Scenario Lab', $composer['description']);
    $this->assertStringNotContainsString('database/database.sqlite', $dockerfile);
    $this->assertDoesNotMatchRegularExpression('/CMD .*migrate --force/i', $dockerfile);
    $this->assertStringNotContainsString('feature/phase-2-', $workflow);
}
```

- [ ] **Step 2: Run RED**

Run:

```bash
php artisan test tests/Feature/M9ReleaseBaselineTest.php
vendor/bin/pint --test
```

Expected: baseline test FAILS on stale release metadata; Pint remains green.

- [ ] **Step 3: Apply minimum release-baseline corrections**

Make only the corrections necessary for the assertions:
- current Tactical Scenario Lab identity;
- pt_BR defaults;
- remove stale auth/MVP language;
- remove obsolete feature branch triggers;
- remove SQLite runtime creation and migration-on-CMD from Dockerfile without yet completing full Gate 3 container hardening.

- [ ] **Step 4: Run GREEN and full matrix**

Run targeted test and then require CI SQLite + PostgreSQL 16 + Pint/build + inherited M6 hardening green.

- [ ] **Step 5: Record evidence**

Write RED SHA/run and GREEN SHA/run into `docs/superpowers/sdd/m9-progress.md`.

---

### Task 2: Gate 2 — Security & Dependency Governance

**Files:**
- Create: `tests/Feature/M9SecurityGovernanceTest.php`
- Modify: `SECURITY.md`
- Modify: `.github/workflows/tests.yml`
- Modify lockfiles only if a real blocking advisory requires a tested upgrade.
- Modify: `docs/superpowers/sdd/m9-progress.md`

**Interfaces:**
- Consumes: `SECURITY.md`, `composer.lock`, `package-lock.json`, workflow YAML.
- Produces: explicit non-suppressive dependency audit gates.

- [ ] **Step 1: Write RED contract**

Assert the policy contains current authenticated multi-organization posture, tenant isolation/PII/knowledge-rendering scope, private reporting path and production-operator link; assert workflow contains literal commands:

```text
composer audit
npm audit --audit-level=high
```

and does not contain `audit fix`, `--force`, blanket ignore/advisory suppression patterns.

- [ ] **Step 2: Prove RED**

Run `php artisan test tests/Feature/M9SecurityGovernanceTest.php` and Pint. Expected: missing dependency-audit workflow assertions fail.

- [ ] **Step 3: Implement policy and workflow checks**

Add Composer audit after dependency install in relevant PHP jobs or a dedicated release-security job; add npm audit with high threshold after `npm ci`. Keep lockfiles immutable in CI.

- [ ] **Step 4: Run actual audits**

Run:

```bash
composer audit
npm audit --audit-level=high
```

If an advisory blocks, upgrade only the affected dependency in a separate tested commit; never add broad suppression.

- [ ] **Step 5: Full matrix and ledger**

Require all inherited jobs green and record exact evidence.

---

### Task 3: Gate 3 — Production Container & Deployment Parity

**Files:**
- Create: `tests/Feature/M9ContainerContractTest.php`
- Modify: `Dockerfile`
- Modify: `docs/PRODUCTION.md`
- Modify: `docs/superpowers/sdd/m9-progress.md`

**Interfaces:**
- Consumes: M6 production contract and current build assets.
- Produces: production image contract with PostgreSQL driver, deterministic frontend assets, explicit migration phase and non-root runtime.

- [ ] **Step 1: Write RED Docker contract**

Test must assert the Dockerfile:
- installs/enables `pdo_pgsql`;
- does not touch/create SQLite;
- does not invoke `migrate` in `CMD`/`ENTRYPOINT`;
- contains a Node build stage or equivalent deterministic `npm ci && npm run build` stage;
- copies built `public/build` into runtime image;
- declares/uses a non-root `USER` before runtime command;
- keeps runtime command limited to serving the application process.

- [ ] **Step 2: Prove RED**

Run targeted test. Expected: PostgreSQL/assets/non-root assertions fail.

- [ ] **Step 3: Implement multi-stage production Dockerfile**

Use a Node 22 stage for frontend assets and PHP 8.4 runtime stage with PostgreSQL client headers/`pdo_pgsql`. Install Composer production dependencies with optimized autoloading. Create Laravel writable directories and chown them to the runtime user. Do not embed secrets or migrations.

- [ ] **Step 4: Synchronize production runbook**

Document distinct commands/phases:

```bash
php artisan production:preflight
php artisan migrate --force
php artisan config:cache
php artisan route:cache
```

Migration runs with migration identity; web/queue runtime uses least-privilege identity.

- [ ] **Step 5: Validate**

Require contract test, production asset build and full CI matrix green; record evidence.

---

### Task 4: Gate 4 — CI & Release Pipeline Hardening

**Files:**
- Create: `tests/Feature/M9CiReleaseContractTest.php`
- Modify: `.github/workflows/tests.yml`
- Modify: `docs/superpowers/sdd/m9-progress.md`

**Interfaces:**
- Consumes: existing M6 CI steps and Gate 2 audits.
- Produces: current PR/main policy and release-quality checks without provider deployment.

- [ ] **Step 1: Write RED workflow contract**

Assert workflow triggers exactly cover PRs to `main` and pushes to `main`, while retaining named steps for:
- Composer validation;
- frontend build;
- SQLite tests;
- PostgreSQL 16 tests;
- least-privilege runtime role;
- M6 rollback/reapply;
- 3× concurrency invariants;
- Pint;
- Composer audit;
- npm high-severity audit.

- [ ] **Step 2: Prove RED**

Run targeted contract. Expected: any missing release-quality gate fails.

- [ ] **Step 3: Normalize workflow**

Remove historical branch exceptions, preserve inherited matrix, add only deterministic release checks. Do not add auto-deploy.

- [ ] **Step 4: Full CI**

Every release job must complete successfully on the same candidate SHA.

- [ ] **Step 5: Record evidence**

Update ledger with RED/GREEN SHAs and run/job IDs.

---

### Task 5: Gate 5 — Reliability & Deterministic Performance Budget

**Files:**
- Create: `tests/Feature/M9ReliabilityContractTest.php`
- Modify: `.github/workflows/tests.yml` only if cacheability needs explicit command gates.
- Modify: `docs/superpowers/sdd/m9-progress.md`

**Interfaces:**
- Consumes: current health endpoints and application boot/config/routes.
- Produces: cacheability and secret-safe health reliability release gate.

- [ ] **Step 1: Write RED reliability contract**

Tests must preserve:
- `/health/live` response independent of database access;
- `/health/ready` coarse response contract with no host/user/password/SQL/exception details;
- expected authenticated routes remain resolvable;
- production config/route cache commands are part of CI.

- [ ] **Step 2: Add CI cacheability gate**

Use deterministic commands:

```bash
php artisan config:cache
php artisan route:cache
php artisan config:clear
php artisan route:clear
```

Do not add wall-clock thresholds.

- [ ] **Step 3: Prove GREEN**

Run targeted health/reliability tests and full SQLite/PostgreSQL matrix.

- [ ] **Step 4: Record evidence**

Update ledger.

---

### Task 6: Gate 6 — UX, Localization & Accessibility Finalization

**Files:**
- Create: `tests/Feature/M9FinalUiContractTest.php`
- Modify UI/layout/config files only where a failing contract demonstrates a real inconsistency.
- Modify: `.env.example` if required by the final contract.
- Modify: `docs/superpowers/sdd/m9-progress.md`

**Interfaces:**
- Consumes: M7 UI contract, M8 Knowledge routes and pt_BR release defaults.
- Produces: final release presentation contract without redesign.

- [ ] **Step 1: Write RED/contract tests**

Assert rendered authenticated shell/core surfaces keep:
- Tactical Scenario Lab product identity;
- skip link and `main` target;
- canonical sidebar without `href="#"` placeholders;
- accessible Knowledge navigation and contextual help;
- low-light browser-local contract (`localStorage`, no backend persistence endpoint);
- reduced-motion support in authored CSS;
- page/document titles identify the product.

- [ ] **Step 2: Prove RED only where real inconsistency exists**

If all inherited UI contracts already pass, introduce no cosmetic churn; Gate 6 may be GREEN through explicit final-contract coverage plus any demonstrated naming/localization correction.

- [ ] **Step 3: Minimal corrections**

Fix only contract failures. No M7 redesign.

- [ ] **Step 4: Full matrix and ledger**

Require all UI/knowledge/inherited tests green and record evidence.

---

### Task 7: Gate 7 — Observability, Recovery & Release Documentation

**Files:**
- Create: `tests/Feature/M9DocumentationContractTest.php`
- Create: `docs/RELEASE.md`
- Create: `CHANGELOG.md`
- Modify: `README.md`
- Modify: `docs/PRODUCTION.md`
- Modify: `SECURITY.md`
- Modify: `docs/superpowers/sdd/m9-progress.md`

**Interfaces:**
- Consumes: actual container, CI and health contracts delivered by Gates 1–6.
- Produces: operator release/recovery documentation synchronized with executable behavior.

- [ ] **Step 1: Write RED documentation contract**

Assert `docs/RELEASE.md` and `CHANGELOG.md` exist and release docs explicitly distinguish:
- application rollback;
- schema rollback;
- backup/PITR restore;
- migration vs runtime database identity;
- traffic admission through live/ready probes;
- exact release SHA evidence.

Also reject stale MVP/auth-future/SQLite-production/migrate-on-start claims across release docs.

- [ ] **Step 2: Prove RED**

Expected: missing release/changelog artifacts fail.

- [ ] **Step 3: Write synchronized artifacts**

`CHANGELOG.md` summarizes milestones M1–M9 as repository history without inventing semantic versions. `docs/RELEASE.md` provides preflight, migration, cache, health, admission, verification, rollback decision tree and evidence capture.

- [ ] **Step 4: Full matrix and ledger**

Require docs contract + all inherited jobs green; record evidence.

---

### Task 8: Gate 8 — Final Forensic Audit & Protected Integration

**Files:**
- Create: `tests/Feature/M9ForensicReleaseContractTest.php`
- Create: `docs/PHASE_M9_AUDIT.md`
- Modify: `docs/superpowers/sdd/m9-progress.md` only before candidate freeze.
- Update PR #12 metadata/comments after exact-head without modifying repository files.

**Interfaces:**
- Consumes: all seven GREEN gates and current `main`.
- Produces: exact-head verified release-ready `main` and post-merge evidence.

- [ ] **Step 1: Write forensic RED contract**

Assert release files/docs/workflow/container/security policy all represent the final M9 contracts and no known stale release claims remain.

- [ ] **Step 2: Produce audit artifact and candidate GREEN**

Create `docs/PHASE_M9_AUDIT.md` covering delta, invariants, security/dependencies, container/deploy, CI, reliability, UX, observability/recovery, limitations and evidence.

- [ ] **Step 3: Freeze candidate**

After ledger/audit docs are final, make no additional repository-file commits.

- [ ] **Step 4: Fresh exact-head verification**

Fetch workflow run for frozen SHA and require:
- SQLite SUCCESS;
- PostgreSQL 16 SUCCESS;
- Pint SUCCESS;
- dependency audits SUCCESS;
- production build/cacheability SUCCESS;
- all inherited M6/M7/M8/M9 tests SUCCESS.

- [ ] **Step 5: PR forensic review**

Verify branch `0 behind` current `main`, mergeable, expected file delta, zero unresolved review threads and no critical/high finding.

- [ ] **Step 6: Protected integration**

Mark PR ready and merge with:

```text
expected_head_sha=<exact frozen verified SHA>
merge_method=merge
```

GitHub must reject if HEAD moved.

- [ ] **Step 7: Post-merge verification**

Confirm PR closed+merged and:

```text
compare base=<merge commit> head=main => identical, ahead_by=0, behind_by=0
```

Where available, compare synthetic tested merge ref to actual merge tree and require zero file differences.

- [ ] **Step 8: Closeout evidence**

Record exact-head run/job IDs, candidate SHA, merge commit and any process anomaly in a PR comment, not a new repository commit.

---

## Plan Self-Review

- Spec coverage: all M9 sections map to Tasks 1–8.
- Placeholder scan: no TBD/TODO/"implement later" instructions.
- Type/interface consistency: evidence ledger path and final audit path are stable across all tasks.
- Scope: no provider deploy, new product domain or fabricated release tag is included.
- TDD: each gate begins with an executable contract and ends with full-matrix evidence.

## Execution Mode

Use **inline execution with `superpowers:executing-plans`** in this connected environment because no independent subagent dispatcher is exposed. Maintain the same RED → GREEN → full CI → ledger checkpoint discipline gate by gate.
