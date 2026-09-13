# Black Cat AI Work State

This is the compact recovery/checkpoint file for AI-assisted work on Black Cat / Portable Trusted Linux.

**Purpose:** a fresh or recovering AI session reads this file first instead of bulk-loading the repository. Full history remains in GitHub and is retrieved only when the next action requires it.

## Current project state

- State revision: 5
- Last checkpoint: 2026-09-13
- Current milestone: M13B — rebuild and physically retest Black Cat no-typing System Check after report-permission fix
- Active branch: `codex/remove-chroot-systemd-boot`
- Pull request: #6 (unmerged)
- Project progress: 74%
- Approved release: none
- Current blocker: final physical confirmation that internal disks remain unmounted by default
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

## Latest physical evidence

- The corrected candidate physically reached **BLACK CAT - QUICK CHECK** on real hardware.
- The earlier ACPI `AE_NOT_FOUND` firmware messages did not prevent boot; the system progressed to the quick-check screen.
- The previous `/run/blackcat/quick-check.txt` / `.json` `Permission denied` errors are absent from the photographed corrected run.
- Screen observations: UEFI; Secure Boot ENABLED; TPM DEVICE_FOUND; TCG event log FOUND; dm-verity root NOT_ACTIVE; no active network link observed; kernel `6.12.107+deb13-amd64`; architecture `x86_64`.
- Evidence remains **OBSERVED unless independently verified**; Secure Boot enabled is a firmware-state observation, not proof that the Black Cat boot chain is authenticated.
- M13B is not closed yet because the photographed quick-check does not display internal-disk mount state.

## Last completed engineering state

- Root autonomy/recovery policy remains governed by `AI_AUTONOMY_POLICY.md` and this compact checkpoint.
- Future M26 architecture and reusable fail-closed project scaffolding remain preserved under `docs/15-black-cat-ai-project-automation.md`, `templates/ai-project/` and `schema/ai-project-policy.schema.json`.
- Corrected physical retest confirms the report-permission regression is no longer visible and the no-typing quick-check path reaches its intended screen.
- `project_content.json` must be the final repository write of this work cycle.

## Next automatic actions

1. Preserve the corrected physical quick-check result as M13B evidence.
2. After the operator supplies disk/mount evidence, classify the internal-disk-default-unmounted gate.
3. If that gate passes, complete M13B documentation and advance planning toward M14 automatically, stopping only at the next genuine human gate.
4. Keep M26 design/template work future-only until the core M13B→M25 security roadmap reaches its required gates.

## Next genuine human gate

Final M13B physical disk-state observation:

- from the quick-check screen press Enter to reach the shell;
- run a short block-device/mount listing;
- photograph the result so internal disks can be distinguished from the live USB and checked for mount points.

Physical/destructive device work remains a HUMAN_GATE. This observation is read-only and must not mount, write or repair any internal disk.

## Context health

Context health is an estimate based on observable conversation/workload signals. It is **not** an exact percentage of ChatGPT's hidden context window.

- Checkpoint revision: 5
- Estimated context pressure: AMBER
- Confidence: medium-low (initial calibration)
- Confirmed continuity/dropout events since checkpoint: 0
- Calibration version: v0.1 (provisional)
- Reason for AMBER: this remains a long, repository/tool-heavy conversation with physical-test evidence being added; compact checkpointing protects recovery.

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