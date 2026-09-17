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

## Next automatic actions

Execute `docs/21-json-only-candidate-publication-plan.md`: artifact/evidence and the viewer contract are reverified; persistent compatibility/negative fixtures are added. After their source check passes, update compatible JSON at the deployed viewer's actual source branch, without a PHP replacement. Verify the live candidate before handoff. The project-status-live migration proposal is superseded for this repair. Lower/lighter models must follow the plan or obtain explicit human sign-off for deviations.

## Next genuine human gate

After the authoritative live-page verification passes, obtain explicit approval for the exact spare USB device, then follow `docs/13-physical-boot-test.md`. Do not write a device, merge to main, create production signing authority, approve a release or deploy without the corresponding human gate.

## Context health

This checkpoint is sufficient for a lighter model. Selectively reload `docs/20-graphical-candidate-evidence.md`, `design/README.md` and the physical-test procedure; do not reload the whole history. Escalate new trust-policy choices or ambiguous security evidence. Context pressure itself is never a human-approval gate.

## Recovery sequence

Verify the review-branch head and exact ISO identity. Read this file, the current evidence report, design authority and specific procedure. Historical images and their observations must never be transferred to this candidate.
