# Black Cat AI Work State

Compact handoff for a lighter coding model. Read this first, then the current evidence report and only the source relevant to the next action. This is an evidence-backed continuation checkpoint, not a release claim.

## Model hierarchy rule

Project-wide: a lower model MUST NOT change any higher-model plan, idea or suggestion without explicit human sign-off. Report proposed deviations first; do not self-authorize. Unknown model rank requires clarification before changing inherited direction. See PROJECT_RULES.md.

## Current project state

- Review branch: `codex/graphical-evidence-review`; main is **not merged**.
- Automated VM gates are complete for the artwork-enabled graphical candidate.
- Human gates remain: spare-USB selection/write, physical boot, hardware observations, production signing, release approval, and merge. Routine JSON publication is not PHP deployment.
- Canonical design authority: `design/README.md`; original folder is `/linux usb project/Graphical design for black cat project` (18 files).
- The build uses seven selected canonical assets: one wallpaper plus six category icons. Other design files remain references.

## Current exact test candidate

- Filename: `live-image-amd64.hybrid.iso`
- Size: `770703360` bytes
- SHA-256: `f498776780c543013e437161a878e22ca4fc40a66f47fc271d6fd6a964c29d75`
- Source: `ed5d80ac09c3f1650d67594242643d27efcf15e9`
- Build run: `35179377345`
- Artifact: `blackcat-graphical-development-35179377345` (ID `10480220416`)
- Builder copy: `/home/github-runner/blackcat-builds/run-35179377345-attempt-1/live-image-amd64.hybrid.iso`
- VM evidence: run `35180150392`, harness commit `c1b623d0989c4c6d30814f4b92b80a44b79f501a`

## Established VM observations

QEMU 10.0.13 used OVMF UEFI, TCG, 2 vCPU, 2048 MiB, no guest NIC and no host disks. Six artwork icons and the tagline were visible. Browser selection produced `Requested (NOT activated): Browser`; CLEAR returned `none`. OPEN DESKTOP exposed a stable canonical wallpaper in two successive captures. System Check reached its photo prompt after 140 seconds.

The VM reported UEFI; Secure Boot `UNKNOWN`; TPM and TCG event log `NOT_EXPOSED`; dm-verity `NOT_ACTIVE`; network `NO_ACTIVE_LINK_OBSERVED`. These are development observations, not verified security or physical-hardware claims.

## Exact-candidate malware triage

Disposable GitHub-hosted run `35231392125` independently downloaded artifact `10480220416`, matched ZIP SHA-256 `e7d6a6202aa9acf998e407070021cdf0b26cebd330d361afe7b00dbef09a96ba`, extracted the sole ISO, and matched ISO size `770703360` and SHA-256 `f498776780c543013e437161a878e22ca4fc40a66f47fc271d6fd6a964c29d75`. ClamAV 1.5.3 scanned 43,350 files in the extracted ISO/root filesystem and reported 0 infected files with exit 0. See `docs/22-candidate-malware-triage.md`.

This is bounded one-engine static evidence. It does not explain Chrome's warning, prove safety, authenticate the original build authority, or approve a release. Verify the locally downloaded ISO hash before Rufus.

## Next automatic actions

Live candidate publication and bounded static malware triage are complete. The page was accepted at 2026-09-17T09:17:52Z against blob `f012581ef46bfa8b1977d82727c55b91c3ce864a`; no PHP replacement occurred. The next automatic preparation is the physical-test checklist. The operator should verify the downloaded ISO's SHA-256 before Rufus.

## Next genuine human gate

The authoritative live-page verification and bounded static scan passed. Obtain explicit approval for the exact spare USB device, confirm the local ISO SHA-256, then follow `docs/13-physical-boot-test.md`. Do not merge to main, create production signing authority, approve a release or deploy without the corresponding human gate.

## Context health

This checkpoint is sufficient for a lighter model. Selectively reload `docs/20-graphical-candidate-evidence.md`, `design/README.md` and the physical-test procedure; do not reload the whole history. Escalate new trust-policy choices or ambiguous security evidence. Context pressure itself is never a human-approval gate.

## Recovery sequence

Verify the review-branch head and exact ISO identity. Read this file, the current evidence report, design authority and specific procedure. Historical images and their observations must never be transferred to this candidate.
