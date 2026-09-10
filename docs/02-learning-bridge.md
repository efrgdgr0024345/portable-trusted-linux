# Portable Trusted Linux — Learning Bridge

This document bridges the gap between the plain-English project overview and the technical architecture.

## 1. Bits, bytes and files

At the lowest useful level, a file is an ordered sequence of bytes. If important bytes change, the file is no longer exactly the same file.

## 2. Cryptographic hashes

A cryptographic hash takes data and produces a fixed-size fingerprint. SHA-256 produces a 256-bit digest.

Important distinction:

- **Integrity:** does this data exactly match the expected reference?
- **Authenticity:** who created or authorised that reference?

A matching SHA-256 supports integrity. It does not by itself establish authenticity.

## 3. Digital signatures

Digital signatures use asymmetric cryptography. A private key is kept secret by the signer. A public key is distributed so others can verify signatures.

For software releases, the signature connects a release or manifest to a trusted signing identity.

## 4. UEFI and the boot process

Modern PCs typically begin in UEFI firmware before the operating system starts. The simplified startup path is:

```text
Power on
  ↓
UEFI firmware
  ↓
Boot software
  ↓
Linux kernel / initrd
  ↓
Userspace
```

Because firmware starts before Linux, firmware trust is a separate problem from operating-system trust.

## 5. Secure Boot

Secure Boot is a UEFI mechanism that checks whether boot software is signed by an authorised key. It is best understood as an execution policy: “may this component run under the configured trust policy?”

It does not prove that every authorised component is free of vulnerabilities.

## 6. Kernel, initrd and UKI

- **Kernel:** the low-level core of Linux.
- **initrd / initramfs:** the temporary early-boot environment used before the main root filesystem is available.
- **UKI:** Unified Kernel Image, a signed executable image that can package the kernel, initrd and boot metadata together.

## 7. TPM

TPM means Trusted Platform Module. It provides cryptographic platform-security functions and protected measurement registers.

It is not an antivirus scanner and should not be treated as one.

## 8. PCRs

PCR means Platform Configuration Register. Measurements are extended into PCRs so the final value depends on previous state and the sequence of measurements.

Conceptually:

```text
new_PCR = HASH(old_PCR + new_measurement)
```

## 9. Secure Boot vs Measured Boot

- **Secure Boot:** controls what may execute.
- **Measured Boot:** records cryptographic evidence about what was measured during startup.

They solve related but different problems.

## 10. TCG event log

The event log describes measured boot events and helps a verifier reconstruct or interpret how PCR values were formed.

## 11. TPM Quote, nonce and attestation

A TPM Quote is a TPM-signed statement over selected PCR values. A verifier can provide a fresh random challenge, usually called a nonce, which is included in the signed evidence to reduce replay risk.

Roles:

- **Attester:** the system producing the evidence.
- **Verifier:** an independent system evaluating the evidence.

## 12. dm-verity and Merkle trees

dm-verity verifies a read-only block device using a hierarchy of cryptographic hashes. The structure is a Merkle tree, whose authenticated top value is the root hash.

If protected data changes, the verification path no longer agrees with the trusted root hash.

## 13. Immutable root and RAM overlay

The trusted base Linux filesystem should remain read-only. Temporary changes can be stored in a RAM-backed writable overlay.

```text
Verified read-only Linux
        +
Temporary writable RAM layer
        =
Normal working session
```

On shutdown, normal temporary session state disappears unless deliberately exported.

## 14. Trust anchor

A trust anchor is an independently trusted starting point, such as a known project public key, authenticated release manifest or tightly controlled hardware root of trust.

Every chain of trust has to start somewhere.

## 15. Threat model

A threat model states which attacks the system is designed to address and which attacks remain outside scope or only partially detectable.

This prevents the product from making stronger claims than its evidence supports.

## 16. Reference profile

Measurements become meaningful only when they can be interpreted against expected state. A reference profile can describe expected firmware, boot components, image identities or event-log policy for a particular host or class of host.

Cross-host corroboration should not naïvely expect identical PCR values across unrelated PCs. Stable portable-image identity can be compared universally, while platform-specific measurements need platform-appropriate reference policy.

## 17. Evidence history

Once an attestation report is created, the project also wants to preserve its history.

A report can be canonicalised, hashed, digitally signed and linked to the previous report hash. Its hash can then be published to an external transparency system.

This protects report history, not the truth of the original measurements.
