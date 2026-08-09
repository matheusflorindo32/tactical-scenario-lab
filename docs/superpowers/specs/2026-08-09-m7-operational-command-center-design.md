# M7 — Operational Command Center Design

Date: 2026-08-09
Status: Approved for implementation
Branch: `feature/m7-operational-command-center`

## 1. Objective

Evolve Tactical Scenario Lab from a functionally mature institutional product into an operational command-center experience for instructors, evaluators and administrators, without changing the domain truth established by M1–M6.

M7 is a UX/UI and information-architecture milestone. It MUST preserve tenant isolation, authorization abilities, immutable historical truth, PostgreSQL hardening, concurrency guarantees and reporting semantics.

## 2. Design direction

Use the existing Laravel 13 + Blade + Tailwind CSS v4 + Alpine.js architecture. Do not rewrite the product as a SPA.

The interface should feel institutional, precise, low-friction and operational. It must avoid entertainment-style visual language, decorative gradients, neon, gratuitous animation or dashboard density that hides critical actions.

Primary principle: **attention before decoration**.

The product should help the user answer, in order:

1. What requires attention now?
2. What operation am I in?
3. What can I safely do next?
4. What is frozen historical truth versus editable operational state?
5. How do I return to the relevant context with minimal navigation cost?

## 3. Global constraints

- Preserve Blade + Tailwind CSS v4 + Alpine.js.
- Preserve all M1–M6 domain and database invariants.
- No new clinical or tactical domain rules in M7.
- No schema change unless a failing behavioral test proves it is strictly necessary.
- No mandatory third-party frontend dependency.
- Portuguese (Brazil) remains the UI language.
- Keep current semantic color families: navy, stone, ink, emergency, clinical and alert.
- Meet WCAG 2.2 AA for relevant authored UI: keyboard, focus, labels, contrast, target sizes, reduced motion and responsive behavior.
- M8 Wiki/documentation and M9 release work are explicitly out of scope.
- Placeholder navigation links must not remain visible.
- Critical destructive transitions remain explicit forms and keep existing authorization/domain checks.

## 4. Information architecture

### 4.1 Global topbar

The topbar is global context, not a second copy of the sidebar.

It contains:
- mobile navigation trigger;
- brand;
- active organization context when available;
- one contextual primary action when appropriate;
- account/session menu.

It must not duplicate the complete application navigation.

### 4.2 Sidebar

The sidebar becomes the canonical application navigation.

Sections:

**Operação**
- Painel
- Cenários
- Templates
- Histórico

**Análise**
- Visão executiva, when the current access grants `reports.view`
- Export/report entry points only when they are meaningful routes, never placeholders

**Gestão**
- Pessoas
- Organizações
- Acessos, only when authorized

Remove the current placeholder entries `Guias` and `Referência`; these belong to M8.

Every navigable item must resolve to a real route. Active state uses `aria-current="page"`.

### 4.3 Page shell

Keep skip-link, responsive drawer behavior and visible focus. Improve hierarchy with:
- stable contextual header region;
- optional action region;
- consistent content width;
- reduced duplicate breadcrumbs/navigation copy;
- responsive spacing from mobile through large desktop.

## 5. Design system evolution

Keep existing design tokens as the source of truth. Add only tokens justified by reusable behavior.

M7 introduces:
- `x-table`: accessible responsive data table/list primitive with empty-state support;
- `x-section-nav`: compact contextual navigation for long operational workbenches;
- `x-attention-item`: standardized operational attention item for urgent/pending work;
- optional surface tokens/classes for low-light institutional theme.

Components must accept semantic props and avoid hard-coded business-specific text where reuse is intended.

## 6. Dashboard — attention model

The instructor dashboard continues to use existing M5 reporting/query truth.

Priority order:
1. Running executions.
2. Overdue action items.
3. Completed executions without assessment.
4. Draft assessments.
5. Actions due soon.
6. Recently finalized assessments.

No new metric is invented in M7. Existing counts and collections are reorganized into a clearer attention model.

Filters remain GET-based and tenant-safe. Their UI becomes consistent with design-system form patterns.

