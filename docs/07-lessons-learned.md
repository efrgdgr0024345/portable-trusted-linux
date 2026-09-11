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

## 2026-09-11 — A 30% volunteer-compute idea is large enough to justify a dedicated research track

### What was attempted
Estimate how much consumer compute could exist if roughly 30% of the addressable global PC and smartphone base opted into a shared LLM compute commons.

### What happened
Public estimates support an order-of-magnitude pool of roughly 2 billion PCs and at least 4.4 billion own-smartphone mobile-internet users. A 30% device-level adoption thought experiment therefore produces about 600 million PCs and 1.32 billion smartphones, or roughly 1.92 billion enrolled devices before capability filtering.

### Why it matters
Even low simultaneous availability can produce physically significant aggregate electrical scale. This is not enough to claim equivalent hyperscale AI throughput, but it is large enough that the idea should be evaluated experimentally rather than dismissed as marginal.

### Decision / change
Create a separate Community Compute Commons (CCC) research/architecture track and measure useful completed AI work, not merely theoretical device count or TOPS.

### Evidence classification
**RESEARCH ESTIMATE.** Device-count inputs are sourced; 30% adoption is a hypothetical scenario, not a forecast.

---

## 2026-09-11 — Enrolment and simultaneous availability are different quantities

### What was attempted
Translate billions of enrolled devices into a usable compute-capacity estimate.

### What happened
Consumer workers are intermittent: they may be powered off, in use, on battery, thermally limited, on metered networks or disconnected. A reference power model gives approximately 33 GW only if the entire 30%-adoption pool computes simultaneously. At 10% simultaneous availability the same model is around 3.3 GW; at 25%, around 8.3 GW.

### Why it matters
Quoting the full enrolled fleet as permanently available would overstate capacity by potentially an order of magnitude or more.

### Decision / change
All future CCC capacity reporting must show at least enrolled devices, active workers, accepted useful work, availability distribution and power/energy separately.

### Evidence classification
**ARCHITECTURAL REQUIREMENT / SCENARIO MODEL.** Concurrency assumptions must be replaced with pilot measurements.

---

## 2026-09-11 — Electrical scale is not equivalent to LLM compute scale

### What was attempted
Compare volunteer-device capacity with multi-gigawatt AI data centres.

### What happened
The volunteer network can reach comparable power-scale numbers under some concurrency scenarios, but hyperscale AI centres concentrate modern accelerators, high-bandwidth memory and specialised low-latency interconnect. Consumer hardware is heterogeneous and geographically separated.

### Why it matters
A statement such as '8 GW of volunteered devices equals an 8 GW AI data centre' would be technically misleading.

### Decision / change
CCC will avoid an 'equivalent data centre' compute claim until real model-specific throughput, energy and network measurements exist. Comparisons may use electrical scale only when clearly labelled as such.

### Evidence classification
**HIGH-CONFIDENCE ARCHITECTURAL CONSTRAINT.**

---

## 2026-09-11 — The pre-OS screen should collect consent; it should not execute the LLM workload

### What was attempted
Place the Community Compute feature at the pre-OS loading page.

### What happened
The pre-OS location is valuable for explicit mode selection, but actual LLM work requires accelerator drivers, networking, model runtimes and sandboxing. Putting those into firmware/pre-OS code would dramatically expand the trusted computing base.

### Why it matters
The original project exists to minimise and verify the boot/runtime trust chain. Mixing a remote-work execution stack into that same early environment would work against the core security goal.

### Decision / change
The pre-OS menu offers Secure Inspection Mode and Community Compute Commons Mode. CCC then boots a separate signed minimal Linux worker environment before enabling the network and accepting jobs.

### Evidence classification
**ARCHITECTURE DECISION.** Implementation remains future work.

---

## 2026-09-11 — Secure Inspection Mode and Community Compute Mode must be separate boot profiles

### What was attempted
Determine whether donated compute could run alongside the normal trusted user/inspection session.

### What happened
CCC intentionally enables external networking, remote job scheduling and large accelerator runtimes, all of which increase attack surface.

### Why it matters
A compromise of the volunteer worker should not contaminate the trust assumptions of the high-assurance inspection environment.

### Decision / change
Use separate signed boot profiles/UKIs and security policies. Transition from CCC to Secure Inspection Mode requires a complete reboot.

### Evidence classification
**SECURITY ARCHITECTURE DECISION.**

---

## 2026-09-11 — High-throughput independent AI jobs are a better first target than frontier synchronous training

### What was attempted
Assess whether billions of household devices could act like one giant AI training supercomputer.

### What happened
BOINC shows that heterogeneous volunteer systems excel at high-throughput work. Petals demonstrates that large-model inference can be distributed over consumer-grade networks, and DiLoCo/Decoupled DiLoCo demonstrate substantial reductions in training communication. But tightly coupled frontier training still depends heavily on fast predictable interconnect and accelerator memory bandwidth.

### Why it matters
Trying to imitate a hyperscale training fabric across random phones and PCs would waste much of the theoretical compute on communication, stragglers and churn.

### Decision / change
Initial CCC workloads should prioritize independent inference, embeddings, reranking, evaluation, synthetic-data generation and other batchable jobs. Distributed training remains a research track for stronger worker clusters.

### Evidence classification
**RESEARCH-SUPPORTED DESIGN DECISION.**

---

## 2026-09-11 — 'Free compute' can unfairly move costs onto volunteers

### What was attempted
Evaluate the social goal of using donated compute to reduce the rich/poor AI compute divide.

### What happened
The central infrastructure cost may be avoided, but volunteers still pay electricity, data charges and hardware/battery wear. A simple 10%-concurrency scenario is tens of TWh/year globally.

### Why it matters
A system intended to reduce inequality could accidentally ask lower-income people to subsidise other users' electricity and hardware costs.

### Decision / change
CCC must be explicitly opt-in, expose power/data controls, prefer AC/unmetered operation, account for useful verified work, and support donated/community credits without making cryptocurrency a requirement. Social recipients should not necessarily have to donate hardware themselves.

### Evidence classification
**SOCIAL/ENGINEERING CONSTRAINT.** Exact energy economics require pilot measurements.

---

## 2026-09-11 — Volunteer nodes and private prompts must be treated as mutually distrustful by default

### What was attempted
Consider arbitrary user inference on anonymous volunteer devices.

### What happened
TLS protects network transport but does not prevent a worker executing a model from seeing the data needed for the computation. Likewise, the coordinator cannot assume a volunteer worker returns honest results.

### Why it matters
A public LLM commons creates two trust problems at once: protecting volunteers from jobs and protecting jobs/users from volunteers.

### Decision / change
The first CCC version uses public/non-sensitive workloads and open-weight models. It requires signed job manifests, sandboxing and result-verification/redundancy. Confidential inference is a separate future research problem.

### Evidence classification
**SECURITY ARCHITECTURE DECISION.**
