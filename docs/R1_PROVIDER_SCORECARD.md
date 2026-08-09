# R1 Provider Scorecard — Production Operational Validation

Date: 2026-08-09
Status: GATE 1 CANDIDATE
Baseline: M9 `main` merge `1d77b89ef273e97cc53c7901df2d0f405684df45`

## Decision

**Selected provider architecture: AWS.**

Selected stack:

```text
GitHub exact SHA
  -> Amazon ECR immutable image tag/digest
  -> Amazon ECS on AWS Fargate
  -> ECS native BLUE_GREEN deployment
       -> blue target group (current)
       -> green target group (candidate)
       -> test listener/rule for candidate validation
       -> production listener shifts only after R1 admission
  -> Application Load Balancer + ACM HTTPS
  -> Amazon RDS for PostgreSQL 16
       -> TLS required
       -> sslmode=verify-full with RDS CA
       -> automated backups + PITR
  -> AWS Secrets Manager
       -> separate migration and runtime secrets
  -> CloudWatch Logs
```

The reason for selecting AWS is **compatibility with the security contract already implemented in M6/M9**, not provider popularity. RDS maps directly to the existing `DB_SSLMODE=verify-full` posture and supports endpoint certificate verification and `rds.force_ssl`; ECS/Fargate supports isolated container tasks; one-off migration tasks and service runtime can reference different secret sets; ECR supports immutable tags; ECS blue/green provides a candidate target group/test path before production traffic; CloudWatch provides private container logs; and RDS PITR restores into a new DB instance.

## Blocking capability scorecard

Legend: PASS = demonstrated capability exists in current primary documentation; FAIL = capability absent/incompatible; UNKNOWN = not verified. Any FAIL/UNKNOWN on the **selected** architecture blocks Gate 1.

| Blocking requirement | AWS | Google Cloud | Azure |
|---|---|---|---|
| Validated Linux container execution | PASS — ECS/Fargate | PASS — Cloud Run | PASS — Container Apps |
| Managed PostgreSQL suitable for production | PASS — RDS PostgreSQL | PASS — Cloud SQL PostgreSQL | PASS — Azure Database for PostgreSQL Flexible Server |
| HTTPS/TLS for hosted application | PASS — ALB + ACM | PASS — Cloud Run TLS termination | PASS — Container Apps ingress/TLS |
| PostgreSQL encrypted transport with cert validation path | PASS — RDS SSL, `verify-full`, RDS CA | PASS capability; integration path differs from current runbook | PASS — PostgreSQL Flexible Server TLS |
| External secret management | PASS — Secrets Manager | PASS — Secret Manager | PASS — Key Vault |
| Separate staging/production security boundaries | PASS — separate accounts preferred; distinct VPC/services/DB/secrets minimum | PASS — separate projects/services/DB/secrets | PASS — separate subscriptions/resource groups/services/DB/secrets |
| Health/traffic admission controls | PASS — ALB + ECS blue/green test/production routing | PASS — Cloud Run probes/revisions/traffic migration | PASS — Container Apps revisions/ingress |
| Private/persistent runtime logs | PASS — CloudWatch Logs | PASS — Cloud Logging | PASS — Log Analytics |
| Backup + PITR + restore to isolated target | PASS — RDS point-in-time restore creates new DB instance | PASS — Cloud SQL PITR can clone/restore to target instance | PASS — Flexible Server PITR creates a new server |
| Migration/runtime credential separation | PASS — separate ECS task definitions/secrets and DB roles | PASS capability — job/service secret separation | PASS capability — job/app secret separation |
| Immutable release/image identity | PASS — ECR immutability + task definition revision/digest | PASS — immutable image digest + revision | PASS — registry digest + Container App revision |
| Operator credentials can remain outside repo | PASS — IAM/Secrets Manager | PASS — IAM/Secret Manager | PASS — Entra/Managed Identity/Key Vault |

**Selected AWS result: 12/12 PASS, 0 FAIL, 0 UNKNOWN.**

GCP and Azure remain viable alternatives. They are not rejected as insecure. AWS is selected because it requires the least reinterpretation of the repository's existing PostgreSQL TLS/preflight and migration/runtime model, reducing adaptation risk at the first real deployment.

## Critical provider-specific finding: ALB health-check fail-open

AWS documents that if all registered targets in a target group are unhealthy, Application Load Balancer can route to all targets anyway. Therefore **ALB health status alone is not R1 traffic-admission authority**.

R1 mitigation is mandatory:

1. ECS service uses native `BLUE_GREEN` deployment strategy.
2. Blue and green revisions use distinct target groups.
3. The green candidate is exercised through a test listener/rule before production shift.
4. `/health/live`, `/health/ready`, least-privilege verification and release-critical smoke checks must pass on green.
5. Production listener remains on blue until the R1 operator explicitly allows traffic shift.
6. Rollback capability remains available during bake/validation.

This converts ALB health from a useful signal into one layer of admission rather than the sole fail-closed control.

## Environment isolation decision

Preferred security model:

- **separate AWS accounts** for staging and production under an organization/control boundary;
- separate VPCs;
- separate RDS instances;
- separate Secrets Manager secrets;
- separate ECS services/task roles;
- separate ACM certificates/hostnames where appropriate;
- shared ECR only if cross-account pull is explicitly controlled, otherwise separate registries.

