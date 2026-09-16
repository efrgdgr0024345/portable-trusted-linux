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
