# Black Cat AI Cost Accounting

## Goal

Black Cat should increase useful work without hiding or normalising unnecessary model spend.

Every paid AI call should produce two distinct records:

1. **provider usage evidence** — model and raw token counts returned by the provider;
2. **estimated cost** — a calculation made from a versioned pricing table.

The raw usage record is the durable fact. The dollar value is a recalculable estimate.

## Current OpenAI text-token reference rates

Pricing reference date: **2026-09-16**.

| Model | Input / 1M | Cached input / 1M | Output / 1M |
|---|---:|---:|---:|
| GPT-5.6 Luna | $0.20 | $0.02 | $1.20 |
| GPT-5.6 Terra | $2.00 | $0.20 | $12.00 |
| GPT-5.6 Sol | $4.00 | $0.40 | $20.00 |
| GPT-6 Astra | $10.00 | $1.00 | $50.00 |

These rates can change. Tool calls and other modalities may have separate charges. GPT-5.6 long-context requests above the provider threshold may also use higher multipliers. The Console therefore labels its dollar figures **estimated API spend** rather than billing truth.

## Usage record

The MVP writes successful remote-AI calls to private `data/api-usage.jsonl` storage. Each record contains, where returned by the provider:

- timestamp;
- project ID;
- task type;
- provider;
- actual/returned model;
- requested model;
- input tokens;
- uncached input tokens;
- cached input tokens;
- output tokens;
- reasoning tokens;
- total tokens;
- estimated USD cost;
- pricing-table version;
- whether a long-context multiplier was applied;
- response ID.

The usage file is runtime-private state and must not be committed to Git.

## Dashboard

The mobile Console displays:

- estimated spend today;
- estimated spend this month;
- monthly tokens;
- monthly planning budget and percentage used;
- project-specific lifetime API usage;
- model-by-model usage;
- recent paid calls;
- per-call cost after coaching/chat requests.

`BLACKCAT_MONTHLY_API_BUDGET` sets the displayed monthly planning budget. It defaults to USD $30 in the MVP.

The initial budget is a **soft planning control**, not a provider-side billing limit. A later hard-budget router should refuse or downgrade discretionary requests before a configured Black Cat limit is exceeded.

## Routing principle

Prefer the lowest-cost path that can reliably complete the task:

```text
Deterministic/local operation
        ↓ if reasoning needed
Local model / Luna
        ↓ if more capability needed
Terra
        ↓
Sol
        ↓ only when justified
Astra / deep review
```

Human-led work that is already covered by a ChatGPT subscription should not automatically be duplicated as metered API traffic merely because Black Cat can call an API.

## Future accounting

Later versions should add:

- API tool-call charges;
- transcription/audio usage;
- image generation usage;
- browser-computer charges;
- local-model compute estimates;
- subscription/credit usage where provider data is available;
- cost per project milestone;
- cost per successful task;
- savings from prompt caching;
- router recommendations such as `use subscription`, `use Luna`, or `deep review justified`;
- hard daily/monthly safety ceilings.

## Evidence status

**IMPLEMENTED IN SOURCE / NOT YET DEPLOYMENT-VERIFIED.**

The repository contains the token and estimated-cost accounting logic and dashboard UI, but no claim is made yet that it has captured a real API billing event on `blackcat-chatbot-01`.
