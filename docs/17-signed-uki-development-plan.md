# Signed UKI Development Gate — M15

**Status:** VERIFIED — requested development-key acceptance passed at source `3125a1c99342b961ac614878d3ce9b3f874de33e`; not merged to main; firmware enforcement NOT VERIFIED
**Target:** Debian 13/trixie amd64, systemd-ukify 257-series toolchain  
**Safety boundary:** development keys only; no firmware key enrollment; no target-disk writes.

## Accepted development result — 10 November 2026

Fresh normal-pipeline run [42711645617](https://github.com/efrgdgr0024345/portable-trusted-linux/actions/runs/42711645617), job `129482434136`, passed at source `3125a1c99342b961ac614878d3ce9b3f874de33e`. The normal pipeline generated a development-signed UKI from the exact live kernel/initrd, verified its expected sections and metadata, and read the signed fallback back from the ISO filesystem, actual El Torito extent and hybrid EFI partition. All 28 M15 regression checks and 12 runner checks passed. This closes the operator's requested **development-only acceptance**, not real firmware enforcement or release approval.

| Check | Actual result |
| --- | --- |
| Correct development certificate + exact inputs | VERIFIED |
| Kernel byte changed after signing | Signature FAILED as expected |
| Wrong certificate/key | Signature FAILED as expected |
| Missing outer signature | Signature FAILED as expected |
| Changed expected command line | Binding FAILED as expected |
| Actual ISO fallback/boot extents | VERIFIED; same signed UKI bytes |
| Fresh normal pipeline + public artifact upload | Passed |

The live kernel is `6.12.116+deb13-amd64`. The UKI is 67,962,376 bytes, SHA-256 `e42219c658b6cfe285c02f1c667ce505768dcf32bfd7c78fdf10570e27cfddeb`. Certificate DER SHA-256 is `af0794b4e3d1e1830f3610cf4532ed362df639864225ab45d654e4b0c9949193`; public-key SPKI SHA-256 is `e5daf01a5a04a4ce6f0fc6187a34da6b0ad90719ce4aeca2398dc90151430a9e`. Those identities belong only to this run's fresh DEVELOPMENT ONLY key.

Durable public record, raw results, public key/certificate and 22 exact commands: [evidence/2026-11-10-m15](../evidence/2026-11-10-m15/README.md). The signed binary bundle is [artifact 13716513159](https://github.com/efrgdgr0024345/portable-trusted-linux/actions/runs/42711645617/artifacts/13716513159); use **m15/**, not the prior-image diagnostic folder. It expires 17 November 2026 at 06:38 AWST. Preserve the binary bundle before expiry or deleting its builder copy; fresh ephemeral keys prevent byte-identical reconstruction from source.

### Scope and surprises

- Exact live inputs can have hard-link/copy aliases; byte identity, not filename count, selects them.
- objcopy can rewrite a signed PE unless given a separate output; the verifier now checks byte preservation.
- ukify 257 needs `inspect IMAGE --all`, and skips headerless SBAT input despite assembly exit zero; actual-tool regressions cover both.
- Root-owned build evidence must be copied before the independent unprivileged verifier writes reports. Installed sfdisk can be hidden by service PATH.
- The privileged hook's raw `github_run_id` is `unknown` because sudo drops that variable. Raw provenance is preserved, and the outer evidence record binds the actual run/job/source through GitHub metadata.
- Kernel package inputs came from the recorded Debian repositories, not the builder's running kernel. This run embedded 6.12.116; it does not inherit earlier host-kernel observations.
- Large-ESP El Torito load-size warning, physical boot, firmware enforcement, root authentication, legacy BIOS and bit-for-bit reproducibility remain outside the verified claim. No production key, enrollment, TPM ownership/PCR-policy action or target-device write was introduced.

Implementation is on `codex/m15-signed-uki-development`. Main merge remains HUMAN_GATE. No approved Black Cat ISO or new physical-test handoff is created. The live page could not be inspected because the cloud proxy returned CONNECT 403; updating the status feed is distinct from verifying its deployed rendering.

The original plan and dated integration notes below are preserved as history. Their pending states are superseded by this exact accepted run, not silently erased.

## Purpose

M15 will bind the Linux kernel, initrd, kernel command line and OS metadata into a Unified Kernel Image (UKI), then authenticate that PE/EFI binary through a Secure Boot signing path.

This work is separate from M14 release signing:

- **M14 release key** authenticates release metadata and exact artifact identity.
- **M15 Secure Boot key/path** authorizes EFI/UKI execution under firmware/shim policy.

The two identities must remain logically separate even if temporary development fixtures are used during engineering.

## Debian 13 toolchain facts

Debian trixie's `systemd-ukify` provides `ukify build`, `ukify genkey` and `ukify inspect`. The `build` command can combine a kernel and one or more initrds and can embed command-line, OS-release, uname, SBAT and other UKI sections. It can sign for Secure Boot when a Secure Boot private key/certificate are supplied.

For Black Cat, repository scripts should pass explicit paths/options rather than relying on ambient `/etc/systemd/ukify.conf`, so a builder's local configuration cannot silently alter the produced UKI.

Reference: Debian trixie `ukify(1)` man page for the installed 257-series toolchain.

## Initial development flow

The first M15 development build should:

1. take the exact kernel and initrd produced for the Black Cat live image;
2. provide an explicit Black Cat kernel command line;
3. provide explicit OS-release metadata;
4. provide the kernel version explicitly where known;
5. generate a UKI with `ukify build`;
6. inspect the UKI with `ukify inspect` and record section digests;
7. calculate and record the whole-file UKI SHA-256;
8. sign using an **ephemeral DEVELOPMENT ONLY** Secure Boot key/certificate;
9. verify the PE signature independently with an appropriate verification tool;
10. preserve unsigned and deliberately modified copies for negative tests.

The development keypair must be created in temporary storage and destroyed after the test. It is not a production trust root and must never be enrolled into the operator's firmware as part of an automated test.

## Required UKI bindings

Initial Black Cat UKI content should include at minimum:

- `.linux` — exact kernel;
- `.initrd` — exact initrd/initramfs;
- `.cmdline` — explicit trusted command line;
- `.osrel` — Black Cat/Debian OS-release identity;
- `.uname` — explicit kernel version when known;
- `.sbat` — retain valid SBAT metadata so future revocation remains possible.

M17 later adds the authenticated dm-verity root identity/root-hash relationship. M15 must leave an explicit place for that binding rather than hard-code an incompatible layout now.

## Fail-closed development checks

Before any M15 artifact can be called a successful development UKI, automated tests should prove:

- expected sections are present;
- whole UKI SHA-256 is recorded;
- PE/EFI signature verification succeeds with the expected development certificate;
- verification fails with the wrong certificate/key identity;
- byte modification after signing causes verification failure;
- an unsigned UKI is not reported as signed/authorized;
- the embedded command line is the expected exact string;
- the embedded kernel/initrd digests correspond to the intended build inputs;
- build scripts do not discover or use a production/private key implicitly.

## Secure Boot compatibility modes

Black Cat retains two conceptually different deployment modes from the Prototype 1 specification:

### Commodity compatibility mode

Use an ordinary vendor/Microsoft-compatible trust path where practical, likely involving shim or an equivalent signed first-stage path. This maximizes random-PC compatibility but inherits a larger external trust base.

### Controlled-hardware mode

Use explicitly project-owned Secure Boot keys only on hardware deliberately prepared for that policy.

The project must never silently enroll Black Cat keys into arbitrary firmware. Firmware enrollment, key ownership and Secure Boot policy changes remain HUMAN_GATE actions.

## Development-key policy

Until a separate M15 Secure Boot key-custody decision is approved:

- CI/dev tests may generate ephemeral keys;
- development certificates must be labelled DEVELOPMENT ONLY;
- no generated private key is committed or uploaded as a retained artifact;
- no general-purpose builder key becomes a production authority by accident;
- no M14 release private key is reused for Secure Boot signing.

## Measurement preparation

A UKI also provides a cleaner measured-boot boundary for later M16 work. M15 should preserve enough evidence to correlate:

- UKI whole-file SHA-256;
- section digests from `ukify inspect`;
- exact command line;
- kernel/initrd identities;
- Secure Boot signing certificate fingerprint;
- later TPM/event-log measurements.

The project must still distinguish **signature verification** from **firmware enforcement evidence**. A correctly signed UKI on disk does not prove firmware actually enforced that signature during a particular boot.

## M15 preparation acceptance point

Safe pre-work can continue before the real USB/signing hardware exists when:

- the UKI build inputs and evidence schema are documented;
- development-only key policy is explicit;
- negative-test requirements are defined;
- scripts can later consume exact live-build kernel/initrd outputs without changing target disks or firmware.

Actual production Secure Boot key ownership, enrollment and real-hardware enforcement remain later human/physical gates.

## Active development integration (2026-11-10; results pending)

Normal entry point remains `bash build/20-build-live.sh` after `sudo bash build/01-install-deps.sh`. Use a fresh absolute `PTL_BUILD_WORK_DIR` and a clean `out/` evidence destination. The mandatory live-build binary hook calls `build/50-build-development-uki.py`; post-image `build/52-verify-development-uki-iso.py` reads the actual ISO back.

The hook uses the exact live kernel/initrd, checks their chroot version pairing, runs ukify with `/dev/null` configuration and an explicit systemd stub, signs with a fresh one-day DEVELOPMENT ONLY RSA certificate, and deletes private keys from tmpfs before success. `--uefi-secure-boot disable` controls live-build loader selection only; no firmware setting is changed. The hook replaces `boot/grub/efi.img` and the filesystem UEFI fallback with the signed UKI; it does not enroll a key or authorize stock firmware to accept it. Legacy BIOS remains outside this signed-UKI guarantee.

The independent CLI verifier requires an explicitly pinned certificate DER SHA-256, actual `sbverify --cert`, mandatory UKI sections and exact embedded payload/metadata bindings. The bundle's certificate and manifest alone are not an authenticated external trust root. No dm-verity root hash is bound yet; M17 remains separate.

Regression checks include signed synthetic PE fixtures (not bootable kernels), real CLI signature rejection, byte-preserving verification, inconsistent input rejection, and build/log/cache failure propagation. These checks do not replace the pending real live-kernel build, final ISO boot-extent read-back or firmware enforcement evidence.

### Inspector compatibility follow-through
The 257-series inspector is invoked as `ukify inspect IMAGE --all`, with the positional image preceding the option. Remote attempts 42702518668 and 42704330214 exposed this parser ordering constraint. Two tests exercise the real package tool rather than only shell/Python syntax. Retained-input diagnostics are not clean acceptance evidence, and their output is stored separately. Full integration remains pending until the fresh normal pipeline passes.

### SBAT input contract
The product SBAT row is supplied together with the standard `sbat,1,...` header. ukify 257 silently skips headerless input despite exit zero; run 42706245365 was rejected by the independent required-row check. A real-tool build regression now verifies that the row survives merging with the stub/kernel SBAT data. The strict verifier is retained.

### ISO binding regressions
`tests/test_m15_iso_binding.py` exercises the real xorriso/FAT/partition tools on an explicitly NOT BOOTABLE signed fixture. It accepts the correct final-image layout and rejects a modified actual ESP extent or missing hybrid EFI partition. These checks supplement, rather than replace, real-kernel normal-pipeline read-back. Result reports remain scoped by exact ISO hash and pipeline outcome.

### Root/unprivileged evidence boundary and actual-image limitation
The binary hook creates public evidence under privileged live-build. The normal wrapper copies it into runner-owned `out/m15/` before the independent unprivileged ISO verifier writes reports. Run 42706847403 proved signing/rejection controls but failed at this ownership boundary; it is retained as failure evidence, not accepted as a successful pipeline. A regression exercises a non-writable source bundle. Real-image diagnostics and a fresh normal build must both be classified accurately.

The generated large ESP exceeds the legacy El Torito 65535-sector load-size field; xorriso encodes zero with a warning. Exact extent/signature checks do not prove any particular firmware can boot/enforce this development path. No firmware enforcement or physical boot claim is made by M15's development-only signing acceptance.

### Standalone tool resolution
The ISO verifier locates sfdisk through caller PATH or the standard system sbin directories. A service shell can omit those even after package installation; run 42709997738 failed on that lookup after signature/El Torito checks. Actual-layout tests now cover an installed but hidden tool and a genuinely missing tool. The verifier remains unprivileged and fail-closed.
