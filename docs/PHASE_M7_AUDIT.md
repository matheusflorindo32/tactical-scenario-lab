# Phase M7 Audit — Operational Command Center

Date: 2026-08-09
Branch: `feature/m7-operational-command-center`
PR: #10
Scope: M7 UX/UI, information architecture and design-system evolution only
Status: final CI candidate pending at the time this document is written

## 1. Audit objective

Verify that the M7 redesign materially improves operational information architecture without weakening the domain, tenant, authorization, historical-integrity or database guarantees established before M7.

The audit treats test/build evidence as authoritative for executable contracts and source review as authoritative for scope/delta inspection.

## 2. Delta against `main`

At the final-audit candidate preparation point:

- branch was **40 commits ahead** of `main`;
- branch was **0 commits behind** `main`;
- merge base was `eedcfb8f67595f8277e58909541c1da3a5921eda`;
- no migration, model or service file was changed by M7;
- the only production PHP file outside views/assets was `app/Http/Controllers/ScenarioController.php`;
- that controller delta is read-side only: it uses the existing `AccessAbility` constant, reads current organization access, eager-loads `latestVersion` with execution count and exposes `canManage` to the view;
- scenario create/store/show/execute write behavior was not changed by the M7 controller delta.

Primary change classes:

- Blade layout/components and authenticated views;
- Tailwind design tokens/low-light selectors;
- Alpine browser-local theme store;
- M7 feature/contract tests;
- design/product documentation.

## 3. Invariants reviewed

### Tenant and authorization

- canonical navigation is ability-aware;
- backend authorization remains authoritative;
- no free client-controlled tenant selector was introduced;
- management and reporting routes continue using existing controllers/abilities;
- hiding or showing a control is not treated as an authorization boundary.

### Version and history truth

- scenario workspace is based on the latest `ScenarioVersion` presentation rather than `Scenario.score` legacy presentation;
- published-definition behavior is unchanged;
- execution timeline is explicitly presented as append-only historical truth;
- finalized assessment content is explicitly presented as frozen;
- authorized action-item status transitions remain available after finalization without reopening action content.

### Database/runtime hardening

M7 did not alter:

- structural-integrity migrations;
- PostgreSQL immutability triggers;
- runtime-role provisioning code;
- concurrency managers/locks;
- production configuration validator/preflight.

The existing CI PostgreSQL job continues to rebuild migrations, provision the least-privilege runtime role, rollback/reapply M6 guard migrations and repeat concurrency invariants three times before the full PostgreSQL suite.

## 4. Information-architecture audit

### Shell

- sidebar is the canonical application navigation;
- topbar is contextual rather than a duplicated navigation bar;
- active organization is exposed in the topbar;
- account actions use real controls/routes;
- placeholder `Guias`/`Referência` entries were removed from M7 and remain reserved for future documentation work.

### Dashboard

Instructor attention order:

1. running executions;
2. overdue actions;
3. completed executions without assessment;
4. draft assessments;
5. actions due soon;
6. recently finalized assessments.

The reordering uses existing M5 collections/counts and does not invent a new operational metric.

### Scenario workspace

Lifecycle is explicit:

`Rascunho → Publicado → Preparar → Executar → Avaliar → Histórico`

The old scenario-list presentation of legacy score as an assessment average was removed.

### Execution cockpit

The execution page exposes anchored regions for lifecycle, timeline, teams, resources, injects and assessment. Timeline precedes configuration context and is labelled as historical/append-only.

### Assessment workbench

Draft assessments expose anchored sections for summary, rubric/evidence, critical errors, key times, debrief, action plan and finalization. Finalized assessments omit the finalization control, present frozen-state language and retain only authorized action-status follow-up.

### Management

People, Organizations and Access indexes now share canonical Tactical Scenario Lab branding, navigation, badges and `x-table` semantics.

## 5. Accessibility and interaction audit

Automated/source contracts verify:

- skip link to `#main` remains present;
- canonical navigation uses `aria-current="page"`;
- workbench anchor navigation uses native links and `aria-current="location"` when current;
- mobile menu controls have accessible names;
- primary shell controls use 44 px touch-target sizing where introduced by M7;
- tables expose accessible labels and column headers;
- theme toggle exposes an accessible name and `aria-pressed` state;
- status remains textually identifiable and is not color-only;
- `prefers-reduced-motion` remains enforced in CSS;
- M7 canonical authenticated views contain no `href="#"` or `href='#'` placeholder navigation;
- targeted legacy/undefined token families are absent from canonical M7 authenticated views.

