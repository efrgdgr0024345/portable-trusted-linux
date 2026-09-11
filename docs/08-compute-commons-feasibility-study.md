# Community Compute Commons — Feasibility Study

**Research status:** Deep-research baseline  
**Date:** 2026-09-11  
**Project:** Portable Trusted Linux  
**Concept:** An explicitly opt-in mode that lets people donate otherwise idle PC/laptop resources—and, through a separate companion client, smartphone resources—to a public/community LLM compute pool.

## Executive conclusion

The idea is technically plausible and potentially very large, but the useful comparison is not simply “billions of consumer devices versus millions of data-centre GPUs.” A heterogeneous volunteer network and an AI hyperscale data centre are different kinds of machines.

A defensible reference estimate is that the world has roughly **2 billion active PCs** and at least **4.4 billion people using their own smartphone for mobile internet**. If 30% of each addressable pool opted in, that corresponds to about **600 million PCs** and **1.32 billion smartphones**, or approximately **1.92 billion devices** before excluding unsuitable hardware.[1][2]

Using the current notebook/desktop shipment mix as a rough proxy for the installed PC base, roughly 71% of the 600 million opted-in PCs would be laptops and 29% desktops: about **426 million laptops** and **174 million desktops**. This split is an analytical approximation, not a measured installed-base statistic.[3]

The strict Portable Trusted Linux **pre-OS** concept can directly cover only PCs/laptops. Smartphones cannot practically run the same pre-OS flow; they would join through a separate signed worker application or future trusted mobile client. Therefore the direct pre-OS addressable pool at 30% adoption is closer to **450–600 million PCs**, depending on whether one uses conservative or upper installed-base estimates. Adding smartphones creates the broader **1.8–2.1 billion device** compute-commons envelope.

At full simultaneous load, that many consumer devices could draw tens of gigawatts. In a deliberately simple reference power model—25 W incremental compute load per laptop, 100 W per desktop, 4 W per charging smartphone—the entire 30%-adoption pool would draw about **33.3 GW**. More realistic simultaneous-availability assumptions of 5%, 10%, 25% and 50% of enrolled devices correspond to roughly **1.7 GW, 3.3 GW, 8.3 GW and 16.7 GW** respectively. These are scenario assumptions, not observed averages.

That is physically meaningful scale. OpenAI says its Stargate programme has already surpassed its original 10 GW infrastructure target; Meta is building a 1 GW Prometheus cluster and says Hyperion begins coming online in 2028 with capacity to scale to 5 GW; Microsoft has described Fairwater as scaling to 2 GW and has said its overall data-centre footprint is on track to roughly double over two years.[4][5][6]

However, **equal electrical power does not mean equal AI capability**. Hyperscale clusters concentrate modern accelerators, high-bandwidth memory and extremely fast networks. Meta reports a 72-GPU GB200 pod at roughly 140 kW producing about 360 PFLOPS of FP16 compute.[5] A geographically scattered consumer network cannot reproduce that tightly coupled training fabric. It can nevertheless be extremely valuable for workloads that split into independent or loosely coupled jobs: small/medium-model inference, batch generation, embeddings, reranking, evaluation, synthetic-data generation, some parameter-efficient fine-tuning, and research workloads.

The core recommendation is therefore to add the idea to Portable Trusted Linux as a **separate future boot profile called Community Compute Commons**, not as a feature inside the trusted inspection session. The pre-OS menu may collect user consent, but actual compute should begin only after a minimal signed Linux worker environment has booted and isolated itself from local disks. Secure Inspection Mode and Community Compute Mode should require separate boots and separate security policies.

## 1. The social objective

The motivating idea is to reduce the concentration of useful AI compute in organisations able to finance multi-gigawatt infrastructure. Instead of treating idle consumer hardware as wasted capacity, people could voluntarily contribute a portion of their existing hardware to a shared pool whose output is allocated to education, public-interest research, open models, low-income users, community organisations or other approved uses.

