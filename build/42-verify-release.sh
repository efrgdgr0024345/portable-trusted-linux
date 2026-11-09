#!/usr/bin/env bash
set -Eeuo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
VALIDATOR="$ROOT_DIR/build/lib/validate-release-manifest.sh"

usage() {
  echo "Usage: $0 <release-manifest.json> <signature.bin> <ed25519-public-key.pem> [artifact]" >&2
  exit 2
}

[[ $# -eq 3 || $# -eq 4 ]] || usage
MANIFEST="$1"
SIGNATURE="$2"
PUBLIC_KEY="$3"
ARTIFACT="${4:-}"

[[ -f "$MANIFEST" ]] || { echo "Manifest not found: $MANIFEST" >&2; exit 1; }
[[ -s "$SIGNATURE" ]] || { echo "Signature missing or empty: $SIGNATURE" >&2; exit 1; }
[[ -f "$PUBLIC_KEY" ]] || { echo "Public key not found: $PUBLIC_KEY" >&2; exit 1; }
[[ -z "$ARTIFACT" || -f "$ARTIFACT" ]] || { echo "Artifact not found: $ARTIFACT" >&2; exit 1; }

for cmd in jq openssl sha256sum stat; do
  command -v "$cmd" >/dev/null || { echo "$cmd is required" >&2; exit 1; }
done
[[ -x "$VALIDATOR" ]] || { echo "Manifest validator missing: $VALIDATOR" >&2; exit 1; }

bash "$VALIDATOR" "$MANIFEST"

if ! openssl pkey -pubin -in "$PUBLIC_KEY" -text -noout 2>&1 | grep -qi 'ED25519'; then
  echo 'PUBLIC KEY TYPE: FAILED (Ed25519 required)' >&2
  exit 1
fi

actual_fingerprint="$(openssl pkey -pubin -in "$PUBLIC_KEY" -outform DER 2>/dev/null | sha256sum | awk '{print $1}')"
expected_fingerprint="$(jq -r '.signing.key_fingerprint_sha256' "$MANIFEST")"
[[ "$actual_fingerprint" == "$expected_fingerprint" ]] || {
  echo 'RELEASE KEY FINGERPRINT: FAILED' >&2
  exit 1
}
echo "RELEASE KEY FINGERPRINT: VERIFIED ($actual_fingerprint)"

if ! openssl pkeyutl -verify -pubin -inkey "$PUBLIC_KEY" -rawin -in "$MANIFEST" -sigfile "$SIGNATURE" >/dev/null 2>&1; then
  echo 'RELEASE SIGNATURE: FAILED' >&2
  exit 1
fi
echo 'RELEASE SIGNATURE: VERIFIED'

if [[ -n "$ARTIFACT" ]]; then
  expected_name="$(jq -r '.artifact.filename' "$MANIFEST")"
  expected_size="$(jq -r '.artifact.size_bytes' "$MANIFEST")"
  expected_sha256="$(jq -r '.artifact.sha256' "$MANIFEST")"
  actual_name="$(basename "$ARTIFACT")"
  actual_size="$(stat -c '%s' "$ARTIFACT")"
  actual_sha256="$(sha256sum "$ARTIFACT" | awk '{print $1}')"

  [[ "$actual_name" == "$expected_name" ]] || { echo 'ARTIFACT FILENAME: FAILED' >&2; exit 1; }
  [[ "$actual_size" == "$expected_size" ]] || { echo 'ARTIFACT SIZE: FAILED' >&2; exit 1; }
  [[ "$actual_sha256" == "$expected_sha256" ]] || { echo 'ARTIFACT SHA-256: FAILED' >&2; exit 1; }

  echo 'ARTIFACT FILENAME: VERIFIED'
  echo 'ARTIFACT SIZE: VERIFIED'
  echo "ARTIFACT SHA-256: VERIFIED ($actual_sha256)"
fi

echo 'BLACK CAT AUTHENTICATED RELEASE VERIFICATION: PASS'