Authored UI target: WCAG 2.2 AA.

## 6. Low-light theme audit

The M7 low-light mode:

- defaults to light in SSR via `data-theme="light"`;
- is opt-in through the authenticated topbar;
- uses Alpine store state;
- reads/writes only `localStorage['tsl-theme']`;
- updates `document.documentElement.dataset.theme`;
- does not call `fetch` or Axios for theme persistence;
- creates no database/API contract;
- preserves semantic emergency/clinical/alert families with low-light surfaces.

## 7. Source hygiene searches

Repository searches performed during Gate 8 returned no matches for:

- canonical view `href="#"` placeholders;
- single-quoted `href='#'` placeholders;
- `Tactical Medicine Academy` branding under `resources/views`;
- targeted legacy/undefined classes such as `ink-200`, `ink-600`, `ink-800`, `emergency-800`, `clinical-800` and generic `amber-*` under the canonical views;
- `TODO`, `TBD` or `FIXME` in the M7 view search;
- `Scenario.score` presentation search in M7 scenario views.

A dedicated `M7ForensicUiContractTest` makes the critical hygiene/documentation assertions executable in CI.

## 8. Documentation audit

`docs/DESIGN_SYSTEM.md` was updated to the implemented M7 state:

- `x-table`, `x-section-nav` and `x-attention-item` are documented as implemented;
- low-light is documented as browser-local and server-independent;
- the accessibility target is stated as WCAG 2.2 AA;
- the roadmap no longer lists already-delivered M7 primitives as pending.

`README.md` was refreshed because the previous file objectively described an obsolete MVP state. It now reflects:

- PostgreSQL as the production database and SQLite as local/regression support;
- Blade + Tailwind CSS v4 + Alpine.js;
- dual-database CI and current quality gates;
- current route families instead of removed legacy assessment routes;
- the execution/assessment/history architecture and institutional invariants.

## 9. TDD/CI evidence before Gate 8

- Gate 1: RED CI #734 → GREEN CI #736.
- Gate 2: RED CI #737 → GREEN CI #740.
- Gate 3: RED CI #741 → GREEN CI #743.
- Gate 4: RED CI #745 → GREEN CI #749.
- Gate 5: RED CI #751 → GREEN CI #752.
- Gate 6: RED CI #754 → GREEN CI #755.
- Gate 7: first RED attempt #758 discarded because the test itself failed Pint; valid RED CI #759 → GREEN CI #766.

For each GREEN gate, the promoted matrix included SQLite, PostgreSQL 16 and Pint; the PostgreSQL job also retained the M6 hardening/concurrency sequence.

## 10. Pull-request audit

At pre-final review:

- PR #10 is draft and mergeable;
- no PR discussion/review comments were present;
- M7 is isolated from `main`;
- the branch was 0 commits behind `main` at the audit comparison point;
- M8 Wiki/documentation-product scope and M9 release scope were not introduced.

Before integration, these conditions must be re-read on the exact final HEAD.

## 11. Visual-validation limitation

This connected execution environment supports source inspection, GitHub Actions and rendered-HTML/contract testing, but it does not provide an authenticated interactive browser session for a pixel-by-pixel visual pass of the running application.

Therefore:

- semantic DOM, accessibility contracts, responsive primitives, build validity and regression behavior are automated/verified;
- pixel-level browser composition across specific viewport/device combinations is **not claimed as directly observed** in this audit.

This is a validation limitation, not a hidden assumption. It does not weaken the database/domain/security evidence, but a future human/browser visual QA pass can still improve cosmetic confidence without changing the M7 domain contract.

## 12. Final integration gate

M7 may be merged only when all of the following are freshly true on the exact branch HEAD:

- `composer validate --strict` succeeds in CI;
- production frontend build succeeds;
- `php artisan migrate:fresh --force` succeeds;
- full SQLite suite succeeds;
- full PostgreSQL 16 suite succeeds;
- least-privilege runtime role provisioning succeeds;
- M6 guard rollback/reapply succeeds;
- repeated M6 concurrency invariants succeed;
- Pint succeeds;
- PR remains mergeable and 0 commits behind `main`;
- no unresolved substantive review finding exists;
- merge uses a protected exact expected head SHA.

No Critical/High M7 finding is identified by the source/contract audit at this candidate stage. Final completion is contingent on the fresh exact-head gate above.
