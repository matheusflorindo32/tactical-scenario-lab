# R1 Production Release & Operational Validation Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Turn the M1–M9 release-ready repository into verified staging and production operational evidence using a staging-first, fail-closed promotion model.

**Architecture:** Use AWS as the preferred R1 provider stack: Amazon ECS on Fargate for the validated Docker image, Application Load Balancer + ACM for HTTPS/traffic admission, Amazon RDS for PostgreSQL 16 with enforced TLS and PITR, AWS Secrets Manager for application/database secrets, Amazon ECR for immutable images, and CloudWatch Logs for private runtime logs. Staging and production are separate security boundaries; migration credentials are available only to controlled one-off migration tasks, while web/worker tasks use a distinct least-privilege database role.

**Tech Stack:** Laravel/PHP 8.4, Docker, PostgreSQL 16, AWS ECS/Fargate, ECR, RDS, ALB, ACM, Secrets Manager, CloudWatch, GitHub Actions.

## Global Constraints

- Production promotion is blocked until staging passes every applicable promotion gate.
- Staging never uses the authoritative production database or production PII by default.
- Staging and production do not share APP_KEY, PII_FINGERPRINT_KEY, database passwords, provider tokens or migration/runtime credentials.
- HTTPS is mandatory for hosted authenticated traffic.
- Production PostgreSQL must support PITR before promotion.
- Backup configuration without a successful isolated restore drill is not GREEN.
- Runtime PostgreSQL identity must not own schema objects or have DDL capability.
- Migration credentials must not remain in web/worker runtime after deployment.
- Release identity is an exact Git SHA plus immutable image digest; mutable `latest` is insufficient.
- Any unresolved Critical/High finding or blocking UNKNOWN/FAIL prevents promotion.
- M9 Security, Container, SQLite, PostgreSQL and Pint matrix remains mandatory after any repository-file change.
- No provider/deploy/restore/browser/production evidence may be fabricated.

---

### Task 1: Provider Selection and R1 Evidence Ledger

**Files:**
- Create: `docs/R1_PROVIDER_SCORECARD.md`
- Create: `docs/superpowers/sdd/r1-progress.md`
- Modify: PR #13 body only; no runtime files.

**Interfaces:**
- Consumes: approved R1 spec and M9 production/release contracts.
- Produces: selected provider architecture, blocking capability matrix, gate ledger and explicit external blocker list.

- [ ] **Step 1: Verify provider capabilities from current primary documentation**

Verify container runtime, managed PostgreSQL 16, TLS, secret management, environment isolation, traffic health checks, private logging, backup/PITR/restore, migration/runtime credential separation, immutable image identity and operator access.

- [ ] **Step 2: Record a three-candidate scorecard**

Score AWS, GCP and Azure as `PASS`, `FAIL` or `UNKNOWN` for each blocking requirement. Do not use popularity or cost to override a blocking requirement.

- [ ] **Step 3: Select the provider**

Select AWS only if every blocking requirement needed by this design is PASS. Preferred stack:

```text
GitHub exact SHA
  -> ECR immutable image digest
  -> ECS/Fargate service
  -> ALB HTTPS listener + ACM certificate
  -> RDS PostgreSQL 16 (TLS required, PITR enabled)
  -> Secrets Manager
  -> CloudWatch Logs
```

Use distinct staging and production services, databases and secrets. Prefer separate AWS accounts; if unavailable, require at least separate VPC/service/database/secret boundaries and document the residual risk.

- [ ] **Step 4: Create the progress ledger**

Record M9 baseline merge SHA `1d77b89ef273e97cc53c7901df2d0f405684df45`, R1 spec path, R1 plan path and Gate 1 evidence.

- [ ] **Step 5: Commit**

```bash
git add docs/R1_PROVIDER_SCORECARD.md docs/superpowers/sdd/r1-progress.md
git commit -m "docs(r1): select provider and establish operational ledger"
```

### Task 2: Isolated Staging Infrastructure Contract

**Files:**
- Create: `docs/R1_STAGING_RUNBOOK.md`
- Create if justified by provider access: `infra/aws/staging/README.md`
- Test: repository contract test only if new executable configuration is added.

**Interfaces:**
- Consumes: AWS scorecard and exact release SHA/image digest.
- Produces: staging topology and evidence checklist for Gate 2.

- [ ] **Step 1: Define staging resource boundary**

Required logical resources:

