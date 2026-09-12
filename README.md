# Black Cat

**Black Cat** is the user-facing name for the Portable Trusted Linux (PTL) security research and engineering project.

The project explores a portable, independently verifiable Linux environment for use on computers that may not be trustworthy. It boots independently of the installed operating system, progressively authenticates the portable OS, collects measured-boot and TPM evidence where available, minimizes persistence, and produces evidence that can later be independently verified.

Internal repository names, `PTL_` environment variables, workflow names and evidence identifiers remain unchanged for stability. The Black Cat change is branding, not a trust-model or protocol rename.

## Current status

- Debian 13/trixie amd64 build baseline established.
- First Debian Live hybrid ISO built successfully on the remote builder.
- The first ISO was written to USB in raw/DD mode and **physically booted successfully on real hardware**.
- That physical boot is currently classified as **OBSERVED bootability**, not proof of Secure Boot, TPM attestation, dm-verity or release authenticity.
- The no-typing **System Check** boot path passed the integrated build + UEFI/QEMU smoke gate in GitHub Actions run `34669451222` and was then physically exercised on real hardware.
- The physical System Check automatically displayed UEFI boot, Secure Boot enabled, a TPM device, TCG event-log exposure, no active network link, kernel `6.12.107+deb13-amd64`, architecture `x86_64`, and dm-verity root `NOT_ACTIVE` (expected before M17).
- That physical test also exposed a duplicate-execution bug: the root systemd service created `/run/blackcat` evidence first, then the normal-user login hook tried to run the collector again and received `Permission denied` overwriting the root-owned reports.
- The login hook is now display-only and systemd is the sole report producer. A rebuilt candidate must pass CI and one more physical no-typing test before M13B is complete.
- The previously frozen candidate `live-image-amd64.hybrid.iso` / SHA-256 `34903e52268bf995207cc51e86de9d1950406690445fca7cedfe5a98371f7b32` is therefore **superseded for M13B completion** and must not be mistaken for the corrected candidate.

## No-typing diagnostic path

The candidate image presents a boot choice similar to:

```text
Black Cat - Start
Black Cat - System Check (no typing)
Black Cat - Advanced options
```

Selecting the System Check entry adds `blackcat.check=1` to the kernel command line. The root-owned `blackcat-check.service` runs `/usr/local/bin/blackcat-check` once and creates `/run/blackcat/quick-check.txt` plus JSON evidence. The later login/profile hook only displays that already-created evidence and asks the operator to photograph it; it does not rerun the collector as the live user.

The quick-check output is deliberately classified as **OBSERVED** unless a later independent cryptographic verifier upgrades an individual result. It must never display a universal "SAFE" claim.

## Living project page

Public viewer: https://easyai.com.au/USB/project.php

The public PHP viewer reads `project_content.json` from this private repository using a server-side, read-only GitHub token. Development distribution and release distribution are deliberately separate: the viewer may show one exact **Current Test Candidate** for operator testing while the **Approved Black Cat ISO** remains disabled until explicit release promotion requirements are met. A candidate that exposes a physical-test defect is removed/superseded until the corrected rebuild passes its gates.

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
