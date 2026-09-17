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

---

## 2026-09-12 — PR CI needs concurrency rules or documentation commits can consume build capacity

### What was attempted
Keep validating the evolving Black Cat pull request while recording lessons and living-project metadata after each engineering change.

### What happened
Every pull-request synchronization re-evaluated workflow path filters against the full PR diff. Because the PR already contained build/config/workflow changes, later documentation and `project_content.json` commits could trigger another copy of the expensive smoke/build workflows. With a single self-hosted builder, superseded runs accumulated and delayed the newest evidence gate.

### Why it matters
An evidence pipeline should preferentially test the source revision that could actually be promoted, not spend scarce builder time proving intermediate metadata-only revisions. At the same time, cancelling a self-hosted `live-build` in the middle of chroot/mount activity is less safe than cancelling a disposable hosted-runner job.

### Decision / change
All three PR workflows now use per-workflow/per-PR concurrency. Disposable hosted Black Cat smoke tests and the read-only builder-evidence job use `cancel-in-progress: true`. The self-hosted ISO workflow uses `cancel-in-progress: false`, which preserves an already-running build while GitHub collapses superseded pending runs to the newest revision. Build work directories remain run-specific.

### Evidence classification
**OBSERVED CI QUEUE BEHAVIOR / SOURCE FIX IMPLEMENTED.** The concurrency policy reduces redundant CI without changing the Black Cat ISO contents or relaxing any release gate.

---

## 2026-09-12 — Test-candidate retrieval and approved-release distribution are different operator states

### What was attempted
Restore the original development convenience of obtaining the exact ISO selected for physical testing from the living project page without weakening the deliberately strict Approved Black Cat ISO gate.

### What happened
The integrated live-build and UEFI/QEMU smoke workflow run `34669451222` completed successfully. Its preserved build artifact `10290629064` contains `live-image-amd64.hybrid.iso`; the archived build manifest and an independent SHA-256 calculation agree on exact size `376438784` bytes and ISO digest `34903e52268bf995207cc51e86de9d1950406690445fca7cedfe5a98371f7b32`, sourced from commit `c65c1cf27fc8bf21bbbb95d5bca989bed54e34df`. The viewer had previously hidden all CI candidates, which prevented the operator from retrieving the deliberately selected physical-test image through the project page.

### Why it matters
A development operator needs an easy route to one exact candidate while the project still needs a separate, stronger meaning for an approved release. Treating these as the same state either creates unnecessary friction during testing or weakens release semantics.

### Decision / change
The living viewer now supports a separate **Current Test Candidate** panel. It is enabled only when candidate metadata includes an exact filename, byte size, SHA-256, source commit, workflow run, artifact identity, HTTPS GitHub artifact link and `smoke_test=passed`. It is prominently labelled **TEST CANDIDATE — NOT AN APPROVED RELEASE**. The Approved Black Cat ISO panel and its promotion requirements remain independent and unchanged. GitHub Actions artifact distribution is temporary development infrastructure; authenticated release signing remains a later gate.

### Evidence classification
**VERIFIED BUILD IDENTITY / IMPLEMENTED DISTRIBUTION-SEPARATION DESIGN.** Automated CI boot evidence has passed for this candidate; physical no-typing menu and persistence validation remain pending.

---

## 2026-09-12 — Physical no-typing test exposed duplicate diagnostic execution

### What was attempted
Physically boot the frozen M13B candidate and use the Black Cat - System Check menu without typing diagnostic commands.

### What happened
The candidate booted successfully on real hardware and the photograph-friendly quick-check screen appeared automatically. It observed UEFI boot, Secure Boot enabled, a TPM device, a TCG event log, no active network link, kernel `6.12.107+deb13-amd64`, architecture `x86_64`, and dm-verity root `NOT_ACTIVE` (expected before milestone M17). However, the screen also showed `Permission denied` when writing `/run/blackcat/quick-check.txt` and `/run/blackcat/quick-check.json`.

Source review identified a deterministic cause: `blackcat-check.service` already runs `/usr/local/bin/blackcat-check` as root before `getty@tty1.service`, creating root-owned report files under `/run/blackcat`. The profile hook then executed the same collector again as the normal live user. That second run could display observations but could not overwrite the root-owned evidence files.

### Why it matters
An evidence collector should have one authority/producer. Running the same collector twice under different privilege levels creates confusing output and weakens confidence in which report is authoritative even when the observations themselves are useful.

### Decision / change
The systemd service remains the **sole root-owned evidence producer**. The login/profile hook is now display-only: it waits briefly for `/run/blackcat/quick-check.txt`, displays the service-generated text/JSON paths, asks the operator to photograph the screen, and never executes `blackcat-check` itself. Static checks now fail if the profile hook directly invokes the collector again. The previously frozen candidate is superseded for M13B completion; a rebuilt candidate must pass CI and then receive one more physical no-typing test with no permission errors.

### Evidence classification
**PHYSICAL OBSERVATION / ROOT CAUSE VERIFIED IN SOURCE / FIX IMPLEMENTED, REBUILD AND PHYSICAL RETEST PENDING.**

---

## 2026-09-12 — Project autonomy needs an explicit pre-pause rule

### What was attempted
Reduce unnecessary operator interruptions during an active engineering workflow without weakening the project's safety and security boundaries.

### What happened
The project had strong technical security rules but no equally explicit operating rule telling the assistant when *not* to ask for permission. As a result, routine transitions—especially pending CI, safe follow-up checks and ordinary continuation—could be presented as if they required the operator to say “proceed” again.

### Why it matters
Unnecessary confirmation points slow development, consume operator attention and can leave safe, reversible work unfinished. At the same time, indiscriminate autonomy would be unsafe around USB writes, firmware/TPM ownership, destructive actions, credentials and irreversible trust decisions. The project needs a precise boundary between ordinary autonomous engineering and genuine human gates.

