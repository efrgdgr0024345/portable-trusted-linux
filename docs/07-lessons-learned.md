# Lessons Learned Register

This file is mandatory under `PROJECT_RULES.md`. Every meaningful project session must add lessons here before the session is considered complete.

## 2026-09-11 — Repository becomes the canonical project workspace

### What was attempted
Move the project from conversational notes and isolated artifacts into a structured private GitHub repository with a living public project viewer.

### What happened
The repository was expanded to include human-readable documentation, a living JSON index, a canonical web viewer source and an explicit version history.

### Why it matters
The project now has a durable source of truth that can evolve without relying on manual copying of long documents between chats or servers.

### Decision / change
All substantial project work will now update repository documents first and `project_content.json` last.

### Evidence classification
**OBSERVED / IMPLEMENTED.** The repository structure and files exist in GitHub.

---

## 2026-09-11 — Lessons learned must be a hard project rule

### What was attempted
Make ongoing learning and failed experiments part of the engineering record rather than informal chat context.

### What happened
`PROJECT_RULES.md` was created with a mandatory rule requiring every meaningful engineering session, test, research result, failure, architecture change or implementation surprise to be recorded.

### Why it matters
Security projects often fail when undocumented assumptions and past failures are forgotten. A formal lessons register reduces the chance of repeating mistakes and makes later security review more credible.

### Decision / change
Every session must update this file, relevant technical docs, architecture decisions/milestones where appropriate, and then update `project_content.json` last.

### Evidence classification
**IMPLEMENTED POLICY.** Compliance must still be maintained on every future session.

---

## 2026-09-11 — The first prototype must remain smaller than the final product

### What was attempted
Translate the broad architecture into an implementation-ready first build.

### What happened
The existing specification was directionally correct but still mixed long-term design goals with Prototype 1 requirements.

### Why it matters
Trying to add transparency logs, blockchain, polished UI, cross-host comparison and full platform-reference intelligence before the local trust chain works would make failures harder to isolate.

### Decision / change
Prototype 1 will focus on the shortest demonstrable trust path: Debian Live base, signed UKI boot, measured-boot evidence, dm-verity protected root, RAM-backed disposable state, structured attestation bundle and independent verifier. External evidence anchoring remains designed into the schema but is implemented later.

### Evidence classification
**DESIGN DECISION.** It becomes proven only after the prototype is built and tested.

---

## 2026-09-11 — PCR equality across arbitrary PCs is not a valid universal test

### What was attempted
Define how multi-PC verification should compare boot evidence.

### What happened
PCR values depend on host firmware, configuration, boot policy and software measurements. Different legitimate machines should not be expected to produce identical complete PCR sets.

### Why it matters
A naive 'all PCRs must match' rule would create false failures and could encourage unsafe simplification of measured boot.

### Decision / change
Portable image identity can be compared universally. Host PCR/event-log evidence must instead be evaluated against host/model/configuration-aware policy or other valid reference information.

### Evidence classification
**ARCHITECTURAL CONSTRAINT.** Supported by TPM measured-boot design and retained in the threat model/specification.

---

## 2026-09-11 — A report hash must not be self-referential

### What was attempted
Design an attestation report that contains both its own digest and signatures/anchor receipts.

### What happened
Placing `report_hash` inside the exact bytes being hashed creates a circular definition unless fields and canonicalisation boundaries are explicitly defined.

### Why it matters
Ambiguous report hashing would make independent verification unreliable and could create incompatible implementations.

### Decision / change
Use a two-part model: a deterministic `payload` is canonicalised and hashed; an outer `envelope` carries the payload digest, signatures and external-anchor receipts. Exact canonicalisation is a Prototype Build Specification requirement.

### Evidence classification
**DESIGN DECISION.** Implementation and cross-language test vectors remain to be completed.

---

## 2026-09-11 — Debian 13/trixie has the required first-stage tooling

### What was attempted
Confirm that the selected Debian 13/trixie baseline actually provides the core build tools instead of assuming package availability.

### What happened
Current Debian package metadata confirms `live-build` is available in trixie, `systemd-ukify` is available as a separate package, `tpm2-tools` is available, and the trixie live-build package includes a `binary_dm-verity` build component.

### Why it matters
This removes an avoidable architecture risk: the first prototype can be built around tools distributed by Debian rather than immediately depending on manually compiled security tooling.

