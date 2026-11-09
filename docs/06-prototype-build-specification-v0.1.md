# Prototype Build Specification v0.1

**Status:** Implementation-ready baseline for Prototype 1  
**Target architecture:** amd64 / x86-64  
**Base distribution:** Debian 13 (trixie), current stable series  
**Prototype purpose:** demonstrate the shortest defensible trust chain before UI polish or external anchoring is implemented.

> Mandatory process rule: every build/test session must update `docs/07-lessons-learned.md`, any affected technical documents, architecture decisions/milestones where appropriate, and `project_content.json` last. See `PROJECT_RULES.md`.

## 1. Prototype objective

Prototype 1 must demonstrate, with positive and negative tests, that we can:

1. build a known Debian-based live environment;
2. authenticate its release/build manifest;
3. verify USB data after writing it;
4. boot through an authorised UEFI/Secure-Boot path where supported;
5. collect measured-boot evidence from TPM 2.0 where supported;
6. boot an immutable root protected by dm-verity;
7. keep normal writable session state ephemeral;
8. produce a structured attestation evidence bundle;
9. bind a TPM Quote to a fresh verifier nonce;
10. verify that evidence from a separate verifier;
11. report missing capabilities as lower assurance, not as success; and
12. preserve enough metadata that another engineer can reproduce and audit the test.

Prototype 1 is not required to provide a polished desktop, blockchain publication, universal firmware validation, remote fleet management or automatic cross-host reference intelligence.

## 2. Locked baseline

### 2.1 Distribution

Prototype 1 uses **Debian 13 `trixie` amd64**.

The build scripts will reference the stable `trixie` suite rather than an unversioned `stable` label. The exact Debian point release and every installed package version must be recorded in the generated build manifest.

Reason: a reproducible security artifact must identify exact inputs. The word `stable` can later refer to a different Debian release.

### 2.2 Build environment

Initial supported builder:

- Debian 13/trixie amd64 host or VM;
- root/sudo access;
- `live-build` for the Debian live root/image workflow;
- systemd UKI tooling available from the selected Debian release;
- `cryptsetup-bin` / `veritysetup` for dm-verity generation and inspection;
- `tpm2-tools` for TPM evidence tests;
- `sbsigntool` or the selected supported signing tool for PE/EFI signing;
- `openssl`, `sha256sum`, `jq`, `xorriso`, `dosfstools`, `gdisk`/`sgdisk`, and standard GNU tooling as required.

`build/00-host-check.sh` must fail early if mandatory build tools are unavailable.

### 2.3 Package/version recording

Every completed build must write `artifacts/<build-id>/build-manifest.json` containing at minimum:

- UTC build time;
- git commit SHA of this repository;
- Debian suite and `/etc/debian_version` from the build host;
- architecture;
- package versions relevant to live-build, systemd/ukify, kernel, cryptsetup/veritysetup, TPM tooling and signing tooling;
- source/build configuration digest;
- output image digests;
- UKI digest;
- root filesystem digest;
- dm-verity root hash;
- signing-key identifier/fingerprint, never a private key;
- reproducibility/environment notes.

## 3. Repository/build layout

Target repository structure:

```text
build/
├── README.md
├── 00-host-check.sh
├── 10-prepare-live-build.sh
├── 20-build-root.sh
├── 30-build-verity.sh
├── 40-build-uki.sh
├── 50-build-image.sh
├── 60-build-manifest.sh
├── 70-verify-artifacts.sh
├── 80-write-usb.sh
└── lib/common.sh

config/
├── packages.list
├── live-build/
└── policy/

schema/
├── attestation-payload.schema.json
└── attestation-envelope.schema.json

verifier/
├── README.md
└── verify-report.sh

artifacts/
└── <build-id>/             # generated, should normally be gitignored
```

Prototype scripts must use `set -Eeuo pipefail` and explicit error handling. Security-critical command failures must not be silently ignored.

## 4. USB / image layout

Prototype target: GPT-partitioned USB/image with clearly separated trusted boot/root material from any future export area.

Initial logical layout:

1. **EFI System Partition (ESP)** — FAT32, contains only the required authorised boot path/UKI and minimal boot metadata.
2. **Immutable root partition/image** — read-only root filesystem data.
3. **dm-verity hash area/partition** — hash tree for the root image if not appended in the selected layout.
4. **Optional metadata partition** — read-only build/release manifest and public verification material.
5. **No default persistent user partition** in the high-assurance Prototype 1 profile.

Partition UUIDs and filesystem UUIDs must be generated during build and written to the build manifest.

The final exact byte layout is considered locked only after successful UEFI boot, dm-verity and USB read-back tests.

## 5. Release and signing trust

Prototype 1 separates two signing identities:

- **Build/release signing identity** — signs manifests and project release metadata.
- **Secure Boot signing identity/path** — authorises EFI/UKI execution under the selected firmware policy.

They may initially be backed by related development keys but must be modelled separately.

Private keys must never be committed to GitHub.

The repository may contain:

- public keys/certificates;
- fingerprints;
- key-generation documentation;
- test-key identifiers explicitly labelled DEVELOPMENT ONLY.

### Random-PC rule

Prototype tooling must never automatically:

- enroll project Secure Boot keys into target firmware;
- clear or take ownership of a TPM;
- change firmware configuration;
- write to target internal disks.

### Boot modes

**Commodity compatibility mode** may use the ordinary vendor/Microsoft-compatible Secure Boot chain where practical. It maximises compatibility but inherits a larger trust base.

**Controlled-hardware mode** may later use project-owned Secure Boot keys on hardware explicitly prepared for the project.

These modes must never be described as providing identical trust guarantees.

## 6. Unified Kernel Image

The target UKI should bind the early boot material that must not drift independently, including where supported by the chosen systemd flow:

- Linux kernel;
- initrd/initramfs;
- kernel command line;
- OS release metadata;
- boot splash/metadata only if needed;
- dm-verity root identity/root-hash binding information;
- other sections explicitly documented by the selected UKI toolchain.

The output UKI must be hashed and its SHA-256 recorded in the build manifest.

A deliberately modified/unsigned test UKI must be included in negative testing and must not be reported as an authorised Secure-Boot success.

### Current M15 development implementation (2026-11-10)

The normal `build/20-build-live.sh` path now includes the live-build binary hook `build/50-build-development-uki.py` and post-ISO `build/52-verify-development-uki-iso.py`. Implementation acceptance is tracked in `docs/17-signed-uki-development-plan.md`; a generated file or a passing retained-input diagnostic alone does not close the clean-pipeline gate.

UKI provenance, whole-file SHA-256, explicit development certificate/public-key fingerprints, exact commands and section/input identities are recorded in `out/m15/provenance.json`, `inputs.json`, `commands.jsonl` and verification logs. The outer ISO provenance remains `out/build-manifest.json`. The development UEFI fallback and actual ISO EFI extents must contain the verified signed UKI. Each build creates new temporary development keys; no production identity or firmware enrollment is used.

The implemented M15 scope binds kernel, initrd, command line, OS release, kernel version and SBAT. The final-design dm-verity root-hash binding listed above remains **NOT IMPLEMENTED / M17**. The root squashfs is not authenticated by this development gate, legacy BIOS is outside its signature guarantee, and firmware enforcement/physical boot are **NOT VERIFIED**. No approved ISO follows from this work.

## 7. Secure Boot evidence

The attestation collector must distinguish:

- firmware appears to be UEFI vs legacy/unknown;
- Secure Boot enabled;
- Secure Boot disabled;
- Secure Boot state unavailable/unknown;
- authorised UKI signature verified by our own artifact verifier;
- firmware enforcement evidence observed.

`Secure Boot enabled` must not be translated to `firmware trusted`.

## 8. Measured Boot and PCR strategy

Prototype 1 records rather than over-interprets PCRs.

Minimum evidence set:

- TPM version/capabilities where readable;
- available PCR banks;
- selected SHA-256 PCR values;
- raw TCG firmware event log if exposed by the kernel;
- parsed event-log representation where tooling permits;
- Secure-Boot-related platform state;
- UKI/systemd-related measurements where provided by the actual boot chain.

PCR selection must be written into policy rather than hidden in shell code.

### Initial policy