This is not unprecedented as a computing model. BOINC defines volunteer computing as the use of consumer devices—including desktops, laptops, tablets and smartphones—for high-throughput computing. It explicitly assumes heterogeneous, sporadically available and untrusted worker nodes.[7] Folding@home demonstrated that public volunteer participation can produce extraordinary aggregate scale; during the 2020 COVID surge it crossed the exaFLOP threshold and later reported a peak figure of 2.43 exaFLOPS.[8]

The novel part here is combining a volunteer-compute model with a **measured, disposable, security-oriented boot environment** and applying it specifically to modern AI workloads.

## 2. How many people and devices could theoretically participate?

### 2.1 Internet population sanity check

ITU estimates approximately **6.0 billion people were online in 2025**, around 74% of the world population.[9] Thirty percent of that online population is **1.8 billion people**.

That is a useful sanity check: a 30% participation assumption implies a human community on the order of 1.8 billion people, not 30% of all hardware ever manufactured.

### 2.2 PC/laptop pool

Deloitte's 2025 technology outlook uses an estimated global installed base of about **2 billion PCs**.[1] Microsoft had already reported more than **1.4 billion monthly active Windows 10/11 devices** in 2022, which provides an independent lower-bound scale for one major PC platform.[10]

Because installed-base estimates vary by definition, this study uses a range of **1.5–2.0 billion active PCs** and a reference case of 2.0 billion.

At 30% participation:

| PC assumption | Enrolled PCs |
|---|---:|
| 1.5 billion installed base | 450 million |
| 1.75 billion installed base | 525 million |
| 2.0 billion installed base | 600 million |

IDC's Q1 2026 worldwide PC tracker shows notebook volumes at roughly 71–72% of notebook-plus-desktop shipments in recent quarters and forecasts a similar balance through the later 2020s.[3] Using **71% notebooks / 29% desktops only as a proxy** gives, in the 600-million reference case:

- laptops: ~426 million;
- desktops/workstations: ~174 million.

The actual installed base may differ because desktops and laptops have different replacement lifetimes.

### 2.3 Smartphone pool

GSMA reports that **4.4 billion people were using their own smartphone to access mobile internet at the end of 2024**, up by 250 million in one year.[2] Counterpoint reports that the active smartphone installed base continued growing in 2025.[11]

Using 4.4 billion as a deliberately conservative measurable floor, 30% participation is **1.32 billion smartphones**. A 2026 practical range of roughly 4.4–5.0 billion compute-capable active smartphones would imply **1.32–1.50 billion** enrolled phones at 30% adoption.

### 2.4 Combined addressable envelope

| Device class | Conservative 30% | Reference 30% | Upper planning case |
|---|---:|---:|---:|
| PCs/laptops | 450M | 600M | 600M |
| Smartphones | 1.32B | 1.32B | 1.50B |
| **Total** | **1.77B** | **1.92B** | **2.10B** |

This is the enrolled-device envelope, **not** the number that would be online and computing simultaneously.

## 3. Simultaneous availability matters more than enrolment

Volunteer hardware is intermittent. A laptop may be off, in use, on battery, thermally limited or disconnected. A phone may be on cellular data, too warm, low on battery, or needed by its owner. BOINC's architecture explicitly treats workers as sporadically available and heterogeneous.[7]

A useful model therefore separates **enrolment** from **concurrency**.

### Reference power assumptions

The following are modelling assumptions, not sourced global averages:

- opted-in laptop incremental compute draw: 25 W;
- opted-in desktop incremental compute draw: 100 W;
- opted-in smartphone incremental compute draw: 4 W, only while charging/thermally safe;
- enrolled reference pool: 426M laptops + 174M desktops + 1.32B smartphones.

If every enrolled device computed simultaneously, incremental load would be approximately **33.33 GW**.

| Share of enrolled pool simultaneously computing | Active devices | Approx. load | Annual energy if sustained |
|---|---:|---:|---:|
| 5% | 96M | 1.67 GW | 14.6 TWh |
| 10% | 192M | 3.33 GW | 29.2 TWh |
| 25% | 480M | 8.33 GW | 73.0 TWh |
| 50% | 960M | 16.67 GW | 146.0 TWh |
| 100% | 1.92B | 33.33 GW | 292.0 TWh |

