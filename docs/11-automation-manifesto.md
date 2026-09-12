# Black Cat Automation Manifesto

This manifesto defines how Black Cat / Portable Trusted Linux engineering should proceed with the least unnecessary operator interruption while preserving safety, security, auditability and human control at genuine trust boundaries.

## Core principle

> **Proceed automatically through all safe, reversible, auditable engineering steps. Treat the current environment as potentially untrusted, strengthen security progressively, preserve exact evidence and artifact identity, fail closed on uncertainty, and involve the human only when physical action, authorization, irreversible change, or meaningful security ownership requires it.**

The normal stopping condition is not “there is another step.” The normal stopping condition is: **everything that can be safely and legitimately automated has been completed, and the next step is a genuine human gate.**

## 1. Default to proceed, not pause

When the next project step is clear, in scope, reversible and authorized by the established project workflow, continue automatically. Do not ask the operator to say “proceed” merely because one task finished and another routine task begins.

## 2. Run the pre-pause check before asking the operator anything

Before asking the operator to proceed, confirm, approve a routine engineering step, or return merely because CI is still running, evaluate this checklist:

1. **Can the next step be completed with an already-authorized project tool or connected system?** If yes, do it.
2. **Is the next step reversible and non-destructive?** If yes, default to doing it.
3. **Can missing information be obtained from GitHub, CI artifacts, repository files, the builder, logs, existing project evidence or another connected source?** If yes, retrieve it instead of asking the operator.
4. **Is a workflow merely pending/running?** If yes, continue any independent safe work and inspect the workflow again when useful; do not ask the operator for permission to keep monitoring within the active task.
5. **Did a test expose a clear, bounded source defect?** If yes, fix it, add a regression test, document the lesson, rebuild and continue through automated gates.
6. **Would proceeding modify physical hardware, destroy data, alter trust ownership, expose a secret, create a financial/legal commitment, or cross an authorization boundary?** If yes, stop at that boundary.
7. **Is essential information genuinely unavailable and impossible to derive safely?** Only then ask the operator.

If none of the genuine human-gate conditions apply, **do not pause for permission**.

## 3. Security improves monotonically

Changes should strengthen the system or preserve its current security level. Temporary weaker prototype controls are permitted only under the documented progressive-hardening rule. Never silently weaken an existing security boundary for convenience.

## 4. Assume the development environment may be compromised

The Windows workstation, browser state, locally stored files and manually copied values must not be treated as authoritative when a more independent evidence source exists. Prefer repository identity, CI-produced evidence, independent hashes, signed material and later hardware-backed trust roots.

## 5. Separate observation from proof

A successful boot, screen message, hash, TPM presence, Secure Boot state or smoke test proves only what that evidence supports. Maintain the project classifications: `VERIFIED`, `ATTESTED`, `OBSERVED`, `WARNING`, `UNKNOWN`, `NOT VERIFIABLE`, and `FAILED`.

## 6. Exact artifact identity is mandatory

Never direct the operator to a vague “latest ISO.” Every test candidate must be pinned to an exact filename, byte size, SHA-256, source commit, workflow run and artifact identity before physical testing.

## 7. Candidate and approved release are separate states

Automation may build, test, supersede and publish a clearly labelled **Current Test Candidate** when its candidate gates are satisfied. It must never silently promote that candidate to **Approved Black Cat ISO**. Release approval remains a stronger, separately documented security state.

## 8. Automate before involving the operator

Use GitHub Actions, repository tooling, the builder, static tests, artifact inspection and connected systems before requesting manual work. Human effort should be reserved mainly for actions automation cannot legitimately perform, especially physical-hardware interaction and trust-ownership decisions.

## 9. Do not stop merely because CI is running

A pending build is not itself a human gate. Continue source review, test preparation, documentation, evidence inspection and other independent work that cannot invalidate the running build. If the requested task specifically depends on the result, inspect the run again rather than asking the operator whether to continue.

This rule does not create background execution: work continues only while an active interaction/tool execution is possible. When the system cannot remain active until a future event, report that factual limitation without pretending human approval is required.

## 10. Fix clear defects automatically

When evidence exposes a reproducible, bounded defect and the correction is safe and within project scope, implement the correction, add a regression test, document the lesson, trigger/observe the relevant automated gates and supersede any affected candidate. Do not ask for routine permission first.

## 11. Pause only at genuine human gates

A pause/request is justified when at least one of these applies:

- physical action is required;
- an action is destructive or difficult to reverse;
- internal disks, firmware, Secure Boot ownership, TPM ownership or other platform state may be changed;
- a new credential, secret, permission or external authorization is required;
- the action creates a financial, legal, contractual or public-release commitment;
- the operator must choose between materially different security/trust policies;
- essential information cannot be safely derived from available evidence;
- an applicable platform/tool requires explicit user confirmation that cannot be delegated.

When pausing, state **exactly which human gate was reached** and why automation cannot safely cross it.

## 12. Physical-device safety overrides speed

Before USB writes, repartitioning, firmware changes, Secure Boot key changes, TPM clearing or similar operations, make the target explicit and prevent ambiguous/internal-device selection wherever possible.

## 13. Never expose or weaken secrets

Tokens, private keys, signing keys, recovery material and future trust roots must not be embedded in source, public pages or logs. Low-value prototype credentials must not be silently reused as higher-value trust roots.

## 14. One authority per security function

Prefer one authoritative producer for evidence, credentials, release state and other security-sensitive state. Other components may display or verify that state rather than independently recreating it. Duplicate authorities require explicit design justification.

## 15. Fail closed for trust decisions

Missing evidence, ambiguous identity, failed verification, unsafe permissions or unavailable authoritative metadata must produce an unavailable/unknown/failed state rather than a trusted-looking fallback.

## 16. Negative tests are first-class work

Security controls require deliberate failure tests. Automation should create and execute negative tests where safe rather than waiting for the operator to request them individually.

## 17. Preserve an audit trail

Meaningful engineering changes must be traceable through source commits, CI runs, artifacts, evidence and documented lessons. Historical conclusions must remain visible when superseded.

## 18. GitHub is the engineering source of truth

Project source, tests, architecture decisions, lessons, milestone state and artifact metadata live in the repository. Conversation is the control interface, not the durable engineering record.

## 19. Documentation follows engineering automatically

Meaningful engineering work automatically updates affected documents and `docs/07-lessons-learned.md`. Do not ask whether documentation should be updated.

## 20. `project_content.json` remains the final repository write of each work cycle

All source, tests and documentation changes happen first. Update `project_content.json` last. Once that final write occurs, do not perform another repository write within the same work cycle; a later user instruction or newly discovered engineering task begins a new cycle.

## 21. No routine permission-seeking

An established active project instruction, including “proceed” or “continue,” authorizes the engineering workflow to advance through ordinary builds, tests, safe fixes, documentation, CI inspection and candidate preparation until a genuine human gate is reached.

The assistant should not use phrases such as “tell me if you want me to continue” for ordinary in-scope project work.

## Expected stopping message

A good project stopping point should look like:

> **Automation complete to the current boundary. Human gate: physical hardware action is now required. The exact candidate and verification instructions are below.**

It should not look like:

> “The build is running. Tell me if you want me to continue.”

## Relationship to other project rules

This manifesto does not override safety, platform authorization requirements, security policy or explicit operator instructions. It defines how aggressively the project should proceed **inside** those boundaries.
