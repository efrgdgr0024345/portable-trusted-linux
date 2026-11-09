# Signed UKI Development Plan — M15 Preparation

**Status:** M15 development-only implementation under integration test; acceptance not yet closed  
**Target:** Debian 13/trixie amd64, systemd-ukify 257-series toolchain  
**Safety boundary:** development keys only; no firmware key enrollment; no target-disk writes.

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
