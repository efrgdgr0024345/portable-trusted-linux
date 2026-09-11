# Prototype 1 Build Scaffold

This directory contains the staged build pipeline for Portable Trusted Linux Prototype 1.

## Mandatory rule

Every meaningful run or change must update `docs/07-lessons-learned.md` before `project_content.json` is updated last. See `PROJECT_RULES.md`.

## Current safe pipeline

```text
00-host-check.sh
      ↓
01-install-deps.sh
      ↓
10-prepare-live-build.sh
      ↓
20-build-live.sh
      ↓
30-artifact-manifest.sh
      ↓
tests/00-static-check.sh
```

These stages prepare and build a first Debian Live artifact and generate evidence about the resulting file. They deliberately do **not** yet implement destructive USB writing, Secure Boot key enrollment, TPM ownership changes, or writes to target-PC internal disks.

## Prototype baseline

- Debian 13 `trixie`
- amd64 / x86-64
- UEFI-first
- Debian `live-build`
- systemd `ukify` planned for the signed UKI stage
- TPM 2.0 optional for lower assurance, required for attested levels
- dm-verity read-only trusted root
- RAM-backed writable session
- network off during initial verification

## First builder sequence

On a clean Debian 13/trixie machine:

```bash
git clone <private-repository-url>
cd portable-trusted-linux
bash tests/00-static-check.sh
bash build/00-host-check.sh
sudo bash build/01-install-deps.sh
bash build/00-host-check.sh
bash build/10-prepare-live-build.sh
bash build/20-build-live.sh
bash build/30-artifact-manifest.sh
```

Every real run must preserve the command output and add any unexpected result to `docs/07-lessons-learned.md`.

## Current remote builder

The first real builder is a disposable DigitalOcean VM named `ptl-builder-01`, running Debian GNU/Linux 13.5 `trixie` on x86-64 with 2 vCPU, approximately 4 GiB RAM and an 80 GiB disk. A repository-scoped self-hosted GitHub Actions runner named `ptl-builder-01` executes as the unprivileged `github-runner` account.

The first remote evidence run completed successfully on 2026-09-11. It verified the expected Debian/x86-64 baseline and approximately 74 GiB free disk space. It also observed that the initial image lacks `git`, `jq` and the planned build-stage packages before `01-install-deps.sh` is run.

The declared dependency set has now been installed and re-verified remotely. `git`, `jq`, `lb`, `xorriso`, `veritysetup`, `ukify`, `sbsign`, and the required TPM2 commands are all detected. The self-hosted runner starts with a reduced service `PATH`, so `00-host-check.sh` now uses a deterministic system PATH including `/usr/sbin`; this prevents installed administration tools such as `/usr/sbin/veritysetup` from being reported as missing.

The DigitalOcean VM did **not** expose `/sys/firmware/efi` or `/dev/tpm*` during the first report. Therefore this VM is a build and artifact-generation host, not evidence that UEFI measured boot or TPM attestation works. Those gates require a later platform that actually exposes the relevant firmware and TPM interfaces.

The first checkout used the GitHub REST archive fallback because `git` was not yet installed on the builder. After dependency installation, Actions uses the normal Git checkout path and the evidence report records the checked-out commit.

## First Debian Live build attempt

GitHub Actions run `34572836941` reached the real privileged `lb build` stage and failed during package cleanup, after substantial live-image construction had already completed. The failure was traced to the **builder host's** `systemd-boot` integration package: its package trigger attempted host bootloader integration on a cloud VM that does not expose normal UEFI runtime.

This is not evidence that the live image itself is invalid. It is a builder-host dependency problem. The dependency installer has therefore been changed to keep `systemd-boot-efi` for EFI binaries while no longer installing the host-integrating `systemd-boot` package.

The failed run's evidence was preserved as GitHub Actions artifact `ptl-first-live-build-34572836941`, including the live-build log. The first successful reference ISO has **not** yet been produced, so milestone M13 remains current.

The build scripts were also hardened after this attempt so privileged live-build scratch data can live in a unique `/tmp` workspace rather than creating root-owned build state inside the GitHub Actions checkout. This reduces cleanup failures and keeps repository ownership boundaries clearer.

## Privileged live-build boundary

The Actions runner remains unprivileged by default. `lb config` and configuration-file preparation run as `github-runner`; the actual `lb build` stage requires root privileges because live-build creates and enters build environments that need privileged filesystem and package operations.

Prototype 1 temporarily grants the runner non-interactive permission for the single `/usr/bin/lb build` command. The sudoers rule was syntax-checked with `visudo`. This is a **temporary weak-to-strong control**, not the final security design. Because live-build configuration can influence privileged build behavior, this permission is effectively a significant trust boundary and must later be replaced by a stronger isolated builder or root-owned build service with tighter input validation.

## Artifact identity

`30-artifact-manifest.sh` records:

- artifact filename;
- SHA-256 digest;
- size in bytes;
- current Git commit;
- current branch;
- build-host identity;
- selected package versions;
- UTC manifest creation time.

This manifest is build evidence. It does not by itself authenticate the artifact; release signing is a later gate.

## Safety boundary

Destructive USB writing will not be implemented until:

1. image generation works reliably;
2. artifact hashing is verified;
3. the target-device selection mechanism has explicit safety checks;
4. negative tests demonstrate that the script refuses ambiguous devices.

Generated artifacts and private signing keys must not be committed to the repository.
