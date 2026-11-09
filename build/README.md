# Prototype 1 Build Scaffold

This directory contains the staged build pipeline for **Black Cat**, the user-facing brand for the Portable Trusted Linux (PTL) project. Internal PTL script names and environment variables remain unchanged for stability.

## Mandatory rule

Every meaningful run or change must update `docs/07-lessons-learned.md` before `project_content.json` is updated last. See `PROJECT_RULES.md`.

## Current pipeline

```text
00-host-check.sh
      ↓
01-install-deps.sh
      ↓
10-prepare-live-build.sh
      ↓
20-build-live.sh
      ├── live-build binary hook: 50-build-development-uki.py
      └── post-ISO read-back: 52-verify-development-uki-iso.py
      ↓
30-artifact-manifest.sh
      ↓
M14 authenticated release-signing layer
      ├── 40-create-release-manifest.sh
      ├── 41-sign-release-manifest.sh
      └── 42-verify-release.sh
      ↓
tests/00-static-check.sh + tests/01-release-signing-check.sh
```

The live-build stages prepare and build a Debian Live artifact and generate provenance evidence. The M14 layer separately creates and verifies authenticated release metadata. None of these stages enroll Secure Boot keys, take TPM ownership, or write to target-PC internal disks.

## Prototype baseline

- Debian 13 `trixie`
- amd64 / x86-64
- UEFI-first
- Debian `live-build`
- authenticated release signing: M14 current
- mandatory systemd `ukify` + ephemeral development-signing stage (M15; acceptance evidence in docs/17)
- TPM 2.0 optional for lower assurance, required for attested levels
- dm-verity read-only trusted root planned
- RAM-backed writable session planned
- network off during initial verification target

## M13B physical result

The corrected M13B image physically booted on real hardware through the no-typing **Black Cat - System Check** path. The old report `Permission denied` regression was absent. A read-only `lsblk -o NAME,SIZE,TYPE,MOUNTPOINTS` observation showed the live USB mounted at `/run/live/medium` while the internal 476.9G NVMe and all four of its partitions had no mount points.

This closes M13B on the tested hardware/configuration as **OBSERVED** evidence. It is not a universal guarantee or attestation result.

## Black Cat no-typing system check

The image provides:

```text
Black Cat - Start
Black Cat - System Check (no typing)
Black Cat - Advanced options
```

The diagnostic entry appends `blackcat.check=1` to the kernel command line. `blackcat-check.service` is the sole root-owned evidence producer; the later login/profile hook only displays the already-created report.

The quick check reports:

- UEFI vs legacy/unknown boot mode;
- Secure Boot state when exposed through EFI variables;
- TPM device exposure;
- TCG firmware event-log exposure;
- whether a dm-verity mapper is active;
- active network-link observation;
- kernel version; and
- architecture.

It writes text/JSON reports under `/run/blackcat/`. These are runtime observations only and do not replace later independent cryptographic verification.

## live-build customisation method

`build/10-prepare-live-build.sh` starts from the bootloader templates installed by the builder's recorded `live-build` package, then overlays the small Black Cat customisations from `config/live-build/`.

The repository carries only the customised menu/configuration files and runtime diagnostic scripts. Debian's bootloader binaries and base templates continue to come from the pinned/recorded builder package set.

## Current remote builder

The first real builder is the disposable DigitalOcean VM `ptl-builder-01`, running Debian 13/trixie on x86-64. The repository-scoped self-hosted GitHub Actions runner executes as the unprivileged `github-runner` account.

The VM is a build and artifact-generation host, not an attestation target. It does not expose the firmware/TPM interfaces required for real UEFI measured-boot and TPM Quote evidence.

## Privileged live-build boundary

The Actions runner remains unprivileged by default. `lb config` and preparation run unprivileged; the actual `lb build` operation is the explicit privileged boundary.

The restored disposable builder currently grants `github-runner` unrestricted passwordless sudo, explicitly authorized by the operator. This is root-equivalent, broader than the earlier narrow `/usr/bin/lb build` arrangement, and temporary under progressive hardening. See `docs/runner-service-startup.md`; do not keep production keys or unrelated workloads there.

## Artifact identity versus release authenticity

`30-artifact-manifest.sh` records image filename, SHA-256, size, source identity, Actions run identity, builder identity and selected package versions. That is **build provenance evidence**.

M14 adds a separate authenticated release layer documented in `docs/16-authenticated-release-signing.md`:

- `40-create-release-manifest.sh` creates canonical signed metadata for one exact artifact and trusted Ed25519 public key;
- `41-sign-release-manifest.sh` signs those exact canonical bytes and refuses a private key whose derived public-key fingerprint does not match the manifest;
- `42-verify-release.sh` independently checks canonical form, key fingerprint, detached Ed25519 signature, artifact filename, exact byte size and SHA-256;
- `tests/01-release-signing-check.sh` generates only an ephemeral development keypair and proves valid verification plus tamper/wrong-key failures.

The signing self-test intentionally requires **no production secret**. It validates mechanics only.

## Signing-key boundary

The release-signing identity is separate from the later Secure Boot signing identity/path. No production private signing key belongs in GitHub or on the general-purpose builder merely for convenience.

Selecting the real release-signing key ownership/custody model is a human trust-policy gate. Until that is explicitly resolved, only ephemeral development keys may be used in automated tests.

## Safety boundary

A project-supplied destructive USB writer remains deferred until explicit device-selection protections and negative tests exist. For physical prototype tests the external imaging tool is used deliberately in raw/DD mode so the hybrid ISO is written as an image rather than reconstructed file-by-file.

## Development signed-UKI pipeline (M15)

From a clean checkout and an empty output destination:

```bash
sudo bash build/01-install-deps.sh
export PTL_BUILD_WORK_DIR="$(mktemp -d /var/tmp/blackcat-m15.XXXXXX)"
bash build/20-build-live.sh
bash build/30-artifact-manifest.sh
```

The normal stage automatically signs exact live kernel/initrd inputs and binds command line, OS metadata, kernel version and SBAT. The signed UKI replaces the development ESP fallback; a post-ISO verifier checks actual filesystem, El Torito and hybrid EFI extents. Each run creates a new DEVELOPMENT ONLY certificate, so repeatable execution is not a bit-for-bit reproducibility claim. Keys remain transient in tmpfs and are unlinked before success; public evidence is under `out/m15/`. Required tamper, wrong-key and unsigned tests must fail at actual signature verification.

The image's UEFI entry goes directly through this UKI with its signed command line. The older GRUB diagnostic/menu observations above belong to earlier candidates; the direct development UEFI profile does not provide those menu choices. Legacy BIOS remains outside M15's signed-UKI guarantee. No UI code was redesigned and no physical retest is implied.

Stock firmware has not enrolled this ephemeral certificate and is not expected to trust it. No Secure Boot enrollment, firmware state change or TPM policy action occurs. The root squashfs is not authenticated by this UKI yet; M17 must bind that root identity. A signed UKI does not by itself verify the complete OS or establish firmware enforcement. See `docs/17-signed-uki-development-plan.md` for validation status and scoped evidence.
