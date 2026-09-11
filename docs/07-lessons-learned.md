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