The 5–25% concurrency range is a better early planning envelope than assuming all volunteers are available at once. A real pilot must measure actual availability rather than treating these scenarios as forecasts.

### “Free” compute is not free energy

At 10% concurrency, 29.2 TWh/year of electricity is significant. At an illustrative electricity cost of US$0.10–0.20/kWh, that corresponds to roughly **US$2.9–5.8 billion per year** borne by whoever pays the volunteer devices' electricity bills. At 25% concurrency, the illustrative cost becomes roughly **US$7.3–14.6 billion/year**.

Therefore the project must never market volunteered compute as costless. The infrastructure capital already exists, but electricity, data, thermal stress, battery cycles and hardware depreciation still have owners.

## 4. Comparison with planned hyperscale AI infrastructure

### 4.1 Named projects

The following numbers are headline infrastructure/capacity figures and are not perfectly comparable because companies report “capacity,” “secured capacity,” “cluster capacity,” GPU counts and timelines differently.

| Programme | Publicly stated scale | Timing/context |
|---|---:|---|
| OpenAI Stargate | >10 GW secured/planned infrastructure | Original target was 10 GW by 2029; OpenAI said in 2026 it had already surpassed that milestone in secured capacity.[4] |
| Meta Prometheus | 1 GW | Meta says the cluster is underway.[5] |
| Meta Hyperion | up to 5 GW | Expected to begin coming online in 2028; 5 GW is eventual scale, not necessarily day-one capacity.[5] |
| Microsoft Fairwater | up to 2 GW for Wisconsin site | Microsoft said Fairwater would scale to 2 GW and that its overall data-centre footprint was expected to roughly double over two years.[6] |
| Anthropic + AWS expansion | up to 5 GW new compute | Long-term agreement; Anthropic said nearly 1 GW total Trainium2/3 capacity was expected by end-2026.[12] |
| xAI Colossus | 200k H100-class cluster, roadmap to 1M GPUs | xAI publishes GPU count rather than a directly comparable GW figure.[13] |

### 4.2 Global data-centre context

The IEA estimates data centres used about **415 TWh in 2024** and projects about **945 TWh by 2030**, with accelerated-server electricity demand growing about 30% annually in its base case and AI being the most important driver of growth.[14]

Interpolating the IEA's 2024-to-2030 base case at the implied compound rate—not an official IEA annual forecast—gives approximate global data-centre electricity use of:

- **2028:** ~718 TWh/year, equivalent to ~82 GW average load;
- **2029:** ~824 TWh/year, equivalent to ~94 GW average load.

Against that power-only benchmark, the reference volunteer network would represent:

| Volunteer concurrency | Volunteer load | Share of interpolated 2028 global data-centre avg. load |
|---|---:|---:|
| 5% | 1.67 GW | ~2% |
| 10% | 3.33 GW | ~4% |
| 25% | 8.33 GW | ~10% |
| 50% | 16.67 GW | ~20% |
| 100% | 33.33 GW | ~41% |

Again, these percentages compare electrical load, **not equivalent usable AI training performance**.

### 4.3 Comparison with a 10 GW AI programme

Relative to a 10 GW headline AI infrastructure programme such as Stargate:

- 5% volunteer concurrency ≈ 0.17 × 10 GW;
- 10% ≈ 0.33 ×;
- 25% ≈ 0.83 ×;
- 50% ≈ 1.67 ×;
- full simultaneous pool ≈ 3.33 ×.

This makes the idea worth researching. But a 10 GW hyperscale cluster has far higher accelerator density and far lower communication latency.

## 5. Why gigawatts do not equal gigawatts of useful LLM compute

Meta provides a useful illustration of modern data-centre density: a six-rack pod with 72 NVIDIA Blackwell GPUs consumes roughly **140 kW** and Meta reports about **360 PFLOPS of FP16 compute**.[5] It also operates clusters with extremely high-bandwidth networking and specialised memory systems.

