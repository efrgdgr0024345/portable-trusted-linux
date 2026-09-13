# Black Cat

**Black Cat** is the user-facing name for the Portable Trusted Linux (PTL) security research and engineering project.

The project explores a portable, independently verifiable Linux environment for use on computers that may not be trustworthy. It boots independently of the installed operating system, progressively authenticates the portable OS, collects measured-boot and TPM evidence where available, minimizes persistence, and produces evidence that can later be independently verified.

Internal repository names, `PTL_` environment variables, workflow names and evidence identifiers remain unchanged for stability. The Black Cat change is branding, not a trust-model or protocol rename.

## Current status

- Debian 13/trixie amd64 build baseline established.
- First Debian Live hybrid ISO built successfully on the remote builder and physically booted from USB on real hardware.
- Physical boot remains **OBSERVED bootability**, not proof of Secure Boot, TPM attestation, dm-verity or release authenticity.
- The first physical no-typing System Check exposed duplicate root/user diagnostic execution and `Permission denied` report writes.
- The source fix makes `blackcat-check.service` the sole root-owned evidence producer; the login/profile hook is display-only.
- The corrected M13B candidate passed the integrated self-hosted build plus UEFI/QEMU smoke gates and is frozen for physical retest.
- Current exact test candidate: `live-image-amd64.hybrid.iso`, 376,438,784 bytes, SHA-256 `cb14d25b7fd43e2b0e563efaada357d6ab196a42d717b016c4857f85968fe641`, source commit `c56389b71ecd96270bdd047369378f5a94d8bf93`, workflow run `34686464650`, artifact `10300658965` (`ptl-first-live-build-34686464650`).
- That image is **TEST CANDIDATE — NOT AN APPROVED RELEASE**. M13B remains current until one physical retest confirms the report-permission fix and internal-disk behavior.
- The earlier candidate with SHA-256 `34903e52268bf995207cc51e86de9d1950406690445fca7cedfe5a98371f7b32` remains superseded evidence and must not be used for M13B completion.
- Black Cat branding is present on GRUB/UEFI and Syslinux/legacy menus using a compact repository-tracked splash. Branding is cosmetic only and cannot determine trust or boot eligibility.
- The **Black Cat Automation Manifesto** and root **AI Autonomy Policy** are mandatory: safe, reversible, auditable work proceeds automatically until a genuine human gate is reached.
- Chat/session continuity is recoverable through compact root `AI_WORK_STATE.md`, selective repository reload and an empirical GREEN/AMBER/RED context-health heuristic rather than repeatedly loading the full project history.
- A future post-core milestone now plans to build this repository-governed automation style directly into Black Cat Linux as a sandboxed, model-neutral user-space project system. It is deliberately scheduled after the core prototype so it does not displace the M13B→M25 trust-chain work.

## No-typing diagnostic path

The candidate image presents a boot choice similar to:

```text
Black Cat - Start
Black Cat - System Check (no typing)
Black Cat - Advanced options
```

Selecting the System Check entry adds `blackcat.check=1` to the kernel command line. The root-owned `blackcat-check.service` runs `/usr/local/bin/blackcat-check` once and creates `/run/blackcat/quick-check.txt` plus JSON evidence. The later login/profile hook only displays that already-created evidence and asks the operator to photograph it; it does not rerun the collector as the live user.

The quick-check output is deliberately classified as **OBSERVED** unless a later independent cryptographic verifier upgrades an individual result. It must never display a universal "SAFE" claim.

## Boot branding

The project icon is treated as the visual source for Black Cat system imagery. The live-build preparation stage decodes a compact repository-tracked PNG asset, verifies its exact SHA-256, and stages it for both GRUB/UEFI and Syslinux/legacy menus.

The current compact splash identity is:

- source file: `assets/blackcat-boot-160x120.png.b64`
- decoded SHA-256: `24d03a442c3f7f04a060d30fbf8710f8b8cd2b5495caffdeea7cc47fcbc546ea`
- dimensions/palette: 160×120, 8 colours
- role: cosmetic boot branding only

GRUB uses the image only when PNG/background rendering is available; otherwise the ordinary text boot menu remains usable. Syslinux likewise retains normal bootable labels independently of the splash.