### Decision / change
`build/01-install-deps.sh` installs the Debian-packaged first-stage toolchain. Exact installed package versions are recorded on the real builder and in the generated build manifest rather than silently assuming the repository version will never change.

### Evidence classification
**VERIFIED AGAINST CURRENT DEBIAN PACKAGE METADATA.** Real build-host installation still needs to be executed and recorded.

---

## 2026-09-11 — Build evidence and release authenticity are different things

### What was attempted
Create a manifest for the first generated Debian Live artifact.

### What happened
The manifest can record SHA-256, artifact size, source Git commit, build host and package versions, but none of those fields alone authenticate the release to an external user.

### Why it matters
It would be easy to overstate a detailed manifest as proof of authenticity. An attacker capable of replacing both an image and an unsigned manifest could produce internally consistent values.

### Decision / change
`build/30-artifact-manifest.sh` is explicitly classified as build evidence. Release signing and verification remain separate future gates.

### Evidence classification
**IMPLEMENTED DESIGN CONSTRAINT.** Cryptographic release signing is not implemented yet.

---

## 2026-09-11 — Destructive USB writing should come after device-safety negative tests

### What was attempted
Plan the transition from ISO generation to physical USB media.

### What happened
A convenient early `dd`-style writer would introduce a serious operational risk: selecting the wrong block device could destroy unrelated data before the trust-chain work is even mature.

### Why it matters
Safety of the development process is part of project quality. A security tool should not introduce avoidable destructive behavior during its own build pipeline.

### Decision / change
USB writing remains intentionally absent. Before it is added, the project must implement explicit device-selection checks and negative tests proving ambiguous/internal devices are refused.

### Evidence classification
**ENGINEERING SAFETY DECISION.** USB-writing implementation remains pending.

---

## 2026-09-11 — Community Compute Commons research is useful but must remain paused while the core trust chain is unfinished

### What was attempted
Explore an opt-in Community Compute Commons concept that could donate spare consumer CPU/GPU/NPU capacity to approved LLM workloads.

### What happened
The feasibility study produced a plausible large-scale scenario and a separate architecture/threat-model extension. It also showed that the idea introduces networking, remote workload execution, privacy, scheduler trust and resource-accounting problems that are distinct from the original Portable Trusted Linux objective.

### Why it matters
Continuing to design CCC now would divide engineering attention before the core boot, dm-verity, TPM and independent-verifier chain has been demonstrated on real hardware.

### Decision / change
CCC research is preserved in `docs/08` through `docs/10` and `research/`, but its implementation milestones are **PAUSED / FUTURE**. Active engineering returns exclusively to Portable Trusted Linux Prototype 1 until the core trust-chain acceptance gates pass.

### Evidence classification
**PROJECT PRIORITY DECISION.**

---

## 2026-09-11 — Real-machine evidence collection should be one simple user action

### What was attempted
Move from repository-only assumptions to measured results from a real Debian 13/trixie builder without requiring the operator to manually collect many separate diagnostics.

### What happened
`build/05-first-run-report.sh` was added. It records OS/build-host identity, Git state, static project checks, host preflight, storage, memory, CPU, UEFI exposure and TPM-device exposure into a timestamped text report under `artifacts/host-checks/`.

### Why it matters
Reducing manual steps makes the evidence more consistent, easier to reproduce and less likely to omit the exact information needed for the next engineering decision.

### Decision / change
The preferred first real-builder interaction is now a single evidence-collection script. The resulting report is reviewed before dependency installation/build behavior is changed.

### Evidence classification
**IMPLEMENTED TOOLING; NOT YET EXECUTED ON THE TARGET BUILDER.**

---

## 2026-09-11 — Progressive hardening is an explicit engineering strategy

### What was attempted
Decide how to make practical progress while the final security architecture is still being built.

### What happened
The first remote builder was provisioned with a deliberately transitional trust setup: the SSH key is currently held on the user's cPanel Linux host rather than in final hardware-backed or short-lived credential infrastructure. The project rule set was updated to require temporary weaknesses to be named, bounded and replaced later.

### Why it matters
A prototype can become misleading if an early convenience control is silently treated as production security. Explicit weak-to-strong stages let the team validate functionality first without losing sight of the stronger target design.

