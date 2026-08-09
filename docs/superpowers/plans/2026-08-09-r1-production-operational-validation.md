# R1 Production Release & Operational Validation Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Convert the M1–M9 release-ready repository into verified staging and production operational evidence using a staging-first, fail-closed promotion model.

**Architecture:** AWS is the selected R1 stack: Amazon ECR for immutable image identity, Amazon ECS on Fargate using native `BLUE_GREEN` deployments, Application Load Balancer + ACM for HTTPS, Amazon RDS for PostgreSQL 16 with enforced TLS and PITR, AWS Secrets Manager for separate application/migration/runtime secrets, and CloudWatch Logs for protected runtime logs. Blue and green revisions use separate target groups; candidate traffic is exercised through a test listener/rule and the production listener does not shift until R1 admission checks pass.

**Tech Stack:** Laravel/PHP 8.4, Docker, PostgreSQL 16, AWS ECS/Fargate, ECR, RDS, ALB, ACM, Secrets Manager, CloudWatch, GitHub Actions.

## Global Constraints

- Production promotion is blocked until staging passes every applicable R1 promotion gate.
- Staging never uses the authoritative production database or production PII by default.
- Staging and production do not share `APP_KEY`, `PII_FINGERPRINT_KEY`, DB credentials, provider tokens or migration/runtime credentials.
- HTTPS is mandatory for hosted authenticated traffic.
- Production PostgreSQL must support PITR before promotion.
- Backup configuration without a successful isolated restore drill is not GREEN.
- Runtime PostgreSQL identity must not own schema objects or have DDL capability.
- Migration credentials must not remain in web/worker runtime after deployment.
- Release identity is exact Git SHA + immutable image digest; mutable `latest` is insufficient.
- Any unresolved Critical/High finding or blocking UNKNOWN/FAIL prevents promotion.
- ALB target health is never the sole admission authority because ALB may fail open when every target is unhealthy.
- Candidate revisions must stay on the alternate target group/test route until `/health/live`, `/health/ready`, least-privilege and release-critical smoke checks pass.
- M9 Security, Container, SQLite, PostgreSQL and Pint matrix remains mandatory after repository-file changes.
- No provider/deploy/restore/browser/production evidence may be fabricated.

---

### Task 1: Provider Selection and Evidence Ledger

**Files:**
- Create: `docs/R1_PROVIDER_SCORECARD.md`
- Create: `docs/superpowers/sdd/r1-progress.md`
- Modify: PR #13 metadata only.

**Produces:** selected provider architecture, blocking capability matrix and explicit external blockers.

- [ ] Verify AWS/GCP/Azure capabilities from current primary documentation.
- [ ] Score each blocking criterion PASS/FAIL/UNKNOWN.
- [ ] Select AWS only if every required AWS criterion is PASS.
- [ ] Record the provider-specific ALB fail-open finding and mandatory ECS native blue/green mitigation.
- [ ] Create the R1 evidence ledger.
- [ ] Run the full M9 CI matrix on the final Gate 1 repository HEAD.
- [ ] Mark Gate 1 GREEN only after CI succeeds.

### Task 2: Isolated Staging + HTTPS

**Files:**
- Create: `docs/R1_STAGING_RUNBOOK.md`
- Create if provider access justifies it: `infra/aws/staging/README.md`

**Produces:** hosted staging boundary and secret-safe evidence format.

Required topology:

```text
staging security boundary
  -> ECR immutable release image
  -> ECS/Fargate service using BLUE_GREEN
       -> blue target group
       -> green target group
       -> HTTPS production listener/rule
       -> HTTPS test listener/rule
  -> staging RDS PostgreSQL 16
  -> staging Secrets Manager secrets
  -> staging CloudWatch log groups
  -> ACM certificate + staging hostname
```

Acceptance checks after real resource creation:

```bash
curl -fsS https://STAGING_TEST_HOST/health/live
curl -fsS https://STAGING_TEST_HOST/health/ready
openssl s_client -connect STAGING_TEST_HOST:443 -servername STAGING_TEST_HOST </dev/null
```

- [ ] Prove staging DB/service/secrets are distinct from production.
- [ ] Prove valid HTTPS/TLS.
- [ ] Prove deployed task definition maps to exact image digest/Git SHA.
- [ ] Keep production listener/routing untouched by candidate validation.

### Task 3: Secrets + Migration/Runtime Identity Separation

**Files:**
- Create if needed: `scripts/ops/r1/verify-runtime-role.sh`
- Create if needed: `tests/Feature/R1OperationalContractTest.php`

**Produces:** migration-only and steady-state runtime identities with secret-safe proof.

PostgreSQL posture:

```sql
CREATE ROLE <migration_role> LOGIN NOSUPERUSER NOCREATEDB NOCREATEROLE;
CREATE ROLE <runtime_role> LOGIN NOSUPERUSER NOCREATEDB NOCREATEROLE NOINHERIT;
GRANT CONNECT ON DATABASE <db> TO <runtime_role>;
GRANT USAGE ON SCHEMA public TO <runtime_role>;
GRANT SELECT, INSERT, UPDATE, DELETE ON ALL TABLES IN SCHEMA public TO <runtime_role>;
GRANT USAGE, SELECT ON ALL SEQUENCES IN SCHEMA public TO <runtime_role>;
```

- [ ] Store migration and runtime DB credentials in separate Secrets Manager objects.
- [ ] Migration one-off ECS task references migration credential only.
- [ ] Web/worker task definitions reference runtime credential only.
- [ ] Verify runtime DDL denial:

