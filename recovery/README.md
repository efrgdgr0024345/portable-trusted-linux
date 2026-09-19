# One-USB Recovery Writer

This directory contains a deliberately narrow recovery payload for the HP OMEN recovery exercise.

## Security model

The payload is intended to run only from an official Ubuntu live session booted with the `toram` kernel option. The `toram` requirement matters because the same physical USB will be overwritten after the live system has copied its read-only media into RAM. Ubuntu's Casper documentation states that `toram` copies the whole read-only media into RAM before mounting the live root filesystem.

The payload:

1. refuses to run destructively unless `toram` is present;
2. accepts exactly one removable USB transport device in the expected 14-18 GB range;
3. rejects an NVMe device as a target;
4. authenticates Canonical's signed `SHA256SUMS`;
5. pins the Canonical CD image signing-key fingerprint;
6. requires the independently established Ubuntu 26.04.1 SHA-256;
7. streams the ISO directly to the USB while hashing it;
8. refuses success unless the streamed hash matches;
9. flushes the physical device;
10. reads exactly the official ISO byte length back from the raw USB device and hashes that data again.

The script defaults to inspection mode and does not write disks. Destructive mode requires `--write` plus an exact interactive confirmation phrase.

## Fixed reference

- ISO: `ubuntu-26.04.1-desktop-amd64.iso`
- Size: `6482409472` bytes
- SHA-256: `601e30fbf5d97759367c632e2c33630665039b7e2158fd068403da3ccf1bda1f`
- Canonical CD image signing key fingerprint: `843938DF228D22F7B3742BC0D94AA3F0EFE21092`

## Important limitation

This payload does not claim that the first Ubuntu live environment is universally trustworthy. It is a bridge that removes the installed Windows operating system from the write/verification path and then independently authenticates the final Ubuntu image. Firmware and first-boot trust are tracked separately.
