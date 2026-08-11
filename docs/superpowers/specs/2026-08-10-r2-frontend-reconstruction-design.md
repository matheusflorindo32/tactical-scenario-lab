# R2 — Frontend Reconstruction & Visual QA — Design

Date: 2026-08-10
Status: design specification for user review
Base candidate: `b7ce97461523deb63ed8d2a4b2062292ce33a19c`
Working branch: `feature/r2-frontend-rebuild`
Production boundary: no merge or Production promotion is authorized by this specification.

## 1. Objective

Reconstruct the Tactical Scenario Lab frontend to a production-grade institutional standard while preserving the already validated R1 backend, database, tenancy, authorization, health, recovery, CI and provider contracts.

The product must communicate:

- institutional trust;
- clinical precision;
- tactical sobriety;
- operational command;
- fast information scanning;
- explicit state and risk hierarchy;
- accessibility and responsive behavior;
- visual consistency across public and authenticated surfaces.

The target is not a generic SaaS template, gaming aesthetic, tactical caricature, decorative neon, gratuitous gradients, or framework rewrite.

## 2. Evidence-based Gate 0 finding — root cause before redesign

The user-provided four-page PDF captures the current public landing page in a near-unstyled state: default browser link styling, oversized brand SVG, missing intended grid/card composition and default-looking typography.

Repository inspection shows that the source is not intentionally unstyled:

- `resources/views/layouts/public.blade.php` loads `resources/css/app.css` and `resources/js/app.js` via `@vite`;
- `resources/css/app.css` imports Tailwind CSS v4 and defines the product tokens/components;
- `resources/views/welcome.blade.php` already contains Tailwind layout, spacing, typography, grid and card classes;
- `resources/views/components/brand.blade.php` explicitly constrains the brand icon using `h-5 w-5` inside an `h-10 w-10` wrapper;
- `vercel.json` contains a static `/build/(.*)` route before the PHP catch-all.

The exact validated Vercel Preview HTML was fetched through the authenticated Vercel connector. It emits Vite CSS and JS URLs with the `http://` scheme while the page itself is served over HTTPS, for example:

- `http://<preview-host>/build/assets/app-BiTk_niV.css`
- `http://<preview-host>/build/assets/app-D1BTLSle.js`

The same HTML also emits named-route URLs using `http://`.

This is a deterministic mixed-content condition. Modern HTTPS browsers block active mixed content such as HTTP scripts and stylesheets. The visual failure therefore must be treated first as a request/proxy scheme detection defect, not as proof that the Tailwind design itself is absent.

`bootstrap/app.php` currently defines aliases only and does not configure trusted proxies. Laravel 13 documentation states that applications behind TLS-terminating cloud/load-balancer proxies may generate HTTP links unless trusted proxies are configured; it documents `trustProxies` in `bootstrap/app.php`, including trusting `X-Forwarded-Proto` and related forwarded headers.

Official references:

- https://laravel.com/docs/13.x/requests#configuring-trusted-proxies
- https://laravel.com/docs/13.x/urls

### Gate 0 design decision

Fix proxy/scheme awareness first and prove that the exact Preview HTML emits HTTPS asset URLs. Do not redesign around a broken asset pipeline.

The preferred solution is framework-native trusted-proxy configuration scoped to the cloud deployment topology. A blanket `URL::forceScheme('https')` is a fallback, not the first choice, because request proxy semantics should be correct rather than globally overridden without need.

## 3. Approaches considered

### Approach A — infrastructure-first reconstruction (selected)

1. repair HTTPS/proxy asset URL generation;
2. prove CSS/JS load correctly in real Preview;
3. take visual baseline screenshots;
4. refine the existing design system;
5. reconstruct public and authenticated surfaces incrementally;
6. run visual, browser, accessibility and regression gates.

Advantages: isolates the real defect, preserves architecture, minimizes regressions, supports TDD, and produces trustworthy before/after evidence.

Trade-off: slower than immediately changing markup, because each visual change is gated.

### Approach B — redesign immediately on current source (rejected)

Change `welcome.blade.php`, components and CSS before fixing asset delivery.

Rejected because a beautiful source still renders broken if the browser cannot load the stylesheet, and visual debugging becomes ambiguous.

### Approach C — migrate frontend framework (rejected)

Replace Blade/Tailwind/Alpine with React/Vue/another SPA stack.

