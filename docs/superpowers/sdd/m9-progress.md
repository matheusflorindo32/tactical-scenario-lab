# M9 Progress — Release & Final Product Hardening

Plan: `docs/superpowers/plans/2026-08-09-m9-release-hardening.md`
Branch: `feature/m9-release-hardening`
PR: #12 — draft
Status: FINAL VERIFICATION

## Gates

- [x] Gate 1 — Global M1–M8 Release Baseline Audit — GREEN
- [x] Gate 2 — Security & Dependency Governance — GREEN
- [x] Gate 3 — Production Container & Deployment Parity — GREEN
- [x] Gate 4 — CI & Release Pipeline Hardening — GREEN
- [x] Gate 5 — Reliability & Deterministic Performance Budget — GREEN
- [x] Gate 6 — UX, Localization & Accessibility Finalization — GREEN
- [x] Gate 7 — Observability, Recovery & Release Documentation — GREEN
- [ ] Gate 8 — Final Forensic Audit & Protected Integration — candidate GREEN; exact-head pending

## Baseline

- M8 merge baseline: `a093da910c246e5186ea53f0720c7f239713fe95`.
- M9 branch was verified identical to `main` before the first M9 commit (`0 ahead / 0 behind`).
- Approved spec: `docs/superpowers/specs/2026-08-09-m9-release-hardening-design.md`.
- Implementation plan: `docs/superpowers/plans/2026-08-09-m9-release-hardening.md`.
- M9 adds no new business/product domain.

## Evidence ledger

### Gate 1
- RED: `103c10f58cd4d3c8108300be7ef102a34f11e2e8`, CI #829 / run `31325365945`.
- GREEN: `21541468161bedcc54c7880eaca63c85ebf72753`, CI #834 / run `31325504446`.

### Gate 2
- RED: `b788e7624f316b3a588eea56ca374eeeb64f830e`, CI #836 / run `31325649447`.
- Rejected #837: Composer audit invocation configuration error.
- Rejected #838: security audit passed but PostgreSQL sequence GRANT scope regressed during workflow rewrite; corrected without suppression.
- GREEN: `9f380d0d62959740e69922bf4c45e9c789a5bafa`, CI #839 / run `31325858072`.
- Security job `93276003095`; SQLite `93276003138`; PostgreSQL `93276003133`; Pint `93276003151` — SUCCESS.

### Gate 3
- RED: `64e63d96308819dead8cd5d21a92790d03952eaa`, CI #841 / run `31325985498`.
- GREEN: `721daffb05894fc1a22a2f7f79e57725b19bf239`, CI #842 / run `31326047120`.

### Gate 4
- RED: `47952a96d6f6a2995b1f4ba9f6bd4c265e13001e`, CI #844 / run `31326160289`.
- GREEN: `ce3d0b79b361462d90a4d81b351e8bf8f75e8aab`, CI #846 / run `31326277134`.

### Gate 5
- RED: `c44df7be1fa4075d528546a238c678e720e379e9`, CI #848 / run `31326403341`.
- GREEN: `b71b7d913973f77fd9b227ca6e2691596620779e`, CI #849 / run `31326470752`.

### Gate 6
- RED: `f721a1786e418bf53c5ffeb85e46dcdcdfa4352c`, CI #851 / run `31326632581`.
- GREEN: `3b85929ab7c35450f65e762139435e3b0dccd726`, CI #852 / run `31326693188`.

### Gate 7
- RED: `798f3a48a96ff2e8f18b1c2ffdc14277b6d85f96`, CI #854 / run `31326868056`.
- GREEN: `c4f07e0ac4ea1dedb8f7d2ff156b617d1a52dbf7`, CI #858 / run `31327018573`.

### Gate 8 — Final Forensic Audit & Protected Integration
- Invalid preliminary RED attempts: #861 and #862 — rejected because Pint was not green; not used as official TDD evidence.
- Official RED SHA: `81e67e6218ab45da735174c0aacfdb6071891049`.
- Official RED CI: #863 / run `31327290746` — Security and Pint SUCCESS; database jobs reached final suites after inherited release/hardening steps and failed on intentionally missing final forensic contracts.
- Green candidate tree SHA before final evidence synchronization: `8eabd649d128c2337a302711aed7ad80f20032d9`.
- Candidate CI: #865 / run `31327553818` — all five jobs SUCCESS:
  - Security `93280222083`;
  - Container `93280222127`;
  - SQLite `93280222097`;
  - PostgreSQL 16 `93280222123`;
  - Pint `93280222072`.
- Candidate result: actual Docker image built successfully; `pdo_pgsql`, non-root runtime and frontend manifest verified; PostgreSQL cacheability, fresh migrations, least-privilege role, rollback/reapply, concurrency invariants and full suite all green.
- Final audit artifact: `docs/PHASE_M9_AUDIT.md`.
- Exact-head CI: pending on the frozen HEAD created by this evidence synchronization.
- Merge evidence: pending; it will be recorded in PR #12 metadata/discussion so the tested repository tree is not moved after exact-head verification.