The prototype verifier should collect a broad enough set to investigate the boot chain, then narrow the formal quoted PCR set after observing real machines and documenting the event mappings.

Do **not** require arbitrary PCs to have identical PCR values. Host firmware/configuration affects PCR state.

## 9. Attestation Key lifecycle

Prototype 1 should use a TPM-generated Attestation Key (AK) without modifying TPM ownership state unnecessarily.

The evidence bundle must include the AK public material required for quote verification.

Trust in the AK itself is a separate question from cryptographic verification of the quote. The verifier must report these independently, for example:

- `QUOTE_SIGNATURE = VERIFIED`
- `AK_TRUST = DEVELOPMENT / UNVERIFIED / CERTIFIED`

Future hardware-rooted profiles may add endorsement/manufacturer certificate validation. Prototype 1 must not pretend this has happened when it has not.

## 10. Fresh nonce protocol

The verifier generates at least 256 bits of cryptographically random challenge data.

Protocol:

```text
Verifier                              Attester
   |                                     |
   |---- random nonce ------------------->|
   |                                     |
   |                   TPM Quote over selected PCRs
   |                   with nonce/qualification bound
   |                                     |
   |<--- quote + signature + PCR data ----|
   |<--- event log + evidence bundle -----|
   |                                     |
   | verify signature
   | verify exact nonce
   | verify requested PCR selection
   | evaluate event log/reference policy
```

A changed nonce or replayed report must fail nonce verification.

## 11. dm-verity root

Linux dm-verity is used only for a read-only trusted root.

Build sequence:

1. construct deterministic root filesystem contents as far as practical;
2. produce the root filesystem image;
3. record its raw SHA-256;
4. run `veritysetup format` (or the selected supported equivalent) to construct the Merkle hash tree;
5. capture the root hash and all necessary parameters;
6. authenticate/bind the root hash through the trusted boot artifact/configuration;
7. boot root through the verity mapping;
8. expose verity status in the attestation collector.

For new layouts use the current dm-verity on-disk format supported by the selected tooling/kernel.

### Mandatory negative test

Flip one byte/block in a copy of the protected root data after verity construction. The altered artifact must not pass protected-root verification. Test details and exact failure behaviour must be recorded in Lessons Learned.

## 12. Disposable writable state

Normal mutable state goes to RAM-backed tmpfs/overlay storage.

Prototype test:

1. create `/home/ptl/session-persistence-test.txt` during a boot;
2. shut down cleanly;
3. boot again;
4. confirm the file does not exist;
5. record PASS/FAIL in the test report.

Ephemeral state reduces persistence. It does not guarantee full RAM zeroisation or defeat malicious firmware, DMA, physical-memory or cold-boot attacks.

## 13. Network and peripheral defaults

Before local verification is complete:

- networking should default off;
- Bluetooth should default off;
- automount of arbitrary writable internal storage should be disabled;
- internal disks should not be modified;
- unnecessary external-device interaction should be minimised;
- external evidence publication is not attempted.

A later explicit user action/policy may enable network access.

## 14. Attestation data model — payload and envelope

The report MUST NOT hash a structure containing its own hash.

Use two layers.

### 14.1 Canonical payload

`payload` contains evidence and status information. It is deterministically serialised according to a documented canonicalisation algorithm.

Initial fields:

```json
{
  "schema_version": "0.1",
  "project_version": "...",
  "report_id": "...",
  "session_id": "...",
  "sequence": 1,
  "previous_payload_digest": null,
  "time": {
    "observed_utc": "...",
    "source": "...",
    "trusted": false
  },
  "portable_image": {
    "sha256": "...",
    "build_id": "..."
  },
  "boot": {
    "uki_sha256": "...",
    "secure_boot_state": "..."
  },
  "root_integrity": {
    "method": "dm-verity",
    "root_hash": "...",
    "status": "..."
  },
  "tpm": {
    "present": true,
    "pcr_bank": "sha256",
    "pcrs": {},
    "event_log_sha256": "...",
    "quote": {},
    "nonce": "..."
  },
  "platform": {},
  "checks": [],
  "warnings": [],
  "unknowns": []
}
```

### 14.2 Envelope

The envelope contains material derived from or attached to the canonical payload:

```json
{
  "envelope_schema_version": "0.1",
  "payload": {},
  "payload_digest": {
    "algorithm": "sha256",
    "value": "..."
  },
  "signatures": [],
  "anchor_receipts": []
}
```

The exact canonical JSON scheme must be frozen and supplied with test vectors before signatures/hash chains are relied on across independent implementations.

## 15. Evidence classifications

Individual checks use explicit classifications, not a single safety boolean:

- `VERIFIED` — cryptographic or deterministic check succeeded relative to stated trust input;
- `ATTESTED` — signed hardware-backed evidence validated according to stated attestation policy;
- `OBSERVED` — detected locally but not independently authenticated;
- `WARNING` — evidence exists but policy or interpretation raises concern;
- `UNKNOWN` — insufficient evidence/reference data;
- `NOT_VERIFIABLE` — outside the verification capability of the current design;
- `FAILED` — an expected security check did not validate.

The report may compute a project assurance level, but the underlying statuses must always remain visible.

## 16. Assurance levels

- **Level 0 — Unverified:** no authenticated image validation.
- **Level 1 — USB verified:** USB accessible bytes match an authenticated release/reference.
- **Level 2 — OS verified:** boot/root artifacts cryptographically authenticated under project policy.
- **Level 3 — Measured boot:** TPM/event-log measurement evidence collected and structurally evaluated.
- **Level 4 — Attested:** fresh nonce-bound TPM Quote independently verified under a stated AK/reference policy.
- **Level 5 — Hardware-rooted:** reserved for a stronger platform-specific trust chain with independently validated hardware/firmware roots. Prototype 1 does not automatically reach this level.

## 17. Independent verifier

The first verifier is command-line based and must run independently from the attester.

It must verify independently separable checks and return non-zero on failed mandatory policy.

Illustrative output:

```text
PAYLOAD DIGEST        VERIFIED
REPORT SIGNATURE      VERIFIED / NOT CONFIGURED
USB IMAGE             VERIFIED / UNKNOWN
UKI                    VERIFIED / UNKNOWN
SECURE BOOT           OBSERVED / DISABLED / UNKNOWN
TPM QUOTE              VERIFIED / FAILED / UNAVAILABLE
NONCE                  VERIFIED / FAILED
PCR SELECTION          VERIFIED / FAILED
EVENT LOG              CONSISTENT / WARNING / UNKNOWN
AK TRUST               DEVELOPMENT / UNVERIFIED / CERTIFIED
DM-VERITY ROOT         VERIFIED / FAILED
FIRMWARE ASSURANCE     PARTIAL / UNKNOWN
HISTORY ANCHOR         PENDING / VERIFIED / NOT CONFIGURED
```

## 18. USB read-back validation

Writing an image successfully is not the same as validating the resulting media.

`build/80-write-usb.sh` must:

1. require an explicit target block device argument;
2. display target identity and size;
3. require explicit confirmation unless an automation flag is deliberately provided;
4. refuse obvious system/root devices where detection is possible;
5. write the image;
6. flush/synchronise writes;
7. read back exactly the written image length;
8. calculate SHA-256 of the read-back bytes;
9. compare against the source image digest;
10. report PASS/FAIL.

This verifies accessible USB storage bytes, not controller firmware.

## 19. Build reproducibility

Prototype 1 distinguishes:

- **repeatable build instructions** — required immediately;
- **input provenance/version capture** — required immediately;
- **bit-for-bit reproducibility** — desirable but must be empirically measured rather than claimed in advance.

Two clean builds should later be compared. If hashes differ, the project records the causes before claiming reproducibility.

## 20. External Evidence Anchoring boundary

Prototype 1 creates fields required for future history chaining and anchor receipts but does not require network publication.

Later implementation:

1. verify/finalise canonical payload;
2. hash payload;
3. sign commitment/report envelope;
4. link `previous_payload_digest`;
5. store locally;
6. queue commitment while offline;
7. publish to transparency backend when explicitly network-enabled;
8. store inclusion/timestamp receipt;
9. optionally add independent blockchain timestamp backend.

The whole private report should not be published by default.

## 21. Failure behaviour

