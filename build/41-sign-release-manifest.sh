#!/usr/bin/env bash
set -Eeuo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
VALIDATOR="$ROOT_DIR/build/lib/validate-release-manifest.sh"

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

for cmd in jq openssl sha256sum; do
  command -v "$cmd" >/dev/null || { echo "$cmd is required" >&2; exit 1; }
done
[[ -x "$VALIDATOR" ]] || { echo "Manifest validator missing: $VALIDATOR" >&2; exit 1; }

bash "$VALIDATOR" "$MANIFEST" >/dev/null

if ! openssl pkey -in "$PRIVATE_KEY" -text -noout 2>&1 | grep -qi 'ED25519'; then
  echo 'Release private key must be Ed25519' >&2
  exit 1
fi

derived_pub="$(mktemp)"
trap 'rm -f "$derived_pub"' EXIT

openssl pkey -in "$PRIVATE_KEY" -pubout -out "$derived_pub" >/dev/null 2>&1
actual_fingerprint="$(openssl pkey -pubin -in "$derived_pub" -outform DER 2>/dev/null | sha256sum | awk '{print $1}')"
expected_fingerprint="$(jq -r '.signing.key_fingerprint_sha256' "$MANIFEST")"

[[ "$actual_fingerprint" == "$expected_fingerprint" ]] || {
  echo 'Private key does not match the release public-key fingerprint embedded in the manifest' >&2
  exit 1
}

mkdir -p "$(dirname "$SIGNATURE")"
tmp_sig="$(mktemp "${SIGNATURE}.tmp.XXXXXX")"
trap 'rm -f "$derived_pub" "$tmp_sig"' EXIT

openssl pkeyutl -sign -rawin -inkey "$PRIVATE_KEY" -in "$MANIFEST" -out "$tmp_sig"
[[ -s "$tmp_sig" ]] || { echo 'Signing produced no signature bytes' >&2; exit 1; }

mv "$tmp_sig" "$SIGNATURE"
trap 'rm -f "$derived_pub"' EXIT

echo 'BLACK CAT RELEASE MANIFEST: SIGNED'
echo "Manifest: $MANIFEST"
echo "Signature: $SIGNATURE"
echo "Release key fingerprint: $actual_fingerprint"