# PHASE M9 AUDIT — Release & Final Product Hardening

Date: 2026-08-09
Baseline: M8 merge on `main` at `a093da910c246e5186ea53f0720c7f239713fe95`
Branch: `feature/m9-release-hardening`
PR: #12
Status: CANDIDATE AUDIT — exact-head verification pending

## 1. Audit objective

M9 closes the planned M1–M9 product program by proving that the feature-complete Tactical Scenario Lab can be packaged, tested, described and operated as a release-ready application without adding a new product domain.

The audit asks whether repository behavior, release documentation, CI, dependency security, container packaging and production runbooks agree with one another and preserve the M1–M8 authorization, tenant, historical and PostgreSQL hardening invariants.

## 2. Scope and delta

Pre-candidate comparison against `main` showed the M9 branch ahead with no divergence from the M8 baseline. The delta is confined to:

- product/release metadata (`.env.example`, `composer.json`, `config/app.php`);
- CI/release workflow;
- reference `Dockerfile`;
- `SECURITY.md`, README, CHANGELOG, production/release documentation;
- M9 spec, implementation plan and evidence ledger;
- M9 release-contract tests.

No migration, Eloquent model, controller, domain service or business-domain write path is introduced by M9. The milestone is release hardening, not feature expansion.

## 3. Invariants preserved

M9 must not weaken and the inherited test matrix continues to cover:

- authenticated organization context and backend authorization;
- tenant isolation;
- published scenario-version immutability;
- append-only execution timeline behavior;
- finalized assessment historical freeze semantics;
- authorized post-finalization action-status evolution;
- PostgreSQL least-privilege runtime role;
- M6 structural/immutability migration rollback and reapply;
- repeated PostgreSQL concurrency invariants;
- M7 shell/accessibility/low-light behavior;
- M8 safe repository-backed Knowledge Center, search and content integrity.

## 4. Gate evidence

### Gate 1 — Release baseline

- RED: `103c10f58cd4d3c8108300be7ef102a34f11e2e8`, CI #829 / run `31325365945`.
- GREEN: `21541468161bedcc54c7880eaca63c85ebf72753`, CI #834 / run `31325504446`.
- Result: stale MVP/authentication claims, generic Laravel identity/locale, SQLite/migrate-on-start production drift and historical branch triggers were removed from the release baseline.

### Gate 2 — Security and dependencies

- RED: `b788e7624f316b3a588eea56ca374eeeb64f830e`, CI #836 / run `31325649447`.
- Rejected candidates #837 and #838 were not promoted: one exposed an incorrect Composer audit invocation; the next exposed an accidental PostgreSQL sequence-GRANT regression. Both were corrected without suppression.
- GREEN: `9f380d0d62959740e69922bf4c45e9c789a5bafa`, CI #839 / run `31325858072`.
- Result: `composer audit --locked` and `npm audit --audit-level=high` are release-blocking and non-suppressive; no blanket ignore list or CI auto-fix is used.

### Gate 3 — Container/deployment parity

- RED: `64e63d96308819dead8cd5d21a92790d03952eaa`, CI #841 / run `31325985498`.
- GREEN: `721daffb05894fc1a22a2f7f79e57725b19bf239`, CI #842 / run `31326047120`.
- Result: reference container declares PostgreSQL support, deterministic frontend build, production Composer install, non-root runtime and no migrate-on-web-startup behavior.

### Gate 4 — CI/release pipeline

- RED: `47952a96d6f6a2995b1f4ba9f6bd4c265e13001e`, CI #844 / run `31326160289`.
- GREEN: `ce3d0b79b361462d90a4d81b351e8bf8f75e8aab`, CI #846 / run `31326277134`.
- Result: the primary CI release line targets `main`, historical branch exceptions are gone, dependency/security/build/database/Pint gates remain present, and GitHub Actions checkout/setup-node were modernized after the RED run surfaced the deprecated Node 20 action-runtime warning.

### Gate 5 — Reliability/cacheability

- RED: `c44df7be1fa4075d528546a238c678e720e379e9`, CI #848 / run `31326403341`.
- GREEN: `b71b7d913973f77fd9b227ca6e2691596620779e`, CI #849 / run `31326470752`.
- Result: config/route cacheability is deterministic in both database jobs; liveness remains database-independent and readiness remains coarse and secret-safe. No wall-clock performance threshold was institutionalized.

### Gate 6 — UX/localization/accessibility

- RED: `f721a1786e418bf53c5ffeb85e46dcdcdfa4352c`, CI #851 / run `31326632581`.
- GREEN: `3b85929ab7c35450f65e762139435e3b0dccd726`, CI #852 / run `31326693188`.
- Result: fallback product identity and locale are Tactical Scenario Lab / `pt_BR`; inherited skip-link, Knowledge navigation, reduced-motion and browser-local low-light contracts remain green without an M7 redesign.

