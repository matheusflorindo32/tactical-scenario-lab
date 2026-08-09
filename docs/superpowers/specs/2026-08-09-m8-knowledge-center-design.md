# M8 — Knowledge & Documentation Center Design

Date: 2026-08-09
Status: Approved for specification review
Branch: `feature/m8-knowledge-center`

## 1. Objective

Integrate a secure, searchable and contextual product knowledge base into Tactical Scenario Lab without introducing a CMS, a new persistent domain or autonomous clinical/tactical guidance.

M8 turns documentation from an external repository concern into a first-class authenticated product experience while preserving the architecture and institutional truth established by M1–M7.

Primary principle: **the right product guidance, in the right operational context, without creating a second source of clinical or domain truth**.

M8 must help a user answer:

1. How do I use this part of the product correctly?
2. What is the lifecycle or invariant behind the current screen?
3. Where is the relevant guide without leaving the authenticated experience?
4. Can I trust that the guide is versioned, reviewed and internally consistent?
5. Can the knowledge layer evolve without weakening tenant, authorization or historical guarantees?

## 2. Architectural decision

Use a **Git-versioned, application-rendered knowledge base**.

The product will not introduce a database-backed CMS in M8. Article metadata is maintained in an allowlisted application catalog and article bodies are Markdown files committed to the repository.

Recommended structure:

```text
config/knowledge.php
resources/knowledge/articles/
    getting-started.md
    scenarios-and-versioning.md
    execution-cockpit.md
    assessment-and-debrief.md
    history-and-reports.md
    people-organizations-access.md
app/Knowledge/
    KnowledgeArticle.php
    KnowledgeRepository.php
app/Http/Controllers/
    KnowledgeController.php
resources/views/knowledge/
    index.blade.php
    show.blade.php
```

The exact file decomposition may be refined during implementation if tests show a smaller boundary is clearer, but the security and domain contracts below are fixed.

## 3. Why this architecture

### 3.1 Rejected: database-backed CMS

A CMS would require schema, editorial roles, authorization rules, revision state, audit history, sanitization of user-authored HTML, publishing workflow and operational backup/restore concerns. M8 does not yet have a validated requirement for those costs.

### 3.2 Rejected: separate documentation site

A separate site would be easy to host but would break contextual navigation and the Operational Command Center experience established in M7.

### 3.3 Selected: repository-backed knowledge center

Benefits:

- Git provides reviewable content history;
- no migration or new runtime persistence is required;
- content changes travel through the existing protected PR/CI process;
- guides can be linked directly from product context;
- all content remains deploy-version aligned with the application;
- attack surface is materially smaller than an editable CMS.

## 4. Global constraints

- Preserve Laravel 13 + Blade + Tailwind CSS v4 + Alpine.js.
- Preserve all M1–M7 tenant, authorization, immutability, reporting, PostgreSQL and concurrency invariants.
- No schema change for M8 unless a RED test proves a strictly necessary requirement that cannot be met by repository-backed content.
- No WYSIWYG editor.
- No public article creation/editing endpoints.
- No user upload into the knowledge repository.
- No AI/RAG, embeddings, vector database or autonomous answer generation in M8.
- No clinical protocol engine and no autonomous tactical recommendation layer.
- Knowledge content is about using Tactical Scenario Lab and understanding its product invariants.
- Portuguese (Brazil) remains the primary UI/content language.
- Knowledge pages inherit M7 light/low-light behavior and WCAG 2.2 AA authored-UI target.
- M9 release/distribution work remains out of scope.

## 5. Knowledge catalog contract

The catalog is application-controlled and must not be derived from request paths.

Use an ordered array of article definitions rather than slug-keyed entries so CI can detect duplicate slugs explicitly.

Each article definition must include:

- `slug` — stable URL-safe identifier;
- `file` — repository-relative Markdown file allowlisted by the catalog;
- `title` — human-readable title;
- `summary` — concise discovery description;
- `category` — controlled category identifier;
- `audience` — one or more controlled audience identifiers;
- `tags` — normalized discovery tags;
- `order` — deterministic display order within category;
- `reviewed_on` — ISO date of last content review;
- `related` — zero or more related article slugs;
- optional `contextual_for` — product surfaces for contextual help mapping.

Required controlled categories for the initial milestone:

- `getting-started` — Primeiros passos;
- `operation` — Operação;
- `assessment` — Avaliação & Debrief;
- `governance` — Histórico & Governança.

Initial audiences:

- `instructor`;
- `evaluator`;
- `manager`;
- `administrator`.

The content catalog is global product knowledge. It must not contain organization-specific secrets or tenant-controlled content in M8.

## 6. Secure repository contract

Introduce a small read-only knowledge repository responsible for turning catalog entries into application article objects.

Security requirements:

1. A request slug is resolved only by exact catalog lookup.
2. Unknown slugs return 404; they are never interpreted as file paths.
3. Catalog file entries must resolve inside the dedicated knowledge article directory.
4. Directory traversal (`..`), absolute paths, URL wrappers and external file sources are invalid catalog definitions.
5. Missing/unreadable allowlisted files fail closed.
6. Markdown rendering must strip/disable raw HTML and reject unsafe links.
7. Scriptable protocols such as `javascript:` must never render as actionable links.
8. Article rendering cannot execute Blade/PHP embedded in Markdown.
9. The repository is read-only at runtime.
10. Knowledge routes do not expose filesystem paths in error output.

Preferred Laravel rendering path is `Str::markdown()` with safe CommonMark options, including raw-HTML stripping and unsafe-link blocking, provided the installed framework behavior satisfies the RED tests. Do not add a new Markdown dependency unless the existing framework path cannot satisfy the security contract.

## 7. Article value object / view model

Knowledge views should consume an explicit article representation rather than raw config arrays.

Minimum normalized fields:

- slug;
- title;
- summary;
- category id + display label;
- audiences;
- tags;
- reviewed date;
- rendered safe HTML;
- deterministic table-of-contents headings when the article contains at least two level-2 headings;
- related article references.

TOC contract:

- include H2 headings and their nested H3 headings only;
- do not create a TOC for fewer than two H2 sections;
- generate stable URL-safe anchor ids from heading text;
- duplicate heading ids receive deterministic numeric suffixes (`-2`, `-3`, ...);
- the same ids must be applied to rendered headings and TOC links;
- heading text is escaped as content and is never treated as raw HTML.

The object is read-only from the perspective of the knowledge UI.

## 8. Routes and authorization

Add authenticated knowledge routes:

- `GET /knowledge` → `knowledge.index`;
- `GET /knowledge/{slug}` → `knowledge.show`.

They must use the same authenticated/account-active boundary as the operational application.

M8 does not create a separate knowledge permission. Any active authenticated account may read the global product knowledge base because the content explains product usage and contains no tenant-sensitive records.

The knowledge controller must not accept `organization_id`, file path or arbitrary template identifiers from the client.

## 9. Knowledge Hub experience

`/knowledge` becomes the discovery hub.

Required regions:

- page identity and purpose;
- search input using GET query semantics;
- category navigation/filter;
- concise article cards/list items;
- audience and reviewed-date metadata;
- helpful empty state when a query returns no article;
- semantic result count;
- stable links to article routes.

The hub must remain useful with JavaScript disabled. Alpine may enhance interaction but cannot be required for search navigation or article access.

Sidebar integration:

- add a real **Conhecimento** section or equivalent canonical item;
- no `href="#"` placeholders;
- active state uses `aria-current="page"`;
- low-light behavior comes from the existing M7 shell, not a second theme implementation.

## 10. Search and discovery

Search is deterministic and server-side over the small repository-backed corpus.

Search fields and relevance weights:

- title — 50;
- tags — 30;
- summary — 20;
- category label — 10;
- Markdown body text — 1.

Search contract:

- the user query is trimmed, internal whitespace is collapsed, lowercased and ASCII-transliterated for comparison;
- matching is case-insensitive and accent-insensitive for Portuguese discovery;
- the normalized query uses substring matching against each normalized field;
- an optional category filter is combined with the text query using AND semantics;
- an empty text query returns the normal catalog/category view rather than a special error;
- each field contributes its weight at most once per article;
- results with a non-empty query are sorted by relevance score descending, then category declaration order, article `order`, then slug;
- results without a text query are sorted by category declaration order, article `order`, then slug;
- the raw query is never rendered as HTML.

Preferred normalization uses framework string utilities such as lowercase + ASCII transliteration. No external search service, database full-text index, Redis, Elasticsearch, Meilisearch or vector store is introduced in M8.

