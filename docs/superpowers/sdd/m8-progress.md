# M8 Progress — Knowledge & Documentation Center

Plan: `docs/superpowers/plans/2026-08-09-m8-knowledge-center.md`
Branch: `feature/m8-knowledge-center`
PR: #11 — draft
Status: IMPLEMENTATION

## Gates

- [x] Gate 1 — Secure knowledge contract — GREEN
- [x] Gate 2 — Knowledge Hub — GREEN
- [x] Gate 3 — Article experience — GREEN
- [x] Gate 4 — Operational guide content — GREEN
- [x] Gate 5 — Contextual help — GREEN
- [x] Gate 6 — Search & discovery hardening — GREEN
- [ ] Gate 7 — Governance & content integrity
- [ ] Gate 8 — Forensic audit & exact-head protected integration

## Baseline

- M7 main baseline: `7d80355ebf4ee6a09ec4026a80ca0ee8bdb16c58`.
- Approved spec: `docs/superpowers/specs/2026-08-09-m8-knowledge-center-design.md`.
- Implementation plan: `docs/superpowers/plans/2026-08-09-m8-knowledge-center.md`.
- M8 remains repository-backed and stateless; no CMS, AI/RAG, autonomous clinical/tactical guidance or M9 release scope.
- `main` remains untouched until final protected integration.

## Evidence ledger

### Gate 1 — Secure knowledge contract
- RED SHA: `912cb8a391136c6e7e2ebeb9559cabe9b80fb21b`
- RED CI: #782 / run `31319431731` — five new M8 security contracts failed because `App\Knowledge\KnowledgeRepository` did not exist; SQLite retained 294 passing legacy tests and Pint was green.
- GREEN SHA: `e0397e99aa0cfb5e3ee530d46e524d7e98c06e57`
- GREEN CI: #785 / run `31319517306` — SQLite, PostgreSQL 16, Pint, production Vite build, fresh migrations, least-privilege runtime role, M6 rollback/reapply and repeated concurrency invariants all green.
- Result: exact catalog lookup, path confinement, generic fail-closed source errors and safe CommonMark rendering are enforced without a new database/runtime persistence layer.

### Gate 2 — Knowledge Hub
- RED SHA: `96aaad0a79dd5c27292e789854fccfce54463f8a`
- RED CI: #787 / run `31319703046` — four expected Hub failures: named knowledge routes absent and `/knowledge` returned 404 for guest, inactive and active users; 299 tests passed in SQLite and Pint was green.
- GREEN SHA: `091889b1d2e6f613382bc9a5c2e81c1957787a91`
- GREEN CI: #792 / run `31319877359` — SQLite, PostgreSQL 16, Pint, production Vite build, fresh migrations, least-privilege runtime role, M6 rollback/reapply and repeated concurrency invariants all green.
- Result: authenticated Knowledge Hub, canonical sidebar entry, GET discovery form, controlled category filter, audience/review metadata, result count and accessible empty state are implemented without new persistence.

### Gate 3 — Article experience
- RED SHA: `6ccbe563ff2e6c940b1b54a974fffa7e4e3a6832`
- RED CI: #794 / run `31319997213` — secure unknown-slug 404 remained green while two new reader/TOC contracts failed; SQLite retained 305 passing tests and Pint was green.
- GREEN SHA: `119012469ff7ca09e2dcd31a51a24791400835c5`
- GREEN CI: #799 / run `31320223534` — SQLite, PostgreSQL 16, Pint, production Vite build, fresh migrations, least-privilege runtime role, M6 rollback/reapply and repeated concurrency invariants all green.
- Result: catalog title is authoritative H1, safe Markdown is rendered inside an institutional reader, H2/H3 receive deterministic ASCII anchors with duplicate suffixes, TOC appears only for at least two eligible headings, related guides stay catalog-resolved and source paths remain private.

### Gate 4 — Operational guide content
- RED SHA: `3e78ebf4acc18c4aa270a354e56a4ff794d374d2`
- RED CI: #801 / run `31320346843` — three expected content-contract failures because the initial catalog was empty; SQLite retained 307 passing tests and Pint was green.
- GREEN SHA: `c96b393efefa42335e6231ca09e6a6687d6b74b4`
- GREEN CI: #802 / run `31320621435` — SQLite, PostgreSQL 16, Pint, production Vite build, fresh migrations, least-privilege runtime role, M6 rollback/reapply and repeated concurrency invariants all green.
- Result: six reviewed product guides ship atomically with the allowlisted catalog, controlled audiences/categories, related/contextual metadata and explicit product-only boundaries; the execution guide states that it does not prescribe clinical or tactical conduct and documents timeline append-only behavior.

### Gate 5 — Contextual help
- RED SHA: `5e307280f6796ddd6a746165c14be53c416164eb`
- RED CI: #804 / run `31320776268` — three contextual-help contracts failed only because the operational pages lacked the guide link; SQLite retained 310 passing tests and Pint was green.
- GREEN SHA: `3880f380495f526c377bded348a66d25c5b4c6c7`
- GREEN CI: #806 / run `31320882046` — SQLite, PostgreSQL 16, Pint, production Vite build, fresh migrations, least-privilege runtime role, M6 rollback/reapply and repeated concurrency invariants all green.
- Result: a reusable contextual-help component plus route-name mapping links scenarios, execution, assessment, history/reporting and management surfaces to the exact product guide; knowledge URLs contain no tenant identifiers.

### Gate 6 — Search & discovery hardening
- RED SHA: `fd74af2f76ef6c75dd913818fa3ceda1f86e23b2`
- RED CI: #808 / run `31321017041` — four new search contracts failed only because `KnowledgeRepository::search()` did not exist; SQLite retained 313 passing tests and Pint was green.
- GREEN SHA: `e532f7cca6cbc731d7f1c59a270bef031f796676`
- GREEN CI: #810 / run `31321182753` — SQLite, PostgreSQL 16, Pint, production Vite build, fresh migrations, least-privilege runtime role, M6 rollback/reapply and repeated concurrency invariants all green.
- Result: server-side search is trim/squish/lower/ASCII normalized, accent-insensitive, weighted 100/60/40/20/10 across title/tags/summary-category/body, deterministic on ties, category-controlled, and wired to Hub `?q=` without external search infrastructure or persistence.

### Gate 7 — Governance & content integrity
- RED/GREEN evidence: pending

### Gate 8 — Forensic audit & exact-head protected integration
- Audit SHA: pending
- Exact-head CI: pending
- Merge evidence: pending
