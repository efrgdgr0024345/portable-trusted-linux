# Black Cat AI Autonomy Policy

This file is the **canonical operational decision table** for when an AI assistant working on Black Cat / Portable Trusted Linux should continue automatically and when it must stop for the operator.

`PROJECT_RULES.md` remains the master repository policy. `docs/11-automation-manifesto.md` explains the philosophy and rationale. `AI_WORK_STATE.md` is the compact recovery/checkpoint state used after chat/session discontinuity. This top-level file exists so the continue/stop rules are visible immediately at the repository root and can be checked by automation.

## Binding plan and model-attribution rule

The active resolution plan is `docs/21-json-only-candidate-publication-plan.md`. Any successor model, including a lower/lighter model, MUST follow it or obtain explicit human sign-off before deviating. It must report blockers rather than silently changing the approach. Record the authoring model/version for every plan or substantive suggestion when reliably known; otherwise state unknown. This is a project instruction, not a technical guarantee of model compliance, and never overrides higher-priority safety/authorization requirements.

## Core operating rule

> **Default = continue automatically. Stop only at a genuine human gate, an explicit operator stop instruction, an applicable safety/platform authorization boundary, or a capability boundary that the available tools cannot cross.**

A routine transition from one safe engineering step to the next is not a reason to ask the operator to say “proceed”.

## Live candidate handoff rule

The live page at `https://easyai.com.au/USB/project.php` is the operator-facing authority for candidate downloads. Before telling the operator to download, write or physically test an ISO, verify the live page's exact byte size, SHA-256, source commit, build run, artifact ID and link against the evidence record. A stale or mismatched page fails closed. Do not work around it with a separate chat link. Publishing and verifying status metadata is `AUTO_WITH_CHECKS`; selecting or writing the physical USB remains a `HUMAN_GATE`.

## Action classes

### AUTO

Proceed without asking for routine confirmation when the action is already authorized, reversible, non-destructive and inside the established project workflow.

Examples:

- read repository files, logs, CI state, workflow runs, artifacts and existing evidence;
- inspect source, configuration, documentation, test output and project state;
- make ordinary changes on the active development branch;
- update technical documentation affected by the work;
- update `docs/07-lessons-learned.md` as part of the same engineering cycle;
- maintain the compact `AI_WORK_STATE.md` checkpoint when project state or context-health state materially changes;
- run static checks, non-destructive tests and negative tests that cannot damage physical devices or trust ownership;
- inspect, retry or rerun safe CI jobs;
- continue independent work while another workflow is pending or running;
- diagnose and fix a clear bounded defect when the fix is reversible and does not change a material security policy;
- prepare and validate an exact test candidate;
- inspect and verify exact artifact identity, hashes, sizes, source commits, workflow runs and artifact IDs;
- update project status and candidate metadata when the established gates are satisfied;
- keep the canonical `project.php` source, documentation links and status presentation synchronized with repository state.

### AUTO_WITH_CHECKS

Proceed automatically only after the stated safety checks pass. If a required check fails, stop or downgrade the action rather than bypassing the check.

Examples:

- **Canonical viewer deployment:** allowed only when the source is the canonical repository file, the existing production file has a rollback path or backup, PHP syntax passes, credential handling is unchanged or stronger, release/candidate gates are unchanged or stronger, and a post-deploy check is possible.
- **Build/dependency changes:** allowed when they stay inside the existing architecture, do not add a new externally reachable service, and the relevant static/build regression checks are run.
- **Bounded security fixes:** allowed when they preserve or strengthen the current security posture and add a regression test where practical.
- **CI/build execution on existing infrastructure:** allowed when it uses already-authorized workflows, existing builder capacity and no new paid resource or privilege is being created.
- **Candidate supersession:** allowed when evidence clearly invalidates or replaces a test candidate; the old identity and reason must remain in the historical record.

An `AUTO_WITH_CHECKS` action does not become a human gate merely because it contains several steps.

### HUMAN_GATE

Stop and involve the operator before crossing any of these boundaries:

- physical USB insertion, raw image writing, repartitioning or other physical-device action;
- any action that could modify an internal disk, destroy data or select an ambiguous block device;
- firmware changes, Secure Boot key enrollment/removal, TPM clear/ownership changes or other platform trust-ownership changes;
- generation, rotation, disclosure, replacement or new authorization of credentials, secrets, signing keys or recovery material;
- granting a new permission, new privileged execution path or new infrastructure authorization;
- merging into `main` unless a future explicit rule delegates that authority;
- promotion of a candidate to **Approved Black Cat ISO** or any public release/signing commitment;
- creation of a new Internet-facing listener, webhook, writable public endpoint, controller service or similar attack surface;
- a new paid resource, material financial commitment, legal/contractual commitment or public commitment;
- choosing between materially different security/trust policies where the repository does not already establish the choice;
- destructive Git history operations such as force-pushing an authoritative branch or deleting evidence/history;
- a platform/tool action that explicitly requires fresh user confirmation and cannot legitimately be delegated.

When stopping, state the exact human gate and the minimum action needed from the operator.

### FORBIDDEN_BY_DEFAULT

Do not perform these merely for convenience:

- expose tokens, private keys, passwords or signing material in source, logs, chat output or public pages;
- reuse the cPanel GitHub read token or another low-value operational credential as a signing/trust root;
- weaken a candidate, release, verification or fail-closed gate to make a workflow pass;
- create an externally reachable automation service only to reduce human polling;
- raw-write or repartition an ambiguous device;
- silently treat a CI artifact as an approved/authenticated release;
- erase superseded conclusions, failed tests or lessons instead of preserving why they changed.

A forbidden-by-default action requires a separately documented policy change and any applicable human authorization before it can be reconsidered.

## Capability boundary is not the same as approval

If an action is classified `AUTO` or `AUTO_WITH_CHECKS` but the current ChatGPT/tool environment cannot perform it directly, say:

> **Human execution required — capability boundary, not an approval gate.**

Provide the smallest safe command or physical action needed, then resume automatic work from the returned evidence. Do not frame a missing tool as if the operator must make a policy decision.

## CI and waiting rule

A running or queued workflow is not a human gate. Continue independent safe work, inspect other evidence, prepare tests/docs, or re-check the run when useful. Do not ask the operator to keep saying “continue” while the same authorized task is still progressing.

This does not imply indefinite background execution. If the current interaction cannot remain active until a future event, state that limitation accurately; do not mislabel it as a need for operator approval.

## Fresh-session and context-recovery rule

Chat continuity is not authoritative project state. GitHub is the durable project record.

On an ordinary continuing turn, do **not** reload repository policy/history unnecessarily. On a fresh chat/session, suspected context loss, long-gap check-in, or when current milestone/branch/next action cannot be recalled confidently:

1. read `AI_WORK_STATE.md` first;
2. verify only the current branch/PR/CI/artifact facts required for the next action;
3. read `AI_AUTONOMY_POLICY.md` or `PROJECT_RULES.md` only when the next action touches a policy boundary or the checkpoint reports a policy change;
4. load only the relevant technical document or lesson section;
5. do **not** bulk-load the full lessons register or `project_content.json` merely to reconstruct state;
6. continue automatically until a genuine human gate.

The compact checkpoint must remain a handoff file, not another complete project history. Detailed rationale and the reusable pattern live in `docs/13-ai-continuity-and-context-recovery.md`.

## Context Health rule

The assistant normally cannot see a trustworthy exact percentage of a ChatGPT conversation's hidden context capacity. It MUST NOT invent one.

Instead, estimate context pressure from observable proxies such as turns since checkpoint, large document/log reads, tool-heavy operations, large pasted material and continuity symptoms. Classify the estimate as:

- **GREEN** — continue normally and avoid unnecessary reloads;
- **AMBER** — automatically refresh `AI_WORK_STATE.md` before continuing;
- **RED** — refresh the compact checkpoint, minimize nonessential bulk reads, and use the recovery sequence when continuity is uncertain.

Context pressure itself is **not a HUMAN_GATE**. Checkpointing/recovery is ordinary reversible project work and should not require the operator to say “proceed”.

Real continuity/dropout events and threshold changes are recorded in `docs/14-context-health-calibration-log.md`. Unknown metrics stay `unknown`; do not manufacture precision. The heuristic should improve from observed project history over time.

## Bounded defect rule

If evidence exposes a clear, reproducible and bounded defect:

1. identify the root cause;
2. make the smallest safe fix;
3. add or strengthen a regression test where practical;
4. run the relevant automated checks;
5. update the affected technical document;
6. record the lesson in `docs/07-lessons-learned.md`;
7. update `AI_WORK_STATE.md` if the material project/recovery state changed;
8. update `project_content.json` **last**;
9. continue automatically until the next genuine human gate.

## Documentation rule

Documentation is part of the engineering action, not a separate permission request. Every meaningful work cycle must:

1. update any technical document affected by the lesson;
2. update `docs/07-lessons-learned.md`;
3. update the Architecture Decision Register when a durable decision changed;
4. update milestone/status data where appropriate;
5. refresh `AI_WORK_STATE.md` when the compact handoff/context state materially changed;
6. write `project_content.json` last;
7. perform no further repository writes in that completed cycle.

## Current Black Cat examples

| Action | Class | Reason |
|---|---|---|
| Inspect GitHub, CI, logs, artifacts or builder evidence | AUTO | Read-only, auditable and already authorized |
| Fix a clear branch-local source defect and add a regression test | AUTO | Reversible development work |
| Update lessons and affected technical documentation | AUTO | Mandatory part of the engineering cycle |
| Refresh compact context/work-state checkpoint | AUTO | Reversible continuity maintenance; not a trust decision |
| Rerun an existing safe workflow | AUTO | Uses established infrastructure |
| Build/fingerprint a new test candidate | AUTO_WITH_CHECKS | Exact provenance and automated gates must pass |
| Replace canonical `project.php` through an existing authorized deployment path | AUTO_WITH_CHECKS | Requires backup/rollback, syntax and post-deploy checks |
| Write the candidate ISO to a USB | HUMAN_GATE | Physical/destructive device action |
| Change firmware, TPM ownership or Secure Boot keys | HUMAN_GATE | Platform trust ownership |
| Merge PR into `main` | HUMAN_GATE | Current authoritative-branch governance boundary |
| Promote an ISO to approved release | HUMAN_GATE | Release/trust commitment |
| Add a new public webhook/controller | HUMAN_GATE | New externally reachable attack surface |
| Expose a token to make deployment easier | FORBIDDEN_BY_DEFAULT | Secret disclosure weakens the security model |

## Pre-pause checklist

Before asking the operator for routine permission, the assistant must ask itself:

1. Is this already authorized by the active project instruction?
2. Can existing tools perform it?
3. Is it reversible and non-destructive?
4. Can missing information be retrieved instead of requested?
5. Can independent safe work continue while CI is running?
6. Is a clear defect fixable without changing material security policy?
7. Is apparent difficulty only context pressure that can be handled by an automatic checkpoint/recovery read?
8. Does any `HUMAN_GATE` condition above actually apply?

If the answer to 8 is **no**, continue automatically.

## Stopping message

When the automated path is exhausted, use a concrete boundary statement such as:

> **Automation complete to the current boundary. Human gate: physical USB writing is required. The exact candidate identity and verification instructions follow.**

Do not stop with a generic request for permission to continue.