Rejected because R1 already validates the current Laravel architecture; a framework migration would enlarge scope, alter runtime contracts and add risk without evidence that it is necessary.

## 4. Architecture boundaries

### Preserved without explicit approval

R2 must not change the semantics of:

- database schema or migrations;
- Neon role grants or runtime credentials;
- tenancy and organization isolation;
- authentication/session behavior;
- authorization, policies, abilities and route access;
- execution lifecycle/business rules;
- assessment/debrief invariants;
- health endpoint semantics;
- recovery documentation or R1 failure drill;
- Vercel Production configuration;
- Production database/secrets;
- R1 branch or frozen R1 candidate.

If a backend change becomes genuinely necessary for a UI requirement, implementation stops and records the proposed contract change separately.

### Frontend technologies retained

- Laravel Blade;
- Tailwind CSS v4;
- Alpine.js where already appropriate;
- existing Blade component system;
- Vite asset pipeline;
- Playwright for browser testing.

## 5. Design language

The existing `docs/DESIGN_SYSTEM.md` remains the source of truth and will be evolved rather than bypassed.

### Visual principles

1. Operational attention before decoration.
2. Navy/Stone/Ink as the institutional base.
3. Emergency/Clinical/Alert only for semantic state.
4. Strong type hierarchy and restrained shadows.
5. Dense operational data may be compact, but never illegible.
6. Critical, attention, active and informational states must be visually distinguishable without depending only on color.
7. Low-light remains an operational presentation mode, not a gaming theme.
8. Responsive behavior is designed, not merely wrapped.

### Public landing information architecture

The landing page should answer, in order:

1. What is Tactical Scenario Lab?
2. What operational problem does it solve?
3. How does the simulation lifecycle work?
4. What can an instructor do with it?
5. Who is it for?
6. What is the educational/simulation safety boundary?
7. What is the primary action?

Hero message direction:

**Tactical Scenario Lab**

Simulação estruturada. Execução controlada. Avaliação objetiva. Debriefing orientado à melhoria.

Lifecycle visualization:

`PLANEJAR → EXECUTAR → AVALIAR → DEBRIEFAR → MELHORAR`

The hero should use product-native UI motifs rather than stock military imagery.

## 6. Authenticated Operational Command Center

The authenticated shell should preserve the existing canonical sidebar/topbar architecture and improve hierarchy, spacing and consistency.

Dashboard prioritization:

1. critical / blocked;
2. needs attention;
3. active work;
4. informational metrics.

Primary authenticated surfaces in R2 scope:

- `/login`;
- `/dashboard`;
- `/dashboard/executive`;
- `/scenarios`;
- scenario creation/show workflow;
- scenario templates;
- execution history;
- Knowledge Center hub/reader;
- people;
- organizations;
- access administration;
- execution cockpit;
- assessment;
- debrief/action items;
- representative 403/empty/error states where the application already exposes them.

No hidden or unauthorized action may be surfaced merely for visual completeness.

## 7. Gate model

### Gate 0 — HTTPS asset delivery and baseline integrity

Required proof:

- exact Preview HTML emits `https://` for Vite CSS/JS;
- CSS asset is reachable under authenticated Preview access;
- JS asset is reachable under authenticated Preview access;
- asset Content-Type is correct;
- no mixed-content asset URLs remain;
- brand SVG respects intended dimensions when stylesheet is applied;
- no regression to liveness/readiness.

TDD requirement:

Add a contract test that fails against the current configuration and proves trusted HTTPS proxy scheme handling. The test must be RED before the fix and GREEN after the minimal fix.

### Gate 1 — visual inventory

For every scoped surface, classify:

- GREEN;
- NEEDS WORK;
- BROKEN.

Capture baseline evidence at representative viewports.

### Gate 2 — design system consolidation

Audit and normalize:

- tokens;
- typography;
- spacing;
- radii;
- shadows;
- buttons;
- cards;
- badges/status pills;
- alerts;
- fields/forms;
- tables;
- empty states;
- modal/dropdown;
- sidebar/topbar;
- breadcrumbs;
- timeline/progress/score;
- toast;
- low-light.

Reusable decisions must be reflected in `docs/DESIGN_SYSTEM.md`.

### Gate 3 — public surfaces

Reconstruct landing and login after Gate 0 is GREEN.

The landing should use clear anchors/sections that match its navigation. Existing header links may not point to missing section IDs.

