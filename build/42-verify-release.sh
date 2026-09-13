#!/usr/bin/env bash
set -Eeuo pipefail

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
[[ -f "$SIGNATURE" ]] || { echo "Signature not found: $SIGNATURE" >&2; exit 1; }
[[ -f "$PUBLIC_KEY" ]] || { echo "Public key not found: $PUBLIC_KEY" >&2; exit 1; }
[[ -z "$ARTIFACT" || -f "$ARTIFACT" ]] || { echo "Artifact not found: $ARTIFACT" >&2; exit 1; }
command -v jq >/dev/null || { echo 'jq is required' >&2; exit 1; }
command -v openssl >/dev/null || { echo 'openssl is required' >&2; exit 1; }
command -v sha256sum >/dev/null || { echo 'sha256sum is required' >&2; exit 1; }
command -v stat >/dev/null || { echo 'stat is required' >&2; exit 1; }

canonical="$(mktemp)"
trap 'rm -f "$canonical"' EXIT

jq -S -c . "$MANIFEST" > "$canonical"
cmp -s "$MANIFEST" "$canonical" || {
  echo 'MANIFEST CANONICAL FORM: FAILED' >&2
  exit 1
}
echo 'MANIFEST CANONICAL FORM: VERIFIED'

jq -e '
  .schema == "blackcat-release-manifest-v0.1" and
  .signing.algorithm == "Ed25519" and
  (.signing.key_fingerprint_sha256 | test("^[a-f0-9]{64}$")) and
  (.artifact.sha256 | test("^[a-f0-9]{64}$")) and
  (.artifact.size_bytes | type == "number" and . > 0) and
  (.source.commit | test("^[a-f0-9]{40}$"))
' "$MANIFEST" >/dev/null || {
  echo 'MANIFEST SCHEMA: FAILED' >&2
  exit 1
}
echo 'MANIFEST SCHEMA: VERIFIED'

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