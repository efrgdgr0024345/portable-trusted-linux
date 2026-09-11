# Portable Trusted Linux

Portable Trusted Linux is a security research and engineering project exploring a portable, independently verifiable Linux environment for use on computers that may not be trustworthy.

The core design boots independently of the installed operating system, authenticates the portable OS, collects measured-boot and TPM evidence where available, operates with minimal persistence, and generates an attestation record that can be independently verified and later anchored to an external transparency system.

A second, explicitly separated research track now explores **Community Compute Commons (CCC)**: an opt-in mode that could donate otherwise idle consumer CPU/GPU/NPU resources to approved LLM and public-interest workloads. CCC is deliberately isolated from Secure Inspection Mode so that networking and remote workload execution cannot weaken the original security objective.

## Current status

**Living document version:** 0.6  
**Core phase:** Prototype 1 Engineering  
**Current core milestone:** Run the build-host preflight and first Debian 13/trixie build on a real clean builder  
**Research track:** Community Compute Commons architecture and feasibility baseline completed

## Living project page

Public viewer: https://easyai.com.au/USB/project.php

The public PHP viewer reads `project_content.json` from this private repository using a server-side, read-only GitHub token. Project content can therefore be updated in GitHub without manually rewriting the public page.

## Repository structure

```text
portable-trusted-linux/
├── README.md
├── PROJECT_RULES.md
├── project_content.json
├── web/
│   └── project.php
├── build/
│   ├── 00-host-check.sh
│   ├── 01-install-deps.sh
│   ├── 10-prepare-live-build.sh
│   ├── 20-build-live.sh
│   ├── 30-artifact-manifest.sh
│   └── lib/
├── config/
├── schema/
├── verifier/
├── tests/
├── research/
│   └── community-compute-30pct-scenarios.csv
└── docs/
    ├── 01-project-overview.md
    ├── 02-learning-bridge.md
    ├── 03-trust-chain-threat-model-v0.1.md
    ├── 04-external-evidence-anchoring.md
    ├── 05-architecture-decisions.md
    ├── 06-prototype-build-specification-v0.1.md
    ├── 07-lessons-learned.md
    ├── 08-compute-commons-feasibility-study.md
    ├── 09-community-compute-commons-architecture.md
    └── 10-threat-model-extension-community-compute-v0.1.md
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

## Future Community Compute Commons boot separation

```text
Pre-OS signed menu
      ├── Secure Inspection Mode
      │     └── offline-first trusted inspection/session
      │
      └── Community Compute Commons
            └── separate signed minimal worker OS
                  └── approved volunteer LLM workloads
```

The pre-OS page records consent and chooses the mode. The LLM worker itself runs only after the separate verified Linux worker environment has booted; it does not run inside firmware/pre-OS code.

## Security principle

The project does **not** attempt to produce a misleading universal `SAFE` result. It distinguishes between what is verified, measured, attested, observed, unknown, not verifiable and failed.

The same principle applies to CCC: aggregate device count or electrical power is not automatically equivalent to hyperscale LLM compute. Useful work must be measured in accepted jobs/tokens, energy, bandwidth, reliability and model-specific performance.

## Mandatory lessons-learned rule

Every meaningful engineering/research session updates `docs/07-lessons-learned.md`, relevant technical/decision documents, and finally `project_content.json` **last**. See `PROJECT_RULES.md`.

## Version history

Git commits preserve the repository's technical history. The human-readable project version history is maintained in `project_content.json`.
