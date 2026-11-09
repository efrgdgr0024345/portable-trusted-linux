# Automatic boot diagnostics and graphical recovery — implementation plan

Author: Codex (exact model/version and relative model rank unavailable).
Status: implementation plan requested by the operator; not implemented or tested.

Consolidation note — 10 November 2026 (AWST), Codex GPT-6 family: the sentence above is the original planning checkpoint. Existing collector/monitor/recovery/export implementation from ab5ebe0093bc78b9145fb670090520168be24e04 is now included in the integration candidate, and its source check passes. Retained GitHub job 105362480213/run 35268766563 supplies historical machine-readable VM observations at source ab5ebe0093bc78b9145fb670090520168be24e04: GUI interaction, settled System Check and the automatic recovery menu after masked-LightDM injection. Its exact ISO is ada9d032a4c957a995f874c693b6181014f64863c312dc0c86b892ec1d1fde65 (778043392 bytes); the artifact is no longer listed. See evidence/2026-11-10-main-integration/historical-recovery-observation.json. These bounded observations do not establish the complete acceptance matrix, combined-image recovery behavior or physical success. The ordered design/acceptance plan below is preserved; source import is not completion. See docs/24-main-consolidation.md.
Authority: expands the six-step plan accepted in this conversation. Preserve PROJECT_RULES.md and docs/21-json-only-candidate-publication-plan.md. A lower model MUST NOT change a higher model's plan, idea or suggestion without explicit human sign-off. Record proposed deviation, reason, risks and approval before changing direction; never infer model rank.

## Goal and evidence

The next candidate must reach either a working graphical session or an automatic, usable recovery screen without requiring commands, log searches or terminal switching from the operator.

User photographs show Debian 13 console autologin on tty2, kernel 6.12.107+deb13-amd64, and failed lightdm.service and plymouth-quit.service after the intended artwork candidate was written to USB. Earlier ACPI AE_NOT_FOUND messages are observed, but their causal role is unknown. The user rebooted before detailed service logs were captured; the previous volatile logs may no longer exist. Neither a graphics-driver cause nor a dependency direction between the failed services is established. Photographs do not independently prove the exact USB hash.

The intended candidate remains ISO f498776780c543013e437161a878e22ca4fc40a66f47fc271d6fd6a964c29d75, build 35179377345, artifact 10480220416. Existing VM and malware evidence stays attached to that image. Physical graphical validation is unsuccessful in the reported attempt. A new image needs new identity and evidence.

## Architecture decision

Use the existing Debian/systemd/LightDM/Xfce stack. Add bounded collection, a boot/session monitor and a graphics-independent console recovery interface. Reuse the current System Check and evidence packaging where suitable. Do not introduce a new display manager, remote shell, autonomous driver installation or firmware changes to diagnose this incident.

Component contracts (proposed names; implementation paths must follow source inspection):
- blackcat-boot-monitor: system service tracking boot ID, image identity, readiness and failure state; independent of LightDM completion.
- blackcat-diagnostics: collector producing structured report.json, readable summary.txt and bounded raw logs.
- blackcat-session-ready: session-side probe proving a usable display connection and live window manager/panel; ongoing liveness evidence.
- blackcat-recovery: text menu on a dedicated virtual console, brought to the foreground automatically after a detected failure.
- Existing System Check: shared collectors/report schema, retaining its explicit boot option and bounded behavior.

Do not order the monitor after graphical.target or a blocking Plymouth quit job. Review tty/getty ownership and Plymouth handoff explicitly so the recovery screen cannot be hidden behind the failed splash. Diagnostics must not block boot indefinitely.

## Work packages and order

### 1. Inspect the exact shipped configuration and preserve evidence
Read build package lists, LightDM configuration, Xfce autostart, Plymouth overrides, boot entries and current System Check/VM harness at the recorded source. Inspect relevant repo policies and design authority before implementation. Document enabled firmware/drivers and recovery dependencies; do not infer physical GPU identity from the VM. Record the reported physical failure and unknown local image identity without rewriting historical successes.

Deliverable: source map, report schema and a list of evidence required to determine the cause.

### 2. Implement bounded automatic collection
Collect early boot context and a failure snapshot: build/ISO identity where available, boot ID, kernel command line (redacted), kernel and system journal, failed units and pending jobs, LightDM/Xorg logs, session logs, graphics PCI IDs and bound drivers, DRM/display connector information, missing-firmware messages, relevant package versions, RAM/disk-space pressure, live-media I/O and filesystem errors.

Each collector reports complete/partial/unavailable/timed-out. Proposed initial budgets: 5 seconds per collector, 45 seconds total, 20 MiB report bundle with rotation/truncation marked explicitly. Use a root-owned private directory in RAM. Never traverse personal files or automatically mount internal disks. Strip credentials, network identifiers and device serials from shareable reports; retain useful GPU model/driver information. Sanitize log text before terminal display.

