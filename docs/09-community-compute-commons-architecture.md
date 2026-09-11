# Community Compute Commons — Architecture v0.1

**Status:** Future subsystem / research track  
**Relationship to Prototype 1:** Must not weaken or delay the core Portable Trusted Linux inspection trust chain.

## 1. Purpose

Community Compute Commons (CCC) is an opt-in distributed-compute mode intended to turn otherwise idle consumer CPU/GPU/NPU capacity into a shared resource for approved AI workloads, public-interest research, education and open-model access.

CCC is not the same security mode as Portable Trusted Linux Secure Inspection Mode. It intentionally enables network access and accepts remotely scheduled workloads, so it has a larger attack surface and a different trust policy.

## 2. Mandatory separation

The pre-OS boot page may offer the choice, but the actual worker must run only after a signed minimal operating environment has booted.

Recommended menu:

```text
Portable Trusted Linux

1. Secure Inspection Mode
   - network off during initial verification
   - trusted personal/security workflow
   - no community compute jobs

2. Community Compute Commons
   - explicitly donated compute
   - separate signed worker UKI/root
   - network enabled only after worker isolation

3. Diagnostics
```

A running CCC worker must not transition into Secure Inspection Mode without a complete reboot.

## 3. Why the computation is not literally pre-OS

Pre-OS/firmware code is the wrong place to execute LLM workloads. Modern accelerators require complex drivers, memory management, networking, model runtimes and sandboxing. Adding those components to the trusted pre-OS environment would massively expand the trusted computing base.

The pre-OS page therefore performs one job: **informed mode selection and consent**.

## 4. Proposed worker trust chain

```text
User selects Community Compute Commons
        ↓
Signed CCC UKI authenticated
        ↓
Minimal read-only Linux root verified
        ↓
Internal disks remain unmounted/inaccessible
        ↓
Ephemeral RAM-backed worker state
        ↓
Hardware capability / thermal / power checks
        ↓
Network enabled
        ↓
Scheduler identity authenticated
        ↓
Signed approved workload manifest
        ↓
Sandboxed model/task runtime
        ↓
Result returned
        ↓
Result verification / redundant sampling
        ↓
Signed contribution receipt
        ↓
Ephemeral shutdown
```

## 5. Worker resource classes

### Class A — phone companion workers

Not part of the PC pre-OS boot flow. Join through a signed mobile client.

Default constraints:

- charging only;
- Wi-Fi only unless metered use explicitly approved;
- thermal/battery thresholds;
- small models and independent jobs;
- immediate owner-preemption.

### Class B — low-power laptops / integrated graphics

Good candidates for:

- embeddings;
- reranking;
- smaller quantised models;
- evaluation;
- batch text generation;
- CPU/iGPU/NPU-compatible jobs.

### Class C — AI-PC NPU workers

Modern 40+ TOPS NPU machines may run efficient on-device inference where supported by the runtime and model format.

### Class D — discrete-GPU desktops/workstations

Potentially suitable for larger quantised models, high-throughput generation, fine-tuning and small groups of model-parallel workers.

### Class E — institutional/community mini-clusters

Schools, universities, libraries, businesses and labs may contribute stable groups of machines with better networking and predictable availability.

## 6. Scheduler principles

The scheduler should optimize for **useful work completed**, not raw device-hours.

Inputs include:

- hardware accelerator type;
- RAM/VRAM;
- measured historical throughput;
- thermal headroom;
- power policy;
- network class;
- cached model shards;
- current reliability score;
- workload privacy class;
- geographic/regulatory constraints where required.

The scheduler should prefer whole-model/request-level routing whenever a model fits on one device. Internet-wide layer-by-layer model parallelism is a secondary technique because every token then depends on network latency and worker availability.

## 7. Workload format

CCC must not provide an arbitrary remote shell.

Every job should be described by a signed manifest containing at minimum:

- job ID;
- scheduler/project identity;
- model/content digest;
- approved runtime identifier;
- input digest/reference;
- output contract;
- resource limits;
- network permissions;
- timeout;
- privacy classification;
- redundancy/verification policy;
- contribution-credit policy.

Unknown or unsigned jobs are refused.

## 8. Sandboxing

The first worker runtime should expose the smallest practical execution surface. Candidate isolation technologies can include containers with strong kernel policy, WASM/WASI for compatible workloads, or microVMs on machines with suitable virtualization support.

No single sandbox technology should be assumed universal until real accelerator pass-through, driver and portability testing is complete.

Worker jobs should have no access to:

