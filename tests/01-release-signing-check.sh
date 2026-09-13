#!/usr/bin/env bash
set -Eeuo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
TMP="$(mktemp -d)"
trap 'rm -rf "$TMP"' EXIT

for cmd in jq openssl sha256sum stat; do
  command -v "$cmd" >/dev/null || { echo "Missing required command: $cmd" >&2; exit 1; }
done

artifact="$TMP/blackcat-test.iso"
private_key="$TMP/release-private.pem"
public_key="$TMP/release-public.pem"
manifest="$TMP/release-manifest.json"
signature="$TMP/release-manifest.sig"
wrong_private="$TMP/wrong-private.pem"
wrong_public="$TMP/wrong-public.pem"

printf 'black-cat-release-signing-test\n' > "$artifact"
openssl genpkey -algorithm ED25519 -out "$private_key" >/dev/null 2>&1
openssl pkey -in "$private_key" -pubout -out "$public_key" >/dev/null 2>&1

export PTL_RELEASE_ID='development-self-test'
export PTL_RELEASE_CREATED_UTC='2026-09-13T00:00:00Z'
export PTL_SOURCE_COMMIT='0123456789abcdef0123456789abcdef01234567'
export PTL_SOURCE_REF='release-signing-self-test'
export PTL_BUILD_RUN='self-test'

bash "$ROOT_DIR/build/40-create-release-manifest.sh" "$artifact" "$public_key" "$manifest"
bash "$ROOT_DIR/build/41-sign-release-manifest.sh" "$manifest" "$private_key" "$signature"
bash "$ROOT_DIR/build/42-verify-release.sh" "$manifest" "$signature" "$public_key" "$artifact"
echo '[PASS] valid signed manifest and artifact verify'

cp "$artifact" "$TMP/artifact.original"
printf 'tamper\n' >> "$artifact"
if bash "$ROOT_DIR/build/42-verify-release.sh" "$manifest" "$signature" "$public_key" "$artifact" >/dev/null 2>&1; then
  echo '[FAIL] tampered artifact unexpectedly verified' >&2
  exit 1
fi
echo '[PASS] tampered artifact is rejected'
mv "$TMP/artifact.original" "$artifact"

jq -S -c '.release_id = "tampered-release-id"' "$manifest" > "$TMP/tampered-manifest.json"
if bash "$ROOT_DIR/build/42-verify-release.sh" "$TMP/tampered-manifest.json" "$signature" "$public_key" "$artifact" >/dev/null 2>&1; then
  echo '[FAIL] tampered manifest unexpectedly verified' >&2
  exit 1
fi
echo '[PASS] tampered signed manifest is rejected'

openssl genpkey -algorithm ED25519 -out "$wrong_private" >/dev/null 2>&1
openssl pkey -in "$wrong_private" -pubout -out "$wrong_public" >/dev/null 2>&1
if bash "$ROOT_DIR/build/42-verify-release.sh" "$manifest" "$signature" "$wrong_public" "$artifact" >/dev/null 2>&1; then
  echo '[FAIL] wrong public key unexpectedly verified' >&2
  exit 1
fi
echo '[PASS] wrong public key is rejected'

if bash "$ROOT_DIR/build/41-sign-release-manifest.sh" "$manifest" "$wrong_private" "$TMP/wrong.sig" >/dev/null 2>&1; then
  echo '[FAIL] signer accepted a private key that does not match manifest fingerprint' >&2
  exit 1
fi
echo '[PASS] signer rejects private-key/fingerprint mismatch'

echo 'BLACK CAT RELEASE SIGNING SELF-TEST: PASS'