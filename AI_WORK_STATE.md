# Black Cat AI Work State

Compact, first-read recovery checkpoint. GitHub is the authoritative history; selectively load documents only when relevant.

## Current project state

- State revision: 10; checkpoint 2026-09-16.
- Branch: `codex/remove-chroot-systemd-boot`; PR #6 remains unmerged.
- Milestone: M14 release signing is current; M15 signed UKI planned; M26 modular AI runtime future.
- Project index previously 80% (not recalculated). Approved release: NONE.
- M13B: complete on ONE tested hardware/configuration, not globally attested.
- M14: closed canonical Ed25519 manifest and local development self-tests implemented; current-head CI evidence not yet verified; production release signing-key custody HUMAN_GATE.
- GUI session prototype source newly committed: packages, Tkinter selector, XFCE autostart, special-boot LightDM condition, static test/workflow, `docs/19-graphical-session-prototype.md`.
- GUI source is NOT a validated new ISO, active module loader, working permission broker or verified graphical boot. No new candidate has been promoted.
- Supplemental UI lesson: `docs/07-lessons-learned-ui-2026-09-16.md`. Mandatory lossless append to `docs/07-lessons-learned.md` and project JSON synchronization remain outstanding until completed; never claim otherwise.

## Current exact test candidate

- Status: TEST CANDIDATE, NOT APPROVED RELEASE; physical M13B evidence only.
- ISO: `live-image-amd64.hybrid.iso`; size 376438784 bytes.
- SHA-256: `cb14d25b7fd43e2b0e563efaada357d6ab196a42d717b016c4857f85968fe641`.
- Source: `c56389b71ecd96270bdd047369378f5a94d8bf93`; run `34686464650`; artifact `10300658965`.
- This ISO PREDATES the new UI code. Never say it contains the graphical selector.

## M13B physical evidence

- Black Cat Quick Check booted without old Permission denied errors; UEFI, Secure Boot enabled, TPM device/event-log observed, dm-verity NOT_ACTIVE, no active network link observed.
- Read-only lsblk showed live USB mounted and internal 476.9G NVMe plus four partitions unmounted by default. One-machine OBSERVED evidence only.

## M14 / M15 boundaries

- M14 scripts use shared strict canonical schema validation, Ed25519 detached signature, fingerprint and exact artifact checks; local development tests passed with ephemeral keys.
- Do not claim current-head CI success until exact run/log evidence is inspected.
- M15 `docs/17-signed-uki-development-plan.md` permits only ephemeral DEVELOPMENT ONLY signing tests; no firmware enrollment or production Secure Boot key provisioning.
- Release-signing and Secure Boot key authority are distinct.

## Future modules and AI workshop

- `docs/18-modular-runtime-and-ai-workshop.md`, ADR-042: small immutable core, independently verified ready-made optional modules and an untrusted external workshop; future M26 after first usable core.
- The new GUI checkbox selection is VISUAL DEMO ONLY. No signed module images, sandbox or capability broker exists yet.
- Separate chatbot droplet configuration remains on hold: no runtime, listener, key or new deployment authorized by this UI task.

## Next automatic actions

1. Verify GUI source and static test, inspect hosted workflow result if any; fix bounded defects.
2. Build new ISO in disposable CI; record source SHA, ISO size/SHA and preserve artifacts; distinguish builder failure from ISO failure.
3. Verify graphical login, XFCE autostart/check boxes and close-to-desktop in a graphical VM with framebuffer/screenshot evidence.
4. Independently verify `blackcat.check=1` retains the photograph-friendly text report with no graphical takeover.
5. Append supplemental UI lesson LOSSLESSLY to canonical lessons; update affected docs and `project_content.json` LAST. Do not delete historical JSON links/evidence to simplify the edit.
6. Continue non-secret M14/M15 work; no release promotion, merge or USB write.

## Next genuine human gate

Production release signing-key ownership/custody, including independent public-key distribution, backup, rotation and compromise response. Separately, actual USB write and firmware/Secure Boot enrollment require explicit human authorization. No production key belongs on CI or general builder.

## Context health

AMBER heuristic, no exact meter. Checkpoint rev 10. Context pressure itself is never a human-approval gate.

## Recovery sequence

Read this file first. Verify PR head, UI/test CI and M14 CI state. Load `docs/19-graphical-session-prototype.md` for GUI work; `docs/16`/`17` for signing; `docs/18` only for future module design. Do not bulk-load history. Preserve safety and genuine human gates.
