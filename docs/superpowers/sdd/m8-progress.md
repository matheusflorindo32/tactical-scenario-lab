# M8 Progress — Knowledge & Documentation Center

Plan: `docs/superpowers/plans/2026-08-09-m8-knowledge-center.md`
Branch: `feature/m8-knowledge-center`
PR: #11 — draft
Status: IMPLEMENTATION

## Gates

- [x] Gate 1 — Secure knowledge contract — GREEN
- [x] Gate 2 — Knowledge Hub — GREEN
- [x] Gate 3 — Article experience — GREEN
- [ ] Gate 4 — Operational guide content
- [ ] Gate 5 — Contextual help
- [ ] Gate 6 — Search & discovery hardening
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
- RED/GREEN evidence: pending

### Gate 5 — Contextual help
- RED/GREEN evidence: pending

### Gate 6 — Search & discovery hardening
- RED/GREEN evidence: pending

### Gate 7 — Governance & content integrity
- RED/GREEN evidence: pending

### Gate 8 — Forensic audit & exact-head protected integration
- Audit SHA: pending
- Exact-head CI: pending
- Merge evidence: pending
