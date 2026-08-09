# AWS Staging Infrastructure Contract

This directory is intentionally documentation-only until authenticated AWS access exists. It defines the resource contract that Gate 2 must create and verify. No account IDs, passwords, secrets, certificate private keys or production identifiers belong here.

## Preferred boundary

Strong preference: dedicated AWS staging account.

If a dedicated account is not available, the minimum acceptable boundary is a staging-only VPC plus staging-only ECS cluster/service, RDS instance, ECR repository or release namespace, ALB target groups/listener rules, Secrets Manager objects, IAM roles and CloudWatch log groups. Residual shared-account risk must be documented before production promotion.

## Required resources

- ECR release repository with immutable candidate identity by image digest.
- ECS/Fargate application service using native `BLUE_GREEN`.
- Two target groups (`blue`, `green`) with target type `ip`.
- HTTPS production rule/listener and separate test routing for green qualification.
- ACM certificate for the staging hostname.
- RDS PostgreSQL 16 with automated backups/PITR enabled.
- RDS CA trust available to application tasks; application uses `DB_SSLMODE=verify-full`.
- Separate Secrets Manager objects for application, migration DB and runtime DB secrets.
- Separate IAM roles for task execution/runtime and deployment automation.
- CloudWatch log group(s) with explicit staging names and retention.
- GitHub Actions -> AWS federation through OIDC/STS rather than committed long-lived keys.

## Deployment identity

Every candidate is the tuple:

```text
Git commit SHA + ECR image digest + ECS task definition/service revision
```

Branch names and mutable tags such as `latest` are not release identities.

## Blue/green invariant

The green candidate must be testable independently before normal traffic changes. Promotion requires application `/health/live`, `/health/ready`, database least-privilege checks and synthetic smoke evidence. ALB target-health state alone is not an authorization to promote.

## Database identity invariant

Migration credentials are used only by a controlled one-off migration path. Web/worker runtime task definitions contain only the least-privilege runtime DB secret reference. The runtime PostgreSQL role must be unable to create/alter/drop schema objects.

## Secret invariant

No plaintext application/database secret is committed to this repository, embedded into the Docker image, placed in task-definition plaintext `environment` entries, or copied into CI/PR evidence.

## Evidence before declaring Gate 2 GREEN

The following must come from the real AWS environment, not from this README:

- AWS staging resources exist;
- staging and production boundaries are distinct;
- staging hostname has valid HTTPS;
- exact image digest is deployed;
- blue/green target groups/test route exist;
- staging RDS and Secrets Manager objects are distinct from production;
- CloudWatch receives logs;
- no production data/credential reuse is observed.

Detailed execution order and secret-safe checks are in `docs/R1_STAGING_RUNBOOK.md`.
