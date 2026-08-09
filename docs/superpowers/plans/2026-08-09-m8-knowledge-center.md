# M8 Knowledge & Documentation Center Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Deliver an authenticated, secure, searchable and contextual Knowledge Center for Tactical Scenario Lab using Git-versioned Markdown, without introducing a CMS, new persistent domain, AI/RAG or autonomous clinical/tactical guidance.

**Architecture:** Product knowledge is defined by an application-controlled catalog in `config/knowledge.php` and Markdown bodies under `resources/knowledge/articles/`. A small read-only `KnowledgeRepository` resolves catalog entries, validates file boundaries, renders safe Markdown and returns immutable `KnowledgeArticle` view models. Authenticated controllers/views expose Hub, Article and deterministic server-side search; CI executes catalog/content integrity and security contracts.

**Tech Stack:** PHP 8.3+ · Laravel 13 · Blade · Tailwind CSS v4 · Alpine.js · PHPUnit 12 · SQLite · PostgreSQL 16 · GitHub Actions.

## Global Constraints

- Preserve all M1–M7 tenant, authorization, historical-integrity, reporting, PostgreSQL-hardening and concurrency invariants.
- No schema change in M8 unless a RED test proves a strictly necessary requirement that cannot be met by repository-backed content.
- No WYSIWYG/CMS, user upload, public editing endpoint, AI/RAG, embeddings, vector database or autonomous clinical/tactical recommendation engine.
- Knowledge content explains use of Tactical Scenario Lab and product invariants; it does not become a second clinical/domain source of truth.
- Portuguese (Brazil) is the primary UI/content language.
- Knowledge routes use `auth` + `account.active` and do not accept arbitrary `organization_id`, file path or template identifiers from clients.
- Markdown rendering must strip raw HTML, block unsafe links, never execute Blade/PHP, and fail closed on invalid/missing catalog files.
- M7 light/low-light behavior and WCAG 2.2 AA authored-UI target remain intact.
- Every gate follows RED → verify RED → minimal GREEN → full CI promotion → ledger update.
- M9 release/distribution scope is excluded.

---

## Task 1 / Gate 1: Secure knowledge contract

**Files:**
- Create: `config/knowledge.php`
- Create: `app/Knowledge/KnowledgeArticle.php`
- Create: `app/Knowledge/KnowledgeRepository.php`
- Create: `tests/Feature/KnowledgeRepositorySecurityTest.php`

**Interfaces:**
- `KnowledgeRepository::all(): Illuminate\Support\Collection`
- `KnowledgeRepository::find(string $slug): ?App\Knowledge\KnowledgeArticle`
- `KnowledgeArticle` exposes immutable normalized metadata, source Markdown, safe rendered HTML and body-search text.
- `config('knowledge.articles')` is an ordered array, not slug-keyed, so duplicate slugs remain detectable by Gate 7.

- [ ] **Step 1: Write RED tests for exact catalog lookup and unknown slugs**

```php
public function test_unknown_slug_is_not_interpreted_as_a_file_path(): void
{
    config()->set('knowledge.articles', []);

    $repository = app(\App\Knowledge\KnowledgeRepository::class);

    $this->assertNull($repository->find('../../.env'));
    $this->assertNull($repository->find('file:///etc/passwd'));
}
```

- [ ] **Step 2: Write RED tests for catalog path confinement**

Create a temporary Markdown file inside `resources/knowledge/articles/` and assert that an allowlisted relative file resolves. Set catalog `file` to `../views/welcome.blade.php`, an absolute path and URL-wrapper path in separate tests; each must throw a deterministic `RuntimeException` without exposing an absolute server path in its message.

- [ ] **Step 3: Write RED tests for Markdown safety**

Given article Markdown containing `<script>alert(1)</script>`, `<img src=x onerror=alert(1)>`, `[bad](javascript:alert(1))`, `{{ config('app.key') }}` and `<?php echo 'x'; ?>`, assert rendered HTML contains no executable script/event handler/javascript URL and does not evaluate Blade/PHP expressions.

- [ ] **Step 4: Verify RED in CI**