Provide an extension registry with bounded collectors for future failure classes. Unrecognized failures produce an UNKNOWN diagnosis plus evidence, not a fabricated explanation.

### 3. Implement readiness monitoring and failure detection
State machine: BOOTING -> DESKTOP_READY or RECOVERY; recovery actions -> RETRYING -> DESKTOP_READY or RECOVERY. A failure event can trigger collection early. Proposed initial deadline: 120 seconds after monitor startup, followed by at most 45 seconds collection; target recovery screen within 180 seconds under a responsive kernel.

LightDM active alone is insufficient. A probe must connect to the actual display and observe a live window manager/panel, stable for 10 seconds. Track a heartbeat to detect a subsequent session crash; suppress false failures during deliberate shutdown/logout. A stale marker from an earlier boot must never satisfy readiness. This is operational health evidence, not security attestation. Validate visible desktop and input separately in VM/physical tests.

### 4. Implement the no-command recovery interface
Automatically display:
- Desktop could not start (or the specific confirmed failure).
- Plain-language summary, observed facts, unknown cause where applicable, image/build reference and short report ID.
- Number/arrow-key options: retry desktop once; try temporary basic graphics; view summary; export report; reboot; shut down.
- A short photo-friendly page requiring no scrolling for the essential evidence.

Capture logs before restarting anything. Allow at most one automated desktop retry per boot; subsequent attempts are user-selected and bounded. Basic graphics is an explicit temporary diagnostic choice, not a guaranteed fix. Provide a boot-menu basic-graphics entry using the appropriate temporary kernel parameters after validating the bootloader path. If one-click reboot into that entry is not available on read-only USB media, clearly guide the user to the named menu choice without commands; do not pretend the mode changed.

No disabling Secure Boot/ACPI, enrolling keys, downloading drivers or persistent configuration changes automatically. Preserve the artwork in graphical states while keeping text recovery legible and independent of graphics.

### 5. Implement offline feedback and optional export
Full diagnostics stay in RAM until explicitly exported. Report ID binds to the report payload digest; keep digest/signature fields outside the hashed payload. Export menu lists identifiable removable writable destinations, refuses internal/ambiguous devices, asks for the selected destination and never formats media. DD-written boot USB may not offer writable storage: explain this and accept another removable drive. Verify copied bundle hash and unmount safely.

No network upload or remote control in this milestone. Network is optional. When no writable destination exists, the photo-friendly summary remains usable. State that reboot loses unexported RAM reports.

### 6. Validate success and failure paths
Extend the existing boot harness with machine-readable reports and screenshot evidence. Test:
- Normal graphical boot, readiness, visible artwork and working controls.
- LightDM failure, X server exit, absent driver/firmware-style failure fixtures, and a session that starts then dies.
- A hung graphics start, stale readiness marker, and Plymouth still owning the screen.
- Missing/unreadable logs, collector timeout, oversized logs and low-space conditions.
- No network, no export drive, read-only boot USB, internal-disk rejection, export write failure and successful export/hash verification.
- Recovery menu key navigation, bounded retries, basic-graphics menu choice, reboot/shutdown.
- Failure-injection hooks only in test fixtures; never enabled by default in the physical candidate.

Simulated driver failures validate detection/reporting, not compatibility with every GPU. Test normal boot and recovery using at least two practical virtual display configurations. Attach every observation to exact image/source identity. An early kernel hang may prevent any in-OS monitor; retain a boot-menu diagnostic entry and document this limit.

### 7. Build, publish and perform the next hardware test
After automated acceptance, build the candidate with exact source, size and hash; run normal boot and injected-failure tests against that exact ISO, then record results. Any post-test code change requires a new build and appropriate revalidation.

Update lessons, technical evidence and handoff first; project_content.json last. Publish compatible JSON to the branch the existing PHP reads, then verify the live HTTPS page displays the exact candidate identity and truthful limitations. No PHP replacement is required for routine publication. Do not supply an alternate chat-only download handoff.

The operator's next work is limited to selecting the correct spare USB, writing the verified candidate, booting it, and photographing the automatic result or choosing Export. If the desktop still fails, the automatic report becomes the next engineering input. Do not fall back to serial command requests as the routine workflow.

## Acceptance and completion

Implementation is complete only when normal desktop readiness and deliberately broken graphics both pass their automated gates; the failure case must visibly show the recovery menu without user console switching. Collection must stay bounded, offline operation must work, internal disks must remain untouched, export must be explicit and verified, and retries must terminate. Physical acceptance separately requires observed desktop/input on the user's hardware OR a usable automatic failure report/recovery screen; recovery success does not count as graphical success.

Track stages independently: planned, implemented, VM-tested, physically observed. No release approval or security guarantee is implied. Current next action: inspect shipped boot/session configuration, then implement collector and monitor before rebuilding.