## 7. Scenario workspace

Scenario and template surfaces should communicate the lifecycle explicitly:

`draft → publish/version → prepare → execute → assess → history`

Rules:
- published definition remains immutable;
- revise creates a draft using existing domain behavior;
- creation, publication, template use and execution actions remain permission-gated;
- tables/lists use the same filter and state language;
- empty states guide the next valid action without fabricating capabilities.

## 8. Execution cockpit

The execution page becomes the operational center of M7.

Top region:
- scenario identity;
- execution sequence/version;
- state;
- start/completion timestamps;
- authorized primary lifecycle action;
- destructive cancel kept visually distinct.

Main workbench:
- timeline as the primary chronological truth;
- injects and resources as operational controls;
- teams/participants as execution context;
- assessment/debrief entry clearly surfaced;
- append-only historical events visually distinguished from editable controls.

The redesign must not allow editing of immutable events or bypass M6 guards.

## 9. Assessment & Debrief workbench

The assessment page remains one institutional record but is reorganized into sections:
- Resumo;
- Rubrica e evidências;
- Erros críticos;
- Tempos-chave;
- Debrief;
- Plano de ação;
- Finalização.

A contextual section navigator may use anchors; no SPA router is required.

For draft assessments, actionable sections show what is incomplete. For finalized assessments, immutable content must visually read as historical/frozen. Authorized action-item status transitions remain available exactly as allowed by M6.

## 10. Management/admin surfaces

People, organizations and access administration use the same shell, form and table primitives.

Rules:
- abilities determine visibility and actions;
- hidden navigation is not a substitute for backend authorization;
- destructive account/access operations require explicit labels and do not become one-click ambiguous controls;
- historical person/organization context continues to be represented by existing domain snapshots.

## 11. Low-light institutional theme

M7 may add an optional low-light presentation based on navy surfaces and existing semantic colors.

Constraints:
- light mode remains the default;
- no database persistence is required;
- preference may use local browser state;
- semantic meaning cannot depend on color alone;
- contrast must remain WCAG 2.2 AA;
- charts or third-party visualization dependencies are not introduced solely for theme support.

## 12. Accessibility and responsive acceptance

Required:
- keyboard access to all interactive elements;
- visible focus;
- skip link preserved;
- mobile drawer closes predictably and Escape works;
- no placeholder anchors (`href="#"`) in canonical application navigation/account actions;
- touch targets at least 44 CSS px for primary mobile controls where practical;
- `prefers-reduced-motion` remains respected;
- page headings remain logically ordered;
- tables have headers/captions or accessible labels and responsive fallback;
- status is communicated by text, not color alone.

## 13. Gates

### Gate 1 — Shell & navigation
Canonical sidebar, contextual topbar, no placeholder nav, route-correct active states, accessibility regression tests.

### Gate 2 — Design-system primitives
Add table, section navigation and attention-item primitives with component tests/usages.

### Gate 3 — Instructor & executive dashboards
Reorganize existing M5 data into attention-first layouts without metric changes.

### Gate 4 — Scenarios, templates & history
Unify lifecycle, filtering, lists and empty states.

### Gate 5 — Execution cockpit
Rework execution page hierarchy around lifecycle, timeline and operational controls.

### Gate 6 — Assessment & debrief workbench
Section navigation, frozen-state clarity, completion cues and action-plan ergonomics.

### Gate 7 — Management & low-light polish
People, organizations, access surfaces; optional local low-light theme; responsive consistency.

### Gate 8 — Forensic UX audit & release gate
Full dual-database tests, Pint, production asset build, route/navigation audit, accessibility contract checks, exact-head CI and PR audit.

## 14. Success criteria

M7 is complete only when:
- all current domain tests remain green on SQLite and PostgreSQL;
- new M7 behavior has test-first evidence;
- no M1–M6 invariant is weakened;
- canonical navigation contains no dead or placeholder entries;
- the dashboard prioritizes operational attention using existing truth;
- execution and assessment workflows are easier to scan without hiding critical actions;
- responsive and keyboard behavior have automated contract coverage where feasible;
- final exact HEAD passes the repository CI before merge.
