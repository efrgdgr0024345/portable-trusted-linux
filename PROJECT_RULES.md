# Portable Trusted Linux — Project Rules

These rules are mandatory for all future work in this repository.

## Higher-model plans, ideas and suggestions require human sign-off to change

Any plan, idea or suggestion from a higher model MUST NOT be changed by a lower model without explicit human sign-off. This applies project-wide, not only to the current task or a named plan.

A lower model may implement the authorized work faithfully and report problems, but MUST NOT independently replace, discard, weaken, reinterpret to change its meaning, or materially depart from the higher model's plan, idea or suggestion. Before a change, it must present the original, the proposed change, the reason and risks, and obtain explicit human approval. It cannot approve its own exception. Generic permission to continue is not sign-off for an undisclosed deviation.

Record the originating model/version and provenance when reliably known. Higher/lower ordering must come from a human-established hierarchy or trustworthy platform metadata, never a model's self-assessment. When authorship or rank is unknown, preserve the existing direction and seek human clarification before changing it.

A suggestion is not automatically authorization to execute it. Safety, platform permissions and explicit human instructions retain precedence; if faithful execution is unsafe or blocked, stop that action and report it rather than silently substituting another plan. This repository policy does not claim technical enforcement over future models.

## Binding plan and model-attribution rule

The active main-only consolidation and publication plan is `docs/27-main-only-consolidation.md`, explicitly authorized by the operator on 11 November 2026 (AWST). `docs/21-json-only-candidate-publication-plan.md` remains the historical accepted recovery record; its instruction to retain a temporary production feed is superseded by this explicit authorization. The evidence-first, compatible-JSON-last and actual-live-verification requirements remain binding.

Preserve plan provenance and model attribution. Material departures still require explicit human sign-off; the current operator directive specifically authorizes this main integration, production feed migration and conditional cleanup. It does not delegate production-key, platform or release authority.

## Priority Rule 0 — The live candidate page is the physical-test authority

The operator-facing page at `https://easyai.com.au/USB/project.php` is the single authoritative download handoff for physical testing. A candidate is not ready to hand to the operator merely because its artifact exists or its repository metadata is correct.

Before asking the operator to download, write or test an ISO, the assistant MUST:

1. publish compatible candidate JSON on `main`, the sole permitted production source; the deployed viewer must hard-code `GITHUB_BRANCH = 'main'`; routine ISO/index updates must not require replacing PHP;
2. verify the live page itself over HTTPS;
3. compare the displayed filename, exact byte size, SHA-256, source commit, build run, artifact ID and download URL with the authoritative evidence record;
4. confirm the page does not label a superseded candidate as current; and
5. record the successful live-page verification in the work-state checkpoint.

If any value is missing, stale or different, the candidate handoff is **FAILED CLOSED**. The assistant must not bypass the failure by pasting a different artifact link into chat or asking the operator to remember which hash is correct. It must repair or clearly report the publication/deployment boundary first.

This rule governs the current **test candidate** only. It does not promote the image to an approved release, authorize a physical device write, merge development work to `main`, or change release/signing gates.

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

## Rule 14 — Compact AI work-state recovery and context-health checkpoint are mandatory

`AI_WORK_STATE.md` is the compact first-read checkpoint for fresh sessions and context recovery. It MUST remain at the repository root and MUST stay deliberately small; its role is to answer the current milestone, branch/PR, exact active candidate/build identity where applicable, last completed work, next automatic action, next genuine human gate, blockers and context-health state.

The repository, not the chat window, is the durable project memory. The assistant MUST NOT bulk-load the full project history at the start of every ordinary turn. Use the recovery sequence only for a fresh chat/session, suspected continuity loss, long-gap check-in, or when current state cannot be recalled confidently:

1. read `AI_WORK_STATE.md` first;
2. verify only the volatile GitHub/CI/artifact facts required for the next action;
3. load policy/technical/history files selectively when the task actually needs them;
4. do not reload the complete lessons register or `project_content.json` merely to reconstruct state;
5. continue automatically until a genuine human gate.

Context pressure MUST be described as an estimate, never as a fabricated exact context-window percentage. Track observable proxies such as turns since checkpoint, large document/log reads, tool-heavy operations, large pasted material and continuity symptoms. Use GREEN/AMBER/RED only as operational heuristics:

- GREEN: continue normally;
- AMBER: refresh `AI_WORK_STATE.md` automatically before continuing;
- RED: refresh the compact checkpoint, minimize nonessential bulk reads and use the recovery sequence when continuity is uncertain.

Context pressure itself is not a human gate. Checkpoint maintenance is `AUTO` work and must not require routine operator permission.

`docs/13-ai-continuity-and-context-recovery.md` documents the reusable pattern for other projects. `docs/14-context-health-calibration-log.md` records real dropout/continuity events and future threshold changes. Unknown measurements remain `unknown`; never invent retrospective precision.

When `AI_WORK_STATE.md` is materially refreshed during a work cycle, it must be written before the final `project_content.json` update so Rule 2 remains intact.



## Rule 15 — Capability evidence and canonical design references

Report progress per capability as designed, implemented, tested and physically observed, with the exact source/image identity, environment and evidence link. Never derive overall security assurance from a milestone count or a single completion percentage. Record unavailable evidence and failed harnesses separately from product failures. Historical evidence for a different image must stay attached to that image.

For visual changes, consult `design/README.md`, `design/brand-notes-2026-09-16.txt` and the user-selected **Graphical design for black cat project** folder before implementation. Preserve source assets and the recognizable cat. The canonical tagline is **Stealth. Intelligence. Independence.** A design mockup neither implements nor proves security controls.


## Rule 16 — Main is the only long-lived branch

`main` is the only long-lived development, status and production-data branch. The public viewer MUST read project_content.json from main and must not silently fall back to a status, ops or development alias. Current documentation links use main or immutable evidence commits, not refs scheduled for deletion.

Do not create or retain another long-lived status, ops, release-tracking or development branch without a separately recorded HUMAN_GATE exception. Short-lived implementation/PR branches may be used for review, validated, merged under the applicable human authorization and removed when their purpose is complete. Do not turn those branches into permanent publication feeds.

Before deleting a ref, check its exact current tip against actual main (or an explicitly approved archive); stop if valuable unique content remains or a concurrent tip moved. Preserve source, evidence and conclusions in reachable history. For the current 11 November cleanup, obsolete branches are retained only as retiring refs until the real HTTPS viewer is verified to read main, then deleted. This conditional retention is part of the operator's directive, not a new permanent-branch exception. If deployment or verification is unavailable, report the boundary and keep deletion blocked; do not call the definition of done complete.

Production-key custody, platform enrollment/ownership, physical/destructive media actions and release approval retain their HUMAN_GATE controls. This policy does not increase any assurance claim.
