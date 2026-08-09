# M7 Operational Command Center Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Redesign the existing institutional UI as an attention-first Operational Command Center while preserving all M1–M6 domain, security, tenant and database guarantees.

**Architecture:** Keep Laravel 13 server-rendered Blade views, Tailwind CSS v4 design tokens and Alpine.js for small interactions. Treat M7 as information architecture + reusable component evolution, not a domain rewrite. Every behavior-changing gate starts with a failing feature/contract test and is promoted only after the complete CI matrix is green.

**Tech Stack:** PHP 8.3+, Laravel 13, Blade, Tailwind CSS v4, Alpine.js 3, PHPUnit 12, Vite 7, SQLite regression + PostgreSQL 16 production parity, Pint.

## Global Constraints

- Preserve Blade + Tailwind CSS v4 + Alpine.js.
- Preserve all M1–M6 domain and database invariants.
- No new clinical or tactical domain rules in M7.
- No schema change unless a failing behavioral test proves it is strictly necessary.
- No mandatory third-party frontend dependency.
- Portuguese (Brazil) remains the UI language.
- Keep navy/stone/ink/emergency/clinical/alert semantics.
- WCAG 2.2 AA is the authored-UI target.
- M8 and M9 remain out of scope.
- No placeholder navigation links.
- Existing backend authorization remains authoritative.

---

### Task 1: Canonical application shell and navigation

**Files:**
- Create: `tests/Feature/M7ApplicationShellTest.php`
- Modify: `resources/views/components/sidebar.blade.php`
- Modify: `resources/views/components/topbar.blade.php`
- Modify: `resources/views/layouts/app.blade.php` only if the tests prove shell changes are needed

**Interfaces:**
- Consumes: existing named routes, `$current`, authenticated user and active-organization access.
- Produces: one canonical sidebar navigation; topbar without duplicated route navigation; no `Guias`, `Referência`, `Preferências` placeholder anchors; correct Painel route.

- [ ] **Step 1: Write failing shell contract tests**

Create tests that authenticate a seeded institutional user and assert:
- dashboard HTML contains a link to `route('dashboard')` with `aria-current="page"` for Painel;
- canonical application HTML contains real links for Cenários, Templates and Histórico;
- rendered authenticated shell does not contain `href="#"` for canonical navigation/account items;
- rendered shell does not contain `Guias` or `Referência`;
- the topbar does not duplicate a full second Painel/Cenários navigation landmark;
- mobile menu control has an accessible name and the skip link still targets `#main`.

- [ ] **Step 2: Verify RED in GitHub Actions**

Push only the test commit. Expected: at least one M7 shell assertion fails against the pre-M7 UI, while unrelated tests remain executable.

- [ ] **Step 3: Implement the minimum navigation redesign**

Update sidebar/topbar only enough to satisfy the contract. Use real named routes. Do not create fake routes. Keep backend authorization unchanged.

- [ ] **Step 4: Verify GREEN**

Run the complete CI matrix: SQLite, PostgreSQL 16 and Pint/build jobs defined by the repository workflow.

- [ ] **Step 5: Commit and record gate evidence**

Update `docs/superpowers/sdd/m7-progress.md` with RED/GREEN SHAs and workflow run IDs.

---

### Task 2: Reusable M7 design-system primitives

**Files:**
- Create: `resources/views/components/table.blade.php`
- Create: `resources/views/components/section-nav.blade.php`
- Create: `resources/views/components/attention-item.blade.php`
- Create: `tests/Feature/M7DesignSystemComponentsTest.php`
- Modify: `docs/DESIGN_SYSTEM.md`
- Modify: `resources/css/app.css` only when a reusable token/class is required

**Interfaces:**
- `x-table`: semantic table shell with accessible label/caption contract, responsive overflow and empty-state slot.
- `x-section-nav`: array of `[label, href, state?]`, renders anchor navigation with keyboard-safe native links.
- `x-attention-item`: semantic `variant`, title, metadata and optional href/actions.

- [ ] Write tests that render each component and assert semantic markup, labels and no inaccessible fake controls.
- [ ] Push RED and verify expected failures because components do not exist.
- [ ] Implement components using current tokens only.
- [ ] Replace the DESIGN_SYSTEM roadmap entries with documented implemented contracts.
- [ ] Run full CI and record evidence.

---

### Task 3: Attention-first instructor and executive dashboards

**Files:**
- Create: `tests/Feature/M7DashboardExperienceTest.php`
- Modify: `resources/views/dashboard.blade.php`
- Modify: executive dashboard Blade view discovered from `ExecutiveDashboardController`
- Modify: dashboard query classes only if a RED proves an existing collection is unavailable; do not invent metrics.

**Interfaces:**
- Existing M5 keys (`running_count`, `overdue_action_count`, `completed_without_assessment_count`, `draft_assessment_count`, `actions_due_soon`, recent finalized assessments) remain the data source.

- [ ] Add tests proving operational-priority sections are present and authorized executive links remain ability-gated.
- [ ] Verify RED against current hierarchy contract.
- [ ] Recompose dashboard using `x-attention-item` and consistent filter components without changing query truth.
- [ ] Validate empty states and mobile ordering.
- [ ] Full CI + ledger.

