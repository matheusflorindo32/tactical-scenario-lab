# M9 Progress — Release & Final Product Hardening

Plan: `docs/superpowers/plans/2026-08-09-m9-release-hardening.md`
Branch: `feature/m9-release-hardening`
PR: #12 — draft
Status: IMPLEMENTATION

## Gates

- [x] Gate 1 — Global M1–M8 Release Baseline Audit — GREEN
- [x] Gate 2 — Security & Dependency Governance — GREEN
- [ ] Gate 3 — Production Container & Deployment Parity
- [ ] Gate 4 — CI & Release Pipeline Hardening
- [ ] Gate 5 — Reliability & Deterministic Performance Budget
- [ ] Gate 6 — UX, Localization & Accessibility Finalization
- [ ] Gate 7 — Observability, Recovery & Release Documentation
- [ ] Gate 8 — Final Forensic Audit & Protected Integration

## Baseline

- M8 merge baseline: `a093da910c246e5186ea53f0720c7f239713fe95`.
- M9 branch was verified identical to `main` before the first M9 commit (`0 ahead / 0 behind`).
- Approved spec: `docs/superpowers/specs/2026-08-09-m9-release-hardening-design.md`.
- Implementation plan: `docs/superpowers/plans/2026-08-09-m9-release-hardening.md`.
- No new product-domain scope is allowed in M9.

## Evidence ledger

### Gate 1 — Global M1–M8 Release Baseline Audit
- RED SHA: `103c10f58cd4d3c8108300be7ef102a34f11e2e8`
- RED CI: #829 / run `31325365945` — the new M9 baseline contract failed exactly on stale release metadata; SQLite retained 323 passing tests with PostgreSQL-specific tests skipped and Pint was green.
- GREEN SHA: `21541468161bedcc54c7880eaca63c85ebf72753`
- GREEN CI: #834 / run `31325504446` — SQLite job `93275087694`, PostgreSQL 16 job `93275087706` and Pint job `93275087677` all SUCCESS. Production asset build, fresh migrations, least-privilege runtime role, M6 rollback/reapply and repeated concurrency invariants remained green.
- Result: SECURITY policy, product/locale example metadata, Composer description, Docker runtime baseline and CI branch triggers no longer contradict the current product. No domain model, migration or business-rule change was introduced.

### Gate 2 — Security & Dependency Governance
- RED SHA: `b788e7624f316b3a588eea56ca374eeeb64f830e`
- RED CI: #836 / run `31325649447` — policy assertions were already green; the new workflow contract failed only because explicit Composer/npm audit commands were absent. SQLite retained 325 passing tests with PostgreSQL-specific tests skipped and Pint was green.
- Rejected candidate SHA: `99820f9b1706f93f3f951a18343fc844e9c9ad8a` / CI #837 — rejected because `composer audit` was run without installed packages or `--locked`; this was a security-job configuration error, not a vulnerability finding.
- Rejected candidate SHA: `d04cd799f2cfa5552f06804dd826d1e49f06c51c` / CI #838 — the security job itself passed `composer audit --locked` and `npm audit --audit-level=high`, but the full run was rejected after an accidental workflow rewrite removed `IN SCHEMA public` from the PostgreSQL sequence GRANT, causing least-privilege provisioning to fail.
- GREEN SHA: `9f380d0d62959740e69922bf4c45e9c789a5bafa`
- GREEN CI: #839 / run `31325858072` — Security job `93276003095`, SQLite job `93276003138`, PostgreSQL 16 job `93276003133` and Pint job `93276003151` all SUCCESS. Composer lockfile audit and npm high-severity audit passed without ignore lists or lockfile mutation; PostgreSQL least-privilege provisioning, rollback/reapply, repeated concurrency invariants and full suite also passed.
- Result: dependency security is now an explicit release-blocking CI gate. No advisory was suppressed; npm reported zero vulnerabilities and Composer lockfile audit passed.

### Gate 3 — Production Container & Deployment Parity
- RED SHA: pending
- RED CI: pending
- GREEN SHA: pending
- GREEN CI: pending

### Gate 4 — CI & Release Pipeline Hardening
- RED SHA: pending
- GREEN SHA: pending

### Gate 5 — Reliability & Deterministic Performance Budget
- RED SHA: pending
- GREEN SHA: pending

### Gate 6 — UX, Localization & Accessibility Finalization
- RED SHA: pending
- GREEN SHA: pending

### Gate 7 — Observability, Recovery & Release Documentation
- RED SHA: pending
- GREEN SHA: pending

### Gate 8 — Final Forensic Audit & Protected Integration
- Candidate SHA: pending
- Exact-head CI: pending
- Merge evidence: pending
