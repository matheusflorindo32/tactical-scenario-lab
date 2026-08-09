# R1 Progress — Production Release & Operational Validation

Branch: `feature/r1-production-operational-validation`
PR: #13 — draft
Baseline: M9 `main` merge `1d77b89ef273e97cc53c7901df2d0f405684df45`
Spec: `docs/superpowers/specs/2026-08-09-r1-production-operational-validation-design.md`
Plan: `docs/superpowers/plans/2026-08-09-r1-production-operational-validation.md`
Status: PROVIDER REVISION — VERCEL + NEON

## Progress model

- Architecture/design: 5% — complete.
- Written spec review + implementation/operations plan: 5% — complete.
- Provider/environment gate: 10% — previously earned; Gate 1A is replacing AWS with Vercel + Neon and does not add duplicate percentage.
- Gates 2–7: 10% each.
- Gate 8 + production promotion/closeout: final 20%.

Current earned R1 progress: **20%**.

Changing the staging provider does not retroactively award progress. Gate 2 raises R1 above 20% only after real hosted staging evidence exists.

## Gates

- [~] Gate 1A — Vercel + Neon Provider & Environment Contract — implementation/revalidation in progress.
- [ ] Gate 2 — Real Isolated Staging + TLS.
- [ ] Gate 3 — Secrets + Database Runtime Least Privilege.
- [ ] Gate 4 — PostgreSQL Recovery / Restore Drill.
- [ ] Gate 5 — Real Deployment + Health Admission.
- [ ] Gate 6 — Authenticated Smoke/E2E + Browser QA.
- [ ] Gate 7 — Observability + Failure/Recovery Drill.
- [ ] Gate 8 — Production Decision + Release Closeout.

## Historical Gate 1

The original provider evaluation selected AWS and proved its repository design with CI #875. That evidence remains historically valid but AWS is no longer the active R1 staging provider because its ECS/Fargate + ALB + RDS + Secrets Manager + CloudWatch topology introduces unnecessary cost/operational surface for this validation phase.

AWS documentation is retained as a future enterprise/higher-control option. No AWS resource was ever claimed as created.

## Gate 1A — Vercel + Neon evidence

Provider/session evidence:

- Vercel workspace authenticated in this ChatGPT session;
- team ID: `team_QHEyDZZUIeF7hGokK8amHy4H`;
- Vercel projects enumerated; no Tactical Scenario Lab project existed at re-selection time;
- Vercel first-party documentation confirms `Dockerfile.vercel` container images, container services, deployment environments, external environment variables, managed deployment HTTPS and runtime logs;
- Vercel first-party documentation confirms a Neon Marketplace provisioning/integration path;
- revised architecture/spec explicitly treats free/Hobby resources as staging only and requires a new Gate 8 production-plan decision.

Repository evidence:

- revised R1 spec commit: `99e98983e3d9f7d0e0d4bb23d43f9cd7411caec4`;
- revised implementation plan commit: `df8e35de3afb7b3d74ab5a515a2f39ba26ed2a35`;
- TDD RED contract test commit: `1b153064179119f724a067d7ed552a5d98a63457`;
- RED CI: #886 / run `31335797197` — `R1VercelContainerContractTest` failed exactly because `Dockerfile.vercel` did not exist; SQLite suite otherwise reported 343 passed / 31 skipped;
- minimal GREEN implementation commit: `2f520df9a8594a05ef13c54e88e407cf24119205` adding `Dockerfile.vercel` while preserving PHP 8.4, PostgreSQL driver, frontend assets, non-root runtime, `$PORT` and no migration-on-startup behavior.

`docs/R1_PROVIDER_SCORECARD.md` is now the active Vercel + Neon provider contract and `docs/R1_STAGING_RUNBOOK.md` is the active execution runbook.

## Gate 2 repository preparation

Prepared repository-side components:

- `Dockerfile.vercel` provider adapter;
- `tests/Feature/R1VercelContainerContractTest.php` contract test;
- provider scorecard and staging runbook revised for Vercel + Neon;
- staging may use a custom `staging` environment when available or an isolated Preview target otherwise;
- dedicated Neon staging PostgreSQL is required;
- staging-only `APP_KEY`, `PII_FINGERPRINT_KEY` and DB credentials must remain outside Git;
- exact Git SHA + Vercel deployment ID/URL are required;
- `/health/live` and `/health/ready` must be verified on the hosted candidate;
- controlled migration path + restricted runtime PostgreSQL role remain mandatory.

## Current provider-action boundary

The Vercel connector available in this session can authenticate, enumerate projects/deployments, inspect builds/logs and deploy a current linked project. It does **not currently expose explicit create-project, create-environment, environment-variable mutation or Neon Marketplace provisioning actions**.

Therefore repository preparation can be completed and verified here, but Gate 2 must not be marked GREEN unless a safe authenticated path actually creates/links the Tactical Scenario Lab project, provisions Neon, scopes secrets and returns a real HTTPS deployment.

No Vercel token, Neon password or database URL should be pasted into chat or committed to GitHub.

## Exact-head policy

Every repository-file write invalidates earlier exact-head CI as final evidence. After the provider-revision documents and configuration are complete, freeze the branch and require the inherited M9 matrix on that exact final HEAD:

- dependency security;
- real release container build/runtime contract;
- SQLite suite;
- PostgreSQL 16 suite including least privilege, rollback/reapply and M6 concurrency;
- Pint.

Gate 1A becomes GREEN only after that final exact-head matrix succeeds.

## Process integrity

Earlier R1 temporary routing artifacts were removed with zero functional file differences and `main` was never modified. The active branch remains the only implementation surface for R1.