### Decision / change
`docs/11-automation-manifesto.md` now defines the Black Cat Automation Manifesto. `PROJECT_RULES.md` makes its **pre-pause check mandatory**. Before asking the operator to proceed or pausing merely because a workflow is running, the assistant must first determine whether the next work can be completed through already-authorized tools, whether it is reversible/non-destructive, whether missing information can be retrieved, and whether independent safe work remains. Routine in-scope work proceeds automatically. Pauses are reserved for genuine human gates such as physical hardware interaction, destructive/irreversible actions, new authorization/credentials, trust-ownership changes, material policy choices, or information that truly cannot be derived safely. Static repository checks require both the manifesto and the mandatory rule to remain present.

### Evidence classification
**IMPLEMENTED PROJECT OPERATING POLICY / STATIC ENFORCEMENT ADDED.** This policy does not override platform authorization requirements or safety rules; it reduces routine permission-seeking inside those boundaries.

---

## 2026-09-12 — Branding assets must be reproducible but remain outside the trust decision

### What was attempted
Use the Black Cat project icon theme on the Debian live image boot/loading experience without making visual branding a reliability or security dependency.

### What happened
A compact 320×240, 16-colour PNG derivative of the Black Cat boot artwork was created for bootloader compatibility. Because the GitHub contents workflow used by this project is text-oriented, the PNG was initially stored as repository-tracked base64 text at `assets/blackcat-boot-320x240.png.b64`.

`build/10-prepare-live-build.sh` decoded the asset locally without a network fetch, verified a pinned SHA-256, and staged the same bytes as `grub-pc/blackcat.png` and `syslinux_common/splash.png`. GRUB uses a native conditional around PNG/background loading so machines that cannot render the image still retain the ordinary text menu. Syslinux retains its normal bootable labels with the splash as menu decoration.

### Why it matters
Branding should be deterministic enough to reproduce and audit, but a corrupted/missing visual must never be confused with a trust failure or prevent a machine from reaching the boot choices. Separating cosmetic presentation from boot/security state prevents future UI work from accidentally weakening availability or evidence semantics.

### Decision / change
Black Cat system images and icons should derive from the established project-cat visual theme. Boot branding must be staged from repository-controlled bytes, checked for exact identity during the build, and treated as **cosmetic only** at runtime. Security claims, boot eligibility and evidence classification must never depend on successful rendering of the cat artwork.

### Evidence classification
**SOURCE IMPLEMENTED / AUTOMATED REBUILD VALIDATION PENDING.**

---

## 2026-09-12 — Large text-encoded binary assets need an early decode gate

### What was attempted
Run CI with the first repository-encoded Black Cat boot splash.

### What happened
Black Cat Boot Smoke Test run `34684121916` failed before Debian image construction. The failure was deterministic and occurred in `build/10-prepare-live-build.sh`: GNU `base64 --decode` reported `invalid input` for the 320×240 splash text. The bootloader, Debian build, System Check and QEMU stages were therefore never reached.

The malformed large asset was removed rather than trying to normalize unknown corrupted bytes. A new 160×120, 8-colour PNG was generated from the same Black Cat artwork. Its repository base64 text is only 4,188 bytes and decodes independently to SHA-256 `24d03a442c3f7f04a060d30fbf8710f8b8cd2b5495caffdeea7cc47fcbc546ea`. The build script and static checks now use that smaller exact asset and explicitly fail with a clear message if decoding itself fails.

### Why it matters
A checksum guard cannot help if transport corruption prevents bytes from being reconstructed in the first place. For binary assets carried through a text-only API, **successful decode is a separate prerequisite before digest verification**. Keeping cosmetic boot assets deliberately small also reduces repository/tooling fragility without changing the trust model.

### Decision / change
Use the compact `assets/blackcat-boot-160x120.png.b64` asset for the current boot splash. Validate in this order: source exists → base64 decodes → decoded SHA-256 matches → stage to bootloader trees. Remove malformed/superseded encoded assets rather than leaving ambiguous alternatives in the repository.

### Evidence classification
**CI FAILURE OBSERVED / ROOT CAUSE VERIFIED / SOURCE FIX IMPLEMENTED.** A replacement CI run is still required before this branding becomes part of a physical-test candidate.

---

## 2026-09-13 — Automation convenience must not create a new attack surface by default

### What was attempted
Design an unattended controller so project work could continue without the operator checking in manually.

### What happened
A cPanel-hosted webhook/controller and optional OpenAI API worker were explored and documented, but the operator challenged the additional attack surface before anything was deployed. Review confirmed that a new public listener, new secrets and another writable control plane were not justified while GitHub Actions already handles the core automated work.

### Why it matters
Automation can accidentally weaken the security posture it is meant to improve. A convenience feature that adds an Internet-facing endpoint or another privileged credential must be judged as a security change, not merely as workflow polish.

### Decision / change
The webhook/controller design is superseded before deployment. Existing GitHub Actions remain the default automation infrastructure. If unattended reconciliation is later justified, prefer an outbound-only watchdog outside `public_html`. New public listeners, API credentials or writable automation services require a separate security decision demonstrating that their benefit outweighs the added attack surface. The deterministic state-machine file may remain as non-networked conceptual documentation.

### Evidence classification
**SECURITY DESIGN REVISION / NO NEW SERVICE DEPLOYED.**

---

## 2026-09-13 — Viewer drift can silently hide the correct ISO even when the build is healthy

### What was attempted
Use the public `project.php` page to retrieve the newly validated M13B ISO candidate.

### What happened
The production page reported `offline-cache` and `GitHub read-only token is not configured`. Inspection showed the live cPanel file was an older viewer that looked for `PTL_GITHUB_TOKEN` or a hard-coded fallback and could serve stale cached data. The protected token file `/home/learning/.blackcat/github_token` still existed with correct private permissions (`700` directory, `600` token). The repository's newer canonical viewer already used the protected token file and had a separate Current Test Candidate panel, but it was still pointed at `main`, whose project metadata lagged behind the active M13B branch.

