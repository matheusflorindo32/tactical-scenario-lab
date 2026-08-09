# R1 Progress — Production Release & Operational Validation

Branch: `feature/r1-production-operational-validation`
PR: #13 — draft
Baseline: M9 `main` merge `1d77b89ef273e97cc53c7901df2d0f405684df45`
Spec: `docs/superpowers/specs/2026-08-09-r1-production-operational-validation-design.md`
Plan: `docs/superpowers/plans/2026-08-09-r1-production-operational-validation.md`
Status: GATE 1 GREEN — GATE 2 PREPARED / EXTERNALLY BLOCKED

## Progress model

- Architecture/design: 5% — complete.
- Written spec review + implementation/operations plan: 5% — complete.
- Gates 1–7: 10% each.
- Gate 8 + production promotion/closeout: final 20%.

Current earned R1 progress: **20%**.

## Gates

- [x] Gate 1 — Provider & Environment Contract — GREEN.
- [ ] Gate 2 — Isolated Staging + TLS — PREPARED, blocked on authenticated AWS execution path.
- [ ] Gate 3 — Secrets + Migration/Runtime Identity Separation.
- [ ] Gate 4 — PostgreSQL + Backup/PITR + Restore Drill.
- [ ] Gate 5 — Real Deployment + Health Admission.
- [ ] Gate 6 — Authenticated Smoke/E2E + Browser QA.
- [ ] Gate 7 — Observability + Failure/Recovery Drill.
- [ ] Gate 8 — Production Promotion + Release Closeout.

## Gate 1 evidence

AWS, Google Cloud and Azure were compared against the blocking R1 provider contract using current official primary documentation.

Selected: **AWS**.

AWS blocking result recorded in `docs/R1_PROVIDER_SCORECARD.md`: **12 PASS / 0 FAIL / 0 UNKNOWN**.

Selected AWS stack:

- Amazon ECR immutable image identity;
- Amazon ECS on Fargate;
- ECS native blue/green deployment with primary/alternate target groups and test routing;
- Application Load Balancer + ACM HTTPS;
- Amazon RDS PostgreSQL 16 with TLS/`verify-full`, `rds.force_ssl` and PITR;
- AWS Secrets Manager with separate migration/runtime/application secrets;
- CloudWatch Logs.

### Critical finding and resolution

The provider review found that Application Load Balancer target health cannot be the sole traffic-admission authority. R1 therefore requires ECS native `BLUE_GREEN`, an alternate target group and test routing before any production listener shift. `/health/live`, `/health/ready`, runtime least privilege and smoke checks must pass before promotion, with blue retained through a bake/rollback window.

Plan-hardening commit: `fee2ca0aa4b3b825567a6a3b14a49852140250fe`.

### Gate 1 CI

Gate 1 GREEN was established on repository HEAD `ef09cc32ef3f0b685548a01ebaa920e985188431` with CI **#875 / run `31333058180`**:

- Security `93294258906` — SUCCESS;
- Container real `93294258902` — SUCCESS;
- SQLite `93294258894` — SUCCESS;
- PostgreSQL 16 `93294258879` — SUCCESS, including cacheability, migrations, least-privilege, rollback/reapply and M6 concurrency;
- Pint `93294258899` — SUCCESS.

## Gate 2 preparation completed

Repository-side handoff is prepared without pretending that provider resources exist:

- `docs/R1_STAGING_RUNBOOK.md` — ordered AWS staging bootstrap and validation procedure;
- `infra/aws/staging/README.md` — infrastructure contract and evidence boundaries;
- GitHub Actions OIDC/STS is the preferred deployment authentication model; long-lived AWS keys are prohibited as the normal path;
- staging topology is fixed to ECR -> ECS/Fargate native blue/green -> ALB/ACM -> RDS PostgreSQL 16 -> Secrets Manager -> CloudWatch;
- staging and production must use distinct secrets/database identities and must not share authoritative production data;
- `DB_SSLMODE=verify-full` with AWS RDS CA trust is required;
- Gate 2 evidence requires real AWS resources, valid HTTPS, distinct RDS/secrets, exact deployed SHA+image digest, blue/green test routing and CloudWatch logging.

### Prepared-package CI

After adding the staging runbook/infrastructure contract and synchronizing this ledger, repository HEAD `04d37751f8b6b32784bbb2e65311085d191cf1cc` passed CI **#878 / run `31333716473`**:

- Security `93295954136` — SUCCESS;
- Container real `93295954201` — SUCCESS;
- SQLite `93295954116` — SUCCESS;
- PostgreSQL 16 `93295954086` — SUCCESS, including cacheability, migrations, least-privilege, rollback/reapply and M6 concurrency;
- Pint `93295954111` — SUCCESS.

This verification proves the repository-side Gate 2 handoff preserves all inherited M9 contracts. It does not substitute for real AWS staging evidence.

## External access boundary

This ChatGPT session currently has no authenticated AWS/ECS/RDS connector. Plugin discovery returned no AWS integration. Therefore no AWS account, credential, VPC, RDS instance, ECS service, DNS name, certificate or deploy is claimed.

Gate 2 remains **BLOCKED — external access unavailable**, not PASS. Static AWS access keys must not be pasted into chat, committed to GitHub or added to PR evidence. Resume through a controlled AWS integration or OIDC/role-based staging execution path.

## Process note

During Gate 1 work, a temporary `__nonexistent__` file was accidentally created on the R1 branch and immediately deleted. Comparison from the pre-incident functional tree to the restored tree showed zero file differences. `main` was never modified.

Any further repository-file change requires the inherited M9 Security + Container + SQLite + PostgreSQL + Pint matrix before it can be treated as a valid R1 candidate.