Commit only the tests. Expected: new tests fail because `KnowledgeRepository`/`KnowledgeArticle` and knowledge config contract do not yet exist; legacy suites remain otherwise green.

- [ ] **Step 5: Implement immutable article model**

```php
namespace App\Knowledge;

final readonly class KnowledgeArticle
{
    public function __construct(
        public string $slug,
        public string $file,
        public string $title,
        public string $summary,
        public string $category,
        public array $audience,
        public array $tags,
        public int $order,
        public string $reviewedOn,
        public array $related,
        public array $contextualFor,
        public string $markdown,
        public string $html,
        public string $searchText,
    ) {}
}
```

- [ ] **Step 6: Implement minimal secure repository**

`find()` performs exact slug lookup only. File resolution rejects empty names, `..`, absolute/drive-prefixed paths and URI schemes before joining to `resource_path('knowledge/articles')`; `realpath()` must remain under the canonical article-directory prefix. Missing files throw `RuntimeException('Knowledge article source is unavailable.')`.

Render with Laravel `Str::markdown($markdown, ['html_input' => 'strip', 'allow_unsafe_links' => false])`; do not evaluate rendered output as Blade.

- [ ] **Step 7: Add empty initial catalog shell**

```php
return [
    'categories' => [
        'getting-started' => 'Primeiros passos',
        'operation' => 'Operação',
        'assessment' => 'Avaliação & Debrief',
        'governance' => 'Histórico & Governança',
    ],
    'audiences' => ['instructor', 'evaluator', 'manager', 'administrator'],
    'articles' => [],
];
```

- [ ] **Step 8: Verify GREEN**

Run full SQLite + PostgreSQL 16 + Pint/build matrix. PostgreSQL must still execute M6 runtime-role, guard rollback/reapply and concurrency sequence.

- [ ] **Step 9: Commit and ledger Gate 1 evidence**

---

## Task 2 / Gate 2: Knowledge Hub and authenticated routes

**Files:**
- Create: `app/Http/Controllers/KnowledgeController.php`
- Create: `resources/views/knowledge/index.blade.php`
- Modify: `routes/web.php`
- Modify: `resources/views/components/sidebar.blade.php`
- Create: `tests/Feature/KnowledgeHubTest.php`

**Interfaces:**
- `GET /knowledge` → `knowledge.index`
- `GET /knowledge/{slug}` → `knowledge.show` (show implementation is completed in Gate 3)
- Both routes use existing `auth` + `account.active` boundary.

- [ ] **Step 1: RED route/auth tests**

Assert guest `/knowledge` redirects to login; inactive accounts remain blocked by existing middleware; active authenticated account receives 200. Assert canonical sidebar exposes real `knowledge.index` link and `aria-current="page"` on Hub.

- [ ] **Step 2: RED Hub HTML tests**

Assert Hub has H1 `Base de Conhecimento`, GET search form named `q`, category navigation, semantic result count, article links, reviewed metadata and accessible empty state.

- [ ] **Step 3: Verify RED**

- [ ] **Step 4: Implement routes/controller/index view minimally**

`KnowledgeController@index(Request $request, KnowledgeRepository $repository)` returns all articles when `q` is empty. Category filter uses controlled catalog IDs only and never becomes filesystem input.

- [ ] **Step 5: Add sidebar item**

Place **Conhecimento** as a real canonical entry; no placeholder links and no duplicate topbar navigation.

- [ ] **Step 6: Full CI GREEN and ledger update**

---

## Task 3 / Gate 3: Article reading experience and deterministic TOC

**Files:**
- Create: `resources/views/knowledge/show.blade.php`
- Modify: `app/Knowledge/KnowledgeArticle.php`
- Modify: `app/Knowledge/KnowledgeRepository.php`
- Modify: `app/Http/Controllers/KnowledgeController.php`
- Create: `tests/Feature/KnowledgeArticleExperienceTest.php`

**Interfaces:**
- `KnowledgeRepository::find()` returns safe HTML plus a deterministic `toc` collection/array added to `KnowledgeArticle`.
- TOC includes H2/H3 only when at least two eligible headings exist.
- Heading IDs are lowercase ASCII kebab slugs; duplicates append `-2`, `-3`, etc.