### Gate 4 — authenticated surfaces

Refine operational shell and all listed authenticated surfaces without changing business semantics.

### Gate 5 — responsive and accessibility

Mandatory viewport matrix:

- 390 × 844;
- 768 × 1024;
- 1440 × 900.

Also verify a wide desktop layout where dense tables/cockpit views require it.

Accessibility target: WCAG 2.2 AA for application-controlled surfaces.

Required checks include keyboard navigation, skip link, focus-visible, heading structure, labels, contrast, reduced motion, table overflow, dropdown/modal behavior and low-light legibility.

### Gate 6 — visual QA

Required browsers:

- Chromium;
- Firefox.

Reject:

- overflow/clipping;
- uncontrolled layout shift;
- oversized SVG/icons;
- overlapping controls;
- inconsistent buttons/fields;
- broken grids;
- unusable mobile tables;
- unreadable low-light states.

Capture AFTER evidence for major surfaces and compare against baseline.

### Gate 7 — regression safety

Required exact-head validation:

- Composer security audit;
- npm security audit at repository policy threshold;
- frontend build;
- PHPUnit SQLite;
- PHPUnit PostgreSQL 16;
- existing PostgreSQL failure/recovery drill;
- Chromium + Firefox Playwright suite;
- Pint;
- exact Vercel Preview deployment admission;
- runtime error/warning/fatal query after hosted QA.

Tests may not be weakened to obtain GREEN status.

### Gate 8 — R2 release candidate

R2 stops with:

- open PR;
- exact frozen HEAD;
- exact Vercel Preview;
- before/after evidence;
- complete test matrix;
- residual-risk statement;
- rollback instructions.

No merge and no Production promotion without a separate explicit user authorization.

## 8. Testing strategy

### Contract tests

The first implementation change is a regression contract for HTTPS proxy handling. It must assert that when the application receives the trusted forwarded HTTPS signal, generated URLs and Vite/asset URLs are HTTPS.

### Browser tests

Existing browser tests remain mandatory. R2 may add focused tests for:

- landing navigation anchors;
- loaded stylesheet effect or stable computed dimensions for the brand icon;
- responsive navigation behavior;
- focus order and keyboard access;
- representative authenticated shell behavior.

Browser tests should verify product behavior and critical visual invariants, not brittle pixel-perfect implementation details.

### Visual evidence

Screenshots are review evidence, not the sole automated test. Major before/after captures should be stored as CI artifacts or attached to the PR rather than treated as production assets.

## 9. Error handling and rollback

### Asset fix rollback

The HTTPS proxy fix must be isolated in a small commit after its RED test. If it causes unexpected host/scheme behavior, revert that commit independently without discarding later design work.

### Frontend rollback

Visual work should be separated into coherent commits by layer/surface so individual regressions can be reverted without touching backend state.

### Provider rollback

R2 uses Preview only. Production remains untouched. If a Preview deployment fails, previous R1 candidate `b7ce97461523deb63ed8d2a4b2062292ce33a19c` remains the known validated technical baseline.

## 10. Evidence package required at completion

The final R2 audit must report:

1. branch;
2. exact HEAD SHA;
3. commits;
4. changed files;
5. Gate 0 root-cause proof;
6. before screenshots;
7. after screenshots;
8. audited route matrix;
9. browser matrix;
10. responsive matrix;
11. accessibility findings;
12. tests and exact conclusions;
13. CI URL;
14. exact Vercel Preview/Inspector URL;
15. runtime log result;
16. residual risks;
17. rollback procedure;
18. percentage by gate.

Percentages are transparent heuristic progress indicators, not provider-native metrics.

## 11. Role split if Kimi K3 is used

Kimi K3 may act as the implementation executor on `feature/r2-frontend-rebuild` only after this specification and the implementation plan are approved.

Independent audit remains separate:

- verify diffs rather than trusting completion statements;
- rerun exact-head tests;
- validate exact Vercel Preview;
- compare before/after visual evidence;
- stop before merge/Production.

## 12. Success criteria

R2 is successful only when:

- the mixed-content asset defect is proven fixed in a real protected Preview;
- the application renders its intended design system rather than browser-default HTML;
- public and authenticated surfaces are visually coherent and operationally legible;
- responsive/accessibility gates are supported by evidence;
- all preserved R1 technical contracts remain GREEN;
- no Production action has occurred without explicit authorization.
