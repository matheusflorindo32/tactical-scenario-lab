# R2 D2 Hero + Public Header Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Implement the approved D2 evidence-based public header and landing hero without changing any other landing section or backend contract.

**Architecture:** Keep Laravel Blade, Tailwind CSS v4, Alpine.js, Vite, and the existing component system. Extend `x-brand` so each surface owns its valid destination, make the public layout own one accessible responsive header, and replace only the current first landing section with a semantic D2 hero whose product panel is explicitly demonstrative.

**Tech Stack:** Laravel 13, Blade, Tailwind CSS v4, Alpine.js 3, PHPUnit 12, Playwright.

## Global Constraints

- Modify only the public header, brand component, hero, strictly related CSS/JavaScript, focused tests, and this plan.
- Do not change database, migrations, models, authentication behavior, tenancy, policies, health probes, providers, runtime, workflows, or Vercel configuration.
- Do not redesign the approved D2 direction or implement other landing sections.
- Do not add dependencies, routes, unverified claims, customer statistics, or decorative operational graphics.
- Preserve `prefers-reduced-motion`, focus visibility, native elements, and WCAG 2.2 AA authoring requirements.
- Produce one cohesive implementation commit only after all required verification is green.

---

### Task 1: Protect the D2 public contract

**Files:**
- Create: `tests/Feature/R2PublicHeroHeaderTest.php`
- Modify: `resources/views/components/brand.blade.php`
- Modify: `resources/views/layouts/public.blade.php`
- Modify: `resources/views/components/layouts/public.blade.php`
- Modify: `resources/views/welcome.blade.php`

**Interfaces:**
- Consumes: named routes `home` and `login`, existing `x-brand`, `x-button`, and the `/` public route.
- Produces: one valid brand link per header, D2 copy, existing-route CTA targets, semantic hero IDs, and an accessible mobile-menu contract.

- [ ] **Step 1: Write failing public behavior tests**

Add HTTP-level assertions that `/` returns 200, contains the approved eyebrow/headline/supporting copy, links the primary CTA to `#recursos`, links the access CTA to `route('login')`, exposes a mobile-menu button with `aria-controls` and `aria-expanded`, identifies demonstration data, and never emits an anchor immediately inside another anchor.

- [ ] **Step 2: Run the focused test and verify RED**

Run: `php artisan test tests/Feature/R2PublicHeroHeaderTest.php`

Expected: FAIL because the D2 copy, CTA targets, mobile navigation contract, and valid public brand structure do not exist yet.

- [ ] **Step 3: Make the brand destination explicit**

Add `href` and `label` props to `x-brand`, defaulting to the existing scenarios destination and accessible label. Render exactly one anchor inside the component. Pass `route('home')` and a home-specific label from both public layouts; do not wrap the component in another anchor.

- [ ] **Step 4: Implement the public header contract**

Use one sticky, restrained header with valid in-hero navigation only: `Visão geral`, `Como funciona`, and `Recursos`; keep `Acessar` on `route('login')`. Add a native button for mobile with `aria-expanded`, `aria-controls`, Escape handling, outside-click dismissal, visible focus, and 44px target size. Do not create a fake `Segurança` destination while that content is outside scope.

- [ ] **Step 5: Implement only the approved D2 hero**

Replace the first `welcome.blade.php` section with `#visao-geral`, approved D2 copy, `Conhecer a plataforma` pointing to the demonstrative product resources at `#recursos`, `Acessar o ambiente` pointing to `route('login')`, audience text, and a product-native demonstration panel. Map `#como-funciona` to the compact lifecycle and `#recursos` to the demonstrative product metrics. Keep every later landing section unchanged.

- [ ] **Step 6: Run the focused test and verify GREEN**

Run: `php artisan test tests/Feature/R2PublicHeroHeaderTest.php`

Expected: PASS with all public contracts protected.

---

### Task 2: Deliver D2 visual behavior without dependencies

**Files:**
- Modify: `resources/css/app.css`
- Modify: `resources/views/layouts/public.blade.php`
- Modify: `resources/views/components/layouts/public.blade.php`
- Modify: `tests/Browser/r2-public.spec.js`

**Interfaces:**
- Consumes: D2 semantic IDs and Alpine loaded by `resources/js/app.js`.
- Produces: responsive D2 composition, scroll-state header treatment, accessible mobile navigation, reduced-motion-safe transitions, and browser QA coverage.

- [ ] **Step 1: Write failing browser tests**

Create `tests/Browser/r2-public.spec.js` to verify the desktop navigation, mobile menu open/close/Escape flow, no horizontal overflow at 390×844, and `prefers-reduced-motion` support on `/`.

- [ ] **Step 2: Run the focused browser test and verify RED**

Run: `npx playwright test tests/Browser/r2-public.spec.js --project=chromium`

Expected: FAIL because the D2 public selectors and mobile behavior do not exist.

- [ ] **Step 3: Add focused D2 presentation styles**

Add component classes under `@layer components` for the public header surface, hero grid, restrained technical background, product demonstration card, metric cells, lifecycle line, and responsive mobile composition. Reuse existing Navy/Stone/Ink/semantic tokens and existing motion reduction; do not add images, charts, or dependencies.

- [ ] **Step 4: Verify focused browser GREEN**

Run: `npx playwright test tests/Browser/r2-public.spec.js --project=chromium`

Expected: PASS with mobile navigation, overflow, and reduced-motion behavior confirmed.

---

### Task 3: Verify, audit, commit, and publish the isolated change

**Files:**
- Verify all files changed by Tasks 1–2.
- Create screenshots outside Git under `C:/Users/mathe/AppData/Local/Temp/tsl-r2-d2/`.

**Interfaces:**
- Consumes: the completed D2 implementation.
- Produces: fresh quality evidence, one commit, one non-force push, CI status, and Preview status when automatically available.

- [ ] **Step 1: Run focused and full verification**

Run in order:

```text
php artisan test tests/Feature/R2PublicHeroHeaderTest.php
composer test
vendor/bin/pint --test
npm run build
npx playwright test tests/Browser/r2-public.spec.js --project=chromium
```

Every command must exit 0 before commit.

- [ ] **Step 2: Perform browser QA and screenshots**

Validate 390×844, 768×1024, 1440×900, and 1920×1080. Record console errors, page errors, request failures, mixed content, asset failures, overflow, keyboard navigation, mobile menu behavior, and reduced motion. Save screenshots outside the repository.

- [ ] **Step 3: Audit the diff and protected paths**

Run:

```text
git status
git diff --check
git diff
```

Explicitly verify no changes under `database/`, models, auth controllers/requests, tenancy, policies, health, `.github/workflows/`, `vercel.json`, or provider/runtime configuration.

- [ ] **Step 4: Create the authorized cohesive commit**

Stage only the plan, focused tests, and Header/Hero implementation. Commit with:

```text
feat(r2): implement evidence-based hero and public header
```

- [ ] **Step 5: Push without force and observe external gates**

Push only to `origin feature/r2-frontend-rebuild`. Do not alter PR base/readiness, merge, Production, Deployment Protection, or any Vercel project. Observe existing CI and automatic Preview only; report if either does not start.