- [ ] **Step 1: RED unknown-slug 404 test**
- [ ] **Step 2: RED reading-surface tests**

Assert breadcrumb, title, summary, category, audiences, reviewed date, article body, related links and no source-path leakage.

- [ ] **Step 3: RED TOC tests**

Markdown:

```markdown
## Execução
### Preparação
## Execução
```

must render IDs `execucao`, `preparacao`, `execucao-2`; TOC order follows document order. One eligible H2 alone renders no TOC.

- [ ] **Step 4: Implement heading extraction/ID injection before final render or through safe post-processing limited to generated heading tags**

Do not parse arbitrary user HTML; only transform the safe renderer output's generated `<h2>`/`<h3>` headings.

- [ ] **Step 5: Add print/readability styles using existing design tokens**
- [ ] **Step 6: Full CI GREEN and ledger update**

---

## Task 4 / Gate 4: Initial operational guide set

**Files:**
- Create six Markdown files in `resources/knowledge/articles/`
- Modify: `config/knowledge.php`
- Create: `tests/Feature/KnowledgeInitialContentTest.php`

**Initial slugs/files:**
- `getting-started` → `getting-started.md`
- `scenarios-and-versioning` → `scenarios-and-versioning.md`
- `execution-cockpit` → `execution-cockpit.md`
- `assessment-and-debrief` → `assessment-and-debrief.md`
- `history-and-reports` → `history-and-reports.md`
- `people-organizations-access` → `people-organizations-access.md`

- [ ] **Step 1: RED content coverage tests**

Assert six required slugs exist; each article has title, summary, controlled category/audience, tags, order, valid review date and meaningful Markdown sections. Assert `execution-cockpit` contains product-behavior language and no autonomous instruction claim such as prescribing treatment or tactical action.

- [ ] **Step 2: Author concise product guides grounded only in implemented product behavior**

Do not reproduce medical protocols; explain interface, lifecycle, invariants and authorized actions.

- [ ] **Step 3: Wire related/contextual metadata**
- [ ] **Step 4: Full CI GREEN and ledger update**

---

## Task 5 / Gate 5: Contextual help from operational surfaces

**Files:**
- Modify: `resources/views/scenarios/index.blade.php`
- Modify: `resources/views/executions/show.blade.php`
- Modify: `resources/views/assessments/show.blade.php`
- Modify: `resources/views/history/executions.blade.php`
- Modify: `resources/views/people/index.blade.php`
- Modify: `resources/views/organizations/index.blade.php`
- Modify: `resources/views/access/index.blade.php`
- Create: `tests/Feature/KnowledgeContextualHelpTest.php`

- [ ] **Step 1: RED tests for exact contextual targets**

Expected mappings:
- Scenarios → `knowledge.show('scenarios-and-versioning')`
- Execution → `execution-cockpit`
- Assessment → `assessment-and-debrief`
- History → `history-and-reports`
- Management → `people-organizations-access`

- [ ] **Step 2: Implement links using explicit language (`Como usar esta tela` / `Ver guia ...`)**
- [ ] **Step 3: Assert no ability bypass/tenant data is embedded in knowledge URLs**
- [ ] **Step 4: Full CI GREEN and ledger update**

---

## Task 6 / Gate 6: Search & discovery hardening

**Files:**
- Modify: `app/Knowledge/KnowledgeRepository.php`
- Modify: `app/Http/Controllers/KnowledgeController.php`
- Modify: `resources/views/knowledge/index.blade.php`
- Create: `tests/Feature/KnowledgeSearchTest.php`

**Search contract:**
- Normalize with trim + collapsed whitespace + lowercase + ASCII transliteration.
- Exact normalized title match = 100 points.
- Title token/prefix match = 60.
- Tag match = 40.
- Summary/category-label match = 20.
- Body match = 10.
- Sort by score DESC, catalog `order` ASC, normalized title ASC, slug ASC.
- Category filter uses exact controlled category IDs; within filtered set, same ordering applies.