The corrected integrated build run `34686464650` and its artifact `10300658965` passed the self-hosted build and integrated UEFI/QEMU smoke gates. The exact ISO is `live-image-amd64.hybrid.iso`, 376,438,784 bytes, SHA-256 `cb14d25b7fd43e2b0e563efaada357d6ab196a42d717b016c4857f85968fe641`, sourced from commit `c56389b71ecd96270bdd047369378f5a94d8bf93`.

### Why it matters
A correct artifact can become operationally inaccessible if the viewer source, credential model and metadata branch drift apart. Serving stale cache is particularly dangerous because it can look healthy while hiding or misrepresenting the exact candidate selected for physical testing.

### Decision / change
`web/project.php` is the single canonical viewer source. It uses only `/home/learning/.blackcat/github_token`, fails closed instead of serving authoritative offline cache, and is temporarily pinned to `codex/remove-chroot-systemd-boot` while PR #6 remains the authoritative M13B line. Static checks now guard the token-file model, forbid the old environment/fallback/cache paths, require the Current Test Candidate panel, and ensure M13B cannot silently lose its candidate record. After the branch is merged, the viewer and its regression check must be switched back to `main` together.

### Evidence classification
**ROOT CAUSE VERIFIED / REPOSITORY FIX IMPLEMENTED / PRODUCTION DEPLOYMENT STILL REQUIRED.**

---

## 2026-09-13 — Viewer hardening must preserve observational operations panels

### What was attempted
Replace the stale production viewer with the repository-canonical fail-closed version while keeping the living project page useful for day-to-day operations.

### What happened
The hardened viewer correctly restored the exact M13B test candidate and protected token-file model, but replacing the older cPanel `project.php` also removed the existing builder/server status windows because those panels were not represented in the canonical repository copy.

### Why it matters
Security hardening and operational visibility are separate requirements. Removing an authoritative fallback is correct; removing a read-only observational status panel is unnecessary and makes the project page less useful. Local builder telemetry must also never be confused with GitHub project authority.

### Decision / change
`web/project.php` again reads `project_cache/builder_status.json` for display-only builder/server telemetry. Secret-like keys are filtered before display. The telemetry is rendered in a separate **Builder / Server Status** panel and cannot affect candidate or release gates. A separate **Project Data Source** panel now clearly shows repository, branch, project file, Git blob, credential source and the fact that authoritative project metadata comes from the live GitHub API. Static tests require both panels and the authority boundary so future viewer replacements cannot silently remove them again.

### Evidence classification
**SOURCE FIX / REGRESSION GUARD IMPLEMENTED.** Production redeployment and visual confirmation remain pending.

---

## 2026-09-13 — Autonomy rules need a root-level operational decision table

### What was attempted
Reduce repeated human delays by making it immediately clear when GPT/AI project work may continue automatically and when it must stop for the operator.

### What happened
The existing Automation Manifesto and Rule 12 already established a strong proceed-by-default principle, but the practical action classes were spread across prose and were not visible at the repository root. That left room for inconsistent interpretation of routine branch work, CI waiting, production-viewer deployment, merges, releases and capability limitations.

### Why it matters
A durable automation policy needs a fast, unambiguous continue/stop decision surface. The operator should not have to restate permission for ordinary safe work, while high-impact boundaries such as physical media, secrets, trust ownership, merges and release promotion must remain explicit.

### Decision / change
Create root-level `AI_AUTONOMY_POLICY.md` as the canonical operational decision table. It classifies actions as `AUTO`, `AUTO_WITH_CHECKS`, `HUMAN_GATE`, or `FORBIDDEN_BY_DEFAULT`; distinguishes a missing-tool capability boundary from a policy approval gate; keeps `PROJECT_RULES.md` as the master policy; retains `docs/11-automation-manifesto.md` as the explanatory rationale; records the durable choice as ADR-037; and adds static checks so the policy, root placement, required action classes and project-page link cannot silently disappear.

### Evidence classification
**IMPLEMENTED POLICY / STATIC ENFORCEMENT ADDED.** The policy governs future AI-assisted work inside existing safety, authorization and platform limits; it does not create new permissions or background execution capabilities.

---

## 2026-09-13 — Long AI chats need compact repository checkpoints, not repeated full-history reloads

### What was attempted
Reduce the natural delays and continuity failures that occur when a long-running GPT/ChatGPT project approaches practical context limits or a chat/session drops out, while avoiding the opposite failure mode of re-reading the entire repository on every turn.

### What happened
The project already treated GitHub as the durable engineering record, but it did not have a deliberately small first-read handoff file or an empirical method for noticing that a conversation was becoming context-heavy. Re-reading `PROJECT_RULES.md`, the autonomy policy, the full lessons register and `project_content.json` at every turn would itself consume the context we are trying to conserve. At the same time, pretending we can see an exact hidden context-window percentage would create false precision.

### Why it matters
A long AI-assisted project should survive a lost chat without requiring the operator to reconstruct the current milestone, exact candidate, next action and human gate. It also needs a way to improve its estimate of practical context pressure using real observed failures rather than unsupported token assumptions.

### Decision / change
Add root `AI_WORK_STATE.md` as a compact checkpoint that is read first only on fresh/recovery sessions or when continuity is uncertain. Ordinary continuing turns do not bulk-reload project history. Add `docs/13-ai-continuity-and-context-recovery.md` as a reusable implementation guide for other projects and `docs/14-context-health-calibration-log.md` as an append-only empirical record of real dropout/continuity events and heuristic changes. `AI_AUTONOMY_POLICY.md` and `PROJECT_RULES.md` now classify context checkpoint/recovery as automatic work and require GREEN/AMBER/RED to remain explicitly heuristic rather than an exact context percentage. ADR-038 records the durable architecture choice and static checks protect the compact state file, selective-reload rules, project-page visibility and no-false-precision boundary.

The context-health model starts at provisional v0.1. Unknown historical counts remain `unknown`; future thresholds are changed only when observed project evidence supports the change.

