# Graphical Black Cat Session UI — development prototype

Status: SOURCE IMPLEMENTED; ISO REBUILD / GUI BOOT / HARDWARE TEST PENDING. This is not a security-enforcing module selector.

## Scope

The current bootable M13B ISO provided a text-first Quick Check, but `config/packages.list` did not explicitly include a graphical desktop. This change adds Xorg, LightDM, Xfce and Python Tk to the Debian Live package seed, installs a locally sourced Tkinter GUI at `/usr/local/bin/blackcat-session-ui`, and configures XFCE autostart after graphical login. The operator may need to log in to the live account; automatic login is not assumed or enabled by this change. The build is not yet validated; Debian/live-config interaction with LightDM must be tested.

The panel shows icons and checkboxes for browser, networking, office/PDF, AI, development and security. Every option is a preview. No `.bcmod` files are built, authenticated, mounted, activated or isolated. Clicking OPEN DESKTOP (DEMO) closes the panel and reveals the Xfce desktop; checking a box does not enable networking or enforce a capability. Its text deliberately says NOT activated and NOT VERIFIED. The panel runs as an ordinary graphical user with no privileged broker, raw device interface, subprocess, shell or remote access.

## Preserve the proven physical-test mode

`blackcat.check=1` is the existing special boot entry. A systemd LightDM drop-in uses `ConditionKernelCommandLine=!blackcat.check=1` to skip graphical login on that path. The GUI itself independently skips execution if this boot flag is set. The existing root-owned Quick Check and display-only login hook remain unchanged. Test the text-only path again after rebuilding, since source inspection alone cannot establish boot behavior.

## Verification stages

1. Run `bash tests/02-session-ui-check.sh`: dependency seed, marker/guard and Python syntax checks. This is STATIC ONLY.
2. Rebuild the image in isolated CI; record source SHA, image SHA, exact size and job outcome. Do not replace the currently frozen M13B candidate unless the new build passes its gates.
3. Boot the newly built image in a graphical VM: observe the LightDM login, Xfce, autostart panel, checkbox preview and close-to-desktop interaction. Screenshot or direct framebuffer evidence required; a serial Quick Check does not prove a GUI started.
4. Boot with `blackcat.check=1` separately: confirm the automatic text report remains visible and graphical login does not take over tty1.
5. When authorized, physically write only a confirmed spare test USB and validate on a real machine. No destructive writing is part of this source change.
6. Only after a future signed module format, independent verifier, broker and runtime sandbox exist may the controls be connected to real modules. User-created modules must not be presented as officially trusted simply because they are signed by a builder.

## Limits and engineering risks

The desktop increases package count and attack surface; it is a usability development profile, not the eventual minimal core. Tk emoji rendering varies by font and desktop. Debian Live session/login naming and graphical target behavior are not yet validated. GUI UI and hardware privilege controls must be kept separate; hiding or disabling a checkbox is not enforcement. Cloud workshop/chatbot remains paused and no new network listener, credential or production key was introduced.
