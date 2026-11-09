# AI Continuity and Context Recovery Pattern

This document explains the Black Cat project's reusable method for reducing human delays caused by long-running AI chats, context loss, session resets and natural conversation dropouts.

It is deliberately written so another project can copy the pattern without copying Black Cat's security architecture.

## 1. Problem

Long AI-assisted engineering projects accumulate substantial conversational context: design decisions, build identities, failed experiments, logs, screenshots, repository changes and human approvals. A chat window is a poor durable state store because:

- context capacity is finite;
- the exact remaining context is usually not exposed as a reliable percentage;
- large files, logs, images, tool schemas and prior messages all consume context differently;
- a new chat/session may not carry every relevant detail forward;
- forcing the model to reload the complete project on every turn wastes context and increases latency;
- asking a human to repeatedly reconstruct state defeats the point of automation.

The design goal is therefore not to keep one chat alive forever. The goal is to make interruption cheap and recovery deterministic.

## 2. Core principle

> **The repository is the durable project memory. The chat is a worker/interface, not the authoritative state store.**

A project should be able to survive a lost chat by reconstructing only the minimum state required for the next action.

## 3. Three-layer design

### Layer A — compact root checkpoint

Keep a deliberately small root file such as `AI_WORK_STATE.md`.

It should contain only current operational state:

- state/checkpoint revision;
- current milestone;
- active branch/PR;
- exact active build/candidate identity where relevant;
- last completed work;
- next automatic action;
- next genuine human gate;
- blockers;
- compact policy references;
- context-health estimate and counters.

It should **not** contain the entire lessons history, full architecture, every past failure, large logs or duplicate technical specifications.

Black Cat targets fewer than 100 lines for this file and statically guards against uncontrolled growth.

### Layer B — durable repository documentation

Full history belongs in normal project files such as:

- master project rules;
- architecture decision records;
- lessons learned;
- technical specifications;
- build/test evidence;
- project indexes/changelogs.

These files remain authoritative but are loaded selectively.

### Layer C — execution/automation systems

Use existing automation infrastructure such as CI/CD and build runners for actual engineering work. Do not create a new public listener or privileged controller merely to keep an AI chat alive.

The continuity pattern does not require an AI API, webhook, browser automation or a permanently connected chat session.

## 4. Recovery startup rule

Do **not** re-read every policy and history file on every turn.

Use the compact recovery path only when one of these conditions applies:

- a new chat/session starts;
- the model or operator suspects context loss;
- a long gap has occurred and current state may have changed;
- the operator says "check in", "continue from where we were" or equivalent and state is not already confidently present;
- the model cannot confidently state the current milestone/branch/next action;
- a previous answer contradicts durable repository state.

Recovery sequence:

1. Read `AI_WORK_STATE.md` first.
2. Verify only the current Git/PR/CI/artifact facts needed for the next action.
3. Read the autonomy/master rules only if the next action touches a policy boundary or the checkpoint says policy changed.
4. Read only the relevant technical document or lesson section.
5. Continue work automatically until a genuine human gate.

This is **selective reconstruction**, not bulk reload.

## 5. Context Health: what can actually be measured

A normal ChatGPT session does not provide this workflow with a trustworthy live value such as "73% of the context window is used". Therefore do not fabricate one.

Instead, track observable proxy signals that tend to correlate with context pressure:

- turns since the last compact checkpoint;
- large document/log/file reads;
- tool-heavy turns with many returned results;
- large pasted logs or code blocks;
- repeated images/files;
- repeated need to re-fetch facts that were previously available;
- contradictions with earlier state;
- the model asking for information already supplied;
- the operator reporting that continuity has degraded;
- an actual session/reset/dropout event.

The result is an **estimated pressure class**, not a token measurement.

## 6. Provisional GREEN / AMBER / RED model

Start conservatively and tune from observed failures.

### GREEN

Characteristics:

- recent compact checkpoint;
- few large reads since checkpoint;
- no continuity symptoms;
- current milestone/branch/next action can be recalled confidently.

Action: continue normally. Avoid unnecessary state reloads.

### AMBER

Possible triggers:

- conversation has become long or tool-heavy;
- several large files/logs have been read;
- important state has changed multiple times since the last checkpoint;
- one mild continuity symptom appears;
- confidence in exact current state is declining.

Action: update `AI_WORK_STATE.md` automatically before continuing. Do not interrupt the human simply because the conversation is large.

### RED

Possible triggers:

- confirmed loss/contradiction of earlier state;
- repeated retrieval of already-established facts;
- multiple continuity symptoms;
- a chat/session boundary is imminent or has occurred;
- a complex next action would be unsafe without reconstructing state.

Action: create/refresh the compact checkpoint; avoid unnecessary bulk reads; on the next session perform the recovery sequence.

**RED is not a security/human approval gate.** It is a continuity-management state.

## 7. Optional simple scoring model

Projects may use an internal score to make estimates more consistent. The exact weights should remain provisional until enough real dropout events exist.

Example starting model:

- +1 for each 5 ordinary turns since checkpoint;
- +2 for each large document/log read;
- +1 for a tool-heavy turn with several substantive results;
- +2 for a very large pasted log/code block;
- +4 for a mild continuity symptom;
- immediate RED for a confirmed state-loss/contradiction event.

