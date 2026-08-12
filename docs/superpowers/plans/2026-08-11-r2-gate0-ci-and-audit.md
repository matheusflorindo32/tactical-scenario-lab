# R2 Gate 0 CI & Independent Audit Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** make the stacked R2 pull request independently auditable and close Gate 0 with exact-head CI and hosted HTTPS evidence without overlapping the frontend executor's visual work.

**Architecture:** PR #14 remains stacked on the frozen R1 branch `feature/r1-production-operational-validation` at `b7ce97461523deb63ed8d2a4b2062292ce33a19c`. The independent audit lane owns CI triggering, exact-deployment verification, health/runtime evidence, and acceptance gates; the frontend executor owns Blade/Tailwind/Alpine visual implementation.

**Tech Stack:** GitHub Actions, Laravel 13/PHP 8.4, Vercel Preview, Neon staging, Playwright Chromium/Firefox.

## Global Constraints

- Do not merge PR #13 or PR #14.
- Do not modify `main` or Vercel Production.
- Do not change database schema, Neon grants, auth, tenancy, authorization, health semantics, or R1 recovery behavior.
- Keep R2 on Laravel Blade + Tailwind CSS v4 + Alpine.js.
- Every repository write invalidates earlier exact-head CI evidence.
- Gate completion requires exact Git SHA + exact Vercel deployment evidence.

---

### Task 1: Make stacked R2 PR eligible for the full CI matrix

**Files:**
- Modify: `.github/workflows/tests.yml:3-10`

**Interfaces:**
- Consumes: PR #14 base `feature/r1-production-operational-validation`.
- Produces: pull-request workflow runs for both `main` and the frozen R1 branch.

- [x] **Step 1: Confirm current trigger gap**

Current workflow:

```yaml
pull_request:
  branches:
    - main
```

This excludes PR #14 after retargeting it to the R1 branch.

- [ ] **Step 2: Add the stacked-R2 base branch**

Use exactly:

```yaml
pull_request:
  branches:
    - main
    - feature/r1-production-operational-validation
```

Do not remove `main`.

- [ ] **Step 3: Verify workflow syntax and observe a PR run on the exact new HEAD**

Expected jobs:

```text
Security — Composer and npm audit
Container — build and runtime contract
PHPUnit — PHP 8.4 / SQLite
PHPUnit — PHP 8.4 / PostgreSQL 16
Browser smoke — Chromium + Firefox
Lint (Pint)
Hosted Preview — exact deployment admission
```

- [ ] **Step 4: Record run ID and exact HEAD SHA**

Reject evidence from any older SHA.

Observed during audit run #951: Pint found exactly two style defects (`routes/web.php` extra blank line and `ProxySchemeContractTest.php` quote/concat formatting). Both were corrected.

Observed during audit run #952: Pint, security and container gates passed; SQLite failed only because PHPUnit 12 did not consume the legacy `@dataProvider` docblock and invoked the parameterized proxy test with zero arguments. The test was migrated to the PHPUnit 12 `#[DataProvider('proxyHeaderProvider')]` attribute without changing the proxy behavior under test.

---

### Task 2: Close Gate 0 from hosted evidence

**Files:**
- No application-code changes in the audit lane.

**Interfaces:**
- Consumes: exact R2 HEAD and its Vercel deployment.
- Produces: Gate 0 acceptance or blocking findings.

- [x] **Step 1: Verify HTML scheme**

Require generated Vite CSS, JS, `url()`, `route()`, and `asset()` links to use `https://` on the exact Preview.

Observed on deployment `dpl_GiqogAyuXz6Z4oEEnVDFZpzouHvW`: emitted Vite CSS, JS and internal Laravel links use `https://` after the explicit forwarded-header fix.

- [ ] **Step 2: Verify health semantics**

Require:

```text
GET /health/live  -> HTTP 200, {"status":"ok"}
GET /health/ready -> HTTP 200, status=ready, database=ok
```

Do not replace or simplify `HealthController`.

- [ ] **Step 3: Verify hosted asset admission**

Using the existing protected-Preview automation path, require the emitted CSS and JS URLs to return HTTP 200 with correct MIME types and no 302/403/404/5xx after authentication.

- [ ] **Step 4: Inspect runtime errors**

Query exact-deployment error/warning/fatal logs. Gate 0 remains blocked on a release-critical runtime error.

- [ ] **Step 5: Gate decision**

GREEN only when HTTPS scheme, asset delivery, health, exact-head CI, and cleanup are all evidenced.

---

### Task 3: Independent visual gate after Gate 0

**Files:**
- Read-only audit of frontend executor commits unless a blocking technical defect is isolated and explicitly assigned to the audit lane.

**Interfaces:**
- Consumes: executor's Hero/Header commits and Preview.
- Produces: ACCEPT / CORRECT / REJECT decision.

- [ ] **Step 1: Audit diff boundaries**

Reject backend/database/auth/tenancy changes in a visual commit.

- [ ] **Step 2: Review exact Preview at 390x844, 768x1024, 1440x900 and wide desktop**

Check overflow, hierarchy, header behavior, focus, reduced motion, and design-system consistency.

- [ ] **Step 3: Review architecture**

Require Blade/Tailwind/Alpine and native browser APIs unless a dependency is separately approved.

- [ ] **Step 4: Record decision and residual risks before authorizing the next visual block**

Do not authorize full-landing implementation from an unapproved Hero direction.
