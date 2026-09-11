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