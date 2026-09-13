# Black Cat AI Work State

This is the compact recovery/checkpoint file for AI-assisted work on Black Cat / Portable Trusted Linux.

**Purpose:** a fresh or recovering AI session reads this file first instead of bulk-loading the repository. Full history remains in GitHub and is retrieved only when the next action requires it.

## Current project state

- State revision: 4
- Last checkpoint: 2026-09-13
- Current milestone: M13B — rebuild and physically retest Black Cat no-typing System Check after report-permission fix
- Active branch: `codex/remove-chroot-systemd-boot`
- Pull request: #6 (unmerged)
- Project progress: 74%
- Approved release: none
- Current blocker: none for repository work; M13B completion is waiting at a physical-human gate
- Future roadmap: M26 will integrate repository-governed AI project automation into Black Cat user space after M25

## Current exact test candidate

- Status: TEST CANDIDATE — NOT AN APPROVED RELEASE
- Filename: `live-image-amd64.hybrid.iso`
- Size: 376,438,784 bytes
- SHA-256: `cb14d25b7fd43e2b0e563efaada357d6ab196a42d717b016c4857f85968fe641`
- Source commit: `c56389b71ecd96270bdd047369378f5a94d8bf93`
- Build run: `34686464650`
- Artifact ID: `10300658965`
- Artifact name: `ptl-first-live-build-34686464650`
- Automated smoke state: passed

## Last completed engineering state

- Root autonomy/recovery policy remains governed by `AI_AUTONOMY_POLICY.md` and this compact checkpoint.
- Future M26 architecture is documented in `docs/15-black-cat-ai-project-automation.md` and ADR-039/ADR-040.
- A provider-neutral reusable seed now exists under `templates/ai-project/`.
- `schema/ai-project-policy.schema.json` defines the first machine-readable capability-policy contract.
- The sample policy defaults to HUMAN_GATE, network disabled, no inbound listener, no root/raw-device/credential access and explicit forbidden secret/safety/self-expansion rules.
- Static checks now guard the template files, JSON syntax, fail-closed defaults, compact checkpoint shape and future-plan boundaries.
- `project_content.json` must be the final repository write of this work cycle.

## Next automatic actions

1. On a fresh/recovery session, read this file first.
2. Inspect the active branch/PR and only the CI state relevant to the next action.
3. Load other repository files selectively when required by the task or when a policy/version mismatch is detected.
4. Continue safe, reversible, already-authorized work automatically under `AI_AUTONOMY_POLICY.md`.
5. Refresh this checkpoint automatically when context pressure becomes AMBER/RED or project state materially changes.
6. Keep M26 design/template work future-only until the core M13B→M25 security roadmap reaches its required gates.

## Next genuine human gate

Physical M13B USB retest:

- download and verify the exact candidate above;
- raw-write it to the clean spare USB;
- boot **Black Cat - System Check (no typing)**;
- confirm there are no `Permission denied` errors;
- photograph the result;
- verify internal disks remain unmounted by default.

Physical/destructive device work remains a HUMAN_GATE.

## Context health

Context health is an estimate based on observable conversation/workload signals. It is **not** an exact percentage of ChatGPT's hidden context window.

- Checkpoint revision: 4
- Estimated context pressure: AMBER
- Confidence: medium-low (initial calibration)
- Turns since checkpoint: several active design/implementation turns
- Large document/log reads since checkpoint: several repository reads
- Tool-heavy repository operations since checkpoint: high
- Confirmed continuity/dropout events since checkpoint: 0
- Calibration version: v0.1 (provisional)
- Reason for AMBER: this remains a long, repository/tool-heavy conversation; compact checkpointing protects recovery even though it does not shrink the current chat context.

### Pressure actions

- GREEN: continue normally; do not reload repository state unnecessarily.
- AMBER: automatically refresh this compact checkpoint before continuing; do not interrupt the operator merely because the chat is long.
- RED: refresh a compact checkpoint, avoid unnecessary bulk reads, and prefer clean recovery if continuity becomes uncertain.

Context pressure itself is never a human-approval gate.

## Recovery sequence

1. Read `AI_WORK_STATE.md` only.
2. Verify the active GitHub branch/PR and any exact run/artifact needed for the next action.
3. Read autonomy/master rules only if the next action touches a policy boundary or policy changed.
4. Read the relevant technical document/lesson section only when the task needs it.
5. Do **not** bulk-load the lessons register or `project_content.json` merely to reconstruct state.
6. Continue automatically until a genuine human gate is reached.

## Durable references

- Master rules: `PROJECT_RULES.md`
- Continue/stop decision table: `AI_AUTONOMY_POLICY.md`
- Continuity design/calibration: `docs/13-ai-continuity-and-context-recovery.md`, `docs/14-context-health-calibration-log.md`
- Future native project system: `docs/15-black-cat-ai-project-automation.md`
- Reusable seed and machine policy: `templates/ai-project/`, `schema/ai-project-policy.schema.json`
- Full lessons/history: `docs/07-lessons-learned.md`
- Living project index: `project_content.json`

Keep this file compact. The target is under 100 lines; it is a handoff/checkpoint, not a replacement for durable technical documentation.