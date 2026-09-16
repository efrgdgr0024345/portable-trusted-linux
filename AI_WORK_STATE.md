# Black Cat AI Work State

Compact handoff for a lighter coding model. Read this first, then only the named evidence and relevant source. This is a plan-ready handoff, not a claim that the product is finished.

## Current project state

- Review branch: `codex/graphical-evidence-review`; main is NOT merged. Both USB and Console histories are preserved on the review branch.
- Active outcome: finish the exact graphical candidate's interaction and settled System Check tests before physical testing. No scope expansion.
- Design authority: `design/README.md`; original folder is `/linux usb project/Graphical design for black cat project`, 18 files. Canonical tagline: Stealth. Intelligence. Independence.
- The design folder is integrated as a reference; the existing ISO predates that work and is still the earlier graphical prototype.
- Source checks passed in run `35155024291` at source `a1a9afc3d2fba6d5c81ace22019493f4a951ec32`: static guards, negative signing tests and PHP viewer fixtures. Inspect the latest Integration Source Checks run for the current head; never transfer a past pass to a changed source revision.
- Production signing key custody remains undecided; no approved release, new droplet, public listener, Console deployment, production viewer deployment or USB write occurred.

## Current exact test candidate

- Development specimen: `live-image-amd64.hybrid.iso`, 770703360 bytes.
- SHA-256: `81f702cd9e59d4d7ba5654e6a69dbd655617968f92381049881930aef9b2fcb7`.
- Source: `86ddbb8eac1a0dbf912c035d5b916b70453dff88`; construction run `35095923202`.
- Builder copy: `/home/github-runner/blackcat-builds/run-35095923202-attempt-1/live-image-amd64.hybrid.iso`.
- Rehash run `35107102698` matched. Original GitHub artifact upload failed on quota.
- Boot run `35107884622` observed the actual Xfce/Black Cat panel and, in a separate UEFI boot, the Quick Check report. Screens and exact report are in `evidence/2026-09-16-graphical/`.
- GUI capture: missing icon glyphs; interactions NOT tested. Diagnostic capture: Plymouth wait still running; final photo prompt NOT established.
- Earlier physical M13B observations belong to a different 376438784-byte image (`cb14d25b…`), not this candidate.

## Next automatic actions

1. Read `docs/20-graphical-candidate-evidence.md` and `tests/03-existing-candidate-boot.py`.
2. Verify the current review-branch SHA and latest source-check result; fix a bounded failure if present.
3. Extend the existing VM harness to exercise a checkbox, CLEAR SELECTION and OPEN DESKTOP, and wait for the settled System Check photo prompt with a bounded timeout. Retain before/after screenshots and failure evidence.
4. Use existing Actions runner via `.github/workflows/existing-candidate-evidence.yml`. Pushes touching this workflow or its test script on this branch trigger it. It rehashes the fixed candidate and uses temporary unprivileged QEMU tools, no guest network or host disks.
5. Diagnose the Plymouth wait if the final prompt fails to appear. Do not label the early report marker a complete pass. Rebuild only for a confirmed source change, with a new exact identity.
6. Record lessons and evidence, refresh this checkpoint, and update `project_content.json` LAST. Review branch changes are automatic; main merge is a separate gate.

## Next genuine human gate

After automated gates: explicit spare-USB target/physical write authorization. Separately, main merge and production release/Secure Boot key ownership remain gated by existing policy. No production secrets on the builder. No release promotion.

## Context health

Checkpoint refreshed for deliberate handoff. GREEN operational heuristic; no exact hidden context percentage. Context pressure itself is never a human-approval gate.

## Recovery sequence

Read this file, verify current branch/run metadata, then read docs/20 and the named test. Consult design/README before visual work. Do not reload all history or restart planning. Use a stronger review for new trust-policy decisions or ambiguous security findings; routine bounded implementation may continue on a lighter model.
