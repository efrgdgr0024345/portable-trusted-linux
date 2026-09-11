# Black Cat

**Black Cat** is the user-facing name for the Portable Trusted Linux (PTL) security research and engineering project.

The project explores a portable, independently verifiable Linux environment for use on computers that may not be trustworthy. It boots independently of the installed operating system, progressively authenticates the portable OS, collects measured-boot and TPM evidence where available, minimizes persistence, and produces evidence that can later be independently verified.

Internal repository names, `PTL_` environment variables, workflow names and evidence identifiers remain unchanged for stability. The Black Cat change is branding, not a trust-model or protocol rename.

## Current status

- Debian 13/trixie amd64 build baseline established.
- First Debian Live hybrid ISO built successfully on the remote builder.
- The first ISO was written to USB in raw/DD mode and **physically booted successfully on real hardware**.
- That physical boot is currently classified as **OBSERVED bootability**, not proof of Secure Boot, TPM attestation, dm-verity or release authenticity.
- The next Black Cat ISO adds a no-typing **System Check** boot-menu entry.

## No-typing diagnostic path

The next image presents a boot choice similar to:

```text
Black Cat - Start
Black Cat - System Check (no typing)
Black Cat - Advanced options
```

Selecting the System Check entry adds `blackcat.check=1` to the kernel command line. The live environment then runs `/usr/local/bin/blackcat-check` automatically and displays a photograph-friendly summary of boot mode, Secure Boot state, TPM exposure, TCG event-log exposure, dm-verity state, network-link state, kernel and architecture.

The quick-check output is deliberately classified as **OBSERVED** unless a later independent cryptographic verifier upgrades an individual result. It must never display a universal "SAFE" claim.

## Living project page

Public viewer: https://easyai.com.au/USB/project.php

The public PHP viewer reads `project_content.json` from this private repository using a server-side, read-only GitHub token.

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
│   ├── packages.list
│   └── live-build/
│       ├── bootloaders/
│       └── includes.chroot/
├── schema/
├── verifier/
├── tests/
├── research/
└── docs/
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

## Security principle

Black Cat does **not** attempt to produce a misleading universal `SAFE` result. It distinguishes what is `VERIFIED`, `ATTESTED`, `OBSERVED`, `WARNING`, `UNKNOWN`, `NOT VERIFIABLE` and `FAILED`.

A successful physical boot proves bootability on the tested configuration. It does not, by itself, authenticate the release or establish firmware, TPM, root-filesystem or hardware trust.

## Mandatory lessons-learned rule

Every meaningful engineering/research session updates `docs/07-lessons-learned.md`, relevant technical/decision documents, and finally `project_content.json` **last**. See `PROJECT_RULES.md`.
