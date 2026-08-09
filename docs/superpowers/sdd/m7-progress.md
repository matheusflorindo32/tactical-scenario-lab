# M7 Progress — Operational Command Center

Branch: `feature/m7-operational-command-center`
PR: #10 — draft
Status: IN PROGRESS

## Gates

- [x] Gate 1 — Shell & navigation — GREEN
- [x] Gate 2 — Design-system primitives — GREEN
- [x] Gate 3 — Dashboards — GREEN
- [x] Gate 4 — Scenarios/templates/history — GREEN
- [ ] Gate 5 — Execution cockpit
- [ ] Gate 6 — Assessment & debrief workbench
- [ ] Gate 7 — Management & low-light polish
- [ ] Gate 8 — Forensic UX audit & exact-head gate

## Baseline

- Design spec: `docs/superpowers/specs/2026-08-09-m7-operational-command-center-design.md`
- Implementation plan: `docs/superpowers/plans/2026-08-09-m7-operational-command-center.md`
- M7 changes are isolated from `main` until final protected merge.
- No M8/M9 scope is permitted in this branch.

## Evidence ledger

### Gate 1 — Shell & navigation
- RED SHA: `00cf1f0afb914ea932b8ee4d6c72e3ae81276037`
- RED CI: #734 / run `31310731234` — M7 shell assertions failed while legacy regression tests remained green.
- GREEN SHA: `a4753f0a39ba458713dba9313319118e5cd1bb16`
- GREEN CI: #736 / run `31310837397` — SQLite, PostgreSQL 16, M6 concurrency invariants, Vite build and Pint green.
- Result: canonical ability-aware sidebar; contextual topbar; active organization; real account/logout actions; no placeholder navigation.

### Gate 2 — Design-system primitives
- RED SHA: `6fb5cd579a5ff9f68ac21c909bc63791691c655e`
- RED CI: #737 / run `31310915085` — three component contracts failed because the primitives did not yet exist; 274 tests passed.
- GREEN SHA: `23e060befd2c262026b58d9985e768bedcecfd16`
- GREEN CI: #740 / run `31310991688` — SQLite, PostgreSQL 16, Vite build and Pint green.
- Result: `x-table`, `x-section-nav` and `x-attention-item` implemented with semantic/accessibility contracts.

### Gate 3 — Attention-first dashboards
- RED SHA: `6c965cfd473964a4f519af5fa6d4601a3d33317f`
- RED CI: #741 / run `31311059255` — expected failures for missing attention hierarchy and wrong executive active navigation; 278 tests passed in SQLite.
- GREEN SHA: `6323335056c555549ed59532e1a194295b8c22e6`
- GREEN CI: #743 / run `31311210220` — SQLite, PostgreSQL 16, repeated M6 concurrency invariants, Vite build and Pint green.
- Result: instructor dashboard ordered by operational attention using existing M5 truth; executive dashboard prioritizes risk, marks its own navigation state and no longer renders the completed-execution hint as a literal PHP expression.

### Gate 4 — Scenarios/templates/history
- RED SHA: `cb9aa04e4863315d9a6cff9ef8847f3b9f43f0de`
- RED CI: #745 / run `31311357647` — three expected workspace failures; 280 existing tests passed in SQLite. RED proved missing lifecycle language, incorrect Templates/Histórico active state and missing accessible history table contract.
- GREEN SHA: `b36ff5d386f090dea931401d7fc491c8c2565d06`
- GREEN CI: #749 / run `31311539493` — SQLite, PostgreSQL 16, least-privilege role, M6 rollback/reapply, repeated concurrency invariants, Vite build and Pint green.
- Result: scenario workspace is version-first, removes `Scenario.score` from institutional presentation, exposes the lifecycle explicitly, makes Templates/Histórico canonical navigation contexts and uses the accessible table primitive for execution history.

### Gate 5 — Execution cockpit
- RED SHA: pending
- RED CI: pending
- GREEN SHA: pending
- GREEN CI: pending
