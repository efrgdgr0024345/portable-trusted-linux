# Architecture Decision Register

This file records durable project decisions. Changes should be made by new commits and, where a decision is superseded, the old decision should remain visible with a superseded note rather than being silently erased.

## ADR-001 — Reuse mature Linux security components

**Status:** Accepted  
**Decision:** Do not build a custom kernel, bootloader, TPM stack or cryptographic primitive for the first prototype. Use mature upstream components and focus original engineering on orchestration, evidence, verification and reporting.

## ADR-002 — Debian Live as prototype base

**Status:** Accepted  
**Decision:** Use Debian Live for the initial prototype, subject to compatibility testing and reproducibility constraints documented in the build specification.

## ADR-003 — Secure Boot and Measured Boot are separate controls

**Status:** Accepted  
**Decision:** Do not use these terms interchangeably. Secure Boot authorises execution according to signing policy; Measured Boot records evidence about what was measured during startup.

## ADR-004 — Use signed UKI where practical

**Status:** Accepted  
**Decision:** Package important boot components into a signed Unified Kernel Image to simplify authenticated and measured boot relationships.

## ADR-005 — dm-verity for immutable root integrity

**Status:** Accepted  
**Decision:** The trusted Linux root should be read-only and cryptographically protected by dm-verity. Temporary writes belong in a separate ephemeral layer.

## ADR-006 — Offline-first startup

**Status:** Accepted  
**Decision:** Initial verification should not depend on Internet access. Networking remains disabled/restricted until the local trust assessment is complete or the user explicitly enables it.

## ADR-007 — Independent verifier

**Status:** Accepted  
**Decision:** The target system cannot be the sole authority deciding that its own evidence is acceptable. A separate verifier validates TPM Quotes, report signatures, reference policy and evidence consistency.

## ADR-008 — Multi-PC feature is independent corroboration

**Status:** Accepted  
**Decision:** Multiple hosts may independently evaluate portable-image identity and produce host-specific evidence. Agreement is corroboration, not mathematical proof that all participating PCs are trustworthy.

## ADR-009 — Unknown is a valid result

**Status:** Accepted  
**Decision:** Unsupported or unverifiable components must be labelled UNKNOWN or NOT VERIFIABLE rather than being implicitly treated as safe.

## ADR-010 — External Evidence Anchoring

**Status:** Accepted  
**Decision:** Canonical attestation reports will support hashing, signatures, previous-report linkage and external commitment publication.

## ADR-011 — Transparency log preferred; blockchain optional

**Status:** Accepted  
**Decision:** The core protocol will use a backend-neutral External Evidence Anchoring abstraction. A purpose-built cryptographic transparency log is the preferred first backend. A public blockchain may be supported as an optional secondary anchor.

## ADR-012 — Do not publish full private reports to immutable public ledgers

**Status:** Accepted  
**Decision:** Publish only a cryptographic commitment unless a future explicit privacy review approves otherwise.

## ADR-013 — GitHub is the canonical project-development record

**Status:** Accepted  
**Decision:** Human-readable documents, source files and the living `project_content.json` index are versioned in this private GitHub repository. Git history is the project-change record during development.

## ADR-014 — Lessons learned are mandatory project output

**Status:** Accepted  
**Decision:** Every meaningful engineering session, test, research result, failure, architecture change or implementation surprise must be recorded in `docs/07-lessons-learned.md` before the session is considered complete. Relevant technical documents and decisions must also be updated, with `project_content.json` written last.

## ADR-015 — Prototype 1 baseline is Debian 13 `trixie` on amd64

**Status:** Accepted  
**Decision:** The first working prototype targets Debian 13 `trixie` and x86-64/amd64. Build manifests must capture the exact Debian point release and package versions used so the moving meaning of labels such as `stable` cannot silently change the provenance of a build.

## ADR-016 — Attestation report uses payload plus envelope

**Status:** Accepted  
**Decision:** Do not create a self-referential report hash. A deterministic evidence `payload` is canonicalised and hashed. An outer `envelope` contains the payload, payload digest, signatures and external-anchor receipts. Exact canonicalisation must be frozen and tested before cross-implementation signatures or history chains are considered stable.

## ADR-017 — Prototype 1 stops before public anchoring and polished UI

**Status:** Accepted  
**Decision:** Prototype 1 succeeds when the local trust chain through independent attestation passes its defined positive and negative gates. Transparency-log/blockchain publication, broad multi-host automation and polished UI follow only after that core path is demonstrated.

## ADR-018 — Build provenance is recorded separately from release authenticity

**Status:** Accepted  
**Decision:** Each produced image gets a build manifest containing its SHA-256 digest, size, source Git commit, build host and selected package versions. This manifest is evidence about how the artifact was produced; it is not itself proof of release authenticity. A separate signing/verification gate will authenticate approved releases.

## ADR-019 — No destructive USB writer before device-safety tests

**Status:** Accepted  
**Decision:** Prototype build tooling will not write raw images to a block device until explicit device-selection safety checks and negative tests exist. Ambiguous, internal, mounted or otherwise unsafe targets must be refused by default.

## ADR-020 — Community Compute Commons is a separate future subsystem

**Status:** Accepted  
**Decision:** The volunteer LLM compute concept is named **Community Compute Commons (CCC)** and is maintained as a separate research and future implementation track. It must not weaken or delay the core Portable Trusted Linux Prototype 1 security acceptance path.

## ADR-021 — Pre-OS is for consent and mode selection, not LLM execution

**Status:** Accepted  
**Decision:** The signed pre-OS/boot menu may offer Community Compute Commons as an explicit opt-in choice. Actual compute runs only after a separate signed minimal Linux worker environment has booted. GPU/NPU drivers, networking, model runtimes and remote scheduling are not added to the minimal firmware/pre-OS trusted computing base.

## ADR-022 — Secure Inspection Mode and CCC require separate boot profiles

**Status:** Accepted  
**Decision:** Secure Inspection Mode and Community Compute Commons use separate signed UKIs/root policies. Because CCC enables networking and externally scheduled workloads, a running CCC environment may not transition into Secure Inspection Mode without a complete reboot into the independently authenticated inspection image.

## ADR-023 — CCC initially targets high-throughput independent AI workloads

**Status:** Accepted  
**Decision:** The first CCC workloads should be jobs that tolerate heterogeneous, intermittent and high-latency workers: independent inference, embeddings, reranking, evaluation, synthetic-data generation and similar batch tasks. Internet-wide tightly synchronous frontier-model pretraining is not an initial target.

## ADR-024 — Public/non-sensitive workloads and open-weight models first

**Status:** Accepted  
**Decision:** Arbitrary private prompts and confidential/proprietary model weights are not sent to untrusted volunteer nodes in the initial CCC design. The first system uses public or explicitly non-sensitive workloads, approved signed job manifests, sandboxed runtimes and open-weight models. Confidential volunteer inference requires a separately reviewed design.

## ADR-025 — Volunteer costs and social fairness must be explicit

**Status:** Accepted  
**Decision:** CCC must not describe volunteered compute as literally cost-free. Electricity, data, battery cycles, thermal load and hardware wear belong to donors. The system therefore requires explicit opt-in, power/thermal/bandwidth controls, AC/unmetered defaults where practical, transparent accounting, and contribution credits based on accepted useful work rather than claimed uptime. Cryptocurrency is not required for accounting.
