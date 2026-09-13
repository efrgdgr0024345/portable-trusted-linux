# Black Cat AI Project Automation — Future Linux Integration

This document turns the AI-assisted engineering workflow developed during Black Cat into a future user-facing capability that can be built directly into the Black Cat Linux environment.

The goal is not to make an AI process the operating-system authority. The goal is to make a **repository-governed, resumable, least-privilege project workspace** where an AI worker can continue safe work automatically, recover from context/session loss, and stop only at explicit human gates.

This is a **planned post-core-prototype capability**. It must not distract from completing the core Black Cat trust chain through M25.

## 1. Product idea

A Black Cat user should eventually be able to create a project with a command or graphical action such as:

```text
blackcat-project init my-project
```

The system would create a project workspace containing the rules, current work state, lessons, decisions, tests and project metadata needed for an AI assistant to work continuously without treating the chat window as memory.

A later user interaction could be as simple as:

```text
blackcat-project continue
```

or a **Continue project** button in the desktop UI.

The agent would reconstruct the minimum required state, inspect the repository and automation status, perform all permitted work, checkpoint its state, and stop only when a configured human gate is reached.

## 2. Core design principle

> **The repository owns project state. The model is a bounded worker. The operating system enforces capability boundaries. The human owns high-impact decisions.**

This separates four concerns that should never be collapsed:

1. **Durable state** — Git repository and project files.
2. **Reasoning** — local or remote AI model.
3. **Execution authority** — capability broker / operating-system permissions.
4. **Human authority** — destructive, trust, credential, release and policy gates.

Changing the model must not automatically change what the agent is permitted to do.

## 3. Default project layout

A generated project should begin with a compact, portable structure similar to:

```text
my-project/
├── README.md
├── PROJECT_RULES.md
├── AI_AUTONOMY_POLICY.md
├── AI_WORK_STATE.md
├── project-policy.json
├── project_content.json
├── docs/
│   ├── architecture-decisions.md
│   ├── lessons-learned.md
│   ├── ai-continuity-and-context-recovery.md
│   └── context-health-calibration-log.md
├── tests/
├── automation/
└── src/
```

The exact filenames may later become a versioned Black Cat project profile, but the roles should remain stable.

The current repository now carries a concrete reusable seed in `templates/ai-project/` plus the machine-policy schema at `schema/ai-project-policy.schema.json`. That scaffold is design material for M26; it does not grant any new runtime authority in today's Black Cat prototype.

## 4. Project bootstrap workflow

A generic Black Cat project wizard should ask only for information that materially changes the automation boundary:

1. project name and purpose;
2. repository location or whether a new local repository should be created;
3. whether network access is needed;
4. which existing external services, if any, are authorized;
5. project-specific HUMAN_GATE actions;
6. project-specific FORBIDDEN_BY_DEFAULT actions;
7. whether automatic branch commits are allowed;
8. whether remote CI/build infrastructure is already authorized;
9. model preference: local-only, remote-provider allowed, or selectable;
10. retention/privacy requirements for logs and project evidence.

The wizard then generates the project policy files and an initial `AI_WORK_STATE.md`.

## 5. Continue/stop engine

Every proposed action should be classified before execution:

- `AUTO`
- `AUTO_WITH_CHECKS`
- `HUMAN_GATE`
- `FORBIDDEN_BY_DEFAULT`

The classification should come from repository policy plus operating-system capability rules, not from a model deciding how much authority it wants.

Examples:

| Proposed action | Default class |
|---|---|
| Read project files and Git history | AUTO |
| Edit a development branch | AUTO |
| Run ordinary unit/static tests | AUTO |
| Retry an existing non-destructive CI workflow | AUTO |
| Deploy through an already-approved reversible path | AUTO_WITH_CHECKS |
| Install a package into a project sandbox | AUTO_WITH_CHECKS |
| Access a new credential | HUMAN_GATE |
| Write a raw disk/USB device | HUMAN_GATE |
| Change firmware/TPM/Secure Boot ownership | HUMAN_GATE |
| Publish a release or merge protected production state | HUMAN_GATE |
| Expose secrets or bypass a verification gate | FORBIDDEN_BY_DEFAULT |

## 6. Capability broker

The strongest implementation should not give an AI process an unrestricted shell.

Black Cat should provide a small **capability broker** that exposes explicit operations such as:

- read project files;
- write inside one project workspace;
- create/update a development branch;
- run allow-listed test/build commands;
- inspect Git/CI state;
- access configured network destinations;
- request a privileged or physical action from the human.

The broker should enforce policy independently of the model.

A model request such as "write to /dev/nvme0n1" should fail at the capability layer even if the model incorrectly classifies it as safe.

## 7. Least-privilege execution

The AI project worker should normally run as an unprivileged desktop user or dedicated project worker, not as root.

Potential hardening layers include:

- per-project filesystem permissions;
- Linux namespaces;
- `bubblewrap` or an equivalent sandbox;
- systemd user-service hardening;
- restricted environment variables;
- explicit network policy;
- no raw block-device access by default;
- read-only access to system locations unless a capability explicitly grants more;
- no access to signing keys merely because ordinary repository credentials are available.

Future privilege elevation should use a narrow, visible capability request rather than giving the agent general `sudo` rights.

## 8. Model independence

The project format should not depend on one AI provider.

A Black Cat project could eventually use:

- a local model running fully on the machine;
- a larger remote model after explicit network/provider authorization;
- different models for planning, coding or review;
- no model at all for deterministic automation steps.

`AI_WORK_STATE.md`, project rules, Git history and tests remain the durable state regardless of which model is connected.

This is important because a project may outlive a model version, provider account or chat product.

## 9. Local-first and offline operation

A useful first Black Cat implementation should work without a cloud model for deterministic project operations:

- inspect state;
- run tests;
- inspect Git diffs;
- checkpoint state;
- execute already-defined scripts;
- identify that a human gate has been reached.

If a local model is available, it can perform bounded reasoning while offline. Remote AI access should be an optional project capability, not a boot or project-state dependency.

## 10. Background continuation without a public listener

Do not create an inbound webhook merely so the AI can keep working.

For local background work, prefer:

- `systemd --user` services;
- `systemd --user` timers;
- repository/CI polling at a bounded interval;
- filesystem or local process events where appropriate;
- outbound-only API checks for authorized services.

This preserves the Black Cat zero-new-inbound-attack-surface principle.

A background worker should still use the same autonomy policy and HUMAN_GATE rules as an interactive session.

## 11. Checkpoint and recovery

The Linux-integrated project worker should use the same compact recovery pattern already proven useful in this repository:

1. read `AI_WORK_STATE.md` first on startup/recovery;
2. verify only volatile facts needed for the next action;
3. selectively load policy/technical/history files;
4. continue permitted work;
5. refresh the checkpoint when project state materially changes or context pressure rises;
6. never use the conversational transcript as the only copy of important state.

A process crash, reboot, model change or expired chat session should therefore become a normal resume event rather than a project reset.

## 12. Context Health

The integrated worker should expose a simple Context Health indicator:

- GREEN — normal work;
- AMBER — refresh compact checkpoint and avoid unnecessary bulk reads;
- RED — checkpoint immediately and prefer a clean recovery/session boundary.

It must be labelled as a heuristic unless an underlying model/provider exposes a trustworthy exact usage measurement.

The system should retain anonymised/project-local calibration observations such as large-read counts, turns/tasks since checkpoint and observed continuity failures so thresholds can improve over time.

## 13. Audit trail without storing private reasoning

Black Cat should record **actions and concise decision summaries**, not hidden chain-of-thought.

Useful audit events include:

- proposed action;
- policy class;
- checks performed;
- command/tool executed;
- affected files;
- result;
- commit/workflow/artifact identifiers;
- human gate reached;
- human authorization outcome;
- checkpoint revision.

This gives an engineer an auditable execution record without requiring internal model reasoning to become project data.

## 14. Project dashboard

A future desktop application could show one card per project:

```text
Project: Black Cat
State: ACTIVE
Milestone: M13B
Context Health: AMBER
Last action: repository checkpoint updated
Next automatic action: inspect relevant CI/repository state
Next human gate: physical USB retest
Blocked: no
```

Useful controls:

- Continue
- Pause
- View current work state
- View pending human gates
- View changes/diff
- View tests/CI
- Approve one specific gated action
- Reject/modify a proposed action
- Open lessons/decisions

The UI should display *why* work stopped instead of merely showing an opaque "agent paused" state.

## 15. Per-project human-gate policy

The defaults should be conservative, but each project needs its own gates.

For example:

- a documentation project might allow automatic merge to a disposable branch but never publication;
- a web project may allow staging deployment but require approval for production;
- a hardware project may require human confirmation for every device write;
- a financial project might prohibit transactions entirely;
- Black Cat itself requires human control around physical disks, firmware/TPM/Secure Boot ownership, credentials, `main` merge and approved releases.

The important principle is that these are **explicit repository policy**, not remembered conversational preferences.

## 16. Suggested command-line interface

Possible future commands:

```text
blackcat-project init <name>
blackcat-project status
blackcat-project continue
blackcat-project checkpoint
blackcat-project gates
blackcat-project diff
blackcat-project test
blackcat-project audit
blackcat-project pause
blackcat-project resume
```

A command such as `blackcat-project continue` should mean:

> reconstruct minimal state, continue all permitted work, checkpoint as needed, and stop only at the next real HUMAN_GATE/capability boundary.

## 17. Portable project capsule

A longer-term feature could make a repository itself a portable AI project capsule.

Clone it onto another Black Cat machine, then run:

```text
blackcat-project resume .
```

