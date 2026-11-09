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

The schema is **closed**, not extensible by accident. Unexpected top-level or nested fields are rejected. This avoids a future verifier silently ignoring attacker-controlled or semantically ambiguous metadata.

## 4. Repository tooling

### `build/lib/validate-release-manifest.sh`

Central fail-closed validator used by the generator, signer and verifier. It requires:

- exactly one top-level JSON object, never a stream of JSON documents;
- exact canonical JSON bytes;
- the exact supported top-level and nested field sets;
- schema `blackcat-release-manifest-v0.1`;
- constrained release ID format;
- UTC timestamp format;
- basename-only artifact filename with no control characters;
- positive integer artifact size;
- lower-case 64-character SHA-256 values;
- a 40-character lower-case source commit;
- non-empty bounded source-ref/build-run strings;
- Ed25519, raw-detached signature form and the exact manifest-encoding declaration.

Any mismatch exits non-zero.

The validator is tracked executable (`100755`); all three callers require that
prerequisite even though they invoke it through Bash. Preserve that mode in clean
checkouts rather than bypassing the prerequisite.

### `build/40-create-release-manifest.sh`

Creates the canonical release manifest from an artifact and trusted public verification key. It records the public-key fingerprint, never private key material.

Required inputs/environment:

- artifact path;
- trusted Ed25519 public-key path;
- output manifest path;
- `PTL_RELEASE_ID`;
- `PTL_SOURCE_COMMIT`.

Optional provenance fields include `PTL_SOURCE_REF`, `PTL_BUILD_RUN` and an explicit `PTL_RELEASE_CREATED_UTC` for deterministic fixtures.

The generator rejects empty artifacts, invalid release IDs/timestamps and non-Ed25519 public keys, then sends its result through the shared strict validator before publishing the manifest file.

### `build/41-sign-release-manifest.sh`

Signs an already-canonical manifest with an Ed25519 private key supplied by path. Before signing, it:

1. runs the shared strict validator;
2. verifies the private key is Ed25519;
3. derives the corresponding public key;
4. requires its SHA-256 fingerprint to match the fingerprint embedded in the manifest;
5. writes the detached signature through a temporary file and atomically renames it into place.

The script does not create, store, upload or choose a production key.

### `build/42-verify-release.sh`

Performs independent verification:

1. require valid canonical JSON through the shared strict validator;
2. require the exact supported Black Cat release-manifest schema;
3. require an Ed25519 public key;
4. compute the supplied public-key fingerprint and require it to equal the signed manifest value;
5. verify the detached Ed25519 signature;
6. when an artifact path is supplied, require exact filename, byte size and SHA-256 equality with the signed manifest.

Any failed mandatory check exits non-zero.

## 5. Development self-test

`tests/01-release-signing-check.sh` generates a temporary Ed25519 development keypair inside a temporary directory, creates and signs a small synthetic artifact manifest, and proves that verification succeeds.

It then proves negative cases fail, including:

- artifact tampering after signing;
- signed-manifest tampering;
- non-canonical JSON encoding;
- multiple top-level JSON documents, rejected by the validator and signer, and
  by the verifier even when the complete invalid stream has a valid ephemeral
  development signature;
- unexpected manifest fields;
- wrong artifact filename even when bytes match;
- malformed signature bytes;
- verification with the wrong public key;
- signing with a private key whose public fingerprint does not match the manifest;
- attempting to build a release manifest with a non-Ed25519 public key;
- invalid release IDs.

The temporary keypair exists only for the test process and is not a release trust root.

A dedicated GitHub-hosted workflow runs this self-test without repository secrets. Passing this workflow validates the signing/verification mechanics, not production key custody.

A local isolated shell execution of the same scripts is also useful as development evidence when GitHub Actions has not yet produced a run for the current branch revision. Local success must be labelled as local development evidence, not CI evidence.

### 2026-11-09 bounded evidence cycle

On `codex/remove-chroot-systemd-boot`, the clean-checkout baseline exposed a
non-executable validator and acceptance of multiple canonical JSON documents.
The execute bit was restored and canonicalization now requires exactly one
object before the existing byte comparison and closed-schema checks. The new
regression failed before the parser correction. All 14 positive/negative
assertions now pass locally using only temporary development keys, which the
self-test removes on exit.

