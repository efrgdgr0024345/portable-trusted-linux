# Portable Trusted Linux — Project Rules

These rules are mandatory for all future work in this repository.

## Rule 1 — Lessons learned must always be recorded

Every meaningful engineering session, test, research result, failure, security discovery, architecture change, or implementation surprise MUST be recorded in the project documentation before the work is considered complete.

At minimum:

1. Add the lesson to `docs/07-lessons-learned.md`.
2. Update any technical document affected by the lesson.
3. Update the architecture decision record if a design choice changed.
4. Update milestone status where appropriate.
5. Finish the session by updating `project_content.json` last.

A lesson must explain:

- what was attempted;
- what actually happened;
- why it matters;
- what decision or implementation change follows;
- whether the result is proven, observed evidence, an assumption, or still unknown.

## Rule 2 — `project_content.json` is always updated last

The living JSON file is the canonical project index for the public project viewer. All file creation and technical-document updates must happen first. `project_content.json` is then updated as the final repository write so it accurately reflects the completed state of the repository.

## Rule 3 — Never collapse uncertainty into a SAFE/UNSAFE claim

Project outputs must distinguish:

- VERIFIED;
- ATTESTED;
- OBSERVED;
- WARNING;
- UNKNOWN;
- NOT VERIFIABLE;
- FAILED.

A mechanism may only claim what its evidence supports.

## Rule 4 — Separate integrity, authenticity, measurement and trust

A SHA-256 match proves byte identity relative to the compared value; it does not establish who supplied that value. Digital signatures establish authenticity relative to a trusted public key. TPM measurements provide evidence of measured state. None of these alone proves that an arbitrary computer is free from malicious firmware or hardware.

## Rule 5 — The target system must not be its own ultimate verifier

Attestation evidence must be structured so it can be checked independently. A local program displaying `VERIFIED` is not sufficient evidence by itself.

## Rule 6 — Use mature primitives rather than inventing security technology

Prototype work should use established components such as Debian, UEFI Secure Boot, systemd UKIs, TPM 2.0 tooling and dm-verity. New cryptographic algorithms, bootloaders or TPM implementations are out of scope unless a future documented requirement justifies them.

## Rule 7 — Negative tests are mandatory

Every security control must have a test that intentionally breaks it. Examples include a modified root block, invalid signature, changed nonce, missing event log and unauthorised image. A feature is not considered verified merely because its happy path works.

## Rule 8 — Random-PC mode must avoid modifying platform ownership state

The project must not silently enroll Secure Boot keys, clear a TPM, change TPM ownership, alter firmware settings or write to internal disks on arbitrary target machines.

## Rule 9 — Offline-first

The initial verification process should not depend on network access. External evidence anchoring happens after the local evidence bundle has been generated and can be queued for later publication.

## Rule 10 — Preserve history

Git history, the human-readable changelog, architecture decision records, lessons learned and future evidence hash chains are all part of the project's auditability. Do not overwrite historical conclusions without recording why they changed.

## Rule 11 — Build weak-to-strong through explicit progressive hardening

Early development may deliberately use weaker or more convenient controls when that is necessary to make progress, provided the weakness is explicit, bounded and documented.

For every temporary weak control:

1. state the current trust assumption;
2. state what could compromise it;
3. state why it is acceptable for the current stage;
4. identify the stronger replacement or next hardening step;
5. never describe the temporary control as equivalent to the final security design.

Examples include temporarily storing an SSH private key on a trusted cPanel host before later moving to hardware-backed keys, short-lived credentials, dedicated build infrastructure or stronger machine identity.

The project should progress in demonstrable stages: first make the workflow function, then harden individual trust boundaries, then run negative tests against each hardened control.

## Rule 12 — Automation Manifesto and mandatory pre-pause check

`docs/11-automation-manifesto.md` is a mandatory operating rule for the project.

The default behavior is **proceed, not pause**. Safe, reversible, auditable, already-authorized project work should continue automatically through source inspection, repository changes, builds, tests, CI inspection, evidence review, safe defect fixes, regression tests, documentation and candidate preparation.

Before asking the operator to “proceed,” confirm an ordinary next step, or pausing merely because a workflow is running, the assistant MUST run the Automation Manifesto pre-pause check:

1. Can an already-authorized project tool or connected system perform the next step? If yes, do it.
2. Is the next step reversible and non-destructive? If yes, default to doing it.
3. Can missing information be retrieved from GitHub, repository files, CI, artifacts, logs, the builder or existing evidence? If yes, retrieve it instead of asking.
4. Is CI merely pending or running? If yes, continue independent safe work and inspect the workflow again when useful; do not ask permission merely to keep following the active task.
5. Did evidence expose a clear bounded source defect? If yes, fix it, add a regression test, document it, rebuild and continue through automated gates.
6. Does the next action require physical interaction, risk destructive/irreversible change, alter platform/trust ownership, require a new credential/authorization, expose a secret, create a financial/legal/public-release commitment, or require a material security-policy choice? If yes, stop at that human gate.
7. Is essential information genuinely unavailable and impossible to derive safely? Only then ask the operator.

If no genuine human-gate condition applies, the assistant MUST NOT pause solely to obtain routine permission.

When a pause is necessary, state the exact human gate reached and why automation cannot safely cross it.

This rule does not override platform authorization requirements, safety policy, explicit operator instructions or the rule that `project_content.json` is the final repository write of a work cycle.

## Rule 13 — Root AI autonomy decision table is mandatory

`AI_AUTONOMY_POLICY.md` is the canonical operational decision table for classifying project actions as `AUTO`, `AUTO_WITH_CHECKS`, `HUMAN_GATE`, or `FORBIDDEN_BY_DEFAULT`.

The file must remain at the repository root so the continue/stop boundary is immediately visible to humans and automation. `PROJECT_RULES.md` remains the master policy and `docs/11-automation-manifesto.md` remains the explanatory rationale; the root autonomy policy translates those principles into concrete action classes and project examples.

Before asking for routine permission, the assistant MUST consult that decision table. If an action is `AUTO` or `AUTO_WITH_CHECKS` and the required checks pass, continue automatically. If the current tool environment cannot perform an otherwise authorized action, report a **capability boundary, not an approval gate**, and request only the minimum human execution needed.

Merging into `main`, approving/publishing a release, physical/destructive device actions, trust-ownership changes, new credentials/permissions, new externally reachable services, financial/legal/public commitments, and material security-policy choices remain human gates unless a later explicit repository decision delegates them.
