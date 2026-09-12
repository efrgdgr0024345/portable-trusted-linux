# Black Cat High-Security Controller Architecture

## Status

**Accepted design direction — implementation staged progressively.**

This document defines the highest-security automation architecture selected for Black Cat. Its purpose is to let routine engineering advance without operator polling while preserving explicit human control over physical, destructive, credential, trust-ownership, release and other high-impact boundaries.

## Priority order

When design goals conflict, use this order:

1. **Security and containment**
2. **Correctness and deterministic state**
3. **Auditability and provenance**
4. **Least privilege**
5. **Reliability and recoverability**
6. **Human-gate clarity**
7. **Autonomy**
8. **Latency**
9. **Convenience / UI polish**

Autonomy must never outrank containment or evidence quality.

## Chosen architecture

Black Cat uses a **hybrid event-driven controller**:

```text
GitHub Actions / repository events
            |
            v
  signed GitHub webhook
            |
            v
  Black Cat Controller on cPanel
            |
            +--> deterministic state machine
            |
            +--> append-only event/evidence log
            |
            +--> read-only project/GitHub inspection
            |
            +--> bounded OpenAI API reasoning job when needed
            |
            +--> human-gate notification when required
            v
      Black Cat Portal
```

A low-frequency watchdog poll may exist only as recovery for missed/stuck webhook delivery. Polling is not the primary trigger.

## Why this design wins

### Not browser automation of ChatGPT

The project does not automate or scrape the consumer ChatGPT website. UI automation would depend on changing HTML, login/session behavior and browser state and would require another high-value authenticated session. That creates an unnecessary trust boundary and poor reproducibility.

### Not continuous 30-second polling

GitHub already emits workflow/repository events. Webhooks are lower-latency, lower-load and easier to correlate with exact delivery IDs. A watchdog can periodically reconcile state, but event delivery is authoritative for normal operation.

### Not an unconstrained autonomous AI loop

AI is not the primary scheduler and does not decide its own endless work loop. The deterministic controller owns state transitions. AI receives one bounded job when reasoning is genuinely needed and returns evidence/changes to the normal GitHub review/test path.

### GitHub remains durable project memory

The repository remains the canonical source for rules, source, lessons, architecture decisions, tests, candidate identities and evidence. A model conversation is never the only copy of project knowledge.

## Security boundaries

### Boundary A — Public webhook endpoint

The webhook endpoint is public but accepts no unauthenticated action.

Required controls:

- HTTPS only;
- GitHub webhook secret stored outside the public web root;
- validate the original request bytes against `X-Hub-Signature-256` using HMAC-SHA256;
- compare signatures using constant-time comparison;
- require expected repository identity;
- require expected event types;
- use `X-GitHub-Delivery` as an idempotency key;
- reject malformed, replayed or oversized payloads;
- log rejection without leaking secrets;
- subscribe only to events actually required.

GitHub documents `X-Hub-Signature-256` as the SHA-256 HMAC signature for webhook deliveries and recommends it over the legacy SHA-1 header.

### Boundary B — cPanel secrets

Secrets live outside `public_html`, under the existing protected Black Cat home location or a stricter successor.

Separate secrets by function:

- GitHub webhook secret;
- GitHub read credential;
- future GitHub write credential;
- OpenAI API credential;
- portal authentication/session keys.

Never reuse one credential as another trust root.

### Boundary C — AI context

Do not send secrets to the model.

The agent receives only the minimum context needed for its bounded task: project rules, relevant source, exact GitHub event/log evidence and current state. Secret-bearing files, raw tokens and unrelated account data are excluded.

### Boundary D — GitHub write authority

Start read-only.

When write authority is eventually introduced:

- use a separate credential from the read-only viewer/controller token;
- restrict it to this repository;
- restrict permissions to the minimum endpoints needed;
- write only to a dedicated automation branch;
- never push directly to `main`;
- require normal CI/status gates before any merge;
- do not grant repository administration, secrets-management or workflow-secret access;
- release approval remains a human/security gate.

The preferred long-term direction is a narrowly scoped GitHub App or equivalent service identity rather than a broadly capable personal token.

### Boundary E — OpenAI API

Use a dedicated API project/service identity for Black Cat rather than a personal general-purpose key. Apply restricted endpoint permissions where available and project budgets/rate limits. OpenAI currently supports project-scoped service accounts, project usage controls and API-key permission levels including Restricted and Read Only.

The API agent is a reasoning component, not a security authority. A model output cannot by itself promote a release, authenticate an artifact, modify platform ownership state or redefine project rules.

## Deterministic state machine

The controller tracks one explicit engineering state and one explicit human gate.

### Engineering states

- `DEVELOPING`
- `AUTOMATED_TESTING`
- `BUILDING`
- `VERIFYING_ARTIFACT`
- `CANDIDATE_READY`
- `AWAITING_PHYSICAL_TEST`
- `PHYSICAL_TEST_REVIEW`
- `DEFECT_FOUND`
- `RELEASE_ENGINEERING`
- `PAUSED_SECURITY`