Current-revision GitHub Actions evidence remains **NOT OBSERVED**: this
environment's outbound proxy rejects `api.github.com` with HTTP 403. Git access
works, but it is not evidence of an Actions run or conclusion. The existing PR
workflow is eligible to run when these signing paths change; inspect the run's
exact source revision and completed test job before recording CI as observed.
The environment draft service could not be read, so no unknown network allowlist
was replaced. Required connectivity addition: `api.github.com`, preserving all
existing destinations. No production signing secret is needed for this workflow.

The broader static suite separately reports pre-existing login-hook and
Automation Manifesto wording failures. Those unchanged files are outside this
M14-only correction; their failures are not represented as passing checks.

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

## 7. Key rotation and compromise policy baseline

The production key has not been created, but the lifecycle rules can still be defined now.

### Planned rotation

A future replacement key must have a distinct public-key fingerprint. A release must never silently switch trusted signing identity. The new public key/fingerprint must be deliberately published through the project's trusted release-reference path before releases signed only by the new key are treated as approved.

### Suspected compromise

If the production release private key is suspected compromised:

1. stop approving new releases under that key;
2. preserve existing signed evidence and identify the affected trust window;
3. generate/select a replacement identity only through the approved custody process;
4. publish the old key/fingerprint as revoked/superseded through the trusted reference path;
5. require users/verifiers to update the trusted key set deliberately;
6. never claim that deleting a GitHub file or rotating an API credential revokes already-distributed cryptographic trust.

### Recovery

Recovery material must be protected to the same or stronger standard as the primary production private key. A convenient plaintext backup would nullify hardware/offline custody benefits.

These rules define behavior but do not authorize creation of the production key.

## 8. Release verification model

A consumer should ultimately need only:

1. an independently trusted Black Cat release public key or fingerprint;
2. the release manifest;
3. its detached signature;
4. the candidate ISO.

The verifier can then establish that the trusted release key signed a manifest naming the exact ISO filename, size, digest and source/build identity, and that the downloaded ISO matches those signed bytes.

This is stronger than an unsigned SHA-256 displayed beside a download because an attacker cannot replace both the ISO and metadata without also possessing the trusted release private key.

## 9. What M14 does not prove

Authenticated release signing does not prove:

- that firmware is trustworthy;
- that Secure Boot enforced the intended Black Cat chain;
- that the kernel/initrd/command line are bound into a signed UKI;
- that dm-verity protects the root filesystem;
- that TPM measurements or quotes are valid;
- that a particular target PC is trustworthy.

Those remain later milestones.

## 10. Acceptance gates

The non-secret M14 implementation can proceed automatically when:

- canonical manifest generation exists;
- one shared fail-closed manifest validator exists;
- signing and independent verification tooling exists;
- Ed25519 key/manifest fingerprint mismatch fails closed;
- artifact tampering fails verification;
- manifest tampering fails signature verification;
- non-canonical/extra-field manifests fail validation;
- wrong filename, wrong key, malformed signature and unsupported key types fail;
- development self-tests pass without using production secrets;
- CI self-test passes when GitHub Actions produces current-revision evidence;
- documentation continues to distinguish release signing from Secure Boot signing.

M14 cannot be considered production-complete until the release-signing key custody human gate is resolved and the selected trusted public key is deliberately published/pinned.


## 2026-11-10 main-consolidation evidence update

The earlier bounded-cycle API/CI limitation above is historical. During operator-authorized main consolidation, the connected GitHub API supplied actual successful M14 hosted evidence: run 42718300278, job 129505420171, source f2bca2c189328f492c3a0c6be18d63fd3e8dc36a. All 14 positive/negative assertions, including signed multi-document JSON rejection, passed. The same corrected executable validator and regressions are preserved in main merge eb7b969f95ba419acc5a2e16ffe587c40c42446d. No production signing authority or approved release is implied. See docs/24-main-consolidation.md for the combined-source record. Previous failures and capability limits remain recorded rather than rewritten. Author: Codex, GPT-6 family; exact deployed variant unknown.