Search results must not include hidden filesystem metadata or unsafe rendered fragments.

## 11. Article reading experience

`/knowledge/{slug}` is an institutional reading surface, not a generic blog page.

Required:

- breadcrumb back to Knowledge Hub;
- article title and summary;
- category, audiences and reviewed date;
- TOC under the deterministic rule in section 7;
- readable content width;
- semantic headings;
- related articles;
- clean print behavior;
- low-light support through existing design tokens;
- clear link styling and focus states;
- no raw Markdown or source path leakage.

Use the M7 visual language: navy/stone/ink semantics, restrained cards, strong hierarchy and attention to historical/invariant callouts.

## 12. Initial operational guide set

M8 ships an initial coherent product guide set rather than an empty framework.

### 12.1 Primeiros passos

`getting-started`

Covers:

- authentication and active organization context;
- navigation model;
- scenario → execution → assessment → history lifecycle;
- where operational attention appears;
- distinction between editable state and historical truth.

### 12.2 Cenários e versionamento

`scenarios-and-versioning`

Covers:

- scenario versus scenario version;
- draft/publish/revise lifecycle;
- templates;
- why published definitions are immutable;
- how executions bind to a version.

### 12.3 Cockpit de execução

`execution-cockpit`

Covers:

- lifecycle actions;
- teams/participants/resources;
- injects;
- timeline append-only meaning;
- entry into assessment/debrief.

It describes product behavior and must not prescribe autonomous medical/tactical decisions.

### 12.4 Avaliação e debrief

`assessment-and-debrief`

Covers:

- draft workbench sections;
- rubric/evidence;
- critical errors/key times as product records;
- debrief and action plan;
- irreversible finalization;
- post-finalization action-status exception.

### 12.5 Histórico e relatórios

`history-and-reports`

Covers:

- historical execution truth;
- operational versus executive dashboard roles;
- CSV/PDF access;
- why legacy `Scenario.score` is not institutional reporting truth.

### 12.6 Pessoas, organizações e acessos

`people-organizations-access`

Covers:

- active organization;
- people/memberships/roles;
- organization/unit concepts;
- account/access governance;
- interface visibility versus backend authorization.

## 13. Contextual help

M8 reconnects the product to the relevant guide from the screens where a user needs it.

Initial required contextual links:

- Scenario workspace/list → `scenarios-and-versioning`;
- Execution cockpit → `execution-cockpit`;
- Assessment workbench → `assessment-and-debrief`;
- History/reporting → `history-and-reports`;
- Management surfaces → `people-organizations-access` where appropriate.

Link language should be explicit, e.g. **Como usar esta tela** or **Ver guia de execução**.

Contextual help links are navigational only. They do not bypass abilities and do not reveal tenant data.

## 14. Content governance and integrity

CI must make knowledge quality executable.

Required integrity tests:

- slugs are unique;
- slugs match the accepted URL-safe pattern;
- required metadata is present and typed correctly;
- categories/audiences use controlled values;
- referenced Markdown files exist and remain inside the allowlisted directory;
- no duplicate catalog file assignment unless explicitly justified;
- all `related` slugs exist;
- contextual-help target slugs exist;
- internal `/knowledge/{slug}` links resolve to catalog articles;
- unsafe raw HTML/scriptable links do not survive rendering;
- article source files do not contain Blade/PHP execution paths accepted by the renderer;
- every shipped article has a valid `reviewed_on` date;
- no orphan initial article is unreachable from hub/catalog navigation.

Content review dates are transparency metadata, not an automatic validity guarantee. M8 does not fabricate clinical currency claims.

## 15. Error handling

- Unknown article slug → normal 404.
- Missing catalog file → fail closed and surface a non-sensitive application error; CI should prevent deployment of this state.
- Invalid catalog definition → deterministic exception in test/boot path where appropriate; never fall back to arbitrary filesystem access.
- Empty search → normal hub.
- No search matches → accessible empty state with the query echoed safely.
- Broken internal knowledge link → CI failure.

User-facing error output must not expose absolute server paths, stack details or article source internals.

## 16. Accessibility and responsive acceptance

Required authored-UI target: WCAG 2.2 AA, consistent with M7.

Acceptance contracts include:

