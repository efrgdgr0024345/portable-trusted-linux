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

## ADR-029 — Black Cat is the user-facing brand; PTL identifiers remain stable

**Status:** Accepted  
**Decision:** The project/product is presented to users as **Black Cat**. Existing repository names, `PTL_` environment variables, workflow names and evidence identifiers remain unchanged unless there is a later engineering reason to migrate them. Branding must not silently alter protocol meaning, trust claims or evidence semantics.

## ADR-030 — Real-hardware diagnostics must support a no-typing boot path

**Status:** Accepted  
**Decision:** Real-hardware testing should not depend on operators transcribing long shell commands. The live image provides a dedicated **Black Cat - System Check** boot-menu entry that passes a simple kernel flag and automatically launches a modular diagnostic. Its initial output is classified as OBSERVED unless independently verified; it must not collapse observations into a universal safety claim.

## ADR-031 — Test-candidate distribution is separate from approved-release distribution

**Status:** Accepted / Transitional  
**Decision:** The living project viewer may expose one explicitly frozen **Current Test Candidate** from the private GitHub repository so the operator can retrieve the exact ISO selected for physical testing. A candidate link must identify the workflow run/artifact, ISO filename, exact byte size, ISO SHA-256, source commit and automated smoke-test state, and it must be labelled **TEST CANDIDATE — NOT AN APPROVED RELEASE**. The separate **Approved Black Cat ISO** gate remains disabled until explicit release promotion requirements are met. GitHub Actions artifact links are temporary development distribution and may require the repository owner to be logged in; a later approved release uses the release channel and future signing controls rather than treating a CI artifact as release authenticity.

## ADR-032 — Highest-security automation uses an event-driven deterministic controller

**Status:** Superseded by ADR-035 before deployment  
**Decision:** This proposal selected a cPanel-hosted event controller driven by authenticated GitHub webhooks, with deterministic state transitions and a reconciliation watchdog. It was intentionally stopped before deployment after review concluded that a new public listener was not justified merely to reduce operator polling. No webhook/controller service was deployed.

## ADR-033 — AI is a bounded reasoning worker, not automation authority

**Status:** Accepted as a future constraint; no API integration currently approved  
**Decision:** If an API-based reasoning worker is ever introduced after separate security review, the model is not the scheduler, release authority, trust authority or holder of unrestricted infrastructure credentials. It may never write directly to `main`, promote a release, perform physical/destructive operations, change firmware/TPM ownership, or receive raw secret values. No OpenAI API credential is part of the current automation baseline.

## ADR-034 — Controller webhook authentication and replay controls fail closed

**Status:** Superseded by ADR-035 before deployment  
**Decision:** These controls remain technically valid requirements if a webhook design is reconsidered, but there is no current webhook endpoint. Avoiding the listener entirely is preferred while existing GitHub Actions and outbound-only mechanisms are sufficient.

## ADR-035 — Automation must not add externally reachable attack surface for convenience

**Status:** Accepted  
**Decision:** Black Cat must not create a new inbound-facing service merely to reduce human friction. Existing GitHub Actions remain the default automation infrastructure. If unattended reconciliation becomes necessary, prefer an outbound-only scheduled watchdog outside `public_html` that queries exact GitHub commit/run/artifact state. Any future public listener, writable endpoint, OpenAI API integration, new credential, or privileged service requires a separate security justification showing that its benefit outweighs the attack surface it introduces. Browser automation of an authenticated consumer ChatGPT session is rejected.

The existing deterministic `controller/state-machine.json` may remain as a non-networked conceptual state model; retaining state vocabulary does not authorize deployment of a controller service.

## ADR-036 — Living viewer is canonical, fail-closed and candidate-preserving

**Status:** Accepted  
**Decision:** The production `project.php` must be deployed from the canonical repository copy, use only `/home/learning/.blackcat/github_token`, never silently fall back to an offline authoritative cache, and render the Current Test Candidate from `project_content.json` only when all exact identity fields pass validation. While an unmerged development branch contains the authoritative candidate state, the viewer may temporarily read that explicit branch; after merge it returns to `main`. Static checks guard the credential model, fail-closed behavior and candidate panel so routine viewer changes cannot silently remove the ISO download path.

## ADR-037 — Root AI autonomy policy is the operational continue/stop authority

**Status:** Accepted  
**Decision:** `AI_AUTONOMY_POLICY.md` at the repository root is the canonical operational decision table used by AI-assisted project work to classify actions as `AUTO`, `AUTO_WITH_CHECKS`, `HUMAN_GATE`, or `FORBIDDEN_BY_DEFAULT`. `PROJECT_RULES.md` remains the master policy and `docs/11-automation-manifesto.md` remains the explanatory rationale. Routine branch-local engineering, safe CI inspection/retry, bounded defect fixes, documentation and candidate preparation proceed automatically. Merging to `main`, approved-release promotion, physical/destructive actions, trust-ownership changes, new credentials/permissions, new externally reachable services, financial/legal/public commitments and material security-policy choices remain human gates unless a later explicit repository decision delegates them. A missing tool is recorded as a capability boundary rather than misrepresented as a need for policy approval.

