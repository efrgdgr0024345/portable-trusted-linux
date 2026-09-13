# Black Cat AI Work State

This is the compact recovery/checkpoint file for AI-assisted work on Black Cat / Portable Trusted Linux.

**Purpose:** a fresh or recovering AI session reads this file first instead of bulk-loading the repository. Full history remains in GitHub and is retrieved only when the next action requires it.

## Current project state

- State revision: 6
- Last checkpoint: 2026-09-13
- Current milestone: M14 — implement authenticated release signing
- Active branch: `codex/remove-chroot-systemd-boot`
- Pull request: #6 (unmerged)
- Project progress: 76%
- Approved release: none
- Current blocker: none for non-secret M14 design/repository work
- M13B status: COMPLETE on the tested hardware/configuration
- Future roadmap: M26 will integrate repository-governed AI project automation into Black Cat user space after M25

## M13B physical evidence

- Corrected candidate physically reached **BLACK CAT - QUICK CHECK**.
- Earlier ACPI `AE_NOT_FOUND` firmware messages were non-fatal and boot continued.
- Previous `/run/blackcat/quick-check.txt` / `.json` `Permission denied` errors were absent.
- Quick-check observations: UEFI; Secure Boot ENABLED; TPM DEVICE_FOUND; TCG event log FOUND; dm-verity root NOT_ACTIVE; no active network link observed; kernel `6.12.107+deb13-amd64`; architecture `x86_64`.
- Read-only `lsblk -o NAME,SIZE,TYPE,MOUNTPOINTS` showed the 14.6G `sda` live USB with `sda1` mounted at `/run/live/medium` and `loop0` mounted as the live squashfs.
- The 476.9G internal `nvme0n1` and partitions `nvme0n1p1` through `nvme0n1p4` showed no mount points.
- Therefore the tested internal NVMe was **OBSERVED unmounted by default** during this Black Cat boot.
- This is physical observation on one tested configuration, not a universal guarantee or attestation.

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

## Next automatic actions

1. Begin M14 by reviewing the existing release-signing requirements and current build/release path.
2. Design the authenticated release manifest/signature format and verification flow without creating or exposing private signing keys.
3. Keep build/release signing identity separate from the later Secure Boot signing identity/path.
4. Add deterministic verification/negative tests before any release promotion.
5. Continue automatically until a genuine signing-key ownership/custody or other material trust-policy decision requires the operator.

## Next genuine human gate

No immediate physical action is required.

The expected M14 human gate is selection/approval of the real release-signing key ownership and custody model (for example, where the private signing key is generated and held). Private signing material must not be committed to GitHub or placed on the general-purpose builder merely for convenience.

## Context health

- Checkpoint revision: 6
- Estimated context pressure: AMBER
- Confidence: medium-low (initial calibration)
- Confirmed continuity/dropout events since checkpoint: 0
- Calibration version: v0.1 (provisional)
- Reason for AMBER: long repository/tool-heavy conversation with completed physical-test evidence; compact checkpointing protects recovery.

Context pressure itself is never a human-approval gate.

## Recovery sequence

1. Read `AI_WORK_STATE.md` only.
2. Verify the active branch/PR and exact run/artifact only if relevant to the next action.
3. Load autonomy/master rules only when a policy boundary is touched.
4. Load relevant technical documents selectively.
5. Do not bulk-load project history merely to reconstruct state.
6. Continue automatically until a genuine human gate is reached.

## Durable references

- Master rules: `PROJECT_RULES.md`
- Continue/stop decision table: `AI_AUTONOMY_POLICY.md`
- Build specification: `docs/06-prototype-build-specification-v0.1.md`
- Full lessons/history: `docs/07-lessons-learned.md`
- Continuity design/calibration: `docs/13-ai-continuity-and-context-recovery.md`, `docs/14-context-health-calibration-log.md`
- Future native project system: `docs/15-black-cat-ai-project-automation.md`
- Living project index: `project_content.json`

Keep this file compact. It is a handoff/checkpoint, not a replacement for durable technical documentation.