# Portable Trusted Linux — Trust Chain & Threat Model v0.1

## Security objective

Provide a portable Linux environment that can be authenticated independently of the installed operating system, can collect meaningful platform boot evidence where supported, can operate with minimal persistence, and can produce evidence suitable for independent verification.

The project explicitly does **not** claim that arbitrary hardware can be proven universally safe by software running on that hardware.

## Proposed trust chain

```text
Authenticated project/release key
        ↓
Authenticated reference image
        ↓
Verified USB storage image
        ↓
UEFI / Secure Boot policy
        ↓
Measured Boot / TCG event log
        ↓
TPM PCR state
        ↓
Signed Unified Kernel Image
        ↓
Kernel + initrd
        ↓
Authenticated dm-verity root hash
        ↓
Immutable Linux root
        ↓
RAM-backed ephemeral writable state
        ↓
TPM Quote + evidence bundle
        ↓
Independent verifier
```

## Verification domains

1. **Source/release authenticity** — who authorised the software release?
2. **Image identity** — do the image bytes match the authenticated reference?
3. **USB storage integrity** — does media read-back match the intended image?
4. **Boot-chain integrity** — which boot components were authorised and/or measured?
5. **Root-filesystem integrity** — does the read-only Linux root match its authenticated Merkle-tree root?
6. **Runtime/disposable state** — are ordinary writable changes ephemeral?
7. **Platform evidence** — what can be learned about firmware and hardware state?
8. **Evidence-history integrity** — can old reports be changed without detection?

## Trust boundaries

### TB-1 — Release trust
The signing identity and authenticated release metadata are outside the target machine's self-asserted trust.

### TB-2 — Portable media
The readable USB image can be checked against a known reference, but controller firmware is a separate hardware trust problem.

### TB-3 — Platform firmware
UEFI executes before Linux. Secure Boot and measured boot may provide useful evidence, but arbitrary firmware cannot be assumed trustworthy merely because Linux started.

### TB-4 — TPM
A TPM Quote can provide hardware-backed evidence about selected PCR values, subject to trust in the TPM implementation, attestation key and verification policy.

### TB-5 — Runtime
The trusted OS base should be read-only. Writable state should be separated into an ephemeral layer.

### TB-6 — Independent verifier
The target machine must not be the only authority deciding whether its own evidence is acceptable.

## Threats and intended controls

| ID | Threat | Intended control / status |
|---|---|---|
| T1 | Compromised installed Windows/Linux | Do not boot the installed OS |
| T2 | Fake or modified release image | Digital signatures + authenticated hashes |
| T3 | Bad or tampered USB write | Full read-back + digest comparison |
| T4 | Modified bootloader | Secure Boot and measured boot where supported |
| T5 | Modified kernel | Signed UKI / authenticated boot image |
| T6 | Modified initrd | Bind into signed/measured UKI |
| T7 | Modified root filesystem | dm-verity |
| T8 | Malicious UEFI/firmware | Measurements/reference policy where available; cannot universally prove clean |
| T9 | Option ROM/device firmware compromise | Limited evidence; may remain unknown |
| T10 | Malicious USB peripheral | Device restrictions and reduced default attack surface |
| T11 | Malicious network/application content | Offline-first startup, sandboxing/restrictions later |
| T12 | Persistence across shutdown | Read-only base + RAM-backed writable layer |
| T13 | Cold-boot/physical memory attack | Outside ordinary RAM-only guarantee; requires separate mitigations |
| T14 | Malicious USB controller firmware | Not proven by storage hash; stronger media/hardware design may be required |
| T15 | Compromised TPM implementation | TPM evidence is conditional on TPM trust and reference validation |
| T16 | Rewritten historical reports | Digital signatures + report hash chain + external evidence anchoring |

## Attestation report specification — initial fields

A future canonical report should include at least:

- report format version;
- project/software version;
- session identifier;
- target platform metadata needed for verification;
- portable image digest;
- signed UKI digest/identity;
- Secure Boot state and relevant policy evidence;
- selected PCR values;
- TCG event log or digest/reference to it;
- TPM attestation-key information required by verifier policy;
- verifier challenge/nonce;
- TPM Quote;
- dm-verity root hash and verification state;
- persistence/runtime state;
- security warnings and unknowns;
- previous report hash, where report chaining is enabled;
- report signature;
- optional external-anchor receipt(s).

## User-facing assurance levels

- **Level 0 — Unverified**
- **Level 1 — USB verified**
- **Level 2 — OS verified**
- **Level 3 — Measured boot**
- **Level 4 — Attested**
- **Level 5 — Hardware-rooted**

These are product-level summaries, not universal formal security certifications.

## Independent corroboration

Multiple PCs can independently recompute portable-image identity and produce host-specific attestation evidence. This can strengthen corroboration and expose differences, but it must not be described as proof that all agreeing machines are trustworthy.

PCRs are frequently host-, configuration-, firmware- and update-specific. Cross-host comparison therefore needs to distinguish stable portable-image identity from host-specific reference-policy validation.

## Claims the product must never make

- “This PC is 100% safe.”
- “A valid TPM proves firmware is clean.”
- “A USB hash proves USB-controller firmware is clean.”
- “Several PCs agreeing proves every PC is trustworthy.”
- “Secure Boot proves the entire operating system is trustworthy.”
- “RAM-only defeats all attacks.”
- “A PCR value is meaningful without the correct reference context.”
- “A blockchain record proves that the original measurement was true.”

## Prototype acceptance gates

### Gate A — Reference verification
Authenticity and digest of the reference image can be verified independently.

### Gate B — USB read-back
A correctly written USB passes full read-back verification and an intentionally modified image fails.

### Gate C — Authenticated boot
Authorised boot image succeeds and an intentionally invalid signing case is rejected where the platform policy supports that test.

### Gate D — Measured boot
PCR values and TCG event log can be captured reproducibly enough to support policy evaluation.

### Gate E — Verified root
The expected dm-verity root validates and deliberate protected-block corruption is detected.

### Gate F — Disposable runtime
Normal writable session state is stored ephemerally and is absent after shutdown/reboot unless explicitly exported.

### Gate G — TPM attestation
A fresh nonce-bound TPM Quote can be generated and independently verified. Replay using an old nonce/report fails verification.

### Gate H — Cross-host corroboration
The same USB image identity is independently reproduced on multiple hosts while host-specific evidence remains separately evaluated.

### Gate I — Evidence-history integrity
Changing a historical report breaks its signature/hash-chain relationship, and an externally anchored commitment can be used to detect rewritten history.

## Initial engineering direction

The smallest useful prototype is expected to use Debian Live, systemd/UKI tooling, TPM 2.0 tooling, dm-verity, a RAM-backed overlay, a canonical report format, and an independent command-line verifier.