A consumer network has the opposite characteristics:

- mixed CPU, GPU and NPU generations;
- different instruction sets and drivers;
- fragmented RAM/VRAM;
- residential/mobile network latency;
- asymmetric upload bandwidth;
- devices appearing and disappearing;
- thermal and battery constraints;
- untrusted nodes;
- no universal accelerator programming model.

A newer Copilot+ PC has at least a 40-TOPS NPU by Microsoft's definition, and current AMD/Intel/Qualcomm systems can expose 40–50+ TOPS NPUs.[15] Qualcomm has demonstrated a Snapdragon X Elite running a 7B model at a vendor-claimed 30 tokens/s.[16] This shows that useful on-device inference is real, but billions of advertised TOPS cannot simply be summed and called a single supercomputer: precision, model support, memory bandwidth, software stack and utilisation differ.

## 6. What LLM workloads fit a volunteer commons?

### Excellent or promising fit

**Independent inference requests.** If a node can hold the whole selected quantised model, each request can be routed independently. This minimizes cross-device communication.

**Embeddings and reranking.** These are naturally batchable and relatively small.

**Evaluation and benchmarking.** Model/test combinations can be distributed independently and redundantly verified.

**Synthetic-data generation.** Large numbers of independent prompts can be scheduled across nodes, subject to safety and quality controls.

**Public-interest batch research.** Similar to BOINC, jobs that have high arithmetic intensity and little synchronization are attractive.

**Parameter-efficient fine-tuning.** Some LoRA/adapter workloads can be distributed to stronger nodes or small worker groups.

### Possible but harder

**Model-parallel inference across the internet.** Petals demonstrated collaborative inference and fine-tuning of 50B–176B-class models across geodistributed consumer-grade networks, with fault-tolerant rebalancing. This proves the basic concept is possible, though latency and reliability are worse than a tightly coupled cluster.[17]

**Low-communication distributed training.** DeepMind's DiLoCo showed that an 8-worker language-model training setup could match synchronous optimisation while communicating 500 times less.[18] Later Decoupled DiLoCo work demonstrated a 12B model across four U.S. regions using 2–5 Gbps wide-area connections.[19] This is encouraging for geographically distributed clusters, but 2–5 Gbps between data-centre regions is still far beyond many home/mobile connections.

### Poor first target

**Frontier synchronous pre-training across random phones and household PCs.** Modern frontier training depends heavily on fast interconnect, predictable accelerators and high-bandwidth memory. Trying to emulate an NVLink/InfiniBand/AI-WAN cluster using millions of random Internet nodes would waste much of the theoretical arithmetic capacity waiting for communication and recovering from churn.

The first system should therefore optimize for **high-throughput independent jobs**, not one giant globally synchronous model.

## 7. Recommended architecture inside Portable Trusted Linux

### 7.1 Pre-OS page is for consent, not for running the LLM

The user's idea of placing the choice on the pre-OS loading page is strong from a consent and visibility perspective. The actual worker should not run in firmware/pre-OS code, however. GPU/NPU drivers, networking, scheduling and sandboxing need a proper operating environment.

Recommended boot menu:

```text
PORTABLE TRUSTED LINUX

[1] Secure Inspection Mode
    Offline-first verification and trusted personal session.
    No community compute.

[2] Community Compute Commons
    Donate approved spare CPU/GPU/NPU resources.
    Boots a separate signed minimal worker environment.

[3] Diagnostics / Hardware Test
```

The user chooses before either environment starts.

### 7.2 Separate signed boot profiles

The two modes should be separate signed UKIs / policies.

**Secure Inspection Mode** prioritises minimal attack surface, network-off startup, attestation and trusted user work.

**Community Compute Commons Mode** deliberately enables networking and executes externally scheduled workloads, so it has a larger attack surface. It should not be allowed to transition into the high-assurance personal session without a full reboot.

This separation prevents the social-compute feature from weakening the original security objective.

