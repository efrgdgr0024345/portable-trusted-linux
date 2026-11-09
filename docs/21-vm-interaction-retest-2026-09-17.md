# Graphical VM interaction retest — 17 September 2026

This is a bounded development test, **not** release, trust or physical-USB approval. Preserve the earlier candidate and evidence described in [the 16 September record](20-graphical-candidate-evidence.md); do not transfer results between ISO identities.

## Candidate under test

- Build source: `3491587f7bacd04ab116e0fccc47ba9b9ea52e10`.
- Development build: [35159417803](https://github.com/efrgdgr0024345/portable-trusted-linux/actions/runs/35159417803).
- Exact image: `/home/github-runner/blackcat-builds/run-35159417803-attempt-1/live-image-amd64.hybrid.iso`.
- Size: `770703360` bytes; SHA-256: `e579d11818acfe9e885a0c9c55ee2749b070a2154f96663e66db606fba5a0455`.
- The artwork-enabled source/image differs from the previously documented `81f702cd…` candidate. A prior build passing does not prove that this image passed VM or physical testing.

## Observed failure and change

[Run 35160431291](https://github.com/efrgdgr0024345/portable-trusted-linux/actions/runs/35160431291) showed the actual BLACK CAT session preview, clicked the Browser checkbox and observed `Requested (NOT activated): Browser`. Its test then threw `Visible control word not located: CLEAR` while searching a full-frame OCR result. The run failed; it **did not** prove the Clear action, desktop transition or final System Check prompt. The preflight verified the *older* ISO while the test script targeted the *new* image, a misleading mixed-identity preflight.

The correction at [commit 4b6cd79](https://github.com/efrgdgr0024345/portable-trusted-linux/commit/4b6cd79cc4319fc6e261919b4722e159359bce55) aligns the preflight, manifest check and boot script to the **same** new image. Browser is still located and clicked from a real guest screenshot. Focus traversal then navigates six checkboxes to CLEAR SELECTION, activates it and requires the actual summary to show `none`; the next button is activated and two guest frames must no longer show the preview. This avoids depending on OCR recognizing one small button label while still requiring observable state changes.

The same workflow boots System Check separately, selects the diagnostic menu, and waits a bounded 480-second observation schedule for `PHOTO THIS SCREEN` plus `Press Enter`; seeing an intermediate report does not suffice. VM sessions use UEFI/QEMU, no guest NIC, no physical disk write and disposable firmware variables. Reports and guest screenshot evidence stay on the builder and are echoed in the CI log.

## Retest and interpretation

[Retest run 35176125068](https://github.com/efrgdgr0024345/portable-trusted-linux/actions/runs/35176125068) is the authoritative CI result for this harness revision. Read its completed job result and `BOOT_REPORT` before changing any test status to passed. A workflow launch or successful identity check alone is **not** a VM pass. If GUI interactions or settling fail, preserve failure frames and diagnose the specific failure; do not bypass assertions to obtain a green result.

## Reusable lessons

1. The workflow identity guard and the boot script must pin **one** exact image (source, size, SHA and build run); checking two different candidates in one workflow gives misleading provenance.
2. Full-screen OCR of small control text is a brittle input locator. Navigate Tk controls through a known focus order and verify the resulting guest UI state, including screenshots, instead of declaring success from key injection alone.
3. Proving a checkbox changed is not proving Clear, desktop handoff or settled diagnostics. Keep separate assertions and retain failure evidence.
4. An ISO built with newer artwork requires fresh tests; historical VM and physical observations do not automatically transfer. No release or USB-write gate is lifted by this retest.
