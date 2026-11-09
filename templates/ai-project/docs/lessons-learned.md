# Lessons Learned — Template

Record meaningful failures, surprises, corrected assumptions and workflow improvements. Do not use this file for routine status chatter.

For each lesson capture:

## YYYY-MM-DD — Short lesson title

### What was attempted
[Describe the intended action or experiment.]

### What happened
[Record the observed outcome.]

### Why it matters
[Explain the practical engineering/security consequence.]

### Decision / change
[State what changes from now on.]

### Evidence classification
[OBSERVED / VERIFIED / DESIGN DECISION / POLICY / UNKNOWN / etc.]

---

## Seed lesson — Chat state is not project state

### What was attempted
Use AI assistance across a long-running project without requiring the human to repeatedly reconstruct prior work.

### What happened
Conversation context is finite and sessions can end or lose continuity.

### Why it matters
A project that relies on one chat window for memory eventually becomes fragile and difficult to audit.

### Decision / change
Store durable project state in the repository, keep a compact recovery checkpoint, and load larger history selectively.

### Evidence classification
**PROJECT OPERATING DESIGN.**