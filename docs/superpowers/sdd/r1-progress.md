# R1 Progress — Production Release & Operational Validation

Branch: `feature/r1-production-operational-validation`
PR: #13 — draft
Baseline: M9 `main` merge `1d77b89ef273e97cc53c7901df2d0f405684df45`
Spec: `docs/superpowers/specs/2026-08-09-r1-production-operational-validation-design.md`
Plan: `docs/superpowers/plans/2026-08-09-r1-production-operational-validation.md`
Status: GATE 1 VALIDATION

## Progress model

- Architecture/design: 5% — complete.
- Written spec review + implementation/operations plan: 5% — complete.
- Gates 1–7: 10% each.
- Gate 8 + production promotion/closeout: final 20%.

Current earned progress before Gate 1 CI: **10%**.

## Gates

- [ ] Gate 1 — Provider & Environment Contract — CANDIDATE, awaiting exact repository CI.
- [ ] Gate 2 — Isolated Staging + TLS.
- [ ] Gate 3 — Secrets + Migration/Runtime Identity Separation.
- [ ] Gate 4 — PostgreSQL + Backup/PITR + Restore Drill.
- [ ] Gate 5 — Real Deployment + Health Admission.
- [ ] Gate 6 — Authenticated Smoke/E2E + Browser QA.
- [ ] Gate 7 — Observability + Failure/Recovery Drill.
- [ ] Gate 8 — Production Promotion + Release Closeout.

## Gate 1 evidence

### Provider comparison

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

The provider review found that Application Load Balancer may fail open when every target in a target group is unhealthy. R1 therefore does **not** use ALB health as the sole traffic-admission authority.

The execution plan was hardened before Gate 1 completion:

1. native ECS `BLUE_GREEN` deployment is mandatory;
2. candidate green revision uses an alternate target group;
3. candidate validation uses test routing before production shift;
4. `/health/live`, `/health/ready`, least-privilege and smoke checks must pass before listener shift;
5. blue remains available through a bake/rollback window.

Plan-hardening commit: `fee2ca0aa4b3b825567a6a3b14a49852140250fe`.

### External boundary

No AWS account, credential, VPC, RDS instance, ECS service, DNS name or TLS certificate has been fabricated or claimed. Real resource creation belongs to Gate 2 and requires authenticated AWS access.

### Gate 1 promotion rule

Gate 1 becomes GREEN only when the final documentation HEAD passes the inherited M9 CI matrix. Until then, R1 remains at 10%.
