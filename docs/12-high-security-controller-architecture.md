# Black Cat High-Security Automation Architecture

## Status

**Superseded before deployment — zero-new-inbound-surface policy is now the default.**

The earlier controller proposal in this file explored a cPanel-hosted GitHub webhook receiver and optional OpenAI API reasoning worker. That design was never deployed. After security review, the project rejected adding a new public listener merely to reduce operator polling.

## Current priority order

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

## Current architecture direction

Black Cat must not create a network service merely to improve automation convenience.

The default automation path is:

```text
GitHub repository + GitHub Actions
            |
            v
 exact workflow / commit / artifact evidence
            |
            v
 deterministic project rules and state interpretation
            |
            +--> safe repository engineering while an authorised agent is active
            |
            +--> explicit human gate only when required
            v
      existing project viewer
```

No new inbound webhook, public controller endpoint, browser automation of ChatGPT, or OpenAI API credential is part of the current implementation.

If unattended reconciliation becomes genuinely necessary, the preferred next step is an **outbound-only watchdog** running outside `public_html`. It may periodically query GitHub for exact workflow/commit state. It must not listen for inbound network requests and must not expose another public service.

## Security rule

**Automation must reduce human friction without increasing externally reachable attack surface unless a demonstrated security benefit outweighs the new exposure.**

Every proposed listener, writable endpoint, credential, API integration or privileged service must therefore answer:

- what security or reliability benefit it provides;
- what new attack surface it creates;
- why existing GitHub Actions or an outbound-only mechanism cannot provide the same result;
- how privilege is bounded;
- how failure is detected and contained;
- how the component is removed or disabled safely.

If those questions do not produce a compelling answer, do not deploy the component.

## GitHub remains durable project memory

The private repository remains canonical for project rules, source, lessons, architecture decisions, tests, candidate identities and evidence. A chat session, local cache or controller database must never be the only authoritative copy.

## Deterministic state model may remain conceptual

The existing `controller/state-machine.json` is retained as a **non-networked conceptual state model**. Keeping a deterministic vocabulary for states and human gates does not require deploying a controller service.

It may be used to describe states such as:

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

Human gates remain explicit, including physical USB writing, physical boot tests, firmware or TPM ownership changes, credentials, destructive operations, material security-policy choices and release approval.

## Current implementation boundaries

### Existing GitHub Actions

Continue using the existing repository-scoped Actions workflows and self-hosted builder. They already provide an auditable control path without adding another Internet-facing application.

### Existing cPanel viewer

The project viewer is read-only. It accepts one repository-read credential from `/home/learning/.blackcat/github_token`, outside the web root, and fails closed if the credential or GitHub data is unavailable. It must not become an automation command endpoint.

### Outbound-only watchdog — optional future step

If future unattended monitoring is justified, it should:

- run as a scheduled local process outside `public_html`;
- make outbound HTTPS requests to GitHub only;
- use read-only repository permissions unless a separately reviewed change requires more;
- track exact commit/run/artifact identities rather than ambiguous "latest" state;
- perform deterministic checks first;
- avoid storing signing keys or trust roots;
- notify rather than silently crossing human gates.

### AI API integration — not currently approved

Introducing an OpenAI API credential adds another credential and trust boundary. It is therefore not part of the current automation baseline. If reconsidered later, it requires a separate security decision and least-privilege review.

### Browser automation — rejected

Do not automate or scrape the consumer ChatGPT web session. It would create a fragile high-value authenticated-session dependency and is not an acceptable project control plane.

## Failure behavior

Fail closed on ambiguous artifact identity, unavailable project rules, unavailable authoritative GitHub data, credential-permission ambiguity, or inability to prove which commit/run/artifact is being acted upon.

Do not silently substitute stale cache data for live authoritative candidate/release state.

## Historical proposal retained for auditability

The superseded proposal would have used signed GitHub webhooks into a cPanel controller, deterministic state transitions, an append-only event log, an optional bounded OpenAI API worker, and a portal. The design included HMAC verification and replay controls, but it was intentionally stopped before deployment because the additional public listener and credentials increased attack surface without a demonstrated security benefit at the current project stage.

No webhook endpoint, public controller, OpenAI API key, or autonomous GitHub-writing service was deployed as part of that proposal.