---

### Task 4: Scenario, template and history workspace

**Files:**
- Create: `tests/Feature/M7ScenarioWorkspaceTest.php`
- Modify: scenario index/show/create Blade views
- Modify: scenario-template index Blade view
- Modify: execution-history index Blade view
- Use: `x-table`, current status/badge/button/form components

**Interfaces:**
- Existing scenario/version/template/execution routes and abilities remain unchanged.
- Published-definition immutability remains enforced by M6.

- [ ] Add RED tests for explicit lifecycle language, real route CTAs, accessible empty states and consistent list markup.
- [ ] Verify RED.
- [ ] Implement unified workspace layouts without changing controllers unless data absence is proven.
- [ ] Run all feature/domain tests plus full CI.
- [ ] Ledger update.

---

### Task 5: Execution cockpit

**Files:**
- Create: `tests/Feature/M7ExecutionCockpitTest.php`
- Modify: `resources/views/executions/show.blade.php`
- Modify reusable components only when a cockpit requirement is broadly reusable.

**Interfaces:**
- Existing execution lifecycle forms/routes remain authoritative.
- Timeline remains append-only.
- Inject/resource/team/participant/assessment operations remain their current controller/service paths.

- [ ] Write RED tests asserting status/context/primary action hierarchy, timeline landmark, no event edit/delete controls, and clear assessment entry.
- [ ] Verify RED.
- [ ] Recompose cockpit around lifecycle header + timeline + operational side rail/context sections.
- [ ] Preserve all forms and CSRF/method semantics.
- [ ] Full CI + ledger.

---

### Task 6: Assessment & Debrief workbench

**Files:**
- Create: `tests/Feature/M7AssessmentWorkbenchTest.php`
- Modify: `resources/views/assessments/show.blade.php`
- Use: `x-section-nav`, existing assessment/action components.

**Interfaces:**
- Sections: `#summary`, `#rubric`, `#critical-errors`, `#key-times`, `#debrief`, `#action-plan`, `#finalize`.
- Finalized assessment content remains immutable.
- Action-item status transitions remain allowed only through existing transition route/state machine.

- [ ] Write RED tests for section navigation and frozen-state clarity.
- [ ] Add contract assertion that finalized views expose no content mutation forms while authorized action status transitions remain present.
- [ ] Verify RED.
- [ ] Recompose long page into anchored workbench without changing assessment calculation or finalization services.
- [ ] Full SQLite/PostgreSQL/Pint/build verification + ledger.

---

### Task 7: Management surfaces and local low-light preference

**Files:**
- Create: `tests/Feature/M7ManagementExperienceTest.php`
- Create: `tests/Feature/M7ThemeContractTest.php`
- Modify: people/organizations/access Blade index/detail/form views as needed
- Modify: `resources/css/app.css`
- Modify: `resources/js/app.js`
- Modify: topbar account/context control if required for local theme toggle
- Modify: `docs/DESIGN_SYSTEM.md`

**Interfaces:**
- Theme persistence uses browser-local state only; no DB schema/API.
- Light is default.
- Existing semantic colors remain meaningful in both presentations.

- [ ] RED tests for ability-aware management navigation and absence of fake account actions.
- [ ] RED/static contract for theme control and accessible state text.
- [ ] Implement local low-light theme with class/data attribute and Alpine/localStorage, guarding SSR/default light behavior.
- [ ] Audit management table/form consistency using design-system primitives.
- [ ] Full CI + ledger.

---

### Task 8: Forensic UX audit and exact-head gate

**Files:**
- Create: `docs/PHASE_M7_AUDIT.md`
- Modify: `docs/superpowers/sdd/m7-progress.md`
- Modify: `README.md` only for objectively stale product/setup facts exposed by M7/M6, not marketing expansion
- Modify: CI workflow only if a failing audit proves a missing quality gate

**Interfaces:**
- Final branch HEAD is the only SHA eligible for integration.

- [ ] Search all authenticated Blade navigation for `href="#"`, dead canonical routes and duplicate navigation landmarks.
- [ ] Review PR delta for accidental domain/controller/service/schema changes.
- [ ] Run/confirm `composer validate --strict`, migration fresh checks, full SQLite tests, full PostgreSQL tests, Pint and production Vite build through CI.
- [ ] Audit responsive/auth/tenant/finalized-state contracts through tests and source review.
- [ ] Fetch PR review threads/comments and resolve substantive findings.
- [ ] Update audit and ledger with exact workflow/run/job evidence.
- [ ] Mark PR ready only when exact HEAD is green and no Critical/High finding remains.
- [ ] Merge with merge commit and `expected_head_sha`; re-read `main` after merge.

## Self-review

- Spec coverage: all 8 design gates map one-to-one to implementation tasks.
- Domain safety: no task requires schema/service changes by default; any such change needs its own RED.
- Placeholder scan: no TODO/TBD implementation step remains.
- Type/interface consistency: component names and section anchors are defined once and reused consistently.
- Verification: every task includes RED evidence and complete CI promotion.