- [ ] **Step 1: RED case/accent tests (`avaliacao` matches `Avaliação`)**
- [ ] **Step 2: RED weighted-order tests**
- [ ] **Step 3: RED whitespace/empty-query/category tests**
- [ ] **Step 4: Implement repository `search(string $query, ?string $category = null): Collection`**
- [ ] **Step 5: Ensure snippets never expose raw HTML/filesystem metadata**
- [ ] **Step 6: Full CI GREEN and ledger update**

---

## Task 7 / Gate 7: Governance & content integrity

**Files:**
- Create: `tests/Feature/KnowledgeContentIntegrityTest.php`
- Optionally create a small test-only helper under `tests/Support/` if duplication requires it; do not move integrity policy into runtime solely for tests.

- [ ] **Step 1: RED catalog-integrity tests**

Validate unique URL-safe slugs (`^[a-z0-9]+(?:-[a-z0-9]+)*$`), required typed metadata, controlled categories/audiences, unique file assignment, valid ISO review date, existing related/contextual targets and confined readable source files.

- [ ] **Step 2: RED internal-link integrity test**

Extract `/knowledge/{slug}` links from Markdown and assert every slug exists in catalog.

- [ ] **Step 3: RED orphan test**

Every shipped article must be reachable from catalog/Hub; initial related graph does not need full strong connectivity, but no source Markdown file may exist unlisted in the article directory.

- [ ] **Step 4: RED unsafe-content regression fixtures**

Assert catalog renderer blocks raw HTML/scriptable links and never evaluates Blade/PHP content.

- [ ] **Step 5: Implement only fixes revealed by RED; full CI GREEN**
- [ ] **Step 6: Ledger Gate 7**

---

## Task 8 / Gate 8: Forensic audit, exact-head CI and protected integration

**Files:**
- Create: `docs/PHASE_M8_AUDIT.md`
- Modify: `README.md`
- Modify: `docs/DESIGN_SYSTEM.md` only if new reusable knowledge-surface patterns require documentation
- Modify: `docs/superpowers/sdd/m8-progress.md`
- Create/modify forensic tests only if source review identifies an enforceable gap.

- [ ] **Step 1: Source/delta audit against `main`**

Confirm no unintended migrations/models/domain services, no public knowledge routes, no file-path request input, no unsafe renderer bypass, no M9 scope.

- [ ] **Step 2: Audit navigation/accessibility/content integrity**

Search for placeholder links, unresolved knowledge slugs, raw-path leakage, unsafe HTML allowances, undocumented article files and stale route documentation.

- [ ] **Step 3: Refresh README**

Document Knowledge Center route families and architecture without overstating clinical authority.

- [ ] **Step 4: Candidate CI**

Require SQLite, PostgreSQL 16, Pint, Vite build, migrations, least-privilege runtime role, M6 rollback/reapply and repeated concurrency invariants.

- [ ] **Step 5: Remediate any finding and run a new candidate**

A failed candidate is never promoted.

- [ ] **Step 6: Freeze final ledger HEAD**

Record Gates 1–7 evidence and Gate 8 audit status without causing a post-proof SHA move after the exact-head run.

- [ ] **Step 7: Exact-head CI**

All jobs must be green on the exact PR HEAD.

- [ ] **Step 8: Re-read PR immediately before merge**

Require mergeable, exact expected HEAD unchanged, branch 0 behind `main`, no unresolved substantive review/thread.

- [ ] **Step 9: Mark ready and merge with method `merge` + `expected_head_sha`**

- [ ] **Step 10: Verify PR closed/merged and `main` identical to returned merge commit**

## Plan self-review

- Spec coverage: all M8 architecture, security, search, reading, guide, contextual-help, governance, accessibility and exact-head requirements map to Tasks 1–8.
- Placeholder scan: no TODO/TBD or unspecified implementation step remains.
- Type consistency: `KnowledgeRepository`, `KnowledgeArticle`, route names and search contract remain consistent across tasks.
- Scope: M8 stays repository-backed and stateless; CMS, AI/RAG, clinical protocol logic and M9 release scope remain excluded.