### 7.3 Worker lifecycle

```text
Pre-OS consent
      ↓
Signed Community Compute UKI
      ↓
Verified read-only worker root
      ↓
No internal disks mounted
      ↓
Hardware capability/thermal check
      ↓
Network enabled
      ↓
Signed scheduler identity verified
      ↓
Signed/approved model + job manifest
      ↓
Sandboxed worker execution
      ↓
Result integrity/replication checks
      ↓
Contribution receipt / compute credit
      ↓
Ephemeral shutdown
```

### 7.4 No arbitrary remote shell

The service should never be a general remote-code executor. Workers should accept only narrowly defined signed job packages using a controlled runtime and model catalogue. A job must not get arbitrary access to host disks, firmware, USB peripherals or other local networks.

### 7.5 Phone companion mode

Phones should be a separate client architecture. A reasonable default policy is:

- user explicitly opted in;
- charging;
- Wi-Fi unless user explicitly permits metered data;
- battery above threshold;
- thermal headroom available;
- screen-off/idle preference;
- strict storage/network caps;
- immediate pause when the owner needs the phone.

Phones are likely better suited to smaller models and independent jobs than to multi-node model parallelism.

## 8. Security and trust model for volunteer nodes

BOINC's design starts from the fact that volunteer workers can be untrusted and may return incorrect results.[7] An LLM commons must do the same.

### Worker-side threats

A malicious scheduler could send hostile code or attempt to access local data. Mitigations include a signed scheduler identity, signed workload manifests, a whitelist of runtimes/model formats, sandboxing, network egress policy and no access to internal disks.

### Server-side threats

A malicious or compromised volunteer may fabricate results, modify model execution, claim work it did not perform, or coordinate with Sybil identities to steal credits. Mitigations can include redundant execution, probabilistic duplicate jobs, deterministic test vectors, trusted-node reputation, result-consistency checks and signed contribution receipts.

### Prompt confidentiality

TLS protects data in transit, but the volunteer worker ultimately needs access to data it computes on unless a suitable trusted execution or cryptographic scheme is used. Therefore private user prompts should **not** initially be sent to arbitrary volunteer nodes.

The first commons should focus on:

- public/non-sensitive prompts;
- community datasets;
- open-weight models;
- batch research/evaluation;
- workloads explicitly approved for volunteer execution.

Strong confidential inference across untrusted consumer nodes is a separate research problem.

### Model confidentiality

Proprietary model weights cannot safely be distributed to arbitrary household computers without accepting a high extraction risk. Open-weight models are the natural first target.

## 9. Fairness: bridging the compute divide without moving the bill

The social objective fails if low-income participants end up subsidising electricity and data for wealthier users. The system should therefore make contribution costs visible and controllable.

Recommended principles:

1. **Voluntary by default.** No silent background enrolment.
2. **Donor controls.** Power cap, hours, thermal limit, bandwidth limit and model/task categories.
3. **AC-first laptops and phones.** Avoid draining batteries unless explicitly approved.
4. **Metered-network protection.** Default to no mobile-data contribution.
5. **Compute credits.** Useful verified work can earn credits, but recipients of social/community compute should not necessarily have to donate hardware themselves.
6. **Public-interest allocation.** A defined share of donated capacity can be reserved for education, non-profits, open science or verified low-resource communities.
7. **Transparent accounting.** Credits should represent accepted useful work, not merely claimed device uptime.
8. **No cryptocurrency requirement.** A signed accounting ledger is enough initially; blockchain is optional and should not be confused with the social mission.

## 10. Memory and storage scale

Aggregate consumer memory is enormous but fragmented.

For illustration only, if the 600M opted-in PCs averaged 8–16 GB of available system memory and 1.32B smartphones averaged 4–8 GB, the enrolled fleet would physically contain roughly **10–20 exabytes of RAM**. At 10% concurrency, roughly **1–2 exabytes** would be present in active workers.

This does **not** create a 1-exabyte shared-memory machine. Each model must fit on one node or on a deliberately coordinated group, and the network becomes the bottleneck when state must move between devices.