### Evidence classification
**IMPLEMENTED CONTINUITY POLICY / REPOSITORY CHECKPOINT AND DOCUMENTATION ADDED / HEURISTIC CALIBRATION PROVISIONAL.** No exact model context meter is claimed. The value of the thresholds will be established by future observed continuity/dropout events.

---

## 2026-09-13 — Native AI project automation should be a bounded Linux capability, not a privileged AI daemon

### What was attempted
Plan how the autonomy, compact recovery and context-health workflow could eventually be built directly into Black Cat Linux so users could create other projects that follow the same logic.

### What happened
The repository workflow generalises cleanly into a future Linux project system, but only if model reasoning is separated from execution authority. Giving an AI an unrestricted root shell would destroy the very boundary the project is trying to formalise. A safer design is a user-space project worker whose repository defines project policy and whose operating-system capability broker independently controls filesystem, Git, test/build, network, device and privilege operations.

### Why it matters
If Black Cat later provides AI-assisted project automation as a product feature, the security model must survive model mistakes, prompt injection and provider changes. Durable project state should remain portable and model-neutral; human gates must be enforced by code/permissions, not merely remembered by the model. The feature also must not distract from completing the current core trust-chain prototype.

### Decision / change
Add `docs/15-black-cat-ai-project-automation.md` as the implementation/brainstorming blueprint and ADR-039 as the durable architecture direction. Plan M26 after M25 for a project template/CLI, deterministic autonomy engine, sandboxed capability broker, model adapter, user-level background/resume worker, desktop dashboard and adversarial negative tests. Prefer local-first/offline operation, optional remote models, user-level systemd timers/services, no new public listener by default, no unrestricted root authority and concise audit summaries rather than private model reasoning.

### Evidence classification
**DESIGN / ROADMAP DECISION ONLY.** No native AI project subsystem has been implemented yet; the active M13B→M25 Black Cat security roadmap remains the engineering priority.

---

## 2026-09-13 — Reusable AI automation needs a fail-closed project profile, not just good prose

### What was attempted
Turn the future Black Cat AI-project concept from an attractive design document into concrete repository scaffolding that another project could actually start from.

### What happened
A provider-neutral seed was added under `templates/ai-project/` with master rules, an autonomy decision table, compact recovery state, a fail-closed machine-readable `project-policy.json`, ADR/lessons templates and a setup guide. `schema/ai-project-policy.schema.json` now defines the first machine-policy contract. Static checks require the scaffold to remain present, parse the JSON files, verify conservative defaults and preserve key untrusted-data/recovery boundaries.

### Why it matters
Markdown guidance alone cannot safely become execution authority. A future Linux capability broker needs a deterministic, machine-readable policy input whose defaults are conservative and whose permissions cannot be expanded by prompt injection, repository content or a model's own output. At the same time, checking the scaffold into the repository lets other people reproduce the project pattern before the native M26 runtime exists.

### Decision / change
M26 starts from this checked-in template and schema. The sample policy defaults to `HUMAN_GATE`, network disabled, no inbound listeners, no root, no raw block devices and no credential access; secret exposure, safety bypass and self-expanding authority are forbidden. Human-readable project policy remains the source of intent, while the future broker must independently validate the machine profile and fail closed on disagreement. The scaffold is inert design/template material in Prototype 1 and does not authorize a daemon, listener, credential, root privilege or other new runtime capability.

### Evidence classification
**DESIGN SCAFFOLD IMPLEMENTED / STATIC GUARDS ADDED / RUNTIME ENFORCEMENT FUTURE (M26).**

---

## 2026-09-13 — Corrected M13B physical retest clears the report-permission regression

### What was attempted
Physically retest the corrected M13B candidate on real hardware using the no-typing **Black Cat - System Check** path.

### What happened
The machine first displayed repeated firmware/ACPI `AE_NOT_FOUND` messages, but boot continued without intervention and reached the intended **BLACK CAT - QUICK CHECK** screen. The photographed corrected run shows UEFI, Secure Boot ENABLED, TPM DEVICE_FOUND, TCG event log FOUND, dm-verity root NOT_ACTIVE, no active network link observed, kernel `6.12.107+deb13-amd64` and architecture `x86_64`. Crucially, the previous `/run/blackcat/quick-check.txt` and `.json` `Permission denied` errors are absent.

### Why it matters
This is the first real-hardware evidence that the single-producer fix behaves as intended: systemd can create the evidence and the display path no longer visibly attempts an unauthorized second write. It also shows that noisy firmware ACPI diagnostics can precede a successful Black Cat boot and should not automatically be classified as an image failure. However, the quick-check screen does not currently expose internal-disk mount state, so it cannot by itself close the full M13B acceptance gate.

### Decision / change
Record the report-permission regression as **physically observed fixed on this tested configuration**. Preserve the ACPI messages as firmware compatibility evidence rather than suppressing them from diagnostic records. Keep M13B current until one read-only disk/mount observation confirms internal disks remain unmounted by default. A later UX improvement may present non-fatal firmware diagnostics less prominently while retaining them in evidence logs.

### Evidence classification
**PHYSICAL OBSERVATION / REPORT-PERMISSION FIX PASSED ON TESTED HARDWARE / INTERNAL-DISK MOUNT GATE STILL PENDING.**

---

## 2026-09-13 — M13B closes only after internal-disk state is observed separately

### What was attempted
Close the corrected physical-test milestone without assuming that the quick-check screen proved internal storage remained untouched.

### What happened
After the corrected quick-check screen passed, the operator entered the live shell and ran the read-only command `lsblk -o NAME,SIZE,TYPE,MOUNTPOINTS`. The photograph shows `sda` as a 14.6G disk with `sda1` mounted at `/run/live/medium`, matching the live USB, and `loop0` mounted as the live squashfs. The internal `nvme0n1` is 476.9G and its four partitions (`p1` 260M, `p2` 16M, `p3` 475.7G, `p4` 952M) all have blank mount-point columns.

