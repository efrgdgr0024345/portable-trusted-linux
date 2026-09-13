# Authenticated Release Signing — M14

**Status:** M14 implementation design and development-test baseline  
**Scope:** authenticate Black Cat release metadata and artifact identity before any image is promoted as an approved release.

## 1. Purpose

Build provenance and physical boot evidence identify what was built and what was observed, but they do not authenticate an approved release to another person. M14 adds a distinct release-signing layer.

The release-signing identity is deliberately separate from the later Secure Boot signing identity/path used for UKI/EFI execution. A key that authenticates release metadata must not automatically become firmware execution authority.

## 2. Trust boundary

The repository may contain:

- public release-verification keys;
- public-key fingerprints;
- signed release manifests;
- detached signatures;
- verification scripts;
- development-only test keys generated ephemerally during tests.

The repository must never contain a production private release-signing key, passphrase, recovery secret or equivalent signing authority.

The general-purpose remote builder is not automatically trusted with a production release private key. Production key ownership/custody is a human trust-policy gate.

## 3. Initial signing format

M14 uses a deliberately small, auditable format:

- manifest encoding: UTF-8 canonical compact JSON produced by `jq -S -c` with its normal trailing newline;
- signature algorithm: Ed25519;
- signature form: detached raw signature bytes;
- public-key identity: SHA-256 of the DER-encoded Ed25519 public key;
- artifact identity: filename, exact byte size and SHA-256;
- source identity: 40-character Git commit plus source ref;
- build identity: GitHub Actions run ID when available;
- release identity: explicit release ID and UTC creation time.

The signed object is the exact canonical manifest bytes. Verification rejects a manifest that parses as JSON but is not in the required canonical byte representation.

## 4. Repository tooling

### `build/40-create-release-manifest.sh`

Creates the canonical release manifest from an artifact and trusted public verification key. It records the public-key fingerprint, never private key material.

Required inputs/environment:

- artifact path;
- trusted Ed25519 public-key path;
- output manifest path;
- `PTL_RELEASE_ID`;
- `PTL_SOURCE_COMMIT`.

Optional provenance fields include `PTL_SOURCE_REF`, `PTL_BUILD_RUN` and an explicit `PTL_RELEASE_CREATED_UTC` for deterministic fixtures.

### `build/41-sign-release-manifest.sh`

Signs an already-canonical manifest with an Ed25519 private key supplied by path. Before signing, it derives the private key's public key and requires its fingerprint to match the fingerprint already embedded in the manifest. This prevents accidental signing with the wrong release identity.

The script does not create, store, upload or choose a production key.

### `build/42-verify-release.sh`

Performs independent verification:

1. require valid canonical JSON;
2. require the expected Black Cat release-manifest schema and Ed25519 declaration;
3. compute the supplied public key fingerprint and require it to equal the signed manifest value;
4. verify the detached Ed25519 signature;
5. when an artifact path is supplied, require exact filename, byte size and SHA-256 equality with the signed manifest.

Any failed mandatory check exits non-zero.

## 5. Development self-test

`tests/01-release-signing-check.sh` generates a temporary Ed25519 development keypair inside a temporary directory, creates and signs a small synthetic artifact manifest, and proves that verification succeeds.

It then proves negative cases fail, including:

- artifact tampering after signing;
- signed-manifest tampering;
- verification with the wrong public key.

The temporary keypair exists only for the test process and is not a release trust root.

A dedicated GitHub-hosted workflow runs this self-test without repository secrets. Passing this workflow validates the signing/verification mechanics, not production key custody.

## 6. Human gate: production key custody

Before Black Cat can publish an authenticated approved release, the operator must deliberately choose the production release-signing key ownership/custody model.

Candidate models for later decision include:

- offline key generated and held on a dedicated removable/offline medium;
- hardware-backed key/token where the selected tooling and recovery model are acceptable;
- a dedicated isolated signing host with narrowly controlled access.

The project must not silently choose the easiest option merely because it is convenient for CI.

The decision must address:

- who/what owns the key;
- where private key operations occur;
- backup/recovery policy;
- revocation and replacement;
- public-key/fingerprint distribution;
- compromise response;
- whether signing is interactive or can ever be automated.

Until that decision is explicitly made, M14 tooling may use ephemeral development keys only.

## 7. Release verification model

A consumer should ultimately need only:

1. an independently trusted Black Cat release public key or fingerprint;
2. the release manifest;
3. its detached signature;
4. the candidate ISO.

The verifier can then establish that the trusted release key signed a manifest naming the exact ISO filename, size, digest and source/build identity, and that the downloaded ISO matches those signed bytes.

This is stronger than an unsigned SHA-256 displayed beside a download because an attacker cannot replace both the ISO and metadata without also possessing the trusted release private key.

## 8. What M14 does not prove

Authenticated release signing does not prove:

- that firmware is trustworthy;
- that Secure Boot enforced the intended Black Cat chain;
- that the kernel/initrd/command line are bound into a signed UKI;
- that dm-verity protects the root filesystem;
- that TPM measurements or quotes are valid;
- that a particular target PC is trustworthy.

Those remain later milestones.

## 9. Acceptance gates

The non-secret M14 implementation can proceed automatically when:

- canonical manifest generation exists;
- signing and independent verification tooling exists;
- Ed25519 key/manifest fingerprint mismatch fails closed;
- artifact tampering fails verification;
- manifest tampering fails signature verification;
- wrong-key verification fails;
- CI self-test passes without using production secrets;
- documentation continues to distinguish release signing from Secure Boot signing.

M14 cannot be considered production-complete until the release-signing key custody human gate is resolved and the selected trusted public key is deliberately published/pinned.