The new machine reads the compact checkpoint, validates Git state, detects which capabilities are locally available and continues within the same policy.

Machine-specific credentials remain outside the repository. Missing capabilities are reported as capability boundaries rather than silently weakening policy.

## 18. Templates for other people

A reusable Black Cat project template should eventually ship with:

- example `PROJECT_RULES.md`;
- example `AI_AUTONOMY_POLICY.md`;
- compact `AI_WORK_STATE.md` template;
- machine-readable `project-policy.json`;
- lessons and ADR templates;
- context-health calibration guidance;
- safe static checks;
- example Git workflow;
- capability-policy examples;
- a README explaining how to customize human gates.

The first concrete seed is now versioned under `templates/ai-project/`. It already contains the core human policy, compact recovery state, machine policy and durable history templates. The machine-policy contract is described by `schema/ai-project-policy.schema.json`.

This lets another person begin reproducing the automation style without copying Black Cat-specific TPM/USB/security rules, while still making it clear that runtime enforcement belongs to the future broker rather than to Markdown files alone.

## 19. Proposed implementation phases

### M26A — Project template and CLI

Start from the checked-in `templates/ai-project/` seed. Implement `blackcat-project init/status/checkpoint`, schema validation and policy/profile generation with no AI dependency.

### M26B — Deterministic autonomy engine

Parse the project policy and represent AUTO/AUTO_WITH_CHECKS/HUMAN_GATE/FORBIDDEN actions in a machine-readable local state model. Human-readable rules and `project-policy.json` must be checked for policy/profile drift.

### M26C — Sandboxed capability broker

Implement project-scoped filesystem, Git, test/build and network capabilities with no general root shell. Refuse undeclared capabilities by default.

### M26D — Model adapter

Add a model-neutral interface supporting local models first and optional remote providers under explicit authorization. Model replacement must not alter project capability policy.

### M26E — Background/resume worker

Use user-level systemd services/timers for outbound-only continuation, recovery and checkpointing.

### M26F — Desktop project dashboard

Expose state, Context Health, diffs, audit records and exact human gates in the Black Cat UI.

### M26G — Adversarial/negative testing

Test policy bypass attempts, prompt injection from repository content, unsafe shell proposals, credential access, raw-device requests, network escalation, schema/policy mismatch and corrupted work-state recovery.

## 20. Security questions to resolve before implementation

Important design questions for later brainstorming/testing:

- Should generated actions use a declarative capability format instead of arbitrary shell whenever possible?
- How should repository content be labelled as untrusted data so a README cannot grant itself capabilities through prompt injection?
- Should every project execute in its own Linux user, container or namespace?
- How should remote-provider credentials be brokered without exposing them to model prompts or logs?
- What is the safest model for approving temporary network destinations?
- Should a project be able to delegate merge authority, and under what signed policy?
- Can a deterministic verifier independently confirm that an AI-generated action stayed within policy?
- How should the project state be signed or hash-chained if it becomes security-sensitive?
- How should user-level background agents be rate-limited and resource-limited?
- How much audit history should remain local versus committed to Git?

## 21. Relationship to the current Black Cat roadmap

This subsystem is intentionally **after the first usable core prototype**.

The current M13B→M25 security roadmap remains the active priority. The planned M26 milestone captures this future direction so the idea is not lost, but it must not delay the signed-boot, TPM, dm-verity, independent-verifier, negative-test and safe-USB work needed to complete the core product.

The implementation should reuse the autonomy and recovery lessons already proven in project development while applying Black Cat's own security philosophy to the agent runtime itself.

## 22. Design scaffold now baked into the repository

The M26 idea is no longer only prose. The repository contains an implementation-neutral seed that future Black Cat tooling can consume:

```text
templates/ai-project/
├── README.md
├── PROJECT_RULES.md
├── AI_AUTONOMY_POLICY.md
├── AI_WORK_STATE.md
├── project-policy.json
└── docs/
    ├── architecture-decisions.md
    └── lessons-learned.md

schema/
└── ai-project-policy.schema.json
```

The sample `project-policy.json` deliberately starts fail-closed:

- default action class is `HUMAN_GATE`;
- network is disabled;
- inbound listeners are false;
- raw block-device access is false;
- root access is false;
- credential access is `none`;
- secrets/safety-bypass/self-expanding authority are forbidden;
- audit records exclude hidden chain-of-thought.

Future `blackcat-project init` should copy/version this profile, ask only the questions necessary to change its boundaries, validate it against the schema, and require an explicit human decision for any high-impact relaxation.

The future capability broker must treat the machine policy as an enforcement input, not as model prompt decoration. If the model, repository content and machine policy disagree, execution fails closed and surfaces the mismatch rather than letting the model choose the more permissive interpretation.

This design scaffold remains inert in the current Prototype 1 runtime. It records and tests the architecture without adding a daemon, API key, listener, root capability or other new attack surface before M26.