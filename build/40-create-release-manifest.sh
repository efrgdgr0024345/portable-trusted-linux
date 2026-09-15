#!/usr/bin/env bash
set -Eeuo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
VALIDATOR="$ROOT_DIR/build/lib/validate-release-manifest.sh"

usage() {
  echo "Usage: $0 <artifact> <ed25519-public-key.pem> <release-manifest.json>" >&2
  exit 2
}

[[ $# -eq 3 ]] || usage
ARTIFACT="$1"
PUBLIC_KEY="$2"
OUTPUT="$3"

: "${PTL_RELEASE_ID:?PTL_RELEASE_ID is required}"
: "${PTL_SOURCE_COMMIT:?PTL_SOURCE_COMMIT is required}"

[[ "$PTL_RELEASE_ID" =~ ^[A-Za-z0-9][A-Za-z0-9._-]{0,127}$ ]] || {
  echo 'PTL_RELEASE_ID contains unsupported characters or length' >&2
  exit 1
}
[[ "$PTL_SOURCE_COMMIT" =~ ^[A-Fa-f0-9]{40}$ ]] || {
  echo 'PTL_SOURCE_COMMIT must be a 40-character Git commit' >&2
  exit 1
}
[[ -f "$ARTIFACT" ]] || { echo "Artifact not found: $ARTIFACT" >&2; exit 1; }
[[ -s "$ARTIFACT" ]] || { echo 'Artifact must not be empty' >&2; exit 1; }
[[ -f "$PUBLIC_KEY" ]] || { echo "Public key not found: $PUBLIC_KEY" >&2; exit 1; }

for cmd in jq openssl sha256sum stat; do
  command -v "$cmd" >/dev/null || { echo "$cmd is required" >&2; exit 1; }
done
[[ -x "$VALIDATOR" ]] || { echo "Manifest validator missing: $VALIDATOR" >&2; exit 1; }

if ! openssl pkey -pubin -in "$PUBLIC_KEY" -text -noout 2>&1 | grep -qi 'ED25519'; then
  echo 'Release public key must be Ed25519' >&2
  exit 1
fi

artifact_name="$(basename "$ARTIFACT")"
[[ "$artifact_name" != *$'\n'* && "$artifact_name" != *$'\r'* ]] || {
  echo 'Artifact filename contains control characters' >&2
  exit 1
}
artifact_size="$(stat -c '%s' "$ARTIFACT")"
artifact_sha256="$(sha256sum "$ARTIFACT" | awk '{print $1}')"
public_key_fingerprint="$(openssl pkey -pubin -in "$PUBLIC_KEY" -outform DER 2>/dev/null | sha256sum | awk '{print $1}')"
created_utc="${PTL_RELEASE_CREATED_UTC:-$(date -u '+%Y-%m-%dT%H:%M:%SZ')}"
source_ref="${PTL_SOURCE_REF:-unknown}"
build_run="${PTL_BUILD_RUN:-unknown}"

[[ "$created_utc" =~ ^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}Z$ ]] || {
  echo 'PTL_RELEASE_CREATED_UTC must be UTC YYYY-MM-DDTHH:MM:SSZ' >&2
  exit 1
}
[[ -n "$source_ref" && ${#source_ref} -le 255 ]] || {
  echo 'PTL_SOURCE_REF must be non-empty and at most 255 characters' >&2
  exit 1
}
[[ -n "$build_run" && ${#build_run} -le 128 ]] || {
  echo 'PTL_BUILD_RUN must be non-empty and at most 128 characters' >&2
  exit 1
}

mkdir -p "$(dirname "$OUTPUT")"
tmp="$(mktemp "${OUTPUT}.tmp.XXXXXX")"
trap 'rm -f "$tmp"' EXIT

jq -nS -c \
  --arg schema 'blackcat-release-manifest-v0.1' \
  --arg release_id "$PTL_RELEASE_ID" \
  --arg created_utc "$created_utc" \
  --arg filename "$artifact_name" \
  --argjson size_bytes "$artifact_size" \
  --arg sha256 "$artifact_sha256" \
  --arg source_commit "${PTL_SOURCE_COMMIT,,}" \
  --arg source_ref "$source_ref" \
  --arg build_run "$build_run" \
  --arg algorithm 'Ed25519' \
  --arg key_fingerprint_sha256 "$public_key_fingerprint" \
  '{
    schema: $schema,
    release_id: $release_id,
    created_utc: $created_utc,
    artifact: {
      filename: $filename,
      size_bytes: $size_bytes,
      sha256: $sha256
    },
    source: {
      commit: $source_commit,
      ref: $source_ref
    },
    build: {
      run_id: $build_run
    },
    signing: {
      algorithm: $algorithm,
      key_fingerprint_sha256: $key_fingerprint_sha256,
      signature_format: "raw-detached",
      manifest_encoding: "jq-S-c-utf8-newline"
    }
  }' > "$tmp"

bash "$VALIDATOR" "$tmp" >/dev/null
mv "$tmp" "$OUTPUT"
trap - EXIT

echo 'BLACK CAT RELEASE MANIFEST: CREATED'
echo "Manifest: $OUTPUT"
echo "Artifact SHA-256: $artifact_sha256"
echo "Release key fingerprint: $public_key_fingerprint"