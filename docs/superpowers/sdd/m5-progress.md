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

Task 7: green — deterministic fictional DemoSeeder + demo walkthrough
- RED run #652 failed only because DemoSeeder did not yet exist;
- production guard is exercised directly;
- schema enum mismatch found in run #655 was corrected without widening schema;
- initial Task 7 gate: run #657 at `812ad42bf2ab9ebfd47e9b6cb784fef989edf2e4`;
- forensic review then expanded the permanent completeness contract to require running + completed states, draft + finalized assessments, observed error, key time and multiple action-item states;
- audit RED run #661: 262 existing tests green and only the expanded DemoSeeder contract failed;
- audit GREEN run #662: commit `26a685d05ce24a7e80ae7194e35c258871f12cdb`, PHPUnit + Pint green.

Task 8: integration gate — reporting/demo documentation + forensic M5 audit + reinforced exact final CI + protected integration
- `docs/REPORTING.md` documents truth sources, filters, historical attribution, multi-unit semantics, CSV and PDF security;
- `docs/DEMO.md` is aligned to the verified complete fictional graph;
- `docs/PHASE_M5_AUDIT.md` records the forensic audit and remediations;
- audit found no remaining Critical/High after remediation;
- workflow now requires `composer validate --strict`, `php artisan migrate:fresh --force`, `php artisan test`, `vendor/bin/pint --test`, `npm ci` and `npm run build`;
- still mandatory before merge: exact final HEAD CI green, synchronization with `main`, mergeable PR, zero unresolved review threads, final SHA re-read and merge commit bound to that SHA.