```bash
psql "$RUNTIME_DATABASE_URL" -v ON_ERROR_STOP=1 -c 'CREATE TABLE r1_should_fail(id bigint);'
```

Expected: permission denied.

- [ ] Use RDS CA + `DB_SSLMODE=verify-full`.
- [ ] Verify SSL in PostgreSQL without exposing credentials.

### Task 4: Backup/PITR + Isolated Restore Drill

**Files:**
- Create: `docs/R1_RECOVERY_DRILL.md`

**Produces:** real recovery evidence, not backup-existence evidence.

- [ ] Confirm RDS automated backups/PITR retention and earliest/latest restorable time.
- [ ] Restore to a **new staging-recovery RDS instance** using point-in-time restore.
- [ ] Never overwrite the source staging DB for the first drill.
- [ ] Validate migration state and M6 tenant/history/integrity invariants on recovery target.
- [ ] Record observed recovery duration as an observation, not a contractual RTO/SLA.
- [ ] Keep dumps, passwords and private DB URLs out of GitHub artifacts.

### Task 5: Real Staging Deployment + Candidate Admission

**Files:**
- Create if needed: `scripts/ops/r1/staging-smoke.sh`
- Modify: `docs/R1_STAGING_RUNBOOK.md` with secret-safe identifiers only.

**Produces:** green revision qualified before production-listener traffic shift.

- [ ] Freeze Git SHA and ECR digest; reconfirm repository CI.
- [ ] Launch controlled migration ECS task with migration credentials:

```bash
php artisan production:preflight
php artisan migrate --force
php artisan config:cache
php artisan route:cache
```

- [ ] Deploy green ECS service revision to alternate target group with **zero production listener weight/traffic**.
- [ ] Exercise candidate through test listener/rule.
- [ ] Require `/health/live` HTTP 200.
- [ ] Require `/health/ready` HTTP 200 with ready/database ok.
- [ ] Reconfirm runtime task definition contains no migration credential reference.
- [ ] Run minimum synthetic authenticated smoke against green.
- [ ] Shift production listener only after all candidate admission checks pass.
- [ ] Preserve blue revision through a bake/rollback window.

### Task 6: Authenticated Smoke/E2E + Browser QA

**Files:**
- Create if justified: `tests/e2e/r1-staging.spec.*`
- Create: `docs/R1_BROWSER_QA.md`

**Produces:** hosted authenticated/authorization/accessibility evidence.

- [ ] Use disposable synthetic tenant/users/data only.
- [ ] Cover login, organization context, dashboard, scenario/version, execution, assessment/debrief, history/report, Knowledge Center, people/access and logout.
- [ ] Verify restricted identity receives expected forbidden behavior.
- [ ] Verify skip link, keyboard/focus, reduced-motion and low-light persistence.
- [ ] Qualify Chromium plus one independent browser engine where automation is available; otherwise record and complete a manual second-engine check before production promotion.
- [ ] Treat release-critical failures as RED defects requiring fix + full CI + redeploy.

### Task 7: Observability + Failure/Recovery Drill

**Files:**
- Create: `docs/R1_FAILURE_DRILL.md`

**Produces:** operator detection/recovery evidence.

- [ ] Inspect CloudWatch logs for representative requests/errors without exporting raw sensitive logs to Git.
- [ ] Confirm no secret, private DB URL, APP_KEY, PII_FINGERPRINT_KEY or raw PII is present in inspected evidence.
- [ ] Safely induce a staging-only database-unavailable condition.
- [ ] Confirm liveness remains process-oriented while readiness fails closed.
- [ ] Restart/redeploy the same candidate digest and confirm stable recovery.
- [ ] Exercise blue/green rollback to a known schema-compatible blue revision, or document incompatibility and rehearse approved roll-forward instead.
- [ ] Tie failure drill to Gate 4 PITR/restore decision path.

### Task 8: Production Promotion + Release Closeout

**Files:**
- Create: `docs/R1_PRODUCTION_CLOSEOUT.md`
- Update PR/evidence comments after repository evidence freeze only.

**Produces:** production evidence and explicit version/tag decision.

Preconditions:

- Gates 1–7 GREEN.
- Exact candidate SHA/digest unchanged.
- Repository CI GREEN.
- Zero unresolved Critical/High findings.
- Production resources/secrets/roles isolated from staging.
- Production PITR/recovery point confirmed.

Sequence:

1. Run production migration task with production migration credentials.
2. Deploy green production revision to alternate target group with no production traffic.
3. Verify live/ready, runtime least privilege and minimal non-destructive authenticated smoke through test routing.
4. Shift production listener to green only after admission.
5. Keep blue revision for defined bake/rollback window.
6. Inspect CloudWatch alarms/logs for release anomalies.
7. Record Git SHA, ECR digest, ECS revision, migration state, RDS identity and health evidence.
8. Create a semantic tag only if a versioning policy and exact version are explicitly chosen; otherwise SHA/digest remains release identity.

## Verification policy

Any repository-file change during R1 requires the inherited M9 matrix on the exact candidate HEAD:

- Security — `composer audit --locked` + `npm audit --audit-level=high`;
- real Docker image build/runtime contract;
- PHPUnit SQLite;
- PHPUnit PostgreSQL 16 including least-privilege, rollback/reapply and concurrency;
- Pint.

Operational gates use observe-not-green → smallest configuration/fix → rerun → secret-safe evidence. Failed checks are never reworded into PASS.
