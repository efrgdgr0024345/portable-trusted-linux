# Black Cat AI Work State

Compact, first-read recovery checkpoint. GitHub is the authoritative history; selectively load documents only when relevant.

## Current project state

- State revision: 11; checkpoint 2026-11-09 (Australia/Perth).
- Branch: `codex/remove-chroot-systemd-boot`; PR #6 remains unmerged.
- Milestone: M14 release signing is current; M15 signed UKI planned; M26 modular AI runtime future.
- Project index previously 80% (not recalculated). Approved release: NONE.
- M13B: complete on ONE tested hardware/configuration, not globally attested.
- M14: 14 local positive/negative assertions pass with ephemeral development keys only. Fixed validator execute mode and rejection of multiple top-level JSON documents; regression failed before the parser fix.
- Baseline inspected: `4ec20e07c3d7e5bc73fba13e733700ca0c0d7709`; PR #6 head matched that branch before this cycle. Preserve this cycle's final Git revision when checking CI.
- Current-head M14 CI evidence: NOT OBSERVED. Outbound proxy rejects `api.github.com` with HTTP 403; Git works. Environment draft reads failed, so no unknown allowlist was replaced. Add that hostname in environment settings, then verify the exact final branch revision and completed self-test job.
- Production release signing-key custody remains HUMAN_GATE. No approved ISO, production key, firmware enrollment or target-disk write is authorized.
- Broader static checks separately fail on unchanged login-hook execution and Automation Manifesto wording; neither is an M14 signing-workflow check, and neither was weakened or changed.
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

1. Close only the remaining M14 evidence gap: inspect PR #6/current branch and the release-signing self-test workflow once Actions API connectivity is available. Git push alone does not prove a workflow ran.
2. Require successful completed CI for the exact current revision, using only ephemeral development keys; diagnose any bounded failure without weakening fail-closed checks.
3. Record the observed run URL, source identity and outcome; update lessons and this checkpoint, then `project_content.json` LAST. Never substitute local success or an older green run for current-revision CI evidence.
4. Stop at production release-key custody HUMAN_GATE. Do not begin M15, M16–M25, new ISO work or M26; bounded M15 design requires explicit operator instruction.

## Next genuine human gate

Production release signing-key ownership/custody, including independent public-key distribution, backup, rotation and compromise response. Separately, actual USB write and firmware/Secure Boot enrollment require explicit human authorization. No production key belongs on CI or general builder.

## Context health

AMBER heuristic, no exact meter. Checkpoint rev 11. Context pressure itself is never a human-approval gate.

## Recovery sequence

Read this file first. Verify only the active branch/PR head and exact M14 workflow evidence. Load `docs/16-authenticated-release-signing.md` and relevant signing source selectively. The operator's authoritative cycle is M14 evidence only; preserve M13B OBSERVED status, design-only M15 and deferred M26. Do not bulk-load history or resume older GUI/build actions.
