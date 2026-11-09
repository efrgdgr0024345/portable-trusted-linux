# Context Health Calibration Log

This is the empirical calibration record for the AI continuity/context-pressure heuristic described in `docs/13-ai-continuity-and-context-recovery.md`.

The goal is to learn from real conversation dropouts and continuity failures over time. It does **not** claim access to an exact ChatGPT context-window percentage.

## Logging rules

Record a new entry when one of these occurs:

- a confirmed conversation/session dropout;
- earlier project state is materially forgotten or contradicted;
- the model asks for important information that the current checkpoint/repository already contains;
- recovery required re-reading repository state that should have been available from the compact checkpoint;
- an AMBER/RED prediction clearly proved too early or too late;
- a context-health threshold or signal weight is deliberately changed.

Use only observable facts. If a count was not tracked, write `unknown`; do not reconstruct a false precision after the event.

## Event fields

For each event record:

- date/time if known;
- checkpoint revision;
- estimated pressure before the event (GREEN/AMBER/RED/unknown);
- turns since checkpoint if known;
- large document/log reads if known;
- tool-heavy operations if known;
- large pasted logs/files if known;
- continuity symptom;
- consequence;
- recovery action;
- prediction quality (too early / useful / too late / unknown);
- rule/threshold adjustment, if any.

## Calibration history

### 2026-09-13 — v0.1 baseline established

- Type: POLICY BASELINE, not a dropout event
- Checkpoint revision: 1
- Estimated pressure: GREEN immediately after checkpoint
- Turns since checkpoint: 0
- Large document/log reads: 0
- Tool-heavy operations: 0
- Large pasted logs/files: 0
- Continuity symptom: none
- Consequence: none
- Recovery action: none
- Prediction quality: unknown; insufficient empirical events
- Adjustment: initial GREEN/AMBER/RED model adopted as provisional only

### Current provisional heuristic

A simple optional starting score is documented in `docs/13-ai-continuity-and-context-recovery.md`:

- +1 per 5 ordinary turns since checkpoint;
- +2 per large document/log read;
- +1 per tool-heavy turn with several substantive results;
- +2 per very large pasted log/code block;
- +4 per mild continuity symptom;
- confirmed state-loss/contradiction => RED.

Provisional thresholds:

- 0–5: GREEN
- 6–11: AMBER
- 12+: RED

These values are intentionally not described as token percentages or model limits. Replace them when real project evidence supports a better estimate.

## What improvement looks like

As real entries accumulate, look for repeatable patterns such as:

- typical number of large reads before continuity symptoms;
- whether tool-heavy turns matter more than ordinary conversational turns;
- whether large pasted logs are stronger predictors than turn count;
- how often AMBER checkpoints prevent a later expensive recovery;
- whether RED is usually triggered too late;
- whether a compact checkpoint reliably restores the project in one selective-read cycle.

The desired outcome is not a perfect context-window predictor. The desired outcome is a project-specific warning system that makes chat loss cheap and increasingly predictable.