### Decision / change
`PROJECT_RULES.md` now requires progressive hardening: document the current trust assumption, compromise path, temporary justification and stronger replacement for every intentionally weak control. The current cPanel-held SSH key is a temporary bootstrap control, not the final trust design.

### Evidence classification
**IMPLEMENTED POLICY / OBSERVED SETUP.** The DigitalOcean Droplet was created and the public key was enrolled according to the operator-provided control-panel evidence; direct connector-based execution is still pending.

---

## 2026-09-11 — The self-hosted runner provides a working auditable remote execution path

### What was attempted
Remove the user from the command-relay loop and give project automation a direct, reviewable path to execute non-destructive work on the remote Debian builder.

### What happened
A repository-scoped GitHub Actions self-hosted runner named `ptl-builder-01` was registered as a systemd service on the DigitalOcean Droplet. It runs as the unprivileged `github-runner` account. A pull-request-triggered smoke test successfully reached the runner, checked out the repository, executed `build/05-first-run-report.sh`, and returned the full job log through GitHub Actions.

### Why it matters
The project now has an auditable remote execution mechanism tied to repository history rather than relying on manual copy/paste of shell commands and output. This materially reduces operator transcription errors and makes later build evidence easier to correlate with source changes.

### Decision / change
GitHub Actions becomes the initial Prototype 1 remote execution control plane for the disposable builder. Privileged operations remain a separate decision: the runner is currently unprivileged and must not silently acquire permanent root-equivalent rights.

### Evidence classification
**VERIFIED / OBSERVED.** GitHub Actions run `34570941943`, job `103172701572`, completed all smoke-test steps successfully on runner `ptl-builder-01`.

---

## 2026-09-11 — First real-builder report confirms the build baseline but not attestation hardware

### What was attempted
Execute the project evidence collector on the first real Debian builder and compare reality with the planned baseline.

### What happened
The run observed Debian GNU/Linux 13.5 `trixie`, x86-64, kernel `6.12.94+deb13-amd64`, 2 vCPU, approximately 3.8 GiB RAM and about 74 GiB free on the root filesystem. Static shell/file checks passed. `jq` was missing, so JSON parsing was skipped. `git` was also absent, causing `actions/checkout` to use its REST-archive fallback and preventing the report from recording normal local Git repository state. The expected build-stage tools (`lb`, `xorriso`, `veritysetup`, `ukify`, `sbsign`, and TPM2 commands) were not installed yet, which is expected before `build/01-install-deps.sh`.

The VM did not expose UEFI runtime through `/sys/firmware/efi` and did not expose `/dev/tpm0` or `/dev/tpmrm0`.

### Why it matters
The DigitalOcean machine is suitable for build, static-test and artifact-generation work after dependencies are installed, but it cannot be used as evidence that UEFI measured boot or TPM attestation works. Those security gates need a separate platform that exposes the relevant firmware and TPM interfaces. The missing `git`/`jq` results also show why real preflight runs are necessary instead of assuming a cloud base image contains development tools.

### Decision / change
Milestone M11 is complete. M12 becomes current: install the declared build dependencies and rerun preflight. The cloud VM remains the build host; later UEFI/TPM testing will occur on a different capable machine. Subsequent reports should confirm that installing `git` restores normal checkout/provenance capture and that `jq` enables the JSON parse check.

### Evidence classification
**OBSERVED / VERIFIED FOR THIS BUILDER.** UEFI/TPM absence is a statement about interface exposure in this VM, not a claim about DigitalOcean hardware or firmware security.

---

## 2026-09-11 — Dependency verification exposed a runner PATH false negative

### What was attempted
Install the declared Debian build dependencies and verify them through the unprivileged self-hosted GitHub Actions runner.

### What happened
The dependency installation completed and the remote verification run confirmed `git`, `jq`, `lb`, `xorriso`, `ukify`, `sbsign`, and the required TPM2 commands. The first post-install preflight reported `veritysetup` as missing even though Debian's `cryptsetup-bin` package was installed. Investigation showed the runner service starts with a reduced PATH that omits `/usr/sbin`; after `00-host-check.sh` was changed to use a deterministic system PATH, the next run detected `veritysetup` at `/usr/sbin/veritysetup`. Git checkout also switched from the REST-archive fallback to normal Git checkout, and JSON parsing passed with `jq` installed.

### Why it matters
Capability tests must distinguish an absent executable from an executable hidden by the launching service's environment. Otherwise a correct installation can be misclassified as a failed security/build prerequisite.

