# Black Cat Console

Black Cat Console is the mobile-first project and AI control interface for the Portable Trusted Linux / Black Cat project.

## Purpose

The Console is designed to help a user:

- see active projects and their current state;
- speak or type natural instructions from a phone;
- have an AI coach improve those instructions before sending them;
- understand why the improved instruction is better;
- preserve project goals, decisions, lessons, plans, evidence and backlog;
- route routine work to economical models and reserve high-cost reasoning for deliberate reviews;
- expose what project information would leave the local system before a remote AI call;
- remain portable so the same core can run first on a remote Black Cat Core server and later locally from the USB system.

## Architectural rule

Local project state is primary. Remote services are optional extensions.

The Console must not require the remote server to remain useful once the Black Cat USB system can host the core locally.

## MVP

The first milestone proves this loop:

1. Open the Console on a phone.
2. Choose a project.
3. Speak or type naturally.
4. Convert the input into Goal / Current State / Constraints / Success.
5. Show an improved prompt and a short coaching explanation.
6. Let the user approve, edit, or send the original.
7. Send approved input to the configured AI provider.
8. Save useful output into the project's durable state.

## Proposed runtime

Initial remote prototype: existing `blackcat-chatbot-01` DigitalOcean droplet.

Later target: Black Cat USB/local runtime using the same project-state format and core service boundaries.

## Data model

Human-readable project state lives in Markdown. Operational metadata may use SQLite.

Suggested project directory:

```text
projects/<project-id>/
  PROJECT.md
  STATE.md
  PLAN.md
  DECISIONS.md
  LESSONS_LEARNED.md
  EVIDENCE.md
  BACKLOG.md
  AI_RULES.md
```

## Security boundaries

- API keys never enter browser JavaScript or public repository files.
- Secrets are read from server environment/config outside the web root.
- A remote-AI request must be inspectable before sending.
- Deep-review mode is read-only by default.
- Project data must be separable by project.
- The Console must distinguish local facts, user-observed evidence, model inference and unverified claims.

## Status

Architecture/MVP scaffold created. Deployment and live API credentials are not yet configured.
