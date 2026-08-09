# R1 Staging Runbook — AWS

Status: PREPARED — external AWS access required

This runbook executes Gate 2 and prepares Gate 3 without treating documentation as provider evidence. A gate is GREEN only after the real AWS resources and runtime checks exist.

## Safety rules

- Use a dedicated staging AWS account when available. If not, isolate staging with separate VPC, ECS service, RDS instance, Secrets Manager objects, log groups, target groups and IAM roles.
- Never paste or commit long-lived AWS access keys, database passwords, `APP_KEY`, `PII_FINGERPRINT_KEY`, private database URLs or TLS private material.
- Prefer GitHub Actions OIDC to an AWS deployment role instead of static AWS secrets.
- Staging never connects to the authoritative production database and does not require production PII.
- Every deploy is identified by exact Git SHA plus immutable ECR image digest. Do not deploy `latest` as release identity.
- Migration credentials exist only in a controlled one-off migration task. Web/worker task definitions use the runtime database role only.
- Production traffic promotion is out of scope until Gates 1–7 are GREEN.

## Required staging topology

```text
GitHub exact SHA
  -> GitHub Actions OIDC -> AWS staging deploy role
  -> ECR immutable image digest
  -> ECS/Fargate service using native BLUE_GREEN
       -> blue target group
       -> green target group
       -> HTTPS production listener/rule
       -> HTTPS test listener/rule
  -> ACM certificate + staging hostname
  -> RDS PostgreSQL 16
       -> TLS required
       -> RDS CA available to application
       -> automated backups/PITR enabled
  -> Secrets Manager
       -> application secrets
       -> migration database credential
       -> runtime database credential
  -> CloudWatch Logs
```

## Phase A — AWS identity and access bootstrap

1. Create/use a staging AWS account or explicitly documented staging boundary.
2. Configure GitHub OIDC trust for this repository and a staging deployment role. Restrict the trust policy to this repository and the intended branch/environment; do not grant a generic organization-wide subject.
3. Grant the deployment role only the actions required to push the release image and manage the staging resources used by this runbook.
4. Keep human break-glass administration separate from the GitHub deployment role.
5. Capture only secret-safe identifiers in R1 evidence: account alias/id suffix if safe, region, service names, resource ARNs with sensitive account details redacted when evidence is public.

Gate evidence: OIDC role exists, workflow can obtain short-lived AWS credentials, and no long-lived AWS key is stored in GitHub repository variables/secrets for deployment.

## Phase B — network and database boundary

1. Create a staging VPC boundary suitable for ECS and RDS.
2. Keep RDS non-public unless an explicit reviewed exception is required.
3. Security groups permit PostgreSQL only from the application/migration execution path that needs it.
4. Create RDS PostgreSQL 16 with automated backups and PITR retention enabled.
5. Require encrypted PostgreSQL transport. Application connection must use `DB_SSLMODE=verify-full` with the AWS RDS CA trust material available to the runtime.
6. Create separate migration and runtime PostgreSQL roles.
7. Runtime role must not own schema objects and must not have DDL privileges.

Required verification after real creation:

```sql
SELECT current_user;
SHOW ssl;
SELECT ssl FROM pg_stat_ssl WHERE pid = pg_backend_pid();
```

Runtime DDL denial check:

```sql
CREATE TABLE r1_should_fail(id bigint);
```

Expected for runtime role: permission denied.

## Phase C — secrets

Create separate Secrets Manager objects for at least:

- staging application secret material (`APP_KEY`, `PII_FINGERPRINT_KEY` and other application-only secrets);
- staging migration DB identity;
- staging runtime DB identity.

Rules:

- migration DB secret is referenced only by the migration task definition/execution path;
- web/worker task definitions reference only runtime DB credentials;
- secrets are injected through the ECS secrets mechanism, not committed or placed in plaintext task-definition `environment` values;
- rotating a secret requires launching a new ECS task/revision because injected environment values are read at task start.

## Phase D — image and release identity

1. Build the same Docker image contract already validated by M9.
2. Push to staging ECR.
3. Record Git SHA and resulting immutable image digest.
4. Task definitions must reference an immutable release identity/digest for qualification.
5. Reconfirm repository Security, Container, SQLite, PostgreSQL and Pint CI before using the candidate.

## Phase E — ECS/Fargate blue-green

1. Create an ECS/Fargate service using native `BLUE_GREEN` deployment strategy.
2. Configure two ALB target groups: primary/blue and alternate/green.
3. Configure HTTPS production routing and a distinct HTTPS test route/listener for green qualification.
4. Health checks use application health paths appropriate to the deployment stage.
5. Green receives no normal production traffic during qualification.
6. Validate green through test routing first.
7. Preserve blue for a bake/rollback window after any later traffic shift.

Important: ALB target health is not the sole promotion authority. Application live/ready, runtime least privilege and smoke checks must all pass before any listener shift.

## Phase F — TLS and hostname

1. Create a staging hostname under a controlled domain.
2. Issue/attach an ACM certificate valid for the staging hostname.
3. Serve authenticated staging traffic only over HTTPS.
4. Redirect or reject plaintext HTTP according to the selected ingress policy.

Checks:

```bash
curl -fsS https://STAGING_TEST_HOST/health/live
curl -fsS https://STAGING_TEST_HOST/health/ready
openssl s_client -connect STAGING_TEST_HOST:443 -servername STAGING_TEST_HOST </dev/null
```

Do not commit the real private hostname if it is intentionally non-public; secret-safe evidence can use a redacted identifier.

## Phase G — CloudWatch logging

1. Configure the ECS task with the `awslogs` driver.
2. Use a staging-only log group with explicit retention.
3. Confirm representative startup/request/error logs are visible to authorized operators.
4. Inspect for accidental secret/PII leakage before Gate 2/3 promotion.
5. Do not copy raw sensitive logs into GitHub artifacts or PR comments.

## Gate 2 acceptance evidence

Gate 2 can be marked GREEN only when all of the following are observed in AWS/runtime:

- isolated staging service exists;
- staging RDS exists and is not the production DB;
- staging secrets are distinct from production;
- valid HTTPS/TLS is observed;
- exact Git SHA and ECR digest are recorded;
- ECS blue/green has separate blue/green target groups and test routing;
- green can be addressed through the test path without shifting production routing;
- CloudWatch receives staging logs;
- no production credential/data reuse is detected.

Documentation or screenshots of a console configuration without the matching runtime checks are not enough by themselves.

## Gate 3 preparation

After Gate 2 exists, immediately prove:

1. `php artisan production:preflight` under production-like staging configuration;
2. migration task succeeds using migration credentials;
3. web/worker runtime contains no migration credential reference;
4. runtime DB identity can perform required DML;
5. runtime DDL denial succeeds;
6. PostgreSQL connection uses verified TLS.

Until these are observed on the real staging resources, Gate 3 remains not GREEN.
