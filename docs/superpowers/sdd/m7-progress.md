# M7 Progress — Operational Command Center

Branch: `feature/m7-operational-command-center`
PR: #10 — draft
Status: FINAL EXACT-HEAD VALIDATION

## Gates

- [x] Gate 1 — Shell & navigation — GREEN
- [x] Gate 2 — Design-system primitives — GREEN
- [x] Gate 3 — Dashboards — GREEN
- [x] Gate 4 — Scenarios/templates/history — GREEN
- [x] Gate 5 — Execution cockpit — GREEN
- [x] Gate 6 — Assessment & debrief workbench — GREEN
- [x] Gate 7 — Management & low-light polish — GREEN
- [x] Gate 8 — Forensic UX audit — GREEN candidate; exact-head integration proof is attached to PR metadata after this ledger-bearing commit passes CI

## Baseline

- Design spec: `docs/superpowers/specs/2026-08-09-m7-operational-command-center-design.md`
- Implementation plan: `docs/superpowers/plans/2026-08-09-m7-operational-command-center.md`
- Audit: `docs/PHASE_M7_AUDIT.md`
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
- RED SHA: `65242f469872ba8f9b32743eb2fefb71c06cc286`
- RED CI: #751 / run `31311655068` — two expected cockpit-contract failures; 283 existing tests passed in SQLite. RED proved missing command-region structure, contextual section navigation and explicit assessment/timeline historical landmarks.
- GREEN SHA: `29047e5d6982e3dc5f06dc646bee1e1f42d0d2b1`
- GREEN CI: #752 / run `31311727396` — SQLite, PostgreSQL 16, least-privilege role, M6 rollback/reapply, repeated concurrency invariants, Vite build and Pint green.
- Result: execution view is now an operational cockpit with lifecycle command region, anchor navigation, timeline-first hierarchy, append-only historical presentation and explicit assessment/resources/injects operational regions while preserving all existing forms and domain guards.

### Gate 6 — Assessment & debrief workbench
- RED SHA: `a2b81b8a608eb2eba8048319fa2887a80887f517`
- RED CI: #754 / run `31311842267` — two expected workbench failures; 285 existing tests passed in SQLite. RED proved missing section navigation/landmarks and insufficient frozen-state presentation.
- GREEN SHA: `377a288f5d0f94ec2389c376a4b862f22c7d8490`
- GREEN CI: #755 / run `31311983117` — SQLite, PostgreSQL 16, least-privilege role, M6 rollback/reapply, repeated concurrency invariants, Vite build and Pint green.
- Result: assessment is a navigable institutional workbench with real section anchors, explicit draft/finalized state, finalization readiness/irreversibility messaging, frozen historical presentation after finalization and preserved authorized action-status follow-up.

### Gate 7 — Management & low-light polish
- First RED attempt: `be8029ced2a037372e8cc4977bc0b04ce7a1013a` / CI #758 — discarded as RED evidence because Pint found a test-style issue before functional validation.
- Valid RED SHA: `86e69dc7dd379d7c439da9f0d79430aaf6a0cc32`
- Valid RED CI: #759 / run `31312175859` — Pint green; both SQLite and PostgreSQL reached the test phase and failed the new management/theme contracts after build and database hardening steps.
- GREEN SHA: `0e4fd97f199601ee8cbde036553a00991bbfe3ea`
- GREEN CI: #766 / run `31312446776` — SQLite, PostgreSQL 16, production asset build, Pint, least-privilege runtime role, M6 rollback/reapply and repeated concurrency invariants all green.
- Result: management indexes use canonical Tactical Scenario Lab branding, navigation, `x-table` and defined design tokens; low-light mode is opt-in, accessible, browser-local through Alpine + `localStorage`, defaults to light on SSR and introduces no API/database persistence.

### Gate 8 — Forensic UX audit & exact-head gate
- First audit candidate: `d19a53c4bf72ff75762ef3c0256d647ae4eefb3b` / CI #771 run `31312786461` — rejected. The forensic contract detected canonical use of `ink-600`/`ink-800` without corresponding custom Tailwind tokens.
- Remediation: added the missing ink tokens; made the forensic test dynamically require every custom color utility used by canonical M7 views to resolve to an `@theme` token; moved low-light semantic text colors into theme tokens; synchronized Design System and audit documentation.
- GREEN audit candidate SHA: `d7410abb62e483222dad57324049ddee184df2de`
- GREEN audit candidate CI: #776 / run `31313060335` — SQLite, PostgreSQL 16, production Vite build, Pint, least-privilege role, M6 rollback/reapply, repeated concurrency invariants and full dual-database suites all green.
- Forensic result: no Critical/High M7 finding remains identified by source/contract audit. Pixel-level authenticated browser review is explicitly not claimed because this connected environment does not provide that browser surface.
- Exact-head rule: this ledger update intentionally creates the final code/documentation HEAD. Its fresh CI evidence is recorded in PR #10 metadata instead of another repository commit, preventing the proof itself from moving the tested SHA.
- Merge evidence: pending protected merge after the ledger-bearing exact HEAD passes and PR freshness/review conditions are re-read.