## ADR-038 — AI continuity uses a compact repository checkpoint, selective reload and empirical context health

**Status:** Accepted  
**Decision:** Chat history is not the durable state authority for long-running AI-assisted engineering. `AI_WORK_STATE.md` at the repository root is the compact first-read checkpoint for fresh sessions and continuity recovery. Ordinary continuing turns do not bulk-reload the repository. Recovery reads the compact checkpoint first, verifies only volatile branch/CI/artifact facts needed for the next action, and then loads technical/policy/history documents selectively. Context pressure is represented only as a GREEN/AMBER/RED heuristic based on observable proxies; no exact hidden context-window percentage may be fabricated. AMBER/RED checkpoint maintenance is automatic work rather than a human gate. Real dropout/continuity events and heuristic changes are preserved in `docs/14-context-health-calibration-log.md`, while the reusable implementation pattern is documented in `docs/13-ai-continuity-and-context-recovery.md` so other projects can reproduce the approach.

## ADR-039 — Black Cat will later expose repository-governed AI project automation as a user-space subsystem

**Status:** Accepted / Future after core prototype  
**Decision:** After the first usable core Black Cat prototype is complete, the project may implement the autonomy/continuity workflow as a native user-space project subsystem. The planned subsystem uses repository policy and compact work-state files as durable state, a model-neutral AI worker for bounded reasoning, and an operating-system capability broker that independently enforces filesystem/Git/test/network/privilege boundaries. The AI process does not receive unrestricted root authority. Local-first deterministic operation and local models are preferred where practical; remote providers remain optional capabilities. Background continuation uses user-level services/timers or outbound-only checks rather than a new public listener. Human gates remain explicit for destructive/physical operations, new credentials/permissions, trust ownership, protected merges/releases and material policy changes. The implementation plan is documented in `docs/15-black-cat-ai-project-automation.md` and is scheduled after M25 so it cannot distract from completing the current secure-boot/TPM/dm-verity/verifier roadmap.

## ADR-040 — The future AI project subsystem has a portable template and fail-closed machine policy contract

**Status:** Accepted / Design scaffold implemented; runtime enforcement future  
**Decision:** The repository now carries a provider-neutral project seed under `templates/ai-project/` plus `schema/ai-project-policy.schema.json`. The template separates human-readable master/autonomy rules from a machine-readable `project-policy.json` intended for future capability-broker validation. The sample machine policy defaults to `HUMAN_GATE`, network disabled, no inbound listeners, no root, no raw block devices and no credentials. Project/untrusted content cannot grant new authority, and audit state records concise actions/evidence rather than private model chain-of-thought. These files are design scaffolding only: their presence grants no new runtime privilege in the current prototype. M26 implementation must make the operating-system broker validate policy independently of whichever model is connected and must fail closed on policy/schema mismatch.

## ADR-041 — M14 release authenticity uses canonical Ed25519-signed metadata and separate key authority

**Status:** Accepted / Non-secret implementation underway  
**Decision:** Black Cat release authenticity is established by signing exact canonical release-manifest bytes with an Ed25519 release-signing identity that is distinct from the later Secure Boot/UKI signing identity. The canonical manifest binds the release ID, UTC creation time, exact artifact filename, byte size and SHA-256, source commit/ref, build-run identity, signing algorithm and SHA-256 fingerprint of the trusted DER-encoded release public key. Verification fails closed on non-canonical manifest bytes, key-fingerprint mismatch, invalid signature, artifact filename/size/hash mismatch, manifest tampering or wrong-key use. Development CI may generate ephemeral Ed25519 keys solely to test the mechanics; those keys are never release trust roots. The production release private-key ownership/custody model remains a HUMAN_GATE and must not be silently delegated to the general-purpose builder or GitHub repository for convenience. The implementation and trust boundary are documented in `docs/16-authenticated-release-signing.md`.

## ADR-042 — Non-essential software is modular and AI builds occur outside the trusted core

**Status:** Accepted / Future after core prototype  
**Decision:** Black Cat should keep the trusted core deliberately small and immutable. Non-essential applications are to be delivered as pre-built, ready-to-run modules stored outside the core and activated only when selected for the current session. Unselected modules should not be exposed in the normal session namespace where practical. Module selection is coupled to explicit least-privilege capability grants enforced by the operating system rather than by the application or model.

Natural-language tool creation may be offered through a Black Cat AI interface, but heavy/generated software construction should preferably occur in a separate disposable and explicitly untrusted workshop environment such as a VPS/VM. The workshop may design, compile and test software but does not receive trusted-core write authority, raw target-disk authority, user secrets by default, or Black Cat production release/Secure Boot signing keys. Anything returned by the workshop is treated as potentially malicious and must pass independent structural/digest/signature/capability verification before activation. Official, local-user and unverified-candidate modules remain distinct trust classes.

A pre-desktop AI interface may run only after a minimal authenticated Black Cat environment and its capability broker are active; this does not authorize firmware/pre-kernel LLM execution. The detailed design and mandatory negative-test direction are documented in `docs/18-modular-runtime-and-ai-workshop.md`.