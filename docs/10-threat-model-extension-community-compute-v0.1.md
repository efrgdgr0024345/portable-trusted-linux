# Threat Model Extension — Community Compute Commons v0.1

**Status:** Draft architecture threat extension  
**Applies to:** Community Compute Commons (CCC) only  
**Does not replace:** `03-trust-chain-threat-model-v0.1.md`

CCC deliberately enables networking and remote workload execution. It therefore introduces threats that do not belong in the minimal Secure Inspection Mode.

## Security boundary

Secure Inspection Mode and CCC must be separate boot profiles. A compromise in CCC must not be treated as evidence that the inspection environment remains trustworthy. Moving from CCC to Secure Inspection Mode requires a full reboot into the independently authenticated inspection image.

## New threats

| ID | Threat | Example | Primary controls | Residual issue |
|---|---|---|---|---|
| T16 | Malicious scheduler | Coordinator sends hostile workload | scheduler authentication; signed job manifests; approved runtime/model catalogue | signing authority compromise remains high impact |
| T17 | Malicious workload | Model/job attempts local data access or persistence | separate signed worker OS; sandbox; read-only root; no internal-disk mounts; ephemeral state | kernel/driver sandbox escapes remain possible |
| T18 | Volunteer result poisoning | Node returns fabricated or modified outputs | duplicate sampling; hidden test jobs; deterministic seeds; task validators; reputation | stochastic outputs can be difficult to compare |
| T19 | Sybil/credit fraud | Attacker creates many fake workers to earn credits | verified-work accounting; duplicate jobs; rate controls; optional stronger worker attestation | strong identity can conflict with privacy |
| T20 | Prompt confidentiality leak | Volunteer sees a user's private prompt | restrict initial workloads to public/non-sensitive data; no private prompts on arbitrary nodes | confidential distributed inference remains future research |
| T21 | Model-weight leakage | Proprietary weights are extracted from worker | open-weight models by default; do not distribute confidential weights | TEEs are heterogeneous and not universal |
| T22 | Energy/battery abuse | Worker drains laptop/phone or causes overheating | explicit opt-in; AC/charging policy; thermal/power caps; immediate preemption | measurements vary by hardware/OS |
| T23 | Metered-data abuse | Multi-GB model download consumes expensive mobile plan | Wi-Fi/unmetered default; bandwidth caps; content-addressed cache | users may still choose costly networks |
| T24 | Local-network pivot | Remote job scans LAN or attacks nearby devices | deny LAN access; restricted egress; network namespace/firewall | complex workloads may need carefully scoped network access |
| T25 | Persistent compromise | Job writes firmware/disk or survives reboot | no firmware interfaces; no internal-disk mounts; immutable worker root; ephemeral overlay | malicious peripheral/firmware remains outside full control |
| T26 | Coordinator abuse of donated capacity | Infrastructure used for unapproved commercial/abusive work | public workload policy; signed catalogue; audit logs; governance; donor categories | governance is organisational as well as technical |
| T27 | Privacy/linkability | Stable TPM/serial identifiers track donors | project pseudonymous keys; minimise hardware identifiers; privacy review for attestation | anti-fraud and anonymity pull in opposite directions |
| T28 | Supply-chain/runtime compromise | Signed but vulnerable GPU driver/runtime is exploited | pinned/verified artifacts; staged updates; vulnerability response; signed releases | broad accelerator support enlarges TCB |
| T29 | Resource denial / starvation | Scheduler monopolises device, degrading owner's use | owner-priority preemption; quotas; idle detection; resource caps | poor tuning harms user experience |
| T30 | False social-impact accounting | Project reports theoretical TOPS rather than useful delivered compute | accepted-job metrics; tokens/jobs per Wh; transparent assumptions; independent accounting | cross-model token rates remain non-comparable |

## Mandatory negative tests before public pilot

1. Unsigned scheduler certificate/job manifest is rejected.
2. Modified model/runtime digest is rejected.
3. Job cannot enumerate or mount an internal disk.
4. Job cannot reach local LAN addresses under default policy.
5. Job exceeding CPU/GPU/power/thermal policy is stopped or throttled.
6. Worker pauses immediately on user-preemption signal.
7. Deliberately wrong deterministic result is detected through verification policy.
8. Duplicate-result disagreement is logged and credit is withheld pending policy.
9. Phone client refuses compute on metered/cellular network by default.
10. Worker state and downloaded secrets do not survive the configured ephemeral reset, excluding explicitly cached public model blobs.
11. CCC cannot transition into Secure Inspection Mode without reboot.
12. No stable TPM/serial identifier is transmitted unless a documented privacy policy explicitly requires it.

## Assurance statement

A CCC worker that produces valid attestation is not automatically trusted to return correct AI outputs. Platform attestation and workload-result verification answer different questions and must remain separate.

Likewise, the fact that a worker produced useful accepted compute does not prove the physical machine is free of malicious firmware. CCC must inherit the project's VERIFIED / ATTESTED / OBSERVED / WARNING / UNKNOWN / NOT VERIFIABLE / FAILED vocabulary.
