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
      ↓
30-artifact-manifest.sh
      ↓
tests/00-static-check.sh
```

These stages prepare and build a Debian Live artifact and generate evidence about the resulting file. They deliberately do **not** enroll Secure Boot keys, take TPM ownership, or write to target-PC internal disks.

## Prototype baseline

- Debian 13 `trixie`
- amd64 / x86-64
- UEFI-first
- Debian `live-build`
- systemd `ukify` planned for the signed UKI stage
- TPM 2.0 optional for lower assurance, required for attested levels
- dm-verity read-only trusted root planned
- RAM-backed writable session planned
- network off during initial verification target

## First successful live image

The project has produced a successful Debian Live ISO on the remote builder and the resulting hybrid ISO was written to USB in raw/DD mode. The USB then **physically booted successfully on real hardware**.

That is classified as **OBSERVED bootability**, not as proof of release authenticity, Secure Boot enforcement, TPM attestation, dm-verity root integrity or firmware trust.

## Black Cat no-typing system check

The next image adds a user-facing boot option:

```text
Black Cat - Start
Black Cat - System Check (no typing)
Black Cat - Advanced options
```

The diagnostic entry appends `blackcat.check=1` to the kernel command line. A profile launcher detects that flag and runs `/usr/local/bin/blackcat-check` automatically.

The quick check displays:

- UEFI vs legacy/unknown boot mode;
- Secure Boot state when exposed through EFI variables;
- TPM device exposure;
- TCG firmware event-log exposure;
- whether a dm-verity mapper is active;
- active network-link observation;
- kernel version; and
- architecture.

It also writes a text report and, when `jq` is available, a small JSON report under `/run/blackcat/`. These are runtime observations only. They do not replace the later independent verifier.

## live-build customisation method

`build/10-prepare-live-build.sh` starts from the bootloader templates installed by the builder's recorded `live-build` package, then overlays the small Black Cat customisations from `config/live-build/`.

The repository carries only the customised menu/configuration files and runtime diagnostic scripts. Debian's bootloader binaries and base templates continue to come from the pinned/recorded builder package set.

## Current remote builder

The first real builder is the disposable DigitalOcean VM `ptl-builder-01`, running Debian 13/trixie on x86-64. The repository-scoped self-hosted GitHub Actions runner executes as the unprivileged `github-runner` account.

The VM is a build and artifact-generation host, not an attestation target. It does not expose the firmware/TPM interfaces required for real UEFI measured-boot and TPM Quote evidence.

## Privileged live-build boundary

The Actions runner remains unprivileged by default. `lb config` and preparation run unprivileged; the actual `lb build` operation is the explicit privileged boundary.

Prototype 1 currently uses a narrowly documented temporary permission for `/usr/bin/lb build`. This is transitional under the project's progressive-hardening rule and is not the final security design.

## Artifact identity

`30-artifact-manifest.sh` records the image filename, SHA-256 digest, size, explicit source commit/ref, checked-out commit/ref, GitHub run identity, build-host identity and selected package versions.

This is **build provenance evidence**, not release authenticity. Cryptographic release signing is a separate later gate.

## Safety boundary

A project-supplied destructive USB writer remains deferred until explicit device-selection protections and negative tests exist. For the initial physical test the external imaging tool was deliberately used in raw/DD mode so the hybrid ISO was written as an image rather than reconstructed file-by-file.
