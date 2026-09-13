# Black Cat AI Work State

This is the compact recovery/checkpoint file for AI-assisted work on Black Cat / Portable Trusted Linux.

**Purpose:** a fresh or recovering AI session reads this file first instead of bulk-loading the repository. Full history remains in GitHub and is retrieved only when the next action requires it.

## Current project state

- State revision: 3
- Last checkpoint: 2026-09-13
- Current milestone: M13B — rebuild and physically retest Black Cat no-typing System Check after report-permission fix
- Active branch: `codex/remove-chroot-systemd-boot`
- Pull request: #6 (unmerged)
- Project progress: 74%
- Approved release: none
- Current blocker: none for repository work; M13B completion is waiting at a physical-human gate
- Future roadmap: M26 will integrate repository-governed AI project automation into Black Cat user space after the first usable core prototype (M25)

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

- Root `AI_AUTONOMY_POLICY.md` defines AUTO, AUTO_WITH_CHECKS, HUMAN_GATE and FORBIDDEN_BY_DEFAULT actions.
- Root `AI_WORK_STATE.md` is the compact fresh-session/recovery checkpoint; full history is loaded selectively.
- `docs/13-ai-continuity-and-context-recovery.md` publishes the reusable continuity pattern.
- `docs/14-context-health-calibration-log.md` provides empirical GREEN/AMBER/RED calibration without claiming an exact hidden context percentage.
- `docs/15-black-cat-ai-project-automation.md` now designs a future native Linux project subsystem using repository policy, a sandboxed capability broker, model-neutral workers, local-first operation and explicit human gates.
- ADR-039 records that future subsystem as post-core work; M26 is planned after M25 so it cannot displace the current trust-chain roadmap.
- Static checks now protect the future design document and M26/project-page visibility.
- `project_content.json` must be the final repository write of this work cycle.

## Next automatic actions

1. On a fresh/recovery session, read this file first.
2. Inspect the active branch/PR and only the CI state relevant to the next action.
3. Load other repository files selectively when required by the task or when a policy/version mismatch is detected.
4. Continue safe, reversible, already-authorized work automatically under `AI_AUTONOMY_POLICY.md`.
5. Refresh this checkpoint automatically when context pressure becomes AMBER/RED or project state materially changes.
6. Keep M26 future-only until the core M13B→M25 security roadmap reaches its required gates.

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

- Checkpoint revision: 3
- Estimated context pressure: AMBER
- Confidence: medium-low (initial calibration)
- Turns since checkpoint: several active design turns
- Large document/log reads since checkpoint: several repository reads
- Tool-heavy repository operations since checkpoint: high
- Confirmed continuity/dropout events since checkpoint: 0
- Calibration version: v0.1 (provisional)
- Reason for AMBER: this remains a long, repository/tool-heavy conversation; compact checkpointing protects recovery even though it does not shrink the current chat context.

### Pressure actions

- GREEN: continue normally; do not reload repository state unnecessarily.
- AMBER: automatically refresh this compact checkpoint before continuing; do not interrupt the operator merely because the chat is long.
- RED: create/refresh a compact checkpoint, avoid unnecessary bulk reads, and use the recovery sequence on the next session/turn if continuity is uncertain.

Context pressure itself is never a human-approval gate.

## Recovery sequence

Use this sequence only for a fresh chat, suspected context loss, long-gap check-in, or when current project state cannot be recalled confidently:

1. Read `AI_WORK_STATE.md` only.
2. Verify the active GitHub branch/PR and any exact run/artifact needed for the next action.
3. Read `AI_AUTONOMY_POLICY.md` or `PROJECT_RULES.md` only if the requested action touches an autonomy/safety boundary or this file indicates a policy change.
4. Read the relevant technical document/lesson section only when the task needs it.
5. Do **not** bulk-load `docs/07-lessons-learned.md` or `project_content.json` merely to reconstruct state.
6. Continue automatically until a genuine human gate is reached.

## Durable references

- Master rules: `PROJECT_RULES.md`
- Continue/stop decision table: `AI_AUTONOMY_POLICY.md`
- Automation rationale: `docs/11-automation-manifesto.md`
- Reusable continuity/context design: `docs/13-ai-continuity-and-context-recovery.md`
- Empirical calibration log: `docs/14-context-health-calibration-log.md`
- Future native AI project automation design: `docs/15-black-cat-ai-project-automation.md`
- Full lessons/history: `docs/07-lessons-learned.md`
- Living project index: `project_content.json`

Keep this file compact. The target is under 100 lines; it is a handoff/checkpoint, not a replacement for durable technical documentation.