### Decision / change
`build/00-host-check.sh` now sets a deterministic PATH containing `/usr/local/sbin`, `/usr/local/bin`, `/usr/sbin`, `/usr/bin`, `/sbin`, and `/bin`, and reports the resolved paths of build-stage commands. Milestone M12 is complete.

### Evidence classification
**VERIFIED / FIXED.** GitHub Actions run `34572084784`, job `103176175843`, detected `veritysetup` at `/usr/sbin/veritysetup` and all declared build-stage tools.

---

## 2026-09-11 — Pre-build review caught a live-build directory mismatch before the first ISO build

### What was attempted
Advance directly from verified dependencies to the first Debian Live ISO build.

### What happened
Reviewing the build scripts before execution showed `10-prepare-live-build.sh` created `work/live-<BUILD_ID>` while `20-build-live.sh` expected `work/live-build`. The build stage therefore would have entered a different directory and failed. The preparation stage also required root even though `lb config` and configuration-file generation do not require root.

### Why it matters
Staged review prevented an avoidable failed build and reduced the amount of code needing privileged execution. The distinction between unprivileged preparation and privileged image construction is an important security boundary for the remote runner.

### Decision / change
The preparation script now uses the stable `work/live-build` directory and runs unprivileged. `20-build-live.sh` reserves privilege for the actual `lb build` operation and fails explicitly if non-interactive privilege is unavailable. A temporary narrowly documented privilege will be used for Prototype 1, then replaced by a stronger isolated/root-owned build mechanism later.

### Evidence classification
**VERIFIED SOURCE DEFECT / FIXED BEFORE EXECUTION.** The privilege design is transitional and not equivalent to the final security architecture.

---

## 2026-09-11 — systemd-boot inside the live chroot blocked the first ISO attempt

### What was attempted
Build the first complete Debian Live hybrid ISO on `ptl-builder-01`.

### What happened
The build reached deep into live-image construction but failed when the full `systemd-boot` integration package ran package/ESP-related trigger logic inside the live-build chroot. A normal ESP is not present in that build context. Removing `systemd-boot` from the live root package seed while retaining the required EFI/UKI tooling resolved the blocker.

### Why it matters
A package that is appropriate for an installed UEFI host is not automatically appropriate inside a live-image chroot. Boot-manager integration and UKI tooling must be separated rather than assuming they belong in the same package seed.

### Decision / change
Do not seed the full `systemd-boot` integration package into the live root. Stage only the boot components actually required by the selected boot architecture.

### Evidence classification
**OBSERVED / FIXED.** The failed build and subsequent successful builds establish the practical cause/effect for this builder configuration.

---

## 2026-09-11 — A successful build is not enough unless source identity is unambiguous

### What was attempted
Generate the first successful ISO and record its provenance from GitHub Actions.

### What happened
The first success exposed a provenance ambiguity: pull-request checkout can place Actions on a temporary merge commit, while the intended source identity is the PR head commit. Recording only `git rev-parse HEAD` could therefore describe the checkout identity rather than the intended source identity.

### Why it matters
A manifest can be internally consistent yet still be ambiguous about which source revision was intended. That weakens later reproducibility and audit work.

### Decision / change
The workflow passes explicit source commit/ref information to the manifest, and the manifest separately records intended source identity and checked-out identity.

### Evidence classification
**VERIFIED / FIXED.** Successful run `34578438852` used explicit source identity `19deceb045a68112923dbd809e29e327d0736d32` and completed the build-and-fingerprint workflow successfully.

---

## 2026-09-12 — The first generated live image physically booted from USB

### What was attempted
Move from build-only evidence to the first real-hardware boot test.

### What happened
The generated `live-image-amd64.hybrid.iso` was written to USB using the imaging tool's raw/DD mode rather than file-extraction mode. The target PC then successfully booted the live image from the USB device.

### Why it matters
This crosses an important engineering boundary: the project is no longer only producing an ISO in CI; the artifact is bootable on at least one real hardware configuration. It also confirmed that raw/DD imaging is the appropriate early test path when we want to preserve the hybrid image rather than let the writer substitute/reconstruct bootloader files.

### Decision / change
Record the result strictly as **OBSERVED bootability on the tested machine**. Do not infer Secure Boot enforcement, TPM measurement, dm-verity integrity, release authenticity, firmware cleanliness or USB-controller integrity from this boot alone.

### Evidence classification
**OBSERVED.** Operator confirmed successful boot from the generated USB image. Cryptographic/hardware trust gates remain separate and pending.

---

## 2026-09-12 — Real-hardware testing must not depend on long commands typed by the operator

### What was attempted
Collect UEFI, Secure Boot, TPM and other runtime observations from the first physical boot.

### What happened
Long diagnostic command blocks were impractical to transfer into an offline live environment. A photo of the screen is easy to return, but manually entering multi-line shell logic is not.

### Why it matters
A technically correct diagnostic flow that is awkward to operate will create transcription errors and slow multi-machine testing. This becomes especially important when Black Cat is tested on arbitrary computers with networking intentionally disabled.

### Decision / change
The image now includes a modular `/usr/local/bin/blackcat-check` diagnostic and a **Black Cat - System Check (no typing)** boot entry. The menu adds `blackcat.check=1`; the live session detects the flag, runs the check automatically, and presents a photograph-friendly result. The initial result remains `OBSERVED` unless a separate verifier establishes stronger evidence.

### Evidence classification
**IMPLEMENTED IN SOURCE / BUILD TEST PENDING.** The new menu and diagnostic are committed; the rebuilt ISO still needs CI validation and physical menu testing.

---

## 2026-09-12 — Black Cat is branding, not a protocol rename

### What was attempted
Give the project a clearer user-facing identity without destabilising working engineering infrastructure.

### What happened
The user-facing name changed from Portable Trusted Linux to **Black Cat**, while the repository name, `PTL_` variables, workflow names and existing evidence identifiers remain stable.

### Why it matters
Renaming every internal identifier during active prototype engineering would create needless risk and noisy diffs while providing no security benefit. Brand and protocol identity can be separated cleanly.

### Decision / change
Use Black Cat in user-facing boot menus, documentation and status output. Retain PTL internally until a later deliberate migration is justified.

### Evidence classification
**IMPLEMENTED DESIGN / BRANDING DECISION.**

---

## 2026-09-12 — A CI candidate must not automatically become the user download

### What was attempted
Make it easy to download the exact Black Cat ISO from the living project page without accidentally selecting the wrong build output.

### What happened
During testing, a locally selected file reported as approximately 40,090 KB and Rufus warned that it was non-bootable or used an unsupported compression method. That observation did not by itself establish the file's exact origin or failure cause, but it demonstrated that a generic 'latest build' path is too easy to misuse. GitHub Actions artifacts are also candidate-build containers rather than a stable release channel.

### Why it matters
Black Cat's goals require the human operator to know exactly which bytes are intended for testing. Convenience must not collapse candidate build output, approved release identity and release authenticity into one vague 'latest' download.

### Decision / change
The living project viewer now has an **Approved Black Cat ISO** panel driven by `project_content.json`. The download is disabled unless the release record explicitly says `approved` and supplies an HTTPS download URL, exact filename, exact byte size and a syntactically valid SHA-256. The panel also displays source commit, build-run identity and automated smoke-test state. Candidate CI artifacts remain intentionally unlinked. The intended prototype distribution mechanism is a deliberately promoted GitHub Release asset rather than an arbitrary Actions artifact; later cryptographic release signing remains a separate stronger gate.

### Evidence classification
**OBSERVED OPERATOR FAILURE MODE / IMPLEMENTED DISTRIBUTION GUARD.** There is currently no approved Black Cat ISO; the release panel must therefore remain disabled until a candidate passes its required gates and is explicitly promoted.

---

## 2026-09-12 — The public project viewer must fail closed on credentials and authoritative release data

### What was attempted
Harden the living project viewer after reviewing its GitHub credential fallback behavior in the context of a potentially compromised Windows development PC.

### What happened
The viewer previously supported fallback credential sources and could retain cached/offline project data. That was convenient, but it weakened the desired trust posture: a missing primary credential or unavailable GitHub authority could still leave alternate paths active.

### Why it matters
Black Cat is explicitly being developed under the assumption that the Windows workstation may already be compromised. Security-sensitive distribution metadata should therefore prefer loss of availability over silently falling back to another credential source or stale release authority.