If separate AWS accounts are unavailable at Gate 2, production promotion remains blocked until the residual risk is reviewed. At minimum, staging and production must have separate VPC/service/database/secret/role boundaries and no shared application/database credentials.

## Mandatory AWS posture for R1

### ECS/Fargate

- Fargate Linux container runtime.
- `awsvpc` networking.
- task IAM/execution roles least-privilege.
- ECS native blue/green with primary and alternate target groups.
- no mutable `latest` release identity.

### ECR

- image tag mutability set to `IMMUTABLE` for release repository, or deploy strictly by digest.
- release record stores both Git SHA and ECR digest.

### RDS PostgreSQL

- PostgreSQL 16.
- private network path from application tasks.
- `rds.force_ssl=1` explicitly retained even though PostgreSQL 15+ defaults to SSL enforcement.
- application connection uses `sslmode=verify-full` and trusted RDS CA bundle.
- staging and production use distinct DB instances and credentials.
- backup retention/PITR enabled before promotion.
- first recovery drill restores to a **new isolated DB instance**.

### Secrets

Separate secret objects at minimum for:

- staging application secrets;
- staging migration DB credentials;
- staging runtime DB credentials;
- production application secrets;
- production migration DB credentials;
- production runtime DB credentials.

Migration secret must not be referenced by steady-state web/worker task definitions.

### Logs

- application stdout/stderr sent to CloudWatch Logs using `awslogs`.
- log access restricted through IAM.
- no password, APP_KEY, PII_FINGERPRINT_KEY, DB URL, raw PII or private key material accepted in R1 evidence.

## Current official primary sources

AWS:

- Fargate architecture: https://docs.aws.amazon.com/AmazonECS/latest/developerguide/AWS_Fargate.html
- RDS PostgreSQL SSL/TLS: https://docs.aws.amazon.com/AmazonRDS/latest/UserGuide/PostgreSQL.Concepts.General.SSL.html
- RDS point-in-time restore: https://docs.aws.amazon.com/AmazonRDS/latest/UserGuide/USER_PIT.html
- Secrets Manager: https://docs.aws.amazon.com/secretsmanager/latest/userguide/intro.html
- ECS secret injection: https://docs.aws.amazon.com/AmazonECS/latest/developerguide/specifying-sensitive-data.html
- ALB target health: https://docs.aws.amazon.com/elasticloadbalancing/latest/application/target-group-health-checks.html
- ECS blue/green: https://docs.aws.amazon.com/AmazonECS/latest/developerguide/deployment-type-blue-green.html
- ALB resources for ECS blue/green: https://docs.aws.amazon.com/AmazonECS/latest/developerguide/alb-resources-for-blue-green.html
- ECS logs to CloudWatch: https://docs.aws.amazon.com/AmazonECS/latest/developerguide/using_awslogs.html
- ECR image tag immutability: https://docs.aws.amazon.com/AmazonECR/latest/userguide/image-tag-mutability.html
- ACM: https://docs.aws.amazon.com/acm/latest/userguide/acm-overview.html

Google Cloud comparison sources:

- Cloud Run container contract: https://cloud.google.com/run/docs/container-contract
- Cloud SQL PostgreSQL PITR: https://cloud.google.com/sql/docs/postgres/backup-recovery/pitr
- Secret Manager: https://cloud.google.com/secret-manager/docs/overview
- Cloud Run logging: https://cloud.google.com/run/docs/logging
- Cloud Run rollouts/rollbacks: https://cloud.google.com/run/docs/rollouts-rollbacks-traffic-migration

Azure comparison sources:

- Container Apps containers/revisions: https://learn.microsoft.com/en-us/azure/container-apps/containers
- PostgreSQL Flexible Server backup/PITR: https://learn.microsoft.com/en-us/azure/postgresql/backup-restore/concepts-backup-restore
- PostgreSQL Flexible Server TLS: https://learn.microsoft.com/en-us/azure/postgresql/security/security-tls-how-to-connect
- Key Vault: https://learn.microsoft.com/en-us/azure/key-vault/general/overview
- Container Apps ingress: https://learn.microsoft.com/en-us/azure/container-apps/ingress-overview
- Container Apps logs: https://learn.microsoft.com/en-us/azure/container-apps/log-monitoring

## Gate 1 acceptance

- [x] Three major managed-provider stacks evaluated.
- [x] AWS selected based on security-contract compatibility.
- [x] All blocking requirements for selected AWS architecture are PASS.
- [x] TLS/`verify-full` path demonstrated by RDS documentation.
- [x] PITR + isolated restore path demonstrated.
- [x] Secrets externalization demonstrated.
- [x] Private logging capability demonstrated.
- [x] Immutable image identity capability demonstrated.
- [x] ALB fail-open behavior identified and mitigated architecturally with ECS blue/green/test routing.
- [x] Staging/production isolation model defined.
- [ ] Actual AWS account/access verified — belongs to Gate 2, not Gate 1.
- [ ] Real AWS resources created — belongs to Gate 2+.

Gate 1 may be GREEN after repository CI confirms this documentation-only change did not regress M9 gates.
