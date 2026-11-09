# M15 development-only acceptance evidence

**VERIFIED within the stated development scope.** See [report.json](report.json) for exact source/run/artifact identities and limitations. This is not an approved release or firmware-enforcement claim.

The accepted normal pipeline is run [42711645617](https://github.com/efrgdgr0024345/portable-trusted-linux/actions/runs/42711645617), source `3125a1c99342b961ac614878d3ce9b3f874de33e`. Its real kernel is `6.12.116+deb13-amd64`; builder host/kernel identity is separate from that embedded version.

Download the [public UKI evidence bundle](https://github.com/efrgdgr0024345/portable-trusted-linux/actions/runs/42711645617/artifacts/13716513159) for inspection and open **m15/**. The separate m15-iso-diagnostic folder is an older-image diagnostic, not acceptance. The bundle contains the signed UKI, inputs.json, an offline verifier, public certificates/keys, exact commands, package/version logs, negative results and actual ISO read-back results. It contains no private signing key. The ISO itself is retained on the builder, not offered here as a physical-test handoff.

GitHub's artifact expires **17 November 2026, 06:38 AWST**. Archive that binary bundle before expiry or deleting the builder; fresh ephemeral keys cannot recreate these exact signed bytes from source. The small public evidence here remains versioned in Git.

## Offline positive verification

On an independent trusted inspection machine with Python 3, openssl, binutils and sbsigntool installed, run from the bundle's m15/ directory:

```bash
python3 verify-development-uki.py \
  --uki blackcat-development.efi \
  --certificate development-cert.pem \
  --manifest inputs.json \
  --expected-cert-sha256 af0794b4e3d1e1830f3610cf4532ed362df639864225ab45d654e4b0c9949193
```

Pin this fingerprint from the reviewed record, not just a potentially replaced bundle. A replaced image plus replaced self-signed certificate/manifest is not an authenticated release. The public key's SPKI SHA-256 is `e5daf01a5a04a4ce6f0fc6187a34da6b0ad90719ce4aeca2398dc90151430a9e`.

## Negative results and replay

The real normal build rejected a kernel-byte mutation, the wrong supplied certificate and the unsigned UKI at actual sbverify signature checks. A changed expected command line was also rejected. See tests.json and the exact argv/exit codes in commands.jsonl. Private keys and original temporary negative fixture files were not retained.

The bundle includes wrong-key-cert.pem, so `sbverify --cert wrong-key-cert.pem blackcat-development.efi` must fail. To check missing-signature rejection independently, copy the UKI to a temporary regular file, remove its outer signature with `sbattach --remove`, and require sbverify to fail. Never modify the original evidence file. The source's tests/test_m15_uki_verifier.py additionally documents mutation/binding cases on explicitly non-bootable fixtures.

No firmware enrollment, TPM ownership change, target-device write or production key is needed for these offline checks. Actual firmware enforcement, physical boot and authenticated root remain outside this accepted gate.