Likewise, even a small storage contribution per device becomes enormous in aggregate, but model distribution should use content-addressed caching and peer/CDN delivery to avoid repeatedly downloading multi-gigabyte weights.

## 11. Data-centre buildout is still formidable

The volunteer approach should not be framed as “data centres become unnecessary.” Central clusters offer:

- far better accelerator utilisation;
- high-speed GPU-to-GPU interconnect;
- large coherent memory systems;
- predictable cooling and power;
- controlled security perimeter;
- stable availability;
- efficient batching;
- simpler operations.

The IEA expects global data-centre electricity consumption to more than double by 2030 and notes that around 20% of planned projects could face grid-related delay risks.[14] A volunteer commons may therefore be most valuable as **complementary stranded capacity**: it can absorb workloads that do not require a hyperscale fabric and reduce the amount of centralized capacity needed for those workloads.

## 12. Best realistic interpretation of the 30% scenario

### Device count

A credible headline is:

> If 30% of the addressable global PC and smartphone base participated, the theoretical network could contain roughly **1.8–2.1 billion enrolled devices**, including around **450–600 million PCs/laptops** and **1.3–1.5 billion smartphones**.

### Simultaneous power scale

A credible planning statement is:

> If only 10% of those enrolled devices were simultaneously idle and approved for work, a reference model produces roughly **3 GW** of consumer-device compute load. At 25% simultaneous availability it rises toward **8 GW**.

### Compute interpretation

A credible technical statement is:

> That is comparable to the electrical scale of the largest announced AI campuses, but **not equivalent to their frontier-training capability**. The distributed network should be designed as a massive high-throughput inference/evaluation commons rather than as a direct replacement for one tightly interconnected supercomputer.

## 13. Recommended project decision

**Proceed with the concept as a separately isolated future subsystem.**

Name: **Community Compute Commons (CCC)**.

Status: **Architecture/research track; not part of Prototype 1 security acceptance.**

The original Portable Trusted Linux trust chain remains the priority. CCC should be built only after Secure Inspection Mode can reliably verify its own image/root and produce independent attestation evidence.

The first CCC prototype should be much smaller than the 30% thought experiment: perhaps 10–100 heterogeneous volunteer machines running a single open model/task family, with strong logging, contribution accounting, redundant result checks and measured electricity/bandwidth. The purpose is to measure real useful tokens/jobs per watt and per network byte, not to prove theoretical scale.

## 14. Proposed research metrics for a pilot

For each device class record:

- CPU/GPU/NPU model;
- usable RAM/VRAM;
- model loaded;
- quantisation;
- tokens/second or jobs/hour;
- watts above idle;
- tokens or jobs per watt-hour;
- model download bytes;
- recurring bytes per job;
- round-trip latency;
- worker uptime/availability;
- thermal throttling;
- job failure rate;
- duplicate-result disagreement rate;
- scheduler overhead;
- user interruption rate;
- electricity cost if voluntarily provided;
- privacy classification of workload.

Only after these measurements should the project publish an “equivalent data-centre compute” figure.

## 15. Confidence and uncertainty

**High confidence:** billions of consumer devices exist; volunteer computing can aggregate heterogeneous consumer resources; on-device LLM inference is practical on modern hardware; AI data-centre power buildout is measured in multiple gigawatts; high-throughput independent jobs fit volunteer systems better than tightly synchronous training.

**Medium confidence:** a 30% enrolled pool would be around 1.8–2.1B devices. Installed-base definitions differ and smartphone capability varies substantially.

**Low confidence / scenario only:** 5–25% simultaneous availability and the 25W/100W/4W incremental power assumptions. These must be measured in a pilot.

**Not established:** how many useful LLM tokens per second the global fleet would deliver, because model size, quantisation, memory bandwidth, software support, network conditions and hardware mix dominate that number.

# Sources

