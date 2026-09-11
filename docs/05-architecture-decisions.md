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

**Status:** Accepted / Paused  
**Decision:** The volunteer LLM compute concept is named Community Compute Commons (CCC). Its research documents are retained, but implementation is paused until the core Portable Trusted Linux Prototype 1 trust-chain acceptance gates pass.

## ADR-021 — Pre-OS is for consent and mode selection, not LLM execution

**Status:** Accepted / Future  
**Decision:** If CCC is resumed, the pre-OS/boot menu may offer it as an explicit opt-in choice. Actual compute runs only after a separate signed minimal Linux worker environment has booted.

## ADR-022 — Secure Inspection Mode and CCC require separate boot profiles

**Status:** Accepted / Future  
**Decision:** If CCC is implemented, it uses a separate signed UKI/root policy and may not transition into Secure Inspection Mode without a complete reboot.

## ADR-023 — CCC initially targets high-throughput independent AI workloads

**Status:** Accepted / Future  
**Decision:** If resumed, CCC begins with inference, embeddings, reranking, evaluation, synthetic-data generation and similar loosely coupled jobs rather than Internet-wide synchronous frontier training.

## ADR-024 — Public/non-sensitive workloads and open-weight models first

**Status:** Accepted / Future  
**Decision:** If resumed, arbitrary private prompts and confidential/proprietary model weights are not sent to untrusted volunteer nodes in the initial CCC design.

## ADR-025 — Volunteer costs and social fairness must be explicit

**Status:** Accepted / Future  
**Decision:** If resumed, CCC must explicitly account for volunteer electricity, data, battery cycles, thermal load and hardware wear.

## ADR-026 — Core Prototype 1 has exclusive active engineering priority

**Status:** Accepted  
**Decision:** From 2026-09-11, active implementation work focuses exclusively on the Portable Trusted Linux core trust chain until its initial acceptance gates have been exercised on real hardware/build infrastructure. CCC remains preserved as a paused research track and does not consume current implementation effort.

## ADR-027 — Remote cloud builder and attestation target are separate roles

**Status:** Accepted  
**Decision:** The DigitalOcean Debian 13 builder may be used for reproducible build, static-test and artifact-generation work through a repository-scoped self-hosted GitHub Actions runner. It is not an attestation target and must not be used to claim successful UEFI measured boot or TPM evidence when those interfaces are not exposed by the VM. Measured-boot, PCR, event-log and TPM Quote gates require a separate suitably capable platform.

## ADR-028 — Self-hosted runner is the initial remote execution control plane

**Status:** Accepted / Transitional  
**Decision:** During Prototype 1 engineering, GitHub Actions is the primary auditable command path into the disposable remote builder. The runner initially operates as the unprivileged `github-runner` account. Any later elevation needed for package installation or live-image build operations must be explicit, bounded and recorded under the progressive-hardening rule rather than silently granting permanent root-equivalent workflow access.
