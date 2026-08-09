# PHASE M9 AUDIT — Release & Final Product Hardening

Date: 2026-08-09
Baseline: M8 merge on `main` at `a093da910c246e5186ea53f0720c7f239713fe95`
Branch: `feature/m9-release-hardening`
PR: #12
Status: CANDIDATE GREEN — exact-head verification pending

## 1. Objective and scope

M9 turns the M1–M8 feature-complete Tactical Scenario Lab into a release-ready repository without adding a new product domain. It aligns release metadata, dependency security, CI, container packaging, production/recovery documentation, reliability and localization while preserving M1–M8 authorization, tenant, historical and PostgreSQL invariants.

Pre-candidate comparison against `main` showed the M9 branch ahead and `0 behind`. The delta is confined to release/config metadata, CI, Dockerfile, security/operations documentation, M9 spec/plan/ledger and M9 contract tests. No migration, Eloquent model, controller, domain service or business-domain write path is introduced by M9.

## 2. Preserved invariants

The inherited matrix continues to cover authenticated organization context, backend authorization, tenant isolation, published scenario-version immutability, append-only execution timeline, finalized assessment historical freeze, authorized action-status evolution, PostgreSQL least-privilege runtime, M6 rollback/reapply and concurrency, M7 accessibility/low-light and M8 Knowledge Center security/search/content integrity.

## 3. Gate evidence

- **Gate 1:** RED `103c10f58...`, CI #829; GREEN `215414681...`, CI #834. Release metadata, locale, security language, Docker baseline and obsolete branch triggers were corrected.
- **Gate 2:** RED `b788e762...`, CI #836; GREEN `9f380d0d...`, CI #839. `composer audit --locked` and `npm audit --audit-level=high` became release-blocking. Candidates #837/#838 were rejected and corrected rather than suppressed.
- **Gate 3:** RED `64e63d96...`, CI #841; GREEN `721daffb...`, CI #842. PostgreSQL-capable deterministic frontend/container contract and non-root runtime were established.
- **Gate 4:** RED `47952a96...`, CI #844; GREEN `ce3d0b79...`, CI #846. CI now represents the `main` release line and uses current action runtime lines while retaining all M6 gates.
- **Gate 5:** RED `c44df7be...`, CI #848; GREEN `b71b7d91...`, CI #849. Config/route cacheability is verified in both database jobs without wall-clock thresholds; health remains secret-safe.
- **Gate 6:** RED `f721a178...`, CI #851; GREEN `3b85929a...`, CI #852. Product/locale fallbacks are Tactical Scenario Lab / `pt_BR`; M7/M8 UX contracts remain green without redesign.
- **Gate 7:** RED `798f3a48...`, CI #854; GREEN `c4f07e0a...`, CI #858. Release SHA evidence, migration/runtime identities, traffic admission, application rollback, schema rollback, PITR and provider-neutral observability are documented.
- **Gate 8 official RED:** SHA `81e67e6218ab45da735174c0aacfdb6071891049`, CI #863 / run `31327290746`. Security and Pint passed; SQLite/PostgreSQL reached final suites after inherited release/hardening checks, then failed on intentionally absent final forensic contracts. Preliminary #861/#862 were rejected as invalid RED evidence because Pint failed.

## 4. Green forensic candidate

Candidate tree before this audit synchronization: `8eabd649d128c2337a302711aed7ad80f20032d9`, CI **#865 / run `31327553818`**.

All five release jobs passed:

- Security — Composer and npm audit: job `93280222083` — SUCCESS;
- Container — build and runtime contract: job `93280222127` — SUCCESS;
- PHPUnit SQLite: job `93280222097` — SUCCESS;
- PHPUnit PostgreSQL 16: job `93280222123` — SUCCESS;
- Pint: job `93280222072` — SUCCESS.

The container job built the actual Docker image and proved `pdo_pgsql`, non-root default runtime and compiled frontend assets. PostgreSQL also passed release cacheability, fresh migrations, least-privilege provisioning, M6 rollback/reapply, repeated concurrency invariants and the full suite.

This document and the progress ledger are the final evidence synchronization before the frozen exact-head SHA. No repository-file commit is permitted after the exact-head run selected for integration.

## 5. Security and severity disposition

Composer lockfile advisories and npm high-severity vulnerabilities block release. No blanket advisory ignore, npm audit fix or lockfile mutation is used in CI. `SECURITY.md` reflects the authenticated multi-organization product and includes authorization, tenant isolation, PII, injection/session/auth and Knowledge rendering risks.

No known unresolved **Critical** or **High** M9 finding remains in the green candidate. A future Critical or High finding blocks promotion rather than being accepted silently.

## 6. Container and production parity

The real reference image now builds in CI. CI verifies `pdo_pgsql`, non-root runtime and `public/build` output. Ordinary web startup does not run migrations. `docs/RELEASE.md` and `docs/PRODUCTION.md` separate the privileged migration identity from the least-privilege runtime identity.

The image is a reference application runtime, not a complete cloud topology. Ingress, TLS termination, managed PostgreSQL networking and provider-specific controls remain external responsibilities.

## 7. Reliability, observability and recovery

CI verifies frontend build, Laravel config/route cacheability, both databases and PostgreSQL hardening. Public health probes remain minimal; detailed diagnosis belongs in protected logs/provider telemetry. M9 does not fabricate an APM, SIEM or logging-provider integration.

Recovery documentation distinguishes application rollback, schema rollback and backup/PITR **restore**. A real production restore/PITR drill is a **provider**/operator responsibility and is not executed by repository CI.

## 8. UX/accessibility validation boundary

Feature/source contracts cover semantic navigation, skip link, current state, Knowledge help, reduced motion and browser-local low-light behavior. This environment does not provide an authenticated multi-browser **pixel**-by-pixel visual audit, so no pixel-perfect cross-browser claim is made.

## 9. Final exact-head and integration policy

After this audit and ledger are synchronized, the resulting HEAD is frozen. A fresh **exact-head** CI must pass Security, Container, SQLite, PostgreSQL 16 and Pint on that exact SHA. No file commit may occur afterward.

Before protected merge, PR #12 must be mergeable, `0 behind` current `main`, free of unresolved review threads and limited to the expected M9 release-hardening delta. Merge must use `expected_head_sha=<frozen verified SHA>`. Post-merge verification must prove `main` identical to the actual merge commit and, where available, zero file differences between the tested synthetic merge tree and actual merge tree.

No new semantic version/tag is fabricated by M9. Release readiness is defined by the exact tested repository state.