1. Deloitte, *TMT Predictions 2025*, 2024/2025. Footnote 17 estimates the installed base of PCs at about 2 billion. https://cdn.elperiodicodelaenergia.com/2024/12/675fc7f683dab4df0c6826c5.pdf
2. GSMA, *The State of Mobile Internet Connectivity 2025*, 2025. Reports 4.4B people using their own smartphone for mobile internet at end-2024. https://www.gsma.com/somic/wp-content/uploads/2025/09/The-State-of-Mobile-Internet-Connectivity-2025-Trends-in-Mobile-Internet-Connectivity.pdf
3. IDC, *WW Personal Computing Device Market: Q1 2026 Performance & 2026 Outlook*, updated June 8, 2026. https://www.idc.com/promo/pcdforecast/
4. OpenAI, *Building the compute infrastructure for the Intelligence Age*, 2026. https://openai.com/index/building-the-compute-infrastructure-for-the-intelligence-age/
5. Meta Engineering, *Meta’s Infrastructure Evolution and the Advent of AI*, September 29, 2025. Includes 1GW Prometheus, Hyperion beginning in 2028 and scaling to 5GW, and GB200 pod power/compute figures. https://engineering.fb.com/2025/09/29/data-infrastructure/metas-infrastructure-evolution-and-the-advent-of-ai/
6. Microsoft, *Fiscal Year 2026 First Quarter Earnings Conference Call*, 2025. States Fairwater scales to 2GW and total data-centre footprint is expected to roughly double in two years. https://www.microsoft.com/en-us/investor/events/fy-2026/earnings-fy-2026-q1
7. David P. Anderson, *BOINC: A Platform for Volunteer Computing*, Journal of Grid Computing 18 (2020), 99–122; and BOINC 2026 overview. https://boinc.berkeley.edu/boinc_a_platform_for_volunteer_computing.pdf ; https://github.com/BOINC/boinc/wiki/BOINC-overview
8. Folding@home, project statistics/timeline. https://foldingathome.org/ ; https://foldingathome.org/science/timeline
9. International Telecommunication Union, *Facts and Figures 2025*. https://www.itu.int/itu-d/reports/statistics/facts-figures-2025/
10. Microsoft, *Microsoft 2022 Annual Report*. Reports more than 1.4B monthly active Windows 10/11 devices. https://www.microsoft.com/investor/reports/ar22/
11. Counterpoint Research, *Active Installed Base: 8 Smartphone OEMs Top 200 mn; Nearly 1 in 4 is an iPhone*, February 2026. https://counterpointresearch.com/en/insights/Active-Installed-Base-8-Smartphone-OEMs-Top-200-mn-Nearly-1-in-4-is-an-iPhone
12. Anthropic, *Anthropic and Amazon expand collaboration for up to 5 gigawatts of new compute*, April 20, 2026. https://www.anthropic.com/news/anthropic-amazon-compute
13. xAI, *Colossus: The World's Largest AI Supercomputer*, current 2026 page. https://x.ai/colossus
14. International Energy Agency, *Energy and AI — Energy demand from AI*, 2025, current 2026 edition/web presentation. https://www.iea.org/reports/energy-and-ai/energy-demand-from-ai
15. Microsoft Learn, *Copilot+ PCs developer guide*. https://learn.microsoft.com/en-us/windows/ai/npu-devices/
16. Qualcomm, *Rethink what’s possible with Snapdragon X Elite*, October 2023. Vendor benchmark: 7B Llama 2 at up to 30 tokens/s on Snapdragon X Elite. https://www.qualcomm.com/news/onq/2023/10/rethink-whats-possible-with-new-snapdragon-x-elite-platform
17. Borzunov et al., *Distributed Inference and Fine-tuning of Large Language Models Over The Internet*, NeurIPS 2023. https://arxiv.org/abs/2312.08361
18. Google DeepMind, *DiLoCo: Distributed Low-Communication Training of Language Models*, November 2023. https://deepmind.google/research/publications/57039/
19. Google DeepMind, *Decoupled DiLoCo: Resilient, Distributed AI Training at Scale*, 2026. https://deepmind.google/blog/decoupled-diloco/
