#!/usr/bin/env bash
set -Eeuo pipefail

usage() {
  echo "Usage: $0 <release-manifest.json> <ed25519-private-key.pem> <signature.bin>" >&2
  exit 2
}

[[ $# -eq 3 ]] || usage
MANIFEST="$1"
PRIVATE_KEY="$2"
SIGNATURE="$3"

[[ -f "$MANIFEST" ]] || { echo "Manifest not found: $MANIFEST" >&2; exit 1; }
[[ -f "$PRIVATE_KEY" ]] || { echo "Private key not found: $PRIVATE_KEY" >&2; exit 1; }
command -v jq >/dev/null || { echo 'jq is required' >&2; exit 1; }
command -v openssl >/dev/null || { echo 'openssl is required' >&2; exit 1; }
command -v sha256sum >/dev/null || { echo 'sha256sum is required' >&2; exit 1; }

canonical="$(mktemp)"
derived_pub="$(mktemp)"
trap 'rm -f "$canonical" "$derived_pub"' EXIT

jq -S -c . "$MANIFEST" > "$canonical"
cmp -s "$MANIFEST" "$canonical" || {
  echo 'Manifest is valid JSON but is not in required canonical byte form' >&2
  exit 1
}

jq -e '.schema == "blackcat-release-manifest-v0.1" and .signing.algorithm == "Ed25519"' "$MANIFEST" >/dev/null || {
  echo 'Manifest does not declare the supported Black Cat Ed25519 release schema' >&2
  exit 1
}

if ! openssl pkey -in "$PRIVATE_KEY" -text -noout 2>&1 | grep -qi 'ED25519'; then
  echo 'Release private key must be Ed25519' >&2
  exit 1
fi

openssl pkey -in "$PRIVATE_KEY" -pubout -out "$derived_pub" >/dev/null 2>&1
actual_fingerprint="$(openssl pkey -pubin -in "$derived_pub" -outform DER 2>/dev/null | sha256sum | awk '{print $1}')"
expected_fingerprint="$(jq -r '.signing.key_fingerprint_sha256' "$MANIFEST")"

[[ "$actual_fingerprint" == "$expected_fingerprint" ]] || {
  echo 'Private key does not match the release public-key fingerprint embedded in the manifest' >&2
  exit 1
}

mkdir -p "$(dirname "$SIGNATURE")"
openssl pkeyutl -sign -rawin -inkey "$PRIVATE_KEY" -in "$MANIFEST" -out "$SIGNATURE"
[[ -s "$SIGNATURE" ]] || { echo 'Signing produced no signature bytes' >&2; exit 1; }

echo 'BLACK CAT RELEASE MANIFEST: SIGNED'
echo "Manifest: $MANIFEST"
echo "Signature: $SIGNATURE"
echo "Release key fingerprint: $actual_fingerprint"