Example thresholds:

- 0–5: GREEN
- 6–11: AMBER
- 12+: RED

These numbers are not properties of GPT or a context-window size. They are operational heuristics to be calibrated against the project's own history.

A project can also choose a simpler rule-based system and avoid a numeric score entirely.

## 8. Learn from real dropouts

Maintain an append-only calibration log. For each genuine continuity problem, record only observable facts available at the time, for example:

- date/time;
- checkpoint revision;
- turns since checkpoint if known;
- number of large reads if tracked;
- number of tool-heavy operations if tracked;
- number of large pasted logs/files;
- symptom;
- consequence;
- recovery action;
- whether GREEN/AMBER/RED prediction was too early, useful or too late;
- resulting threshold/rule change.

Do not invent missing counts after the fact. Mark them `unknown`.

Over time, the project gains an empirical model of its **practical conversation failure point**, even though it still does not know the model's hidden exact context usage.

Black Cat records these observations in `docs/14-context-health-calibration-log.md`.

## 9. Checkpoint update policy

Refresh the compact checkpoint when:

- a material project state changes;
- a candidate/build identity changes;
- the next human gate changes;
- a blocker appears/disappears;
- an autonomy/security policy revision changes the next-action boundary;
- context pressure becomes AMBER or RED;
- a recovery event exposes stale/missing checkpoint data.

Do not rewrite the checkpoint for every minor sentence or tool call. Excessive checkpoint churn creates noise and unnecessary commits.

For repositories with a "living index updated last" rule, the checkpoint is updated before that final living-index write.

## 10. Security and governance boundary

Continuity automation must not silently expand authority.

A recovered AI session receives only the permissions already established for the project. The checkpoint cannot authorize:

- secret disclosure;
- destructive device operations;
- firmware/trust-ownership changes;
- new credentials/permissions;
- releases/merges where those remain human gates;
- creation of a new Internet-facing controller;
- bypassing required tests or fail-closed controls.

State recovery and action authorization are separate concerns.

## 11. Recommended repository layout

A reusable layout is:

```text
repository/
├── AI_WORK_STATE.md                  # compact first-read checkpoint
├── AI_AUTONOMY_POLICY.md             # continue/stop decision table
├── PROJECT_RULES.md                  # master project rules
├── README.md                         # human entry point
├── project_content.json              # optional living index/status feed
└── docs/
    ├── architecture-decisions.md
    ├── lessons-learned.md
    ├── ai-continuity-context-recovery.md
    └── context-health-calibration-log.md
```

Names can vary; responsibilities matter more than filenames.

## 12. Minimal `AI_WORK_STATE.md` template

```markdown
# AI Work State

## Current project state
- State revision: 1
- Current milestone: ...
- Active branch: ...
- Blockers: ...

## Last completed
- ...

## Next automatic action
- ...

## Next genuine human gate
- ...

## Context health
- Estimated pressure: GREEN
- Confidence: low/medium/high
- Turns since checkpoint: 0
- Large reads since checkpoint: 0
- Tool-heavy operations since checkpoint: 0
- Confirmed continuity events: 0

## Recovery sequence
1. Read this file first.
2. Verify only facts needed for the next action.
3. Load full documents selectively.
4. Continue until a genuine human gate.
```

## 13. Failure modes to avoid

### Bulk-loading all history at every turn

This consumes the resource the system is trying to conserve.

### Treating a heuristic as an exact context percentage

The estimate should be labelled as an estimate with confidence.

### Putting secrets in the checkpoint

The checkpoint is project state, not a secret store.

### Letting the checkpoint become another giant project document

Keep it short. Link to durable documents instead.

### Requiring the human to approve a context checkpoint

Context maintenance is ordinary reversible project work and should normally be automatic.

### Assuming a checkpoint replaces source-of-truth verification

After a long gap or session reset, verify volatile facts such as branch head, CI state and artifact availability from their authoritative source.

## 14. Black Cat implementation

Black Cat currently uses:

- `AI_WORK_STATE.md` — compact first-read checkpoint and context-health status;
- `AI_AUTONOMY_POLICY.md` — canonical AI continue/stop table;
- `PROJECT_RULES.md` — mandatory repository policy;
- `docs/11-automation-manifesto.md` — rationale for proceed-by-default work;
- `docs/14-context-health-calibration-log.md` — empirical dropout/continuity observations;
- `docs/07-lessons-learned.md` — complete engineering lessons;
- `project_content.json` — living public project index, updated last.

The pattern is intentionally independent of Black Cat's Linux/TPM work and can be reused for software, research, data, documentation or other long-running AI-assisted projects.

## 15. Success criterion

The system succeeds when a chat can disappear and a new AI session can answer these questions quickly and accurately without the human reconstructing the project:

1. What are we doing now?
2. What exact state/artifact are we working from?
3. What was completed last?
4. What can the AI do next automatically?
5. What is the next genuine human gate?
6. Which larger documents must be read only if the next action requires them?

If those answers fit in the compact checkpoint, conversation dropout becomes a recoverable interruption rather than a project delay.