## AI autonomy and recoverable chat continuity

Black Cat deliberately separates **project state** from **chat state**.

- `PROJECT_RULES.md` is the master mandatory policy.
- `AI_AUTONOMY_POLICY.md` is the root continue/stop decision table with `AUTO`, `AUTO_WITH_CHECKS`, `HUMAN_GATE` and `FORBIDDEN_BY_DEFAULT` classes.
- `AI_WORK_STATE.md` is a compact first-read checkpoint for fresh sessions/context recovery. It contains only current milestone/branch/candidate/next-action/human-gate/context-health state rather than full project history.
- `docs/13-ai-continuity-and-context-recovery.md` documents the reusable pattern so another project can reproduce the approach.
- `docs/14-context-health-calibration-log.md` records real continuity/dropout evidence so the GREEN/AMBER/RED heuristic can improve over time.

A normal continuing turn does **not** reload all repository documents. After a fresh session or suspected continuity loss, the AI reads `AI_WORK_STATE.md` first, verifies only volatile GitHub/CI facts needed for the next action, then loads larger files selectively.

The project does not pretend to know an exact hidden ChatGPT context-window percentage. Context health is estimated from observable proxies such as turns since checkpoint, large file/log reads, tool-heavy operations, pasted material and actual continuity symptoms. AMBER/RED checkpoint maintenance is automatic work, not a human gate.

This continuity design is independent of Black Cat's Linux/TPM architecture and is intended to be reusable for other long-running AI-assisted engineering/research projects.

## Future: AI project automation built into Black Cat Linux

After the first usable core prototype, Black Cat plans to turn the same automation pattern into a native user-space project facility. The design is documented in `docs/15-black-cat-ai-project-automation.md` and tracked as future milestone **M26**.

The intended experience is roughly:

```text
blackcat-project init my-project
blackcat-project continue
```

A generated project would carry its own `PROJECT_RULES.md`, `AI_AUTONOMY_POLICY.md`, compact `AI_WORK_STATE.md`, lessons, decisions, tests and project metadata. A local project worker would reconstruct only the minimum state it needs and continue until the next genuine human gate.

The important security boundary is that **the model does not become root or policy authority**. The proposed architecture separates repository state, model reasoning, operating-system capability enforcement and human authority. A sandboxed capability broker would control filesystem, Git, test/build, network and privilege operations independently of the model. Local models and deterministic offline operation are preferred where practical; remote AI providers remain optional project capabilities.

Background continuation should use user-level systemd services/timers or outbound-only checks, not a new public webhook/listener. The future subsystem also includes a project dashboard, audit summaries, Context Health, explicit human-gate display, model-neutral adapters and adversarial tests for prompt injection/policy bypass/credential/device-access attempts.

This is deliberately **future work after M25**, not a reason to delay the current signed-boot, TPM, dm-verity, independent-verifier and safe-USB roadmap.

## Living project page

Public viewer: https://easyai.com.au/USB/project.php

The public PHP viewer reads `project_content.json` from this private repository using a server-side, read-only GitHub token stored outside the public web root. Development distribution and release distribution are deliberately separate: the viewer may show one exact **Current Test Candidate** for operator testing while the **Approved Black Cat ISO** remains disabled until explicit release promotion requirements are met.

The viewer also presents display-only Builder / Server Status separately from authoritative GitHub project data, and its document library surfaces the Automation Manifesto, AI Autonomy Policy, compact AI Work State, continuity/recovery guide, context-health calibration log, future Linux-integrated AI project automation design and other current technical records.

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
    ├── 05-architecture-decisions.md
    ├── 07-lessons-learned.md
    ├── 11-automation-manifesto.md
    ├── 12-high-security-controller-architecture.md
    ├── 13-ai-continuity-and-context-recovery.md
    ├── 14-context-health-calibration-log.md
    └── 15-black-cat-ai-project-automation.md
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

Every meaningful engineering/research session updates `docs/07-lessons-learned.md`, relevant technical/decision documents and the compact work-state checkpoint when materially affected, then updates `project_content.json` **last**. See `PROJECT_RULES.md`.
