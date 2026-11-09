# Black Cat lessons learned — graphical VM evidence (17 September 2026)

Supplementary, lossless lesson entry for this work. The canonical `docs/07-lessons-learned.md` remains unchanged until a safe append is possible; do not represent that register as updated. Technical detail and run links: [VM retest record](21-vm-interaction-retest-2026-09-17.md).

## What was attempted
Prove a newer artwork-enabled graphical candidate by booting it in a disposable, networkless UEFI virtual machine, selecting a module preview, clearing it, closing the preview and independently observing the final System Check screen.

## What happened
Run `35160431291` visibly demonstrated that the Browser checkbox toggled and updated the summary, then failed because screenshot OCR could not locate the small `CLEAR` label. A separate error in the workflow verified an older image in preflight while the Python harness booted the newer image. Consequently that run did not establish the Clear, desktop or settled diagnostic milestones.

## Why it matters
A positive check of the wrong image's digest can falsely imply that the candidate under test was identified. Screenshots, keyboard/mouse injection and a green workflow are not interchangeable types of evidence. An OCR failure locating a button is not itself proof of a defective button; the actual state transition has to be observed.

## Decision/change
Pin the identical source SHA, ISO size and ISO SHA-256 in preflight and boot harness. Keep the real screenshot-based Browser click, navigate the known Tk focus order to Clear and Open, then assert actual screen-state changes, including a second post-close observation. Require the separate diagnostic boot to reach the photo/Enter prompt rather than merely finding an early report. Preserve failed logs and don't transfer tests across image revisions.

## Evidence classification
The source/harness change was committed as `4b6cd79cc4319fc6e261919b4722e159359bce55`. Its [VM retest](https://github.com/efrgdgr0024345/portable-trusted-linux/actions/runs/35176125068) is the authority for whether runtime gates passed. No new hardware evidence, signed release, security endorsement, device write or production deployment follows automatically from this change.
