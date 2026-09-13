# Black Cat AI Work State

This is the compact recovery/checkpoint file for AI-assisted work on Black Cat / Portable Trusted Linux.

**Purpose:** a fresh or recovering AI session reads this file first instead of bulk-loading the repository. Full history remains in GitHub and is retrieved only when the next action requires it.

## Current project state

- State revision: 7
- Last checkpoint: 2026-09-13
- Current milestone: M14 — implement authenticated release signing
- Active branch: `codex/remove-chroot-systemd-boot`
- Pull request: #6 (unmerged)
- Project progress: 78%
- Approved release: none
- M13B status: COMPLETE on the tested hardware/configuration
- Current blocker: production release-signing key ownership/custody is a human trust-policy gate; non-secret M14 mechanics are implemented and awaiting CI evidence
- Future roadmap: M26 will integrate repository-governed AI project automation into Black Cat user space after M25

## M13B physical evidence

- Corrected candidate physically reached **BLACK CAT - QUICK CHECK**.
- Earlier ACPI `AE_NOT_FOUND` firmware messages were non-fatal and boot continued.
- Previous `/run/blackcat/quick-check.txt` / `.json` `Permission denied` errors were absent.
- Quick-check observations: UEFI; Secure Boot ENABLED; TPM DEVICE_FOUND; TCG event log FOUND; dm-verity root NOT_ACTIVE; no active network link observed; kernel `6.12.107+deb13-amd64`; architecture `x86_64`.
- Read-only `lsblk -o NAME,SIZE,TYPE,MOUNTPOINTS` showed the 14.6G `sda` live USB with `sda1` mounted at `/run/live/medium` and `loop0` as the live squashfs.
- The 476.9G internal `nvme0n1` and partitions `nvme0n1p1` through `nvme0n1p4` showed no mount points.
- Result remains **OBSERVED on one tested configuration**, not a universal guarantee or attestation.

## M14 non-secret implementation

- `docs/16-authenticated-release-signing.md` defines the release-authenticity format and trust boundary.
- ADR-041 records canonical Ed25519-signed release metadata and separate release-signing authority.
- `build/40-create-release-manifest.sh` creates canonical compact JSON binding release ID/time, artifact filename/size/SHA-256, source/build identity and trusted release-public-key fingerprint.
- `build/41-sign-release-manifest.sh` signs exact canonical bytes and refuses a private key whose derived public fingerprint does not match the manifest.
- `build/42-verify-release.sh` independently verifies canonical form, schema, key fingerprint, detached Ed25519 signature and optional exact artifact identity.
- `tests/01-release-signing-check.sh` uses only an ephemeral development keypair and requires failure for artifact tampering, manifest tampering, wrong public key and private-key/fingerprint mismatch.
- `.github/workflows/release-signing-self-test.yml` runs the self-test on a GitHub-hosted runner with read-only repository permission and no production secret.
- Release signing remains separate from later Secure Boot/UKI signing.

## Next automatic actions

1. Inspect the M14 release-signing self-test workflow for the latest branch revision.
2. If the test fails for a bounded implementation defect, diagnose, repair and rerun automatically.
3. Once the non-secret mechanics pass, preserve the exact CI evidence and keep approved release state closed.
4. Stop at the production release-signing key ownership/custody decision rather than inventing or provisioning a long-lived secret automatically.
5. After that human trust-policy gate is resolved, integrate the deliberately selected trusted public key/fingerprint and complete production M14 verification gates before advancing to M15.

## Next genuine human gate

Choose/approve the production release-signing key ownership and custody model.

The decision must cover where the private key is generated/held, backup/recovery, revocation/replacement, public-key/fingerprint distribution, compromise response and whether signing may ever be automated. Candidate approaches include offline/removable custody, a hardware-backed token or a dedicated isolated signing host.

No production private signing key may be committed to GitHub or placed on the general-purpose builder merely for convenience.

## Context health

- Checkpoint revision: 7
- Estimated context pressure: AMBER
- Confidence: medium-low (initial calibration)
- Confirmed continuity/dropout events since checkpoint: 0
- Calibration version: v0.1 (provisional)
- Reason for AMBER: long repository/tool-heavy conversation; compact checkpoint is current before CI verification and the upcoming trust-policy gate.

Context pressure itself is never a human-approval gate.

## Recovery sequence

1. Read `AI_WORK_STATE.md` only.
2. Verify the active branch/PR and latest M14 signing self-test status.
3. Load autonomy/master rules only when a policy boundary is touched.
4. Load M14 design/scripts/lesson selectively if CI needs diagnosis.
5. Do not bulk-load project history merely to reconstruct state.
6. Continue automatically through bounded fixes until the production key-custody HUMAN_GATE is reached.

## Durable references

- Master rules: `PROJECT_RULES.md`
- Continue/stop decision table: `AI_AUTONOMY_POLICY.md`
- Build specification: `docs/06-prototype-build-specification-v0.1.md`
- M14 design: `docs/16-authenticated-release-signing.md`
- Full lessons/history: `docs/07-lessons-learned.md`
- Continuity design/calibration: `docs/13-ai-continuity-and-context-recovery.md`, `docs/14-context-health-calibration-log.md`
- Future native project system: `docs/15-black-cat-ai-project-automation.md`
- Living project index: `project_content.json`

Keep this file compact. It is a handoff/checkpoint, not a replacement for durable technical documentation.