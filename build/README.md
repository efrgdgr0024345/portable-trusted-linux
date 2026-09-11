# Prototype 1 Build Scaffold

This directory contains the staged build pipeline for Portable Trusted Linux Prototype 1.

## Mandatory rule

Every meaningful run or change must update `docs/07-lessons-learned.md` before `project_content.json` is updated last. See `PROJECT_RULES.md`.

## Planned pipeline

```text
00-host-check.sh
      ↓
10-prepare-live-build.sh
      ↓
20-build-root.sh
      ↓
30-build-verity.sh
      ↓
40-build-uki.sh
      ↓
50-build-image.sh
      ↓
60-build-manifest.sh
      ↓
70-verify-artifacts.sh
      ↓
80-write-usb.sh
```

Only the safe preflight and initial configuration stages are scaffolded first. Destructive USB writing will not be implemented until artifact verification and device-safety checks are in place.

## Prototype baseline

- Debian 13 `trixie`
- amd64
- UEFI-first
- TPM 2.0 optional for lower assurance, required for attested levels
- dm-verity read-only trusted root
- RAM-backed writable session
- network off during initial verification

## Generated artifacts

Build output will eventually live under:

```text
artifacts/<build-id>/
```

Generated artifacts and private signing keys must not be committed to the repository.
