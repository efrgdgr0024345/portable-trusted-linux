# Black Cat

**Black Cat** is the user-facing name for the Portable Trusted Linux (PTL) security research and engineering project.

The project explores a portable, independently verifiable Linux environment for use on computers that may not be trustworthy. It boots independently of the installed operating system, progressively authenticates the portable OS, collects measured-boot and TPM evidence where available, minimizes persistence, and produces evidence that can later be independently verified.

Internal repository names, `PTL_` environment variables, workflow names and evidence identifiers remain unchanged for stability. The Black Cat change is branding, not a trust-model or protocol rename.

## Current status — M15 development gate verified, 10 November 2026

**The requested development-only signed-UKI gate passed in the normal Prototype 1 pipeline.** Source `3125a1c99342b961ac614878d3ce9b3f874de33e`, run [42711645617](https://github.com/efrgdgr0024345/portable-trusted-linux/actions/runs/42711645617). Correct-key verification and actual ISO read-back passed; tampered, wrong-key and unsigned UKIs were rejected. All 28 M15 regression tests passed.

See [the M15 technical record](docs/17-signed-uki-development-plan.md), [public evidence](evidence/2026-11-10-m15/README.md) and [normal build commands](build/README.md). The validated implementation originated on `codex/m15-signed-uki-development`. All existing histories are consolidated into canonical `main` through [PR #9](https://github.com/efrgdgr0024345/portable-trusted-linux/pull/9). Fresh combined-source and main builds passed; see [integration evidence](evidence/2026-11-10-main-integration/README.md). Original M15 evidence remains attached to its original source and image. No production key or firmware enrollment was used. Firmware enforcement, physical boot of this image and dm-verity root binding are NOT VERIFIED. No approved ISO exists.

The old graphical artifact is no longer returned by GitHub's artifact listing; its builder-copy availability is UNKNOWN. Its former physical-test readiness must not be reused. The new UKI bundle is evidence for inspection, not a physical-test or release handoff.

## Historical status — artwork candidate VM gate complete, 17 September 2026

The dated outcome and observations below are preserved for their original image; they are not current readiness or M15 boot evidence.


**Active outcome: prepare the exact artwork-enabled graphical candidate for a controlled spare-USB physical test. Its bounded VM gates passed; no approved release exists.**

| Capability | Evidence stage | Scope |
| --- | --- | --- |
| Graphical ISO construction | CI build observed | Artwork source `ed5d80ac09c3f1650d67594242643d27efcf15e9`, run `35179377345` |
| Preserved ISO identity | Build and VM harness checked | 770,703,360 bytes; SHA-256 `f498776780c543013e437161a878e22ca4fc40a66f47fc271d6fd6a964c29d75` |
| Graphical boot and System Check | VM interactions and settled prompt observed | Run `35180150392`; physical evidence for this exact image remains pending |
| Earlier M13B physical boot | Previously observed on one machine | Different 376,438,784-byte image `cb14d25b…`; never transfer that observation to this graphical image |
| Release signing | Development implementation | Ephemeral-key tests; production key custody remains undecided |
| Secure Boot / TPM attestation / dm-verity guarantees | Planned or incomplete | A successful build or attractive desktop does not verify them |
| Black Cat Console / Focus | Source implemented | Preserved from main; deployment and phone validation remain unverified |

- [Exact candidate evidence and next gates](docs/20-graphical-candidate-evidence.md)
- [Canonical design folder and brand notes](design/README.md): **Stealth. Intelligence. Independence.**
- [Compact working state](AI_WORK_STATE.md)
- The integrated branch preserves both the USB development history and the parallel Console/Focus history. Merge to main requires approval under the existing autonomy policy.
- The public viewer source now presents capability evidence instead of a single completion percentage. It is prepared for reconciled main; it has not been deployed in this session.
- The current artifact upload succeeded as `blackcat-graphical-development-35179377345` (ID `10480220416`). Artifact publication, build success, VM observations and physical success remain separate facts.

## Historical no-typing diagnostic path

The candidate image presents:

```text
Black Cat - Start
Black Cat - System Check (no typing)
Black Cat - Advanced options
```

Selecting the System Check entry adds `blackcat.check=1` to the kernel command line. The root-owned `blackcat-check.service` runs `/usr/local/bin/blackcat-check` once and creates `/run/blackcat/quick-check.txt` plus JSON evidence. The later login/profile hook only displays that already-created evidence and asks the operator to photograph it; it does not rerun the collector as the live user.

The quick-check output is deliberately classified as **OBSERVED** unless a later independent cryptographic verifier upgrades an individual result. It must never display a universal "SAFE" claim.

The corrected physical M13B run observed UEFI, Secure Boot ENABLED, TPM DEVICE_FOUND, TCG event log FOUND, no active network link, kernel `6.12.107+deb13-amd64`, x86_64 and dm-verity `NOT_ACTIVE` (expected before M17). Non-fatal ACPI `AE_NOT_FOUND` firmware messages appeared during startup but did not prevent Black Cat reaching the diagnostic screen.

## M14 authenticated release signing

Build provenance is not release authenticity. An unsigned SHA-256 beside a download can be replaced together with a malicious image. M14 introduces a separate trusted release-signing layer documented in `docs/16-authenticated-release-signing.md`.

The initial format is intentionally small and auditable:

- canonical compact JSON (`jq -S -c`);
- detached Ed25519 signature;
- SHA-256 fingerprint of the DER-encoded release public key;
- exact artifact filename, byte size and SHA-256;
- source Git commit/ref and build-run identity;
- explicit release ID and creation time.

Repository tooling:

```text
build/40-create-release-manifest.sh
build/41-sign-release-manifest.sh
build/42-verify-release.sh
tests/01-release-signing-check.sh
.github/workflows/release-signing-self-test.yml
```

The verifier fails closed on non-canonical manifest bytes, wrong key fingerprint, invalid signature, wrong artifact filename/size/hash, manifest tampering and wrong-key use.

The GitHub self-test generates a temporary development Ed25519 keypair solely inside the test process. Passing it proves signing/verification mechanics; it does **not** create or approve a production release key.

The production key custody decision is intentionally not automated. It must define where the private key is generated/held, backup/recovery, revocation/replacement, public-key distribution, compromise response and whether signing can ever be automated.

## Boot branding

The project icon is treated as the visual source for Black Cat system imagery. The live-build preparation stage decodes a compact repository-tracked PNG asset, verifies its exact SHA-256, and stages it for both GRUB/UEFI and Syslinux/legacy menus.

Current splash identity:

- source file: `assets/blackcat-boot-160x120.png.b64`
- decoded SHA-256: `24d03a442c3f7f04a060d30fbf8710f8b8cd2b5495caffdeea7cc47fcbc546ea`
- dimensions/palette: 160×120, 8 colours
- role: cosmetic boot branding only

## AI autonomy and recoverable chat continuity

Black Cat deliberately separates **project state** from **chat state**.

- `PROJECT_RULES.md` is the master mandatory policy.
- `AI_AUTONOMY_POLICY.md` is the root continue/stop decision table with `AUTO`, `AUTO_WITH_CHECKS`, `HUMAN_GATE` and `FORBIDDEN_BY_DEFAULT` classes.
- `AI_WORK_STATE.md` is a compact first-read checkpoint for fresh sessions/context recovery.
- `docs/13-ai-continuity-and-context-recovery.md` documents the reusable pattern.
- `docs/14-context-health-calibration-log.md` records real continuity/dropout evidence so the GREEN/AMBER/RED heuristic can improve over time.

A normal continuing turn does **not** reload all repository documents. After a fresh session or suspected continuity loss, the AI reads `AI_WORK_STATE.md` first, verifies only volatile GitHub/CI facts needed for the next action, then loads larger files selectively.

The project does not pretend to know an exact hidden ChatGPT context-window percentage. Context health is estimated from observable proxies. AMBER/RED checkpoint maintenance is automatic work, not a human gate.

## Future: AI project automation built into Black Cat Linux

After the first usable core prototype, Black Cat plans to turn the same automation pattern into a native user-space project facility. The design is documented in `docs/15-black-cat-ai-project-automation.md` and tracked as future milestone **M26**.

The intended experience is roughly:

```text
blackcat-project init my-project
blackcat-project continue
```

A generated project would carry its own rules, autonomy policy, compact work state, machine capability policy, lessons, decisions and tests. A sandboxed capability broker—not the model itself—would enforce filesystem, Git, test/build, network, device and privilege boundaries.

The repository now contains a provider-neutral seed in `templates/ai-project/`. Its sample policy deliberately defaults to HUMAN_GATE, network disabled, no inbound listeners, no root, no raw block devices and no credentials. `schema/ai-project-policy.schema.json` defines the first machine-policy contract for future broker validation.

This remains **future work after M25**, not a reason to delay the current signing, Secure Boot, TPM, dm-verity, verifier and safe-USB roadmap.

## Living project page

Public viewer: https://easyai.com.au/USB/project.php

The public PHP viewer reads `project_content.json` from this private repository using a server-side, read-only GitHub token stored outside the public web root. Development distribution and release distribution remain separate: the viewer may show one exact **Current Test Candidate** while **Approved Black Cat ISO** remains disabled until deliberate release requirements are met.

## Repository structure

```text
portable-trusted-linux/
├── README.md
├── PROJECT_RULES.md
├── AI_AUTONOMY_POLICY.md
├── AI_WORK_STATE.md
├── project_content.json
├── assets/
│   └── blackcat-boot-160x120.png.b64
├── web/
│   └── project.php
├── build/
│   ├── 00-host-check.sh
│   ├── 01-install-deps.sh
│   ├── 10-prepare-live-build.sh
│   ├── 20-build-live.sh
│   ├── 30-artifact-manifest.sh
│   ├── 40-create-release-manifest.sh
│   ├── 41-sign-release-manifest.sh
│   ├── 42-verify-release.sh
│   └── lib/
├── config/
├── schema/
│   └── ai-project-policy.schema.json
├── templates/
│   └── ai-project/
├── verifier/
├── tests/
│   ├── 00-static-check.sh
│   └── 01-release-signing-check.sh
├── research/
└── docs/
    ├── 05-architecture-decisions.md
    ├── 06-prototype-build-specification-v0.1.md
    ├── 07-lessons-learned.md
    ├── 11-automation-manifesto.md
    ├── 12-high-security-controller-architecture.md
    ├── 13-ai-continuity-and-context-recovery.md
    ├── 14-context-health-calibration-log.md
    ├── 15-black-cat-ai-project-automation.md
    └── 16-authenticated-release-signing.md
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

Black Cat does **not** attempt to produce a misleading universal `SAFE` result. It distinguishes `VERIFIED`, `ATTESTED`, `OBSERVED`, `WARNING`, `UNKNOWN`, `NOT VERIFIABLE` and `FAILED`.

A successful physical boot proves bootability on the tested configuration. An authenticated release signature proves that a trusted release key signed specific release metadata. Neither alone establishes the later firmware, TPM, dm-verity or hardware trust claims.

## Mandatory lessons-learned rule

Every meaningful engineering/research session updates `docs/07-lessons-learned.md`, relevant technical/decision documents and the compact work-state checkpoint when materially affected, then updates `project_content.json` **last**. See `PROJECT_RULES.md`.



## Main-only publication and branch retirement

Main is the only long-lived branch and the public viewer's required data source. Status/ops branches are not publication endpoints. The operator-authorized transition, complete initial inventory and live-before-delete gate are in [the main-only plan](docs/27-main-only-consolidation.md). Tested viewer hardening and main-only routing merged through PR #10 at `29cd6ba203e9da012480a5bf65a2557becd34bf5`; all 20 recorded starting tips remain reachable. Production deployment and branch removal are pending and must be reported separately from source integration. No approved/current test ISO is created by this process.