### Why it matters
The security requirement is not merely that the live OS boots; internal disks should remain unmounted by default. That requirement needs direct storage-state evidence rather than inference from an unrelated diagnostics screen.

### Decision / change
M13B is complete on the tested hardware/configuration. Record the internal NVMe as **OBSERVED unmounted by default** during this boot. Do not generalise the result to all hardware; later multi-PC testing and stronger runtime policy tests remain separate gates.

### Evidence classification
**PHYSICAL OBSERVATION / M13B COMPLETE ON TESTED CONFIGURATION.**

---

## 2026-09-13 — Release authenticity needs a signed canonical object, not just another checksum

### What was attempted
Begin M14 without prematurely creating or exposing a production signing secret.

### What happened
The project already had strong build provenance and exact candidate SHA-256 values, but an unsigned checksum and unsigned manifest can be replaced together with a malicious artifact. The non-secret M14 implementation now defines one exact canonical JSON release manifest, binds it to an Ed25519 public-key fingerprint, signs the exact manifest bytes with a detached signature, and verifies the signature plus exact artifact filename, byte size and SHA-256. The signer also derives its own public key and refuses to sign when that fingerprint does not match the manifest.

A dedicated development self-test creates only a temporary Ed25519 keypair, verifies the valid path, and requires failure for artifact tampering, manifest tampering, wrong public key and private-key/fingerprint mismatch. A GitHub-hosted workflow runs these mechanics without any production secret.

### Why it matters
This cleanly separates three different questions: what bytes were built, whether those bytes behaved as expected in tests, and whether a trusted release authority authenticated the exact release metadata. It also avoids the dangerous shortcut of placing a long-lived release private key on the general-purpose builder merely to make CI convenient.

### Decision / change
Use the M14 format and tooling documented in `docs/16-authenticated-release-signing.md` and ADR-041. Keep release signing separate from Secure Boot/UKI signing. Automated work may validate signing mechanics with ephemeral keys, but selecting and provisioning the production release-signing key ownership/custody model remains a genuine HUMAN_GATE.

### Evidence classification
**NON-SECRET M14 IMPLEMENTATION ADDED / DEVELOPMENT SELF-TEST PENDING CI / PRODUCTION KEY AUTHORITY NOT YET PROVISIONED.**

---

## 2026-09-15 — Closed manifest schemas reduce signature ambiguity

### What was attempted
Continue M14 safely without a production signing key by strengthening the release-manifest parser/verifier and expanding negative tests.

### What happened
A shared `build/lib/validate-release-manifest.sh` validator now enforces both exact canonical JSON bytes and an exact closed field set. The generator, signer and verifier all use the same validator. The test suite was expanded to reject non-canonical JSON, unexpected fields, wrong artifact filenames, malformed signatures, wrong keys, non-Ed25519 keys and invalid release IDs in addition to the existing tamper checks.

The updated M14 scripts and expanded test suite were reproduced in an isolated local shell and completed successfully with only ephemeral development keys. No production private key was created or stored.

### Why it matters
A valid signature over loosely interpreted JSON is not enough if different implementations can ignore or reinterpret extra fields. A closed canonical schema makes the exact signed meaning smaller and reduces parser ambiguity. Sharing one validator also reduces the chance that the generator, signer and verifier drift into subtly different interpretations.

### Decision / change
Treat `blackcat-release-manifest-v0.1` as closed/fail-closed. Unknown fields require a future explicit schema version rather than being silently accepted. Keep local development-test success distinct from GitHub CI evidence until a current-revision Actions run appears.

### Evidence classification
**SOURCE HARDENING IMPLEMENTED / LOCAL DEVELOPMENT SELF-TEST PASSED / CURRENT-REVISION CI EVIDENCE STILL PENDING.**

---

## 2026-09-15 — M15 can be prepared without creating Secure Boot authority

### What was attempted
Make useful progress toward the signed-UKI milestone while production release-key custody and future Secure Boot key ownership remain intentionally unresolved.

### What happened
Debian 13/trixie's `systemd-ukify` 257-series toolchain supports building, inspecting and Secure-Boot-signing Unified Kernel Images. A new M15 preparation document defines how Black Cat will bind the exact kernel, initrd, command line, OS release, kernel version and SBAT metadata, record UKI/section digests and exercise negative tests with ephemeral DEVELOPMENT ONLY keys.

### Why it matters
The project does not need to stall simply because production key custody is a human gate. We can design the artifact format, evidence and failure tests now while refusing to create a long-lived authority or touch target firmware. This preserves momentum without weakening the trust model.

### Decision / change
`docs/17-signed-uki-development-plan.md` is the M15 pre-implementation plan. Development keys may exist only ephemerally for testing; no automatic firmware enrollment is allowed; M14 release signing and M15 Secure Boot signing remain separate identities.

### Evidence classification
**DESIGN PREPARATION IMPLEMENTED / NO PRODUCTION SECURE BOOT KEY CREATED / NO FIRMWARE CHANGES MADE.**

---

## 2026-09-15 — AI tool creation should be outside the trusted core, and software selection should reduce the active attack surface

### What was attempted
Refine the future Black Cat AI/project concept so users can choose only the tools needed for a session and can ask an AI to create new tools without giving that AI direct authority over the trusted operating system.

### What happened
The architecture now separates three trust zones: a small immutable Black Cat core, pre-built ready-to-run session modules, and a separate disposable AI build workshop. Modules are intended to live outside the core, remain inactive/unmounted unless selected, and request only explicit capabilities. The external workshop may design, compile and test software but is deliberately treated as untrusted and receives no trusted-core write access or production signing authority.

### Why it matters
Sandboxing the AI builder reduces the blast radius of model mistakes or workshop compromise, while keeping unused applications out of the active session can reduce attack surface. However, isolation does not make generated software safe. A compromised workshop can still produce malicious output, so Black Cat must independently verify what crosses back into the trusted environment and must enforce capability limits outside the model/application.