```text
staging VPC/private subnets
staging RDS PostgreSQL 16
staging ECS cluster/service
staging ALB target group
staging ACM certificate + HTTPS listener
staging Secrets Manager namespace
staging CloudWatch log groups
staging ECR image digest reference
```

- [ ] **Step 2: Define DNS/TLS acceptance checks**

```bash
curl -fsS -o /dev/null -w '%{http_code}\n' https://STAGING_HOST/health/live
curl -fsS https://STAGING_HOST/health/ready
openssl s_client -connect STAGING_HOST:443 -servername STAGING_HOST </dev/null
```

Expected after deployment: valid certificate chain, HTTP 200 liveness, HTTP 200 readiness only when DB is ready.

- [ ] **Step 3: Prove production resource non-reuse**

Evidence must show distinct staging DB endpoint, secrets and application service identifiers. Never print secret values.

- [ ] **Step 4: Commit runbook/config only after evidence format is deterministic**

```bash
git add docs/R1_STAGING_RUNBOOK.md infra/aws/staging/README.md
git commit -m "docs(r1): define isolated staging deployment contract"
```

### Task 3: Secrets and Migration/Runtime Identity Separation

**Files:**
- Create if needed: `scripts/ops/r1/verify-runtime-role.sh`
- Modify only if a defect is proven: deployment docs/config.
- Test: `tests/Feature/R1OperationalContractTest.php` for repository-visible contracts.

**Interfaces:**
- Consumes: staging RDS endpoint and external secrets.
- Produces: secret-safe migration task and least-privilege runtime proof.

- [ ] **Step 1: Create separate PostgreSQL users**

Use deployment-specific names. Required posture:

```sql
CREATE ROLE <migration_role> LOGIN NOSUPERUSER NOCREATEDB NOCREATEROLE;
CREATE ROLE <runtime_role> LOGIN NOSUPERUSER NOCREATEDB NOCREATEROLE NOINHERIT;
GRANT CONNECT ON DATABASE <db> TO <runtime_role>;
GRANT USAGE ON SCHEMA public TO <runtime_role>;
GRANT SELECT, INSERT, UPDATE, DELETE ON ALL TABLES IN SCHEMA public TO <runtime_role>;
GRANT USAGE, SELECT ON ALL SEQUENCES IN SCHEMA public TO <runtime_role>;
```

- [ ] **Step 2: Store credentials in separate secret objects**

Migration task references only migration secret; ECS web/worker task definitions reference only runtime secret plus application secrets.

- [ ] **Step 3: Verify runtime cannot perform DDL**

```bash
psql "$RUNTIME_DATABASE_URL" -v ON_ERROR_STOP=1 -c 'CREATE TABLE r1_should_fail(id bigint);'
```

Expected: permission denied. The failed table must not exist.

- [ ] **Step 4: Verify encrypted RDS connection**

Application uses `DB_SSLMODE=verify-full` and the RDS CA bundle. Validate with `pg_stat_ssl`/`sslinfo` without exposing credentials.

- [ ] **Step 5: Commit only repository-side verification helpers**

```bash
git add scripts/ops/r1/verify-runtime-role.sh tests/Feature/R1OperationalContractTest.php
git commit -m "test(r1): verify runtime identity separation"
```

### Task 4: PostgreSQL Backup, PITR and Restore Drill

**Files:**
- Create: `docs/R1_RECOVERY_DRILL.md`
- No production dump files or credentials may enter the repository.

**Interfaces:**
- Consumes: staging RDS instance with automated backups/PITR.
- Produces: isolated restore target and secret-safe integrity evidence.

- [ ] **Step 1: Confirm PITR window**

Use RDS console/API/CLI to record earliest/latest restorable time and backup retention without recording credentials.

- [ ] **Step 2: Restore to a new isolated DB instance**

Use `restore-db-instance-to-point-in-time` targeting a new staging-recovery identifier. Never overwrite the source staging DB for the first drill.

- [ ] **Step 3: Validate restored schema/invariants**

Run migration status and the M6 integrity checks against recovery-target credentials.

- [ ] **Step 4: Record RTO/RPO observation as an observation, not an SLA**

Do not invent contractual RTO/RPO values from a single drill.

- [ ] **Step 5: Commit secret-safe recovery evidence template/results**

```bash
git add docs/R1_RECOVERY_DRILL.md
git commit -m "docs(r1): record staging recovery drill"
```

### Task 5: Real Staging Deployment and Health Admission