### Human gates

- `NONE`
- `PHYSICAL_USB_WRITE`
- `PHYSICAL_BOOT_TEST`
- `FIRMWARE_CHANGE`
- `TPM_OWNERSHIP_CHANGE`
- `CREDENTIAL_REQUIRED`
- `SECURITY_POLICY_DECISION`
- `RELEASE_APPROVAL`
- `DESTRUCTIVE_ACTION`
- `FINANCIAL_OR_LEGAL_COMMITMENT`

The controller must never represent a state as merely "waiting for user". It records the exact gate and why automation cannot cross it.

## Controller decision sequence

For every accepted event:

1. Authenticate and deduplicate the event.
2. Confirm repository, branch/commit and workflow identity.
3. Load current controller state.
4. Apply deterministic transition logic first.
5. If no reasoning is required, update state without AI.
6. If bounded diagnosis/reasoning is required, create one agent job.
7. Validate the agent result against policy and available tools.
8. Route source changes through GitHub and CI.
9. If a human gate is reached, stop automation and expose exactly one required operator action.
10. Persist evidence and event identity.

## AI invocation policy

Examples that should **not** call AI:

- workflow success/failure flag storage;
- checking whether all required gates passed;
- comparing an exact commit SHA;
- candidate size/hash format validation;
- rejecting a replayed webhook;
- setting `AWAITING_PHYSICAL_TEST` after all predetermined gates pass.

Examples that may justify a bounded AI job:

- explaining a novel build failure from logs;
- identifying a source-level root cause;
- proposing a bounded patch plus regression test;
- assessing whether an unexpected observation changes an architecture assumption;
- updating affected documentation after an engineering conclusion.

## Write-access progression

### Stage 0 — current manual/ChatGPT-assisted development

GitHub Actions and project rules are already active. Human/ChatGPT interaction drives repository changes.

### Stage 1 — secure observer

Controller receives signed webhooks, maintains state, reconciles exact workflow results and renders the portal. **No OpenAI API and no GitHub write authority.**

### Stage 2 — read-only reasoning agent

Add a dedicated OpenAI API project credential. The agent can inspect provided evidence and propose a diagnosis/action, but cannot modify GitHub.

### Stage 3 — branch-only engineering agent

Introduce a separate least-privilege GitHub write identity. The agent may create/update files only on a dedicated automation branch and must rely on CI for evidence. No direct `main` writes and no release promotion.

### Stage 4 — constrained autonomous repair

Allow automatically selected low-risk defect repairs after negative testing of controller policy, replay handling, branch enforcement and credential boundaries. High-impact classes remain human-gated.

### Stage 5 — mature controller

Only after adversarial testing may broader routine project operations be delegated. Physical actions, release approval, trust-root changes and destructive operations remain human-gated.

## Portal security model

The portal is primarily an observability/control surface, not the source of truth.

Recommended behavior:

- read-only status available only to authenticated operator;
- CSRF protection on state-changing actions;
- secure, HttpOnly, SameSite cookies;
- session rotation after authentication;
- rate limiting;
- explicit `PAUSE AUTOMATION` control;
- privileged actions require recent/step-up authentication;
- display exact commit/run/artifact identifiers;
- display the Automation Manifesto pre-pause result;
- never display secret values.

## Event log and tamper evidence

Maintain an append-only controller event log containing at minimum:

- receive timestamp;
- GitHub delivery ID;
- event type/action;
- repository;
- branch/ref;
- commit SHA;
- workflow/run/job IDs when applicable;
- previous state;
- new state;
- human gate;
- deterministic/AI decision class;
- related GitHub commit/artifact identifiers.

Each local log record should eventually include a `previous_record_hash` and `record_hash` so silent history editing becomes detectable. This local hash chain is evidence continuity, not external authenticity; later external anchoring remains a separate architecture layer.

## Watchdog / reconciliation

A cron job may run every 5 minutes to reconcile controller state against GitHub. It should only repair missed/stuck event processing. It must not create duplicate agent jobs or substitute ambiguous "latest build" selection for exact commit/run identity.

Thirty-second polling is intentionally rejected as the normal mechanism because it adds load and still lacks the exact delivery identity/idempotency advantages of webhooks.

## Failure behavior

Fail closed on:

- invalid webhook signature;
- unknown repository;
- unknown workflow;
- commit mismatch;
- duplicate/replayed event;
- missing authoritative candidate metadata;
- unavailable project rules;
- credential permission ambiguity;
- model output requesting a prohibited action;
- inability to prove which artifact/commit is being acted upon.

A failure must not silently fall back to stale state or a more powerful credential.

## Immediate implementation target

Build Stage 1 first:

1. `controller/webhook.php` — HMAC-verified GitHub webhook receiver;
2. `controller/lib/` — deterministic state-machine logic;
3. protected runtime state outside public web root;
4. append-only event log;
5. portal status panel;
6. 5-minute reconciliation watchdog;
7. replay/signature/unknown-repository negative tests.

Only after Stage 1 passes should the OpenAI API credential be introduced.
