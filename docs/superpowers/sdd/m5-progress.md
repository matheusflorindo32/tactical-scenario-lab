# SDD ledger — plan: docs/superpowers/plans/2026-08-08-m5-institutional-product.md

Task 1: green — historical participant attribution — CI run #602
Task 2: green — institutional filters + instructor/executive dashboards — CI run #620
Task 3: green — paginated institutional history — CI run #625
Task 4: green — stable, tenant-safe streaming CSV — CI run #630
Task 5: green — institutional PDF — final verified CI run #643
- original gate run #642 exposed a Unicode-only test serialization assertion;
- production/reporting behavior remained valid;
- permanent cross-org PDF block test retained;
- minimal test-only fix verified at commit `2ae408d653f513b5154bd45243df446045879bc4`.

Task 6: green — institutional scenario templates — CI run #651
- RED run #644 failed only on the new template contract;
- create/use/archive are tenant-safe and require `scenarios.manage`;
- template use creates a new draft definition without operational history.

Task 7: green — deterministic fictional DemoSeeder + demo walkthrough — CI run #657
- RED run #652 failed only because DemoSeeder did not yet exist;
- production guard is exercised directly;
- schema enum mismatch found in run #655 was corrected without widening schema;
- final verified head for Task 7: `812ad42bf2ab9ebfd47e9b6cb784fef989edf2e4`.

Task 8: in progress — reporting documentation + forensic M5 audit + reinforced exact final CI + protected integration
- `docs/REPORTING.md` added;
- `docs/DEMO.md` added in Task 7;
- forensic audit, exact final workflow gate, main synchronization, review-thread gate and protected merge remain open.