**Files:**
- Create if needed: `scripts/ops/r1/staging-smoke.sh`
- Modify: `docs/R1_STAGING_RUNBOOK.md` with actual secret-safe evidence identifiers.

**Interfaces:**
- Consumes: exact image digest, migration secret, runtime secret, TLS hostname and RDS.
- Produces: admitted staging release with health evidence.

- [ ] **Step 1: Freeze exact candidate**

Record Git SHA and ECR digest. Reconfirm repository CI on that SHA.

- [ ] **Step 2: Run migration task separately**

The one-off task executes:

```bash
php artisan production:preflight
php artisan migrate --force
php artisan config:cache
php artisan route:cache
```

- [ ] **Step 3: Start/update runtime service with runtime secrets only**

No migration-role secret may be present in the runtime task definition.

- [ ] **Step 4: Admit through health checks**

ALB liveness target check uses `/health/live`. Operator separately requires `/health/ready` to return ready/database ok before functional QA.

- [ ] **Step 5: Capture secret-safe deployment evidence**

Record ECS task definition revision, ECR digest, release SHA, target health and migration state.

### Task 6: Authenticated Smoke/E2E and Browser QA

**Files:**
- Create if needed: `tests/e2e/r1-staging.spec.*`
- Create: `docs/R1_BROWSER_QA.md`

**Interfaces:**
- Consumes: admitted staging environment and synthetic test identities.
- Produces: authenticated workflow and authorization evidence.

- [ ] **Step 1: Create disposable synthetic test identities/data**

No production PII.

- [ ] **Step 2: Exercise release-critical flows**

Cover login, organization context, dashboard, scenarios/versions, execution, assessment/debrief, history/report, Knowledge Center, people/access, restricted-user forbidden behavior and logout.

- [ ] **Step 3: Run accessibility/browser sanity**

Verify skip link, focus visibility, keyboard navigation, reduced motion and low-light persistence in Chromium plus one independent engine when available.

- [ ] **Step 4: Record failures as release blockers**

Any release-critical failure creates a RED defect and must be fixed/retested before Gate 6 is GREEN.

### Task 7: Observability and Failure/Recovery Drill

**Files:**
- Create: `docs/R1_FAILURE_DRILL.md`
- Modify if necessary: staging runbook only.

**Interfaces:**
- Consumes: staging service, CloudWatch logs, RDS and recovery evidence.
- Produces: failure-detection/recovery proof and decision tree evidence.

- [ ] **Step 1: Inspect protected logs for secret/PII leakage**

Check representative app and task logs. Do not export raw sensitive logs into Git.

- [ ] **Step 2: Exercise database-unavailable behavior safely**

Use a controlled staging-only mechanism. Expect `/health/live` to remain process-oriented and `/health/ready` to fail closed.

- [ ] **Step 3: Redeploy/restart same candidate**

Confirm stable recovery to the same SHA/image digest.

- [ ] **Step 4: Exercise rollback or approved roll-forward rehearsal**

Rollback only to a known schema-compatible candidate. If not compatible, document why and rehearse roll-forward instead.

- [ ] **Step 5: Record operator decision path**

Distinguish application rollback, schema rollback and PITR.

### Task 8: Production Promotion and Release Closeout

**Files:**
- Create: `docs/R1_PRODUCTION_CLOSEOUT.md`
- Update: PR #13 body/comment only after repository evidence is frozen.

**Interfaces:**
- Consumes: Gates 1–7 GREEN, exact candidate SHA/image digest and separately configured production resources.
- Produces: production release evidence and explicit version/tag decision.

- [ ] **Step 1: Reconfirm promotion preconditions**

All Gates 1–7 GREEN; zero Critical/High findings; production secrets/roles/DB isolated; PITR available; repository CI still green.

- [ ] **Step 2: Execute controlled production migration/runtime sequence**

Same identity split as staging; production never receives staging credentials.

- [ ] **Step 3: Verify production health and minimal non-destructive smoke**

Confirm `/health/live`, `/health/ready`, login and one safe authenticated path.

- [ ] **Step 4: Capture release identity**

Record Git SHA, image digest, migration state, production service/task revision and health outcome.

- [ ] **Step 5: Make explicit version/tag decision**

Create a semantic tag only if a versioning policy and exact version are explicitly chosen. Otherwise SHA remains the release identity.

- [ ] **Step 6: Close R1**

Update the PR/evidence ledger without moving a repository head after any final exact-head CI selected for integration.
