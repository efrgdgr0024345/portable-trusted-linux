# AI Work State — Template

This is the compact first-read recovery/checkpoint file. Keep it small; target fewer than 100 lines.

## Current project state

- State revision: 1
- Last checkpoint: [DATE/TIME]
- Project: [NAME]
- Current milestone: [MILESTONE]
- Active branch/worktree: [BRANCH]
- Current blocker: [NONE OR DESCRIPTION]

## Last completed work

- [LAST COMPLETED ACTION]

## Next automatic action

- [NEXT SAFE/AUTHORIZED ACTION]

## Next genuine human gate

- [NEXT HUMAN GATE OR NONE KNOWN]

## Important active identity/evidence

Use only when the project has an active build, deployment, artifact, dataset or other identity that must not drift.

- Type: [TYPE]
- Identifier: [ID]
- Source revision: [COMMIT/VERSION]
- Digest/version: [HASH/VERSION]

## Context health

Context Health is heuristic unless the connected model/runtime exposes a trustworthy exact meter.

- Estimated pressure: GREEN
- Confidence: low (initial calibration)
- Turns/tasks since checkpoint: 0
- Large document/log reads since checkpoint: 0
- Tool-heavy operations since checkpoint: 0
- Confirmed continuity/dropout events since checkpoint: 0
- Calibration version: v0.1

### Pressure actions

- GREEN: continue normally; do not reload repository state unnecessarily.
- AMBER: refresh this compact checkpoint and reduce unnecessary bulk reads.
- RED: checkpoint immediately and prefer a clean recovery/session boundary.

Context pressure itself is never a human-approval gate.

## Recovery sequence

1. Read this file first.
2. Verify only volatile repository/CI/runtime facts required for the next action.
3. Read `AI_AUTONOMY_POLICY.md` or `PROJECT_RULES.md` only when the proposed action touches a policy boundary or policy changed.
4. Load relevant technical/history files selectively.
5. Continue permitted work automatically until a genuine human gate.

## Durable references

- Master rules: `PROJECT_RULES.md`
- Continue/stop table: `AI_AUTONOMY_POLICY.md`
- Machine policy: `project-policy.json`
- Architecture decisions: `docs/architecture-decisions.md`
- Lessons: `docs/lessons-learned.md`

Do not turn this file into the full project history.