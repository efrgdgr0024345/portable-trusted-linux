# Black Cat AI Work State

Compact handoff for a lighter coding model. Read this first, then the current evidence report and only the source relevant to the next action. This is an evidence-backed continuation checkpoint, not a release claim.

## Current project state

- Review branch: `codex/graphical-evidence-review`; main is **not merged**.
- Active outcome: prepare the exact artwork-enabled graphical candidate for a controlled spare-USB physical test.
- Automated VM gates are complete for this candidate: checkbox selection, CLEAR, OPEN DESKTOP, stable wallpaper, and the separate System Check photo prompt were observed.
- Human gates remain: physical spare-USB write/boot, hardware observations, production signing, release approval, merge, and deployment.
- Canonical design authority: `design/README.md`; original folder is `/linux usb project/Graphical design for black cat project` (18 files).
- Current build uses seven selected canonical assets: one wallpaper plus six category icons. The other design files remain references and are not claimed as shipped.

## Exact current candidate

- Filename: `live-image-amd64.hybrid.iso`
- Size: `770703360` bytes
- SHA-256: `f498776780c543013e437161a878e22ca4fc40a66f47fc271d6fd6a964c29d75`
- Source commit: `ed5d80ac09c3f1650d67594242643d27efcf15e9`
- Build run: `35179377345`
- Uploaded artifact: `blackcat-graphical-development-35179377345` (artifact ID `10480220416`)
- Preserved builder copy: `/home/github-runner/blackcat-builds/run-35179377345-attempt-1/live-image-amd64.hybrid.iso`
- VM evidence run: `35180150392`
- Evidence-harness commit: `c1b623d0989c4c6d30814f4b92b80a44b79f501a`
- Latest passed source-check run before this documentation checkpoint: `35180150390`

## What the VM evidence establishes

- QEMU 10.0.13, OVMF UEFI, TCG, 2 vCPU, 2048 MiB, no guest NIC, and no host disks.
- The six artwork icons and tagline `Stealth. Intelligence. Independence.` were visible.
- Selecting Browser produced `Requested (NOT activated): Browser`; CLEAR returned the summary to `none`.
- OPEN DESKTOP removed the panel and the canonical wallpaper remained visible in two successive captures.
- System Check reached the stable, photographable prompt after 140 seconds.
- The VM reported UEFI; Secure Boot `UNKNOWN`; TPM and TCG event log `NOT_EXPOSED`; dm-verity `NOT_ACTIVE`; network `NO_ACTIVE_LINK_OBSERVED`.
- These observations are development VM evidence only. They do not establish physical hardware behavior, production signing, Secure Boot, TPM attestation, dm-verity, or release readiness.

## Next action and autonomy boundary

The next meaningful action is the controlled physical test in `docs/13-physical-boot-test.md`, using this exact hash and an explicitly approved spare USB target. Do not write a device, merge to main, create production signing authority, approve a release, or deploy without the existing human gate.

A lighter model can safely take over routine bookkeeping and preparation from this checkpoint. Escalate new trust-policy choices, ambiguous security evidence, physical device selection, release/signing decisions, or merge/deployment approval.

## Recovery sequence

Read this file, then `docs/20-graphical-candidate-evidence.md`, `design/README.md`, and the specific physical-test procedure. Verify the branch head and exact ISO identity before any device operation. Historical images and their observations must never be transferred to this candidate.