- host internal disks;
- Secure Inspection data;
- TPM private objects unrelated to CCC;
- arbitrary USB peripherals;
- local LAN by default;
- firmware update interfaces;
- persistent credentials.

## 9. Untrusted-worker result verification

Volunteer workers are not trusted merely because they boot the project software. Hardware can be faulty or malicious and the user controls the physical machine.

Possible result-integrity controls:

- randomly duplicate a percentage of jobs;
- deterministic test jobs hidden among normal work;
- compare outputs where deterministic inference is expected;
- hash model/runtime artifacts;
- worker reputation based on accepted work;
- signed receipts from the scheduler only after validation;
- stronger attestation on compatible platforms as an optional signal, not universal proof.

For stochastic generation, verification may require seeded deterministic decoding, task-specific validators or evaluator redundancy rather than exact-text equality.

## 10. Prompt and model privacy

Transport encryption does not stop the worker from seeing data needed for computation.

Initial CCC policy should therefore allow only workload classes explicitly approved for untrusted volunteer execution, such as public datasets, public prompts, open science and non-confidential generation.

Private personal prompts should remain on trusted local/cloud infrastructure until a separately reviewed confidential-compute design exists.

Open-weight models are the default because distributing proprietary model weights to arbitrary volunteer machines creates extraction risk.

## 11. Contribution accounting

The word “free” refers only to avoiding new central compute capital for the recipient/platform. Donor electricity and device wear are real costs.

CCC should support:

- voluntary donation with no reward;
- compute credits based on verified useful work;
- donation of earned credits to schools/non-profits/users;
- public-interest reserved capacity;
- transparent per-device energy estimates where measurable;
- no cryptocurrency dependency.

Credits must not be awarded solely for claimed uptime. They should be based on accepted, verified work.

## 12. User controls

Before enabling CCC, the user should be able to set:

- CPU/GPU/NPU percentage or power limit;
- maximum temperature;
- laptop/phone must be on AC power;
- permitted hours;
- bandwidth cap;
- Wi-Fi-only / unmetered-only;
- maximum downloaded model storage;
- task categories;
- whether credits are kept or donated;
- immediate pause/exit behavior.

## 13. Privacy-minimising identity

The project should avoid publishing stable hardware serial numbers, TPM identifiers or other unnecessary cross-session identifiers.

Contribution accounting can use project-generated pseudonymous keys. Hardware-backed attestation may be used selectively but requires a privacy review because stable attestation identities can create linkability.

## 14. Network architecture

Early architecture:

```text
Coordinator / scheduler
      ↓
Signed job manifests
      ↓
Regional relay/cache layer
      ↓
Volunteer workers
      ↓
Result uploads
      ↓
Verification / duplicate sampling
      ↓
Accepted-result store + signed credit receipt
```

Large model files should use content-addressed caching so workers do not repeatedly download identical weights.

A later design may add peer-assisted distribution, but peer-to-peer networking must be reviewed carefully because it expands abuse, privacy and NAT/firewall complexity.

## 15. Abuse prevention

CCC capacity must not become an anonymous general-purpose compute rental layer.

At minimum:

- only approved model/runtime hashes;
- workload-policy enforcement by the coordinator;
- rate limits;
- auditable scheduler decisions;
- abuse reporting;
- capability to revoke signing identities/workload catalog entries;
- no raw arbitrary binaries from requesters directly to workers.

## 16. Pilot sequence

### Pilot 0 — local heterogeneous lab

5–10 machines on one network. Establish worker packaging, metrics, job verification and preemption.

### Pilot 1 — small Internet volunteer test

10–100 machines. Measure churn, bandwidth, model caching, tokens/jobs per watt-hour and duplicate-result consistency.

### Pilot 2 — community organisations

Hundreds to thousands of devices with schools, universities, libraries or non-profits. Test fair credit allocation and predictable workload pools.

### Pilot 3 — public opt-in

Only after security review, code signing, workload governance, abuse controls and transparent energy policies are mature.

## 17. Success metrics

Do not measure success using raw TOPS alone.

Preferred metrics:

- accepted tokens/s by model class;
- accepted jobs/hour;
- useful tokens or jobs per Wh;
- scheduler overhead;
- network bytes per accepted job;
- worker availability distribution;
- result-rejection rate;
- thermal/preemption rate;
- proportion of capacity delivered to public-interest recipients;
- monetary value of compute delivered relative to donor energy cost.

## 18. Project boundary

CCC is a future extension. Portable Trusted Linux Prototype 1 remains focused on demonstrating the core verified/attested USB trust chain.

The CCC architecture may be designed now, but implementation begins only after the inspection environment's security gates pass.
