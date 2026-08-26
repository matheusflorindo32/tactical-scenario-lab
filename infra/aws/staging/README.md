# AWS Staging Infrastructure Contract — Historical/Future Option

Status: **SUPERSEDED FOR CURRENT R1 STAGING**

R1 originally selected AWS for a high-control production-style topology. On 2026-08-09 the active staging strategy was revised to **Vercel + Neon** to minimize current cost and operational surface while preserving M6–M9 security/release contracts.

This file is retained as architecture history and as a future enterprise/higher-control option. It is not the active Gate 2 runbook and no AWS resource is claimed as created.

Active staging contract:

- `infra/vercel/staging/README.md`
- `docs/R1_STAGING_RUNBOOK.md`
- `docs/R1_PROVIDER_SCORECARD.md`

## Historical AWS topology

```text
GitHub exact SHA
  -> ECR immutable image
  -> ECS/Fargate native BLUE_GREEN
  -> ALB + ACM
  -> RDS PostgreSQL 16
  -> Secrets Manager
  -> CloudWatch Logs
```

The original security requirements remain useful if AWS is reconsidered later:

- separate staging/production boundaries;
- immutable release identity;
- migration/runtime database identity separation;
- TLS-verified PostgreSQL transport;
- PITR + isolated restore drill;
- external secrets;
- private logs;
- blue/green candidate qualification before traffic promotion;
- no long-lived provider key committed to Git.

Any future return to AWS requires a new current provider/cost review before this contract becomes active again.
