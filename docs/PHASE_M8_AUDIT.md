# Phase M8 Audit — Knowledge & Documentation Center

Date: 2026-08-09
Branch: `feature/m8-knowledge-center`
PR: #11
Scope: authenticated repository-backed product knowledge, discovery, contextual help and governance
Status: final CI candidate pending at the time this document is written

## 1. Audit objective

Verify that M8 introduces a useful in-product knowledge layer without creating a second clinical/domain source of truth, weakening M1–M7 security/history guarantees or introducing unnecessary runtime persistence.

The audit treats executable test/build evidence as authoritative for code contracts and source/delta review as authoritative for scope and architecture inspection.

## 2. Architectural result

M8 implements a Git-versioned, application-rendered Knowledge & Documentation Center.

Runtime architecture:

- `config/knowledge.php` is the application-controlled catalog;
- `resources/knowledge/articles/*.md` contains global product-guide Markdown;
- `KnowledgeRepository` performs exact slug/context lookup, secure source confinement, safe rendering and deterministic search;
- `KnowledgeArticle` is a readonly normalized view model;
- `KnowledgeController` exposes authenticated Hub and Article reads;
- Blade surfaces render the Hub, article reader and contextual help;
- no CMS is introduced;
- no WYSIWYG editor or content-write endpoint exists;
- no M8 database table, migration or reading-state persistence exists;
- no AI/RAG, embedding, vector database or autonomous answer generation is introduced;
- no autonomous clinical or tactical recommendation engine is introduced.

The knowledge layer explains Tactical Scenario Lab behavior and invariants. It does not replace institutional protocol, certified training or professional decision-making.

## 3. Delta against `main`

At the Gate 8 pre-final audit point, the branch remained based on M7 main commit `7d80355ebf4ee6a09ec4026a80ca0ee8bdb16c58` and had been confirmed 0 commits behind `main` during pre-audit comparison.

M8 does not change:

- database migrations;
- Eloquent models;
- scenario/execution/assessment domain services;
- PostgreSQL structural/immutability guards;
- production configuration validation;
- runtime-role provisioning;
- reporting truth;
- M6 concurrency managers/locks.

Primary production changes are limited to:

- read-only knowledge controller/repository/view model;
- allowlisted knowledge config;
- authenticated GET routes;
- Knowledge Hub/article views;
- canonical sidebar/contextual-help integration;
- repository-backed Markdown content;
- documentation and tests.

## 4. Route and authorization audit

Knowledge routes are:

- `GET /knowledge` → `knowledge.index`;
- `GET /knowledge/{slug}` → `knowledge.show`.

Both sit inside the existing `auth` + `account.active` route boundary.

M8 deliberately creates no separate `knowledge.view` permission because the shipped content is global product guidance and contains no tenant-specific operational record. Active authenticated accounts can read it, while all operational data routes retain their existing abilities and tenant rules.

The Knowledge controller does not accept arbitrary file paths, template names or `organization_id` to resolve articles.

Contextual-help links contain only global knowledge slugs. They do not contain tenant identifiers.

## 5. Repository/path security audit

`KnowledgeRepository` resolves articles by exact catalog slug. An unknown request slug returns no article and becomes a normal 404; it is never treated as a path.

Source-path protections include:

- empty/null-byte sources rejected;
- absolute paths rejected;
- Windows drive-prefixed absolute paths rejected;
- URI-wrapper schemes rejected;
- `..` traversal rejected;
- realpath must remain under `resources/knowledge/articles`;
- missing/unreadable source fails closed;
- the runtime exception message is generic and does not include the absolute server path or requested source filename.

The repository is read-only at runtime.

## 6. Markdown/rendering audit

Markdown is rendered through the framework CommonMark path with raw HTML stripped and unsafe links disabled.

Security contracts verify that article content cannot turn the repository into a Blade/PHP execution path. Raw `<script>`/event-handler HTML and `javascript:` links do not survive as executable output.

The catalog title is the authoritative page H1. A leading Markdown H1 is removed from rendered body content to avoid duplicate page-title semantics.

TOC processing runs after safe Markdown rendering and transforms only generated H2/H3 tags. IDs are lowercase ASCII kebab slugs; duplicates receive deterministic `-2`, `-3`, etc. suffixes. TOC is emitted only when at least two eligible headings exist.

## 7. Search audit

Search is server-side and stateless. No query index/database/service is introduced.

Normalization:

- whitespace trim/collapse;
- lowercase;
- ASCII transliteration for accent-insensitive Portuguese discovery.

Ranking contract:

1. exact normalized title = 100;
2. title/token prefix = 60;
3. tag match = 40;
4. summary/category-label match = 20;
5. body match = 10.

Ties are resolved by catalog order, normalized title and slug. Empty query returns editorial catalog order. Category filtering accepts only controlled category IDs.

Search results expose normalized article objects only; filesystem paths/raw unsafe fragments are not returned to the user.

## 8. Content and governance audit

M8 ships six initial product guides:

1. `getting-started`;
2. `scenarios-and-versioning`;
3. `execution-cockpit`;
4. `assessment-and-debrief`;
5. `history-and-reports`;
6. `people-organizations-access`.

