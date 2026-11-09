# Consolidated main — development evidence only

Main merge: `eb7b969f95ba419acc5a2e16ffe587c40c42446d` via [PR #9](https://github.com/efrgdgr0024345/portable-trusted-linux/pull/9). All 17 starting branch tips in `branch-tips.json` are ancestors. No force push or history deletion was used.

Fresh combined integration source `f2bca2c189328f492c3a0c6be18d63fd3e8dc36a` passed [run 42718280786](https://github.com/efrgdgr0024345/portable-trusted-linux/actions/runs/42718280786); fresh merged main source `eb7b969f95ba419acc5a2e16ffe587c40c42446d` passed [run 42720596290](https://github.com/efrgdgr0024345/portable-trusted-linux/actions/runs/42720596290). Hosted source/PHP publication fixtures, M14 positive/negative assertions, UI/recovery source checks and routing guards also passed; see `report.json` for run/job identities.

## Current main evidence

- [Binary UKI evidence bundle](https://github.com/efrgdgr0024345/portable-trusted-linux/actions/runs/42720596290/artifacts/13720897217) — inspect `m15/`. This is not an ISO handoff or release.
- ZIP SHA-256: `d00ff23c689bfe16fbaf92df23c1997e479d25f01a4fa23c86a69ea40f49bd1f`; artifact 13720897217, 68477219 bytes.
- Expires `2026-11-16T23:41:40Z` (17 November 2026, 07:41:40 AWST).
- Signed UKI: 67962376 bytes; SHA-256 `4b39292b6fd5cb3c2c0ed92ec232b0ac20630980d5027f28bb7b58c8c52b4fe8`.
- Development certificate DER SHA-256: `948e5ed840a78cda310c73b140512d0a9020d0094c4b1ca8a894da4a6ff69ab2`.
- Development public-key SPKI SHA-256: `85b9c7dfd670e9b34ee95fb6b6afdab807ef608e36b38e18d2c6333838ef60fb`.
- ISO SHA-256: `fa7a98cfcdab956969e9c11289a0d9779b57080f3f2257852785fae7bb4aa466`. The generated ISO is builder evidence only; no approved ISO or physical candidate is created.
- Builder evidence copy: `/home/github-runner/blackcat-builds/m15/run-42720596290-attempt-1/`.

`main-build/` contains public certificate/key, fingerprint files, provenance, positive/negative results, final-ISO read-back and 22 exact argv/result records. `integration-build/` contains the separate first combined-source result. Records were extracted from retained CI logs and JSON/ledger entries reserialized; no claim of byte-for-byte artifact-file download is made. Actual UKI signature and ISO binding were checked in CI. Public certificate/key digests and equality were independently checked locally with OpenSSL. API artifact digest matches the upload log; no local ZIP/binary download is claimed.

The correct certificate/exact-input policy passes. Tampered, wrong-key, unsigned and changed-expected-cmdline cases reject. Final filesystem fallback, El Torito EFI extent and hybrid EFI partition recover the same signed UKI. Original M15 evidence under `evidence/2026-11-10-m15/` remains attached to its original source/run, not these newer random keys/images.

Historical GUI/check/injected recovery observations in `historical-recovery-observation.json` apply only to the recorded older ISO; its artifact is no longer listed. Current-image GUI/recovery VM behavior, physical boot, firmware enforcement and root authentication are not established. No production key, platform enrollment/ownership, target-device write or service deployment was performed. Private keys were temporary development material, unlinked before successful signing; secure RAM erasure is not claimed. Fresh builds do not recreate exact deleted-key signatures.

Existing JSON publication endpoints remain `project-status-live` and historically deployed `codex/remove-chroot-systemd-boot`; final index synchronization uses fast-forward refs without PHP migration. Current live rendering remains UNKNOWN after HTTP 403, so physical handoff stays closed. Archive finite-retention binary evidence before deleting the builder. See `docs/24-main-consolidation.md`.