### Decision / change
Add `docs/18-modular-runtime-and-ai-workshop.md`, extend the M26 design in `docs/15-black-cat-ai-project-automation.md`, and record ADR-042. Future modules should be pre-built rather than installed during boot; official, local-user and unverified-candidate modules remain distinct trust classes; a pre-desktop AI interface may run only after a minimal authenticated Black Cat environment and capability broker are active. The current M14–M25 core trust-chain work remains the implementation priority.

### Evidence classification
**ARCHITECTURE/DESIGN DECISION RECORDED / NO RUNTIME MODULE SYSTEM OR AI WORKSHOP DEPLOYED.**

---

# Preserved parallel main-branch history

The following entries were developed on main through e12b48fa15c586399c4a809e84114dadfa60283e. Historical identifiers in this section retain their original main-branch scope; dates do not imply evidence for the graphical candidate.

---

## 2026-09-13 — SSH recovery must be documented without storing the secret

### What was attempted
Recover manual administrative SSH access to `ptl-builder-01` after the exact connection recipe had been forgotten.

### What happened
DigitalOcean still held the expected public SSH key and the cPanel host still held both `~/.ssh/ptl_digitalocean` and `~/.ssh/ptl_digitalocean.pub`. Plain SSH attempts failed because the cPanel SSH configuration only selected the GitHub key and therefore did not automatically present the PTL builder key. Explicitly selecting `~/.ssh/ptl_digitalocean` restored access.

### Why it matters
The key material had not been lost; the operational mapping between host, account and private-key path had been lost. A security control that depends on memory creates unnecessary recovery risk even when the underlying cryptography is sound.

### Decision / change
`docs/11-builder-access-runbook.md` now records the builder identity, DigitalOcean public-key fingerprint, cPanel private-key location, known-working SSH command, recommended SSH alias, verification steps and recovery procedure. GitHub may record public key material, fingerprints and the **path** to a private key, but never the private key contents, passphrases, API tokens or recovery codes. The current direct-root key remains explicitly classified as a temporary Prototype 1 bootstrap control to be replaced by a dedicated non-root administrative path and stronger credential custody.

### Evidence classification
**OBSERVED / RECOVERED / DOCUMENTED.** Administrative access was successfully restored using the existing cPanel-held private key; no secret key material was copied into GitHub.

---

## 2026-09-16 — Project coaching should become a Black Cat capability, not a separate throwaway chatbot

### What was attempted
Design a practical system that improves how the operator uses AI across multiple projects while remaining compatible with the longer-term Black Cat USB goal.

### What happened
The design converged on a mobile-first Black Cat Console with persistent per-project state, voice/text capture, a coaching step that converts natural input into clearer Goal / Current State / Constraints / Success instructions, and a separate project-AI step. The first source MVP now exists under `apps/blackcat-console/`. The existing `blackcat-chatbot-01` server is the intended remote prototype host, while `ptl-builder-01` stays isolated as a build machine.

### Why it matters
A standalone cloud-only project manager would create another system to maintain and later discard. Treating chatbot and project control as Black Cat Core interfaces means work done now can inform the eventual local USB implementation. Separating durable Markdown project state from remote model access also avoids making project memory dependent on one AI provider or server.

### Decision / change
Black Cat Console is now an application-layer prototype of Black Cat Core. Project cognition is stored in human-readable files; remote AI is optional; secrets remain server-side; deep review is intended to be read-only by default; and outbound project data should become explicitly inspectable before remote AI use. The Console reuses the existing Black Cat droplet rather than creating another server.

### Evidence classification
**IMPLEMENTED IN SOURCE / ARCHITECTURE ACCEPTED / NOT YET DEPLOYED OR DEVICE-VERIFIED.** The repository contains the MVP source, deployment guide and static-test script. No claim is made yet that the Console is running on the droplet or proven on iPhone hardware.

---

## 2026-09-16 — Browser execution and model reasoning should be separate layers

### What was attempted
Decide whether Black Cat should automate a browser, including whether it should drive the ChatGPT website to obtain stronger reasoning than the API.

### What happened
Current OpenAI documentation shows that GPT-5.6 Sol and GPT-6 Astra API models support reasoning levels through `max`, and Astra supports computer use and other agentic tools. The ChatGPT product can still behave differently because it adds a product-level agent harness, Work/Codex orchestration, memory and browser tooling around models. This means browser automation is valuable, but not as a workaround for an inherently weak API. A dedicated Black Cat browser worker using Playwright/Chromium is a cleaner abstraction than scripting `chatgpt.com` itself.

### Why it matters
Coupling Black Cat to one consumer website UI would be brittle, harder to secure and would mix reasoning with browser execution. Separating those layers allows the same browser executor to work with OpenAI API models, future local models or other providers, while ChatGPT Work remains an explicit optional handoff when its product-level harness is specifically useful.

### Decision / change
Playwright with Chromium is the preferred first Black Cat-owned browser worker. The design must run the worker unprivileged, isolate sessions, protect cookies/passwords from model prompts, audit consequential actions and require confirmation for irreversible operations. Direct API reasoning, Black Cat browser execution, OpenAI computer-use and ChatGPT Work are treated as distinct routes chosen per task.

### Evidence classification
**VERIFIED AGAINST CURRENT PUBLIC DOCUMENTATION / ARCHITECTURE ACCEPTED / NOT YET IMPLEMENTED ON HOST.**

---

## 2026-09-16 — Console deployment is blocked by host execution access, not by source readiness

### What was attempted
Deploy the Black Cat Console onto the existing `blackcat-chatbot-01` droplet without creating another server.

### What happened
The available DigitalOcean integration can inspect and manage droplet lifecycle resources but exposes no remote shell/exec function. No established SSH credential mapping for `blackcat-chatbot-01` was available through the current project records. External checks also found no currently reachable HTTP/HTTPS service on the droplet. Safe local-bootstrap and DNS-gated HTTPS publish scripts were added to the repository, but they have not been executed on the host.

