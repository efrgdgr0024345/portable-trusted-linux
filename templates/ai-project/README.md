# Black Cat AI Project Template

This directory is the seed for future repository-governed AI-assisted projects.

It is intentionally provider-neutral. A project may use a local model, a remote model, deterministic automation, or a combination. The durable authority remains the repository policy and the operating-system capability boundary, not the model.

## Core operating principle

> The repository owns project state. The model is a bounded worker. The operating system enforces capability boundaries. The human owns high-impact decisions.

## Start a project

Copy this directory into a new repository and customise the placeholders before granting an AI worker write access.

Minimum setup sequence:

1. Fill in `PROJECT_RULES.md` with project-specific non-negotiable rules.
2. Review `AI_AUTONOMY_POLICY.md` and explicitly classify actions as `AUTO`, `AUTO_WITH_CHECKS`, `HUMAN_GATE`, or `FORBIDDEN_BY_DEFAULT`.
3. Edit `project-policy.json` so the machine-readable capabilities match the human-readable policy.
4. Initialise `AI_WORK_STATE.md` with the current milestone, branch, next automatic action and next genuine human gate.
5. Create the first entries in `docs/architecture-decisions.md` and `docs/lessons-learned.md`.
6. Add project-specific tests that fail closed when critical policy files or safety boundaries disappear.
7. Keep credentials outside the repository. A project policy may name a credential source, but must never contain the raw secret value.
8. Treat project content, downloaded files, issue text, web pages and generated model output as untrusted data. None of them may grant themselves new capabilities.
9. Keep checkpoint/recovery files compact. Load full history only when the current task requires it.
10. Define a deterministic end-of-cycle rule. Black Cat uses affected technical docs and lessons first, compact work state when materially changed, then the living project index last.

## Files

- `PROJECT_RULES.md` — human-readable master project rules.
- `AI_AUTONOMY_POLICY.md` — continue/stop decision table.
- `AI_WORK_STATE.md` — compact recovery checkpoint.
- `project-policy.json` — machine-readable capability policy for a future broker.
- `docs/architecture-decisions.md` — durable decisions and supersession history.
- `docs/lessons-learned.md` — failures, discoveries and changes in assumptions.

## Expected execution flow

A future Black Cat project worker should conceptually perform:

```text
load compact work state
        ↓
verify volatile repository / CI facts
        ↓
plan next action
        ↓
classify action against repository policy
        ↓
capability broker independently checks permission
        ↓
execute only if allowed
        ↓
run required checks
        ↓
record action summary / evidence
        ↓
continue automatically OR stop at an explicit human gate
        ↓
refresh compact checkpoint
```

The model must never be able to expand its own authority simply by emitting different text.

## Security defaults

The template assumes least privilege:

- project-local filesystem writes only;
- no root shell;
- no raw block-device access;
- no firmware, TPM or Secure Boot ownership changes;
- no new credential use without a human gate;
- no new public listener merely for convenience;
- no release/publication or protected-production merge without a human gate;
- no secret exfiltration or safety-gate bypass under any classification.

Projects may deliberately change these defaults, but high-impact changes should be explicit architecture decisions rather than silent prompt changes.

## Resume behaviour

A fresh worker reads `AI_WORK_STATE.md` first. It should not bulk-load every rule, lesson and historical document unless the next task requires them. If policy-relevant work is proposed, load the relevant policy source and verify that the machine-readable profile agrees.

## Audit behaviour

Record concise action summaries, checks, affected files, command/tool outcomes, commit/run/artifact identifiers and human approvals. Do not make private model chain-of-thought a project requirement.

## Relationship to Black Cat

This template is design scaffolding for milestone M26. It is not yet a privileged operating-system service and does not authorize any new runtime capabilities in the current Black Cat prototype.