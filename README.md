# Portable Trusted Linux

Portable Trusted Linux is a security research and engineering project exploring a portable, independently verifiable Linux environment for use on computers that may not be trustworthy.

The design aims to boot independently of the installed operating system, authenticate the portable OS, collect measured-boot and TPM evidence where available, operate with minimal persistence, and generate an attestation record that can be independently verified and later anchored to an external transparency system.

## Current status

**Version:** 0.3  
**Phase:** Architecture / pre-prototype  
**Current engineering milestone:** Prototype Build Specification

## Living project page

Public viewer: https://easyai.com.au/USB/project.php

The public PHP viewer reads `project_content.json` from this private repository using a server-side, read-only GitHub token. Project content can therefore be updated in GitHub without manually rewriting the public page.

## Repository structure

```text
portable-trusted-linux/
├── README.md
├── project_content.json            # Canonical living-project index
├── web/
│   └── project.php                 # Source for the public project viewer
└── docs/
    ├── README.md
    ├── 01-project-overview.md
    ├── 02-learning-bridge.md
    ├── 03-trust-chain-threat-model-v0.1.md
    ├── 04-external-evidence-anchoring.md
    └── 05-architecture-decisions.md
```

## Core runtime trust chain

```text
Trusted release/signing reference
        ↓
Verified USB image
        ↓
UEFI / Secure Boot
        ↓
Measured Boot / TCG Event Log
        ↓
TPM PCR evidence
        ↓
Signed UKI
        ↓
dm-verity protected immutable Linux
        ↓
RAM-backed disposable session
        ↓
Nonce-bound TPM Quote
        ↓
Independent verifier
        ↓
Attestation report
```

## Evidence-history chain

```text
Attestation report
        ↓
Canonical representation
        ↓
SHA-256
        ↓
Digital signature
        ↓
Previous-report hash
        ↓
Local hash chain
        ↓
External transparency log
        ↓
Optional blockchain anchor
```

## Security principle

The project does **not** attempt to produce a misleading universal `SAFE` result. It distinguishes between what is verified, what is measured, what is inferred, what is unknown, and what cannot be verified from software running on arbitrary hardware.

## Version history

Git commits preserve the repository's technical history. The human-readable project version history is also maintained in `project_content.json`.
