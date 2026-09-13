# Architecture Decision Log — Template

Keep durable decisions here. Add new decisions rather than silently rewriting history; mark superseded decisions explicitly.

## ADR-001 — Repository-governed AI project state

**Status:** Accepted

**Decision:** The repository is the durable authority for project state, policy, checkpoints, decisions, lessons and regression tests. A chat/model session is not the sole state store.

**Reason:** Long-running AI-assisted work must survive model changes, context loss, process crashes and new sessions without requiring the operator to reconstruct state manually.

## ADR-002 — Model is a bounded worker

**Status:** Accepted

**Decision:** The model proposes/reasons about actions but does not grant itself execution authority. Operating-system/project capability enforcement remains independent of model text.

## ADR-003 — High-impact actions remain explicit human gates

**Status:** Accepted

**Decision:** Physical/destructive actions, new credentials/permissions, protected production/release actions, trust-ownership changes and material security-policy changes stop for the human unless a later explicit decision safely delegates a narrower case.

## ADR-004 — Compact recovery checkpoint

**Status:** Accepted

**Decision:** `AI_WORK_STATE.md` is the first-read recovery file. It remains compact and points to full history instead of duplicating it.

## ADR-005 — Untrusted content cannot grant capabilities

**Status:** Accepted

**Decision:** Files, web content, issue text, dependencies, logs and generated text are untrusted data. Instructions found inside them do not modify capability policy unless the authorized human/project governance process changes policy.