- keyboard-accessible search, category navigation and article links;
- visible focus;
- logical heading hierarchy;
- meaningful link text;
- no color-only category/status communication;
- readable line length on desktop;
- responsive layout without mandatory horizontal scrolling for article prose;
- table-of-contents links are native anchors;
- skip link and shell behavior remain intact;
- print stylesheet does not print irrelevant navigation chrome where practical;
- reduced-motion behavior remains inherited.

## 17. Data and privacy boundaries

M8 is intentionally stateless with respect to user reading behavior.

No M8 analytics table, reading history, favorites, comments or per-user article state is introduced.

Search query uses the request only for rendering/filtering and is not persisted by a new M8 feature.

Knowledge articles must not include secrets, PII, environment credentials or tenant-specific operational records.

## 18. Testing strategy

Every implementation gate follows RED → GREEN → full CI promotion.

Test layers:

- repository unit/feature contracts for catalog/path/Markdown security;
- route/auth tests;
- rendered HTML contracts for Hub and Article pages;
- search normalization/discovery tests;
- contextual link tests;
- content-integrity/forensic tests;
- existing full SQLite regression suite;
- existing full PostgreSQL 16 suite;
- existing M6 least-privilege, migration rollback/reapply and concurrency sequence;
- production frontend build;
- Pint.

A failed RED caused by the test itself is not valid evidence and must be corrected before implementation, following the M7 precedent.

## 19. Gates

### Gate 1 — Secure knowledge contract

RED must prove absence of secure catalog/repository behavior. GREEN delivers catalog, read-only article representation, path containment, safe Markdown rendering and authenticated routes at the contract level.

### Gate 2 — Knowledge Hub

RED covers canonical navigation, authenticated hub, GET search surface, category/audience metadata and accessible empty state. GREEN delivers the discovery UI without requiring JavaScript.

### Gate 3 — Article experience

RED covers article route, metadata, semantic content, deterministic TOC/anchors, related content, safe links and print/readability hooks. GREEN delivers the institutional reading experience.

### Gate 4 — Operational guide content

RED/integrity tests require the six initial guides and their mandatory concepts. GREEN adds reviewed, product-focused content with no autonomous protocol guidance.

### Gate 5 — Contextual help

RED proves the required operational screens lack real guide links. GREEN adds route-correct contextual help to scenarios, execution, assessment, history/reporting and management surfaces without changing abilities.

### Gate 6 — Search & discovery hardening

RED covers accent/case normalization, weighted deterministic relevance, body/tag discovery, category+query AND semantics and safe no-result behavior. GREEN completes the server-side search contract without external services.

### Gate 7 — Governance & content integrity

RED introduces catalog/content forensic checks: duplicate slug, missing file, related/internal broken links, invalid metadata, unsafe Markdown and unreachable content. GREEN makes all shipped knowledge content pass.

### Gate 8 — Forensic M8 audit & exact-head integration gate

Required before integration:

- all M8 gates have fresh GREEN evidence;
- `composer validate --strict` succeeds;
- production Vite build succeeds;
- `php artisan migrate:fresh --force` succeeds on SQLite/PostgreSQL as currently enforced;
- full SQLite suite succeeds;
- full PostgreSQL 16 suite succeeds;
- least-privilege runtime role provisioning succeeds;
- M6 database guard rollback/reapply succeeds;
- repeated M6 concurrency invariants succeed;
- Pint succeeds;
- PR remains mergeable and 0 commits behind `main`;
- no unresolved substantive review finding remains;
- M8 changed-file audit contains no unintended schema/domain/M9 scope;
- final merge is `merge` method and protected by exact expected head SHA.

## 20. Explicitly out of scope

M8 does not implement:

- database CMS;
- WYSIWYG editing;
- article comments/reactions;
- favorites/bookmarks/read tracking;
- uploadable document library;
- organization-specific knowledge articles;
- AI assistant, RAG, embeddings or semantic/vector search;
- protocol recommendation engine;
- autonomous clinical decision support;
- public unauthenticated wiki;
- localization platform;
- M9 packaging/release/distribution.

## 21. Definition of done

M8 is complete only when an authenticated active user can discover, search and read version-aligned product guidance inside Tactical Scenario Lab; the core operational screens link to the correct contextual guide; unsafe or inconsistent knowledge content is blocked by executable tests; all M1–M7 invariants continue passing; and the exact audited HEAD is integrated through a protected merge.
