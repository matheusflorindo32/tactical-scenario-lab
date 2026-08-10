# R1 Recovery Drill — Neon Staging

Date: 2026-08-10

## Scope

This drill validates that the active Neon staging database can produce an isolated recovery branch containing the current application schema and M6 database integrity controls without modifying the primary staging branch.

## Active staging source

- Neon project: `tactical-scenario-lab-staging`
- Project ID: `curly-moon-55089444`
- Primary branch: `main` (`br-empty-cloud-afi5pujf`)
- Database: `neondb`
- PostgreSQL observed: 18.4
- Project history retention observed: 21,600 seconds (6 hours)
- Primary staging state at drill start: 37 public application tables, 37 migration rows, 0 scenario rows

## Recovery target

An isolated Neon branch was created from the real staging state:

- Branch name: `r1-recovery-drill-20260810`
- Branch ID: `br-orange-queen-afng2tl3`
- Parent: `br-empty-cloud-afi5pujf`

No production database or production data was used.

## Validation evidence

The recovery branch was queried independently and returned:

- 37 public base tables
- 37 Laravel migration rows
- 0 scenario rows, matching the staging source
- PostgreSQL 18.4
- 10 non-internal M6 integrity/immutability triggers
- 53 public foreign-key constraints
- 15 public check constraints

Representative M6 trigger names observed on the recovery branch included:

- `m6_published_scenario_version_immutable`
- `m6_finalized_assessment_immutable`
- `m6_execution_events_append_only`
- `m6_action_items_immutable`
- assessment/debrief/key-time immutability guards

A Neon schema comparison between the recovery branch and its parent returned an empty diff, proving schema equivalence at the time of the drill.

## Cleanup

After validation, the recovery branch `br-orange-queen-afng2tl3` was deleted successfully. The primary staging branch was not modified by the drill.

## Recovery posture and limits

This evidence proves branch-based recovery from the current retained staging history and schema/integrity reproduction on the active Neon project. It does not claim a commercial production SLA, unlimited PITR window, or recovery beyond the provider retention observed above. Production recovery posture must be reviewed again at Gate 8 before production promotion.

## Verdict

**Gate 4 recovery drill: GREEN for R1 staging validation.**
