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