### Gate 7 — Release/recovery documentation

- RED: `798f3a48a96ff2e8f18b1c2ffdc14277b6d85f96`, CI #854 / run `31326868056`.
- GREEN: `c4f07e0ac4ea1dedb8f7d2ff156b617d1a52dbf7`, CI #858 / run `31327018573`.
- Result: release SHA evidence, migration/runtime identity split, traffic admission, application rollback, schema rollback, PITR and provider-neutral observability boundaries are documented. CHANGELOG preserves the historical 0.1.0 record and does not fabricate a new semantic version.

### Gate 8 — Forensic candidate

Two preliminary attempts (#861 and #862) were rejected before being treated as official RED evidence because Pint detected style violations in the new forensic test. No release requirement was weakened.

Official RED:

- SHA: `81e67e6218ab45da735174c0aacfdb6071891049`
- CI: #863 / run `31327290746`
- Security: SUCCESS
- Pint: SUCCESS
- SQLite/PostgreSQL reached their final suites only after release cacheability, migrations and PostgreSQL hardening steps had succeeded, then failed on the intentionally absent final forensic contracts.

The remediation adds a real container-build/runtime job and this audit artifact. Candidate and exact-head evidence remain pending until those checks pass on a frozen SHA.

## 5. Security and dependency posture

The release line explicitly blocks on Composer lockfile advisories and npm vulnerabilities at high severity or above. M9 does not use broad advisory ignores, lockfile auto-fix or forced audit remediation inside CI.

`SECURITY.md` reflects the current authenticated, multi-organization product and includes tenant isolation, PII, authorization, session/auth, injection and Knowledge rendering in scope.

No production secret, database password, certificate private key, `APP_KEY` or `PII_FINGERPRINT_KEY` is introduced into source control by M9.

## 6. Container and production parity

The M9 reference image is required to build in CI. CI additionally inspects that:

- `pdo_pgsql` exists in the built runtime;
- the default runtime UID is not root;
- compiled frontend assets are present.

The container does not run migrations on ordinary web startup. `docs/RELEASE.md` and `docs/PRODUCTION.md` require the migration identity to execute preflight/migrations separately from the least-privilege runtime identity.

The reference container is not represented as a complete production topology. Ingress, TLS termination, process supervision, managed PostgreSQL networking and provider-specific controls remain infrastructure responsibilities.

## 7. Reliability and observability

CI verifies Laravel config/route cacheability, frontend build, fresh migrations, both database suites and PostgreSQL hardening.

Public health probes remain intentionally minimal. Detailed diagnosis belongs in protected logs/provider telemetry; M9 does not fabricate an APM, SIEM or logging-provider integration.

## 8. Recovery posture

The repository documents three distinct decisions:

1. application rollback when the previous application is schema-compatible;
2. schema rollback only after compatibility/data-integrity review;
3. backup/PITR restore when data recovery is required.

A real production **restore** or PITR drill is provider/operator work and is not executed by this repository CI. The documentation explicitly requires periodic recovery testing rather than treating a backup job as proof of recoverability.

## 9. UX/accessibility audit boundary

Source contracts and rendered feature tests cover authored accessibility behavior such as semantic navigation, skip link, labels/current state, reduced motion and M7/M8 interaction contracts.

This connected environment does not provide a real authenticated multi-browser session for a **pixel**-by-pixel visual audit. Therefore M9 does not claim pixel-perfect validation across browser engines, viewport classes or operating systems.

## 10. Provider/deployment boundary

No real production **provider** deployment is executed by this audit. CI proves repository-side packaging and runtime contracts. Provider-specific TLS, secrets, backup/PITR, network policy, observability and traffic routing must still be configured and verified by the production operator.

## 11. Severity disposition

Candidate promotion requires that no known **Critical** or **High** M9 finding remain open. Security advisories at release-blocking severity must be remediated rather than suppressed.

At this pre-candidate stage, no known unresolved Critical or High finding has been accepted for release; final disposition is confirmed only after the candidate and exact-head matrices are fully green.

## 12. Final integration policy

The final candidate must be frozen after audit/ledger synchronization. A fresh **exact-head** CI must then pass on that exact SHA. No repository-file commit may occur after the exact-head run used for integration.

Before protected merge, the PR must be mergeable, zero commits behind current `main`, free of unresolved review threads and contain only the expected M9 release-hardening delta.

Merge uses `expected_head_sha=<frozen verified SHA>`. Post-merge verification must prove `main` is identical to the actual merge commit and, where available, that the tested synthetic merge tree and real merge tree have zero file differences.

A hosted tag/release is not required for M9 completion and will not be fabricated without an unambiguous versioning decision.