### Why it matters
Source-code readiness is not evidence of deployment. Resetting, rebuilding or replacing the host merely to bypass an access-control gap would risk existing state and violate the project's evidence-first approach.

### Decision / change
Do not rebuild or reset `blackcat-chatbot-01` simply to force deployment. Establish a documented administrative shell path first, run the local-only bootstrap and validation, then expose HTTPS only after DNS and authentication checks pass.

### Evidence classification
**OBSERVED ACCESS LIMITATION / DEPLOYMENT NOT YET VERIFIED.**

---

## 2026-09-16 — AI spend should be visible without turning estimates into false billing evidence

### What was attempted
Make Black Cat suitable for substantially higher AI usage while keeping variable API cost understandable on a phone.

### What happened
The Console now records successful OpenAI calls in a private append-only `api-usage.jsonl` file with project, task type, returned model, input/cached/output/reasoning/total token usage, response ID, pricing-version metadata and an estimated USD cost. The mobile dashboard displays today/month totals, monthly token use, a configurable monthly planning budget, model totals, project totals, recent calls and per-call spend. Pricing is kept separate from raw usage so past calls can be recalculated when provider prices change.

### Why it matters
Per-request API pricing can become material as usage scales. Hiding spend encourages inefficient routing, while hard-coding a dollar figure into history would make old records misleading after price changes. Keeping raw provider usage as evidence and cost as a recalculable estimate supports later model routing and hard budget controls.

### Decision / change
Use `BLACKCAT_MONTHLY_API_BUDGET` as a soft planning budget in the MVP. Prefer the lowest-cost route that can reliably complete a task, and preserve raw token evidence independently of the current price table. Add hard refusal/downgrade thresholds only after real usage patterns are observed.

### Evidence classification
**SOURCE IMPLEMENTED / STATICALLY VERIFIED.** GitHub Actions run `35048351446`, job `104643052976`, passed the repository and Black Cat Console static checks against commit `164fd8a53f213bf6976ec3c781f5ae440df5c778`. The runner did not have PHP installed, so PHP syntax was not independently checked by that run; live API accounting remains NOT YET DEPLOYMENT-VERIFIED.

---

## 2026-09-16 — The current builder has a renewed preflight blocker: systemd-boot integration state

### What was attempted
Use the normal self-hosted builder workflow to validate the updated repository and Console checks.

### What happened
The workflow itself completed successfully, and both static-check scripts passed. However, the first real-builder report's host preflight returned `HOST_CHECK_EXIT=1` because the disposable builder currently retains systemd-boot host integration state `config-files`. The preflight explicitly requires that state to be purged before live-build while allowing `systemd-boot-efi` to remain installed.

### Why it matters
A green GitHub workflow does not mean every embedded engineering gate passed. The evidence collector intentionally records preflight failure without failing the whole reporting job. Treating workflow success as build readiness would therefore be incorrect.

### Decision / change
M13 remains current but blocked on cleaning the unexpected systemd-boot host integration state and rerunning preflight before the first live-build. This cleanup is separate from the Black Cat Console cost-tracking work and should be handled as an explicit builder-maintenance step.

### Evidence classification
**OBSERVED / VERIFIED ON `ptl-builder-01`.** GitHub Actions run `35048351446`, job `104643052976`, recorded the systemd-boot preflight failure on Debian 13.7.

---

## 2026-09-16 — The first useful project automation should reduce scope, not increase it

### What was attempted
Turn the broad Black Cat Console concept into something immediately useful for tracking real projects without creating another autonomous layer that can generate more work than it finishes.

### What happened
A deliberately narrow `focus.php` tracker was added. Each project has one current milestone, one next action and one blocker. Completing work is recorded in project state and activity history. New ideas are written to `BACKLOG.md` but cannot change active focus. Only an explicit focus edit can change the milestone or next action. Existing API-spend data remains visible without requiring any new AI calls.

### Why it matters
The user's main risk is not lack of ideas; it is allowing useful new ideas to interrupt unfinished work. Automation that freely promotes backlog items or rewrites plans would amplify project creep. A constrained state machine creates compounding project memory while keeping human control over priority changes.

### Decision / change
Treat focus discipline as the first project-automation primitive. AI advice may be added later, but plan changes remain user-approved. Browser automation, advanced routing and autonomous implementation stay outside this tracker milestone until the basic focus workflow is used and validated.

### Evidence classification
**SOURCE IMPLEMENTED / LOCALLY PHP-LINTED / NOT YET DEPLOYMENT-VERIFIED.** `apps/blackcat-console/focus.php` passed PHP 8.4 syntax checking in the development runtime before being committed. Repository static checks were expanded to cover the focus-lock, backlog, milestone, blocker and done-state guard requirements; those updated checks have not yet been rerun on `ptl-builder-01` in this session.



---

# Preserved original graphical prototype lesson

The following original entry is appended losslessly. Its pending states describe the earlier session; the dated evidence review below supersedes them.

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



## 2026-09-16 — Evidence review, recovered graphical ISO and design integration

### Attempt and observed result
Reviewed diverged main/USB records, recovered the existing 770,703,360-byte graphical ISO and independently reran its digest on the same builder. Original build run 35095923202 succeeded but artifact publication failed on storage quota. Identity run 35107102698 matched SHA-256 81f702cd9e59d4d7ba5654e6a69dbd655617968f92381049881930aef9b2fcb7.

The builder lacked QEMU. Authenticated Debian packages were downloaded and extracted into a temporary user-owned directory without installing system packages, running maintainer scripts or acquiring new privilege. Two harness attempts failed before guest boot because the task-local VGA ROM path was unresolved; run 35107884622 then observed the actual Xfce/Black Cat panel and, in a separate UEFI boot, the Quick Check report. No guest NIC or host disk was attached. The screenshots show missing icon glyphs and a Plymouth wait still running on the diagnostic capture. GUI interaction and the settled photo-screen remain untested; a successful marker test must not hide those gaps.