Mandatory principles:

- invalid artifact signature => never label artifact verified;
- altered dm-verity data => trusted-root validation fails;
- TPM missing => continue only with explicitly lower assurance if policy permits;
- quote signature failure => attestation FAILED;
- nonce mismatch => attestation FAILED;
- missing event log => report missing evidence, not `VERIFIED`;
- unknown firmware reference => `UNKNOWN`, never inferred good;
- external anchor unavailable => keep `PENDING`; earlier local verification remains independently reportable;
- inability to identify exact build inputs => reproducibility status `UNKNOWN/WARNING`.

## 22. Mandatory negative-test matrix

| Test | Intentional fault | Required result |
|---|---|---|
| N01 | Change release/image bytes | SHA mismatch / FAILED |
| N02 | Invalid/unauthorised UKI signature | No authorised-success claim |
| N03 | Alter protected root after verity creation | dm-verity validation failure |
| N04 | Replay old TPM Quote with new challenge | Nonce FAILED |
| N05 | Modify quote bytes/signature | Quote signature FAILED |
| N06 | Remove event log | Event-log evidence UNKNOWN/MISSING |
| N07 | Disable TPM | Lower assurance / TPM UNAVAILABLE |
| N08 | Disable Secure Boot | Secure Boot DISABLED, not VERIFIED |
| N09 | Change canonical payload after hashing | Payload digest FAILED |
| N10 | Break previous-report link | History-chain FAILED |
| N11 | No network during anchor step | Anchor PENDING; local report retained |
| N12 | Reboot after creating RAM-only test file | File absent / PASS |

Results from every executed negative test must be appended to `docs/07-lessons-learned.md`.

## 23. Prototype build gates

### Gate A — Reference
- build inputs captured;
- image SHA-256 generated;
- authenticated release manifest design operational.

### Gate B — USB
- image written;
- read-back hash equals source image hash;
- modified media test produces mismatch.

### Gate C — Boot
- x86-64 UEFI boot works;
- UKI identity captured;
- Secure Boot state captured;
- invalid boot artifact does not receive a success result.

### Gate D — Measured Boot
- TPM detected on supported host;
- PCR values captured;
- raw event log saved;
- policy records which PCRs are used and why.

### Gate E — Verified Root
- root uses dm-verity;
- root hash authenticated through the chosen boot path;
- intentional corruption is detected.

### Gate F — Disposable Runtime
- ordinary writes occur in volatile/overlay state;
- persistence test does not survive reboot.

### Gate G — Attestation
- verifier issues fresh nonce;
- TPM Quote generated over declared PCR selection;
- external verifier validates signature and nonce;
- replay test fails.

### Gate H — Independent report
- payload canonicalisation defined;
- payload hash independently recomputed;
- result statuses remain granular;
- verifier can run without trusting the attester's UI.

Prototype 1 is considered successful only when Gates A-H have test evidence. External Evidence Anchoring follows as the next engineering phase.

## 24. Engineering sequence

1. add repository rules and lessons discipline;
2. create build scaffolding;
3. implement host/tool preflight;
4. create Debian 13/trixie live-build configuration;
5. build base root/image and capture exact inputs;
6. integrate dm-verity and corruption test;
7. construct/sign UKI;
8. boot on UEFI test hardware/VM;
9. add TPM/event-log collector;
10. define and test canonical attestation schema;
11. implement nonce-bound Quote flow;
12. implement independent verifier;
13. run complete negative-test matrix;
14. compare two clean builds for reproducibility evidence;
15. only then implement public transparency anchoring and broader product UI.

## 25. Scope exclusions for Prototype 1

Prototype 1 explicitly does not claim to verify:

- malicious USB-controller firmware;
- CPU microcode correctness beyond available signed/vendor evidence;
- arbitrary motherboard/UEFI firmware cleanliness without trusted reference information;
- option-ROM/device firmware cleanliness universally;
- physical hardware implants;
- all runtime software vulnerabilities;
- full RAM erasure after power-off;
- universal remote attestation identity/provenance of every TPM;
- security of a network that has not itself been authenticated.

These exclusions are not defects hidden from the user; they are part of the stated threat model.