### Decision / change
`web/project.php` initially hardened to accept the GitHub credential only from `PTL_GITHUB_TOKEN`, with no hard-coded fallback and no offline/cached project authority. An approved download additionally requires exact size, SHA-256, a 40-character source commit and `smoke_test=passed`. The credential-source choice in this paragraph was subsequently superseded by the single protected token-file decision below; the fail-closed/no-cache principle remains unchanged.

### Evidence classification
**IMPLEMENTED SECURITY HARDENING / CREDENTIAL SOURCE SUPERSEDED.** This improves the public distribution control path but does not itself authenticate the ISO cryptographically; release signing remains a later trust gate.

---

## 2026-09-12 — QEMU firmware selection is test-harness evidence, not ISO evidence

### What was attempted
Require the `PTL First Live Build` workflow to boot the freshly built Black Cat ISO under UEFI/QEMU before treating the CI candidate as suitable for physical testing.

### What happened
GitHub Actions run `34665210673` successfully built the ISO and preserved the build output, but its separate `boot-smoke` job failed before meaningful ISO boot evidence because the workflow selected the first `OVMF_CODE*.fd` file and supplied it through QEMU's `-bios` shortcut. On the hosted runner that selection could be `OVMF_CODE_4M.secboot.fd`, which requires a matching variable-store/pflash arrangement rather than the old shortcut. In parallel, the standalone Black Cat smoke workflow run `34665210671` passed using an explicit non-secboot OVMF code image plus matching writable `OVMF_VARS` pflash drive.

### Why it matters
A VM harness can fail independently of the artifact being tested. Classifying a firmware-launch error as an ISO boot failure would incorrectly lower confidence in an image whose construction stage actually succeeded, and could lead to unnecessary boot-image changes.

### Decision / change
The live-build workflow now uses the same proven OVMF model as the standalone smoke workflow: select `OVMF_CODE_4M.fd` or `OVMF_CODE.fd`, pair it with the matching VARS template, use pflash drives, capture QEMU diagnostics/framebuffer evidence, and use the same 180-second direct-kernel userspace smoke window. The ISO build recipe itself was not changed by this fix.

### Evidence classification
**OBSERVED HARNESS FAILURE / SOURCE FIX IMPLEMENTED.** Commit `b7bdd8ef092934bf30148b135370690923b69062` contains the harness correction. The replacement workflow execution is the evidence gate for the fix; physical Black Cat menu testing remains separate.

---

## 2026-09-12 — The public viewer should have one operational credential source, not a fallback chain

### What was attempted
Deploy the fail-closed Black Cat project viewer on the existing cPanel/LiteSpeed account without embedding a GitHub token in public PHP and without turning credential setup into a blocker for the core project.

### What happened
Neither shell PHP nor web PHP exposed `PTL_GITHUB_TOKEN` because that environment variable had not been configured for the hosting processes. The operator created `/home/learning/.blackcat/` with private directory permissions and stored a newly scoped read-only GitHub token at `/home/learning/.blackcat/github_token` with mode `600`. The production project viewer then successfully fetched the private repository. No token value is stored in `project.php` or in the public web root.

### Why it matters
A single explicit external credential source is easier to reason about than a chain of environment/file/hard-coded fallbacks. It also fits the current threat model better: the possibly compromised Windows workstation does not become the long-term credential authority, and accidental publication of the PHP source does not reveal the token.

### Decision / change
For this cPanel-hosted prototype viewer, `/home/learning/.blackcat/github_token` is the **only** accepted GitHub credential source. The enclosing directory and token file must have no group/world permission bits; missing, empty, unreadable or over-permissive state causes a fail-closed response. `web/project.php` was synchronized to this production model. The token must remain fine-grained, read-only and restricted to the project repository; this mechanism is not suitable for release-signing keys or other high-value trust roots.

### Progressive-hardening note
Current assumption: the cPanel account and its private home directory are sufficiently controlled for a low-privilege repository-read token. Compromise of that hosting account could expose the token, although its read-only/repository-specific scope bounds the impact. A stronger later replacement may use hosting-native secret injection or a purpose-built service identity, but no future change should reintroduce a public-webroot or hard-coded secret fallback.

### Evidence classification
**OBSERVED DEPLOYMENT / IMPLEMENTED REPOSITORY HARDENING.** The operator confirmed the production page works with the protected token file. Canonical source synchronization is recorded in commit `d3bdc15823b65ce15918ad27945c895927579cde`.