Integrated main's Console/Focus additions and both branches' appended documentation into the review branch, retaining both Git parents. Fixed static-check false positives: a comment naming the collector was mistaken for execution, and the manifesto wording differed from a hardcoded phrase. Production runtime/policy was not changed to satisfy these checks.

The user-selected design folder was found with 18 files and moved intact under linux usb project. Repository design references preserve file identities/hashes, exact original cat bytes and brand notes. The welcome concept's older “Freedom” text differs from the authoritative “Independence” tagline; originals remain unchanged.

### Why it matters and decision
Separate design, implementation, image construction, publication, VM observation, physical evidence and release authenticity. Replace the misleading aggregate progress number with scoped capabilities. Never transfer old-image physical observations to a new image or call a screenshot an interaction test. All future visual work uses design/README.md; the existing candidate remains byte-for-byte unchanged for reproducible diagnosis.

### Evidence classification
BUILD AND SAME-BUILDER IDENTITY OBSERVED / GUI PANEL AND QUICK-CHECK REPORT OBSERVED IN VM / INTERACTION, SETTLED DIAGNOSTIC SCREEN AND PHYSICAL RETEST PENDING / NO APPROVED RELEASE. See docs/20-graphical-candidate-evidence.md and evidence/2026-09-16-graphical/report.json.


### Source validation follow-through

Run 35155024291 passed at a1a9afc3d2fba6d5c81ace22019493f4a951ec32 after the false-positive fixes and restoration of the shared signing validator's executable bit. Positive and negative Ed25519 development tests, static guards, PHP 8.4.24 lint and viewer fixtures all passed. The tracked executable-bit defect was a real tooling failure distinct from the two false positives; no production signing key was created. The handoff records the still-pending VM interactions, diagnostic settling, physical test and deployment boundaries.

## 2026-09-17 — Exact artwork candidate completed its bounded VM gate

### What was attempted

Integrate the canonical Black Cat artwork into the graphical live image, keep every visible control usable, and exercise the exact resulting ISO through both graphical interaction and System Check paths.

### What happened

Seven of the 18 canonical design files were selected for the build: one wallpaper and six category icons. Checksummed repository copies prevented silent asset substitution. The first artwork build exposed two real integration defects: the fixed-height panel clipped lower controls, and static Xfce wallpaper configuration did not reliably target the live session's actual property. Increasing the panel height and applying the wallpaper through a session-start wrapper resolved both. The System Check path also exposed an unbounded Plymouth wait; a command-line-conditioned service override removed that wait only for `blackcat.check=1`.

The corrected ISO, SHA-256 `f498776780c543013e437161a878e22ca4fc40a66f47fc271d6fd6a964c29d75`, was built in run `35179377345`. Run `35180150392` observed Browser selection, CLEAR, OPEN DESKTOP, a stable wallpaper across two captures, and the settled System Check photo prompt. Secure Boot remained `UNKNOWN`, TPM and TCG log were `NOT_EXPOSED`, and dm-verity was `NOT_ACTIVE` in this VM.

### Lesson

Visual source inclusion is not proof of usable integration: exact-image interaction and post-login state must be observed. Runtime desktop configuration can be more reliable than assuming a static channel/property path. Boot-time waits need a bounded diagnostic-mode design. Asset accounting must distinguish “available in the design folder” from “selected for this build,” and VM success must remain separate from physical and release approval.

## 2026-09-17 — Current-candidate display must follow the active physical-test identity

### What happened

The project viewer still rendered the preserved 376,438,784-byte M13B record under “Current Test Candidate” even though that object was marked superseded and the newer artwork-enabled ISO had completed its VM gate. The detailed evidence and handoff were correct, but the top-level viewer feed remained stale.

### Lesson

A superseded candidate belongs in an explicit historical-candidate record, not the active viewer slot. Before authorizing removable-media work, compare filename, byte size, SHA-256, source commit and build run against the authoritative evidence report; a “ready” label alone is insufficient.

## 2026-09-17 — A live GitHub fetch can still be authoritatively stale

### What happened

The production page returned uncached data and truthfully displayed its configured source, but the deployed PHP was pinned to `codex/remove-chroot-systemd-boot`. Its “LIVE / SYNCHRONISED” label therefore meant live access to an obsolete branch, not synchronisation with the active candidate. The assistant then supplied the newer artifact link in chat, which undermined the page's intended role as the single safe handoff.

### Decision and lesson

Candidate publication is now a priority fail-closed rule. The viewer targets a stable `project-status-live` source. Before any download or physical-test request, the live HTTPS page itself must match the evidence record across exact bytes, hash, source, run, artifact and URL. A chat link is not an acceptable bypass. Deployment capability and physical-write approval remain separate boundaries.

## 2026-09-17 — Preserve the JSON publication contract

The user correctly identified that routine ISO updates should not require PHP replacement. The assistant had updated a different branch and proposed migrating the viewer rather than maintaining its existing JSON endpoint. Inspection also showed candidate.status must be "test"; the newer label was incompatible. The new plan supersedes the migration proposal, requires narrow compatible JSON publication and live verification, and binds successor models to human-approved deviations. Author: Codex (GPT-5-based); exact model version unavailable. Plan recorded, not implemented.

## 2026-09-17 — Model hierarchy rule covers all plans, ideas and suggestions

The operator clarified that the human-signoff restriction is project-wide: any higher-model plan, idea or suggestion cannot be changed by a lower model without explicit human sign-off. The prior wording over-focused on one publication plan. Root policy and recovery state now state the broader scope, provenance requirements, unknown-rank handling and prohibition on self-approved exceptions. No live publication or ISO change was made in this policy-only session.

## 2026-09-17 — Prepublication compatibility must test more than HTTPS syntax

The current viewer accepts any syntactically valid HTTPS candidate URL once other fields pass. The binding recovery plan therefore adds a stricter prepublication check that binds the run, artifact ID/name and URL to the evidence report. Fixtures also prove missing hashes, wrong status and incomplete identity disable the viewer handoff. This is preparation evidence; the live JSON had not yet been changed at the time of this lesson.
