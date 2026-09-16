# Black Cat Lessons Learned — graphical session prototype (2026-09-16)

This is a supplemental lesson record. The canonical `docs/07-lessons-learned.md` remains unchanged pending a lossless append through a repository-capable editor. Do not claim the mandated canonical register has been updated until it has.

## What was attempted
Implement an actual graphical boot-session selector instead of repeatedly promising an interface without creating source code.

## What happened
The previous package seed contained no explicit Xfce/Xorg/LightDM GUI. The development branch now adds those packages plus Python Tk, a locally packaged GUI selector and XFCE autostart. The UI renders module-labelled icon/checkbox previews but intentionally does NOT activate modules, enable networking, mount disks, grant capabilities or certify trust. A `lightdm.service` kernel-command-line condition retains the `blackcat.check=1` text-only path. A static test and hosted workflow were added. No rebuilt ISO or graphical boot evidence exists yet.

## Why it matters
A visually plausible tick-box security screen can mislead users unless demo controls are clearly identified and the runtime remains incapable of granting unimplemented permissions. Installing a desktop also increases software/attack surface and may regress the previously proven no-typing hardware diagnostics; each boot path requires separate VM/physical verification. Source implementation is not boot evidence.

## Decision/change
Treat the UI as a reversible, explicitly marked development profile. Keep the trusted-core/minimal-module architecture a later engineering milestone. Preserve the previously frozen M13B candidate. Verify UI boot, quick-check boot and module-enforcement behavior independently before making stronger claims.

## Evidence classification
SOURCE IMPLEMENTED / STATIC TEST AVAILABLE / CI, GUI BOOT AND PHYSICAL RETEST PENDING. No production key, listener, credential, chatbot runtime or device write was introduced.



## 16 September reconciliation

The original entry above has now been appended losslessly to the canonical lessons register. Its historical pending states are superseded by docs/20-graphical-candidate-evidence.md.
