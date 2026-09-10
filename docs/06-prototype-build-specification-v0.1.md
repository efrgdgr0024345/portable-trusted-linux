# Prototype Build Specification v0.1

**Status:** Draft / current engineering milestone

This document converts the trust model into a first implementation plan. Exact package versions and command syntax must be verified against current Debian/systemd/tpm2-tools documentation before the build scripts are frozen.

## 1. Prototype objective

Build the smallest useful Portable Trusted Linux image that can:

1. boot independently of an installed operating system;
2. authenticate its portable image and boot components;
3. expose/measurably record important boot state;
4. verify its immutable root filesystem;
5. keep normal writable state ephemeral;
6. generate a structured attestation bundle; and
7. allow that bundle to be verified on a separate system.

## 2. Initial platform assumptions

- x86-64 PC hardware for Prototype 1.
- UEFI firmware preferred.
- TPM 2.0 preferred but not mandatory for lower assurance levels.
- Secure Boot support preferred.
- The tool must not automatically enroll keys or modify random PCs' UEFI/TPM ownership state.
- Read-only inspection should be the default behaviour.

## 3. Base operating system

Initial candidate: Debian Live.

Selection criteria:

- mature live-boot ecosystem;
- package availability for systemd, TPM 2.0 tooling and dm-verity;
- ability to create a deterministic documented build;
- compatibility with a broad range of commodity PCs.

Reproducibility status must be documented for the exact selected Debian release and build pipeline rather than assumed universally.

## 4. USB layout — design target

The exact partition table will be finalized after boot testing. Initial logical separation:

- EFI System Partition containing the authorised boot image/UKI;
- immutable Linux root image;
- dm-verity hash/tree data where not embedded/combined;
- optional read-only project metadata/reference manifest;
- no normal persistent writable user partition in the default security profile.

Any writable export area should be explicitly separated from trusted executable/root content.

## 5. Signing-key architecture

Two operating modes should be considered:

### Commodity compatibility mode
Use a widely accepted signed boot path compatible with ordinary Secure Boot-enabled PCs. This maximises random-PC compatibility but expands the external trust base.

### Controlled hardware mode
Use project-owned Secure Boot keys only on hardware explicitly prepared for the project. This can tighten trust but is unsuitable for silently modifying random machines.

Prototype rule: never automatically enroll or replace Secure Boot keys on a user's PC.

## 6. Unified Kernel Image

The UKI should bind together the important early-boot components needed by the design, such as:

- Linux kernel;
- initrd/initramfs;
- kernel command line;
- OS metadata;
- dm-verity/root-hash binding data where appropriate to the selected systemd design.

The UKI should be digitally signed through the chosen Secure Boot path and measured through the supported measured-boot mechanism.

## 7. TPM measurements

Prototype 1 must record:

- TPM 2.0 availability;
- selected PCR values;
- TCG event log;
- Secure Boot-related evidence;
- UKI/boot measurements provided by the selected systemd/firmware path.

PCR policy must not assume that unrelated PCs should produce identical PCR values. Reference validation needs to be host/model/configuration aware.

## 8. TPM Quote protocol

The verifier should generate a fresh cryptographically random nonce.

The target obtains a Quote over the selected PCR set, binding the nonce to the signed TPM evidence.

Verifier checks should include:

- Quote signature validity;
- expected nonce;
- expected PCR selection;
- consistency with supplied event-log evidence where supported;
- attestation-key trust policy;
- reference-profile policy.

Replay of a Quote/report using an earlier nonce must fail.

## 9. dm-verity root

The trusted root filesystem is read-only.

Build pipeline must:

1. construct the root filesystem image;
2. generate the dm-verity Merkle tree;
3. obtain the root hash;
4. bind/authenticate that root hash through the boot trust chain;
5. verify root blocks during runtime.

A controlled corruption test must demonstrate that modified protected blocks are detected.

## 10. Ephemeral writable state

Normal writes should go to tmpfs/RAM or an equivalent ephemeral overlay.

Testing must verify that ordinary session changes do not survive shutdown/reboot.

A future optional export mechanism can allow deliberate file persistence without making the trusted base filesystem writable.

Full memory zeroisation is not assumed merely because the system is RAM-backed. Cold-boot and physical-memory attacks remain a separate threat class.

## 11. Default network and device policy

Prototype default:

- networking disabled until verification completes;
- Bluetooth disabled by default;
- unnecessary USB devices restricted where technically practical;
- no external evidence publication during the initial trusted boot path.

## 12. Attestation report format

The report should be deterministic/canonical before hashing.

Initial implementation can use canonical JSON if its canonicalisation rules are made explicit and tested. CBOR or another binary canonical format can be evaluated later.

Fields should include:

- report_schema_version;
- project_version;
- report/session ID;
- UTC timestamp where trustworthy time is available, with time-source provenance;
- portable image digest;
- UKI digest/identity;
- dm-verity root hash/status;
- Secure Boot state;
- TPM availability;
- PCR bank and selected values;
- event-log evidence/reference;
- nonce;
- TPM Quote;
- attestation-key metadata needed for verification;
- warnings/unknowns;
- previous_report_hash;
- report hash;
- report signature;
- external-anchor receipts array.

## 13. Independent verifier

The first verifier should be command-line based and intentionally simple.

It should produce a structured result such as:

```text
USB IMAGE            VERIFIED
REPORT SIGNATURE     VERIFIED
TPM QUOTE            VERIFIED
NONCE                 VERIFIED
EVENT LOG             VERIFIED / WARNING
REFERENCE PROFILE     MATCH / WARNING / UNKNOWN
dm-verity ROOT        VERIFIED
FIRMWARE ASSURANCE    PARTIAL / UNKNOWN
HISTORY ANCHOR        NOT YET PUBLISHED
```

It must show which checks failed independently rather than collapsing everything into one boolean.

## 14. Evidence-history format

After attestation verification:

1. canonicalise the final report;
2. calculate its SHA-256;
3. sign the report/commitment;
4. record the previous report hash if chaining is enabled;
5. keep the full report private;
6. optionally publish the commitment later;
7. save returned external-anchor receipt/proof.

## 15. Update and rollback model

Every official portable-OS build needs an authenticated version identity.

Updates should be treated as new signed immutable images, not ad-hoc mutation of a trusted deployed root.

The verifier should distinguish an approved older image from an unauthorised image. Anti-rollback policy can be strengthened later, especially for controlled hardware.

## 16. Failure behaviour

Security failures must fail visibly.

Examples:

- signature invalid -> do not label verified;
- dm-verity corruption -> fail protected-root verification and preferably prevent normal trusted operation;
- TPM unavailable -> continue only at a lower assurance level;
- event log unavailable -> explicitly report missing evidence;
- unknown firmware reference -> report UNKNOWN, not VERIFIED;
- external anchor unavailable -> keep a pending commitment without weakening earlier local verification.

## 17. Prototype acceptance sequence

The engineering sequence is:

1. reproducible/reference image build;
2. image signature and SHA verification;
3. USB write/read-back validation;
4. signed UKI boot;
5. Secure Boot compatibility tests;
6. TPM PCR/event-log collection;
7. dm-verity protected root;
8. RAM-backed ephemeral overlay;
9. canonical attestation bundle;
10. nonce-bound TPM Quote;
11. independent command-line verifier;
12. report signing/hash chaining;
13. external transparency anchor;
14. multi-host corroboration tests.

The prototype should not advance to polished UI work until the core trust chain passes controlled negative tests.