The guide set covers the actual product lifecycle and governance without duplicating medical protocols.

The execution guide explicitly states that it does not prescribe clinical or tactical conduct and explains timeline append-only behavior.

CI integrity contracts enforce:

- unique URL-safe slugs;
- unique source-file assignment;
- required typed metadata;
- controlled categories/audiences;
- valid ISO review dates;
- existing related slugs;
- existing contextual route names;
- no route mapped to multiple contextual articles;
- exact parity between shipped Markdown files and catalog files;
- internal `/knowledge/{slug}` links resolving to catalog slugs;
- no PHP/script source accepted in shipped article content.

Review dates are transparency metadata, not a claim of clinical certification/currency.

## 9. Contextual help audit

The first Gate 5 implementation used a route-to-slug map in the shell. Gate 7 deliberately removed that duplication.

`contextual_for` in `config/knowledge.php` is now the single source of truth. `KnowledgeRepository::findByContext()` resolves the relevant article, and the authenticated layout only asks the repository for the current route's contextual article.

This prevents catalog and shell mappings from drifting independently.

Required contexts include dashboard, scenarios/templates, execution cockpit, assessment workbench, history/executive reporting and management indexes.

## 10. UX/accessibility audit

Automated/source contracts verify or preserve:

- canonical Knowledge item in sidebar;
- `aria-current="page"` on active Knowledge navigation;
- GET search form with real labels;
- controlled category select;
- semantic result count;
- accessible empty state;
- article breadcrumb, title, summary and review metadata;
- native anchor TOC;
- related-guide links;
- contextual help with explicit text `Como usar esta tela`;
- no placeholder `href="#"` links in canonical knowledge surfaces;
- no tenant identifiers in contextual URLs;
- M7 skip link/focus/low-light/reduced-motion behavior remains inherited;
- print-specific classes remove auxiliary navigation where practical.

Authored UI target remains WCAG 2.2 AA.

## 11. TDD/CI evidence before Gate 8

- Gate 1: RED #782 / `912cb8a391136c6e7e2ebeb9559cabe9b80fb21b` → GREEN #785 / `e0397e99aa0cfb5e3ee530d46e524d7e98c06e57`.
- Gate 2: RED #787 / `96aaad0a79dd5c27292e789854fccfce54463f8a` → GREEN #792 / `091889b1d2e6f613382bc9a5c2e81c1957787a91`.
- Gate 3: RED #794 / `6ccbe563ff2e6c940b1b54a974fffa7e4e3a6832` → GREEN #799 / `119012469ff7ca09e2dcd31a51a24791400835c5`.
- Gate 4: RED #801 / `3e78ebf4acc18c4aa270a354e56a4ff794d374d2` → GREEN #802 / `c96b393efefa42335e6231ca09e6a6687d6b74b4`.
- Gate 5: RED #804 / `5e307280f6796ddd6a746165c14be53c416164eb` → GREEN #806 / `3880f380495f526c377bded348a66d25c5b4c6c7`.
- Gate 6: RED #808 / `fd74af2f76ef6c75dd913818fa3ceda1f86e23b2` → GREEN #810 / `e532f7cca6cbc731d7f1c59a270bef031f796676`.
- Gate 7: RED #812 / `975f315e47455430ccc219dfff9c5aa60523143b` → GREEN #814 / `b2e8801c61c6aa3daeb51ab3d771c56572a04aab`.
- Gate 8 RED: CI #816 validates that route/security/hygiene checks are already satisfied while final documentation/audit synchronization is still required.

Every promoted GREEN gate retained the repository CI matrix: SQLite, PostgreSQL 16, Pint, production frontend build, fresh migrations, least-privilege runtime role, M6 guard rollback/reapply and repeated concurrency invariants.

## 12. Visual-validation limitation

This connected environment supports source inspection, rendered-HTML feature contracts, GitHub Actions and repository diffs, but it does not provide an authenticated interactive browser session for a pixel-by-pixel review across specific viewport/device combinations.

Therefore:

- semantic DOM, navigation contracts, accessibility-oriented authored behavior, responsive primitives, safe rendering, build validity and regression behavior are tested/inspected;
- pixel-level visual composition across devices is not claimed as directly observed.

This limitation is explicitly recorded rather than hidden. It does not weaken the security/database/domain evidence and can be supplemented by future human/browser cosmetic QA without changing the M8 architecture.

## 13. Final integration gate

M8 may be merged only when all of the following are freshly true on the exact final PR HEAD:

- `composer validate --strict` succeeds;
- production Vite build succeeds;
- fresh migrations succeed;
- full SQLite suite succeeds;
- full PostgreSQL 16 suite succeeds;
- PostgreSQL least-privilege runtime-role provisioning succeeds;
- M6 guard rollback/reapply succeeds;
- repeated M6 concurrency invariants succeed;
- Pint succeeds;
- forensic knowledge contracts succeed;
- branch remains 0 commits behind `main`;
- PR remains mergeable;
- no unresolved substantive review/thread exists;
- merge uses method `merge` with exact `expected_head_sha`.

No Critical/High M8 finding is identified by the source/contract audit at this candidate stage. Completion remains contingent on the fresh exact-head CI and protected integration above.
