#!/usr/bin/env bash
set -Eeuo pipefail

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

[[ -f "$ARTIFACT" ]] || { echo "Artifact not found: $ARTIFACT" >&2; exit 1; }
[[ -f "$PUBLIC_KEY" ]] || { echo "Public key not found: $PUBLIC_KEY" >&2; exit 1; }
[[ "$PTL_SOURCE_COMMIT" =~ ^[A-Fa-f0-9]{40}$ ]] || { echo "PTL_SOURCE_COMMIT must be a 40-character Git commit" >&2; exit 1; }
command -v jq >/dev/null || { echo 'jq is required' >&2; exit 1; }
command -v openssl >/dev/null || { echo 'openssl is required' >&2; exit 1; }
command -v sha256sum >/dev/null || { echo 'sha256sum is required' >&2; exit 1; }
command -v stat >/dev/null || { echo 'stat is required' >&2; exit 1; }

if ! openssl pkey -pubin -in "$PUBLIC_KEY" -text -noout 2>&1 | grep -qi 'ED25519'; then
  echo 'Release public key must be Ed25519' >&2
  exit 1
fi

artifact_name="$(basename "$ARTIFACT")"
artifact_size="$(stat -c '%s' "$ARTIFACT")"
artifact_sha256="$(sha256sum "$ARTIFACT" | awk '{print $1}')"
public_key_fingerprint="$(openssl pkey -pubin -in "$PUBLIC_KEY" -outform DER 2>/dev/null | sha256sum | awk '{print $1}')"
created_utc="${PTL_RELEASE_CREATED_UTC:-$(date -u '+%Y-%m-%dT%H:%M:%SZ')}"
source_ref="${PTL_SOURCE_REF:-unknown}"
build_run="${PTL_BUILD_RUN:-unknown}"

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

jq -e '
  .schema == "blackcat-release-manifest-v0.1" and
  (.release_id | type == "string" and length > 0) and
  (.artifact.filename | type == "string" and length > 0) and
  (.artifact.size_bytes | type == "number" and . > 0) and
  (.artifact.sha256 | test("^[a-f0-9]{64}$")) and
  (.source.commit | test("^[a-f0-9]{40}$")) and
  .signing.algorithm == "Ed25519" and
  (.signing.key_fingerprint_sha256 | test("^[a-f0-9]{64}$"))
' "$tmp" >/dev/null

mv "$tmp" "$OUTPUT"
trap - EXIT

echo "BLACK CAT RELEASE MANIFEST: CREATED"
echo "Manifest: $OUTPUT"
echo "Artifact SHA-256: $artifact_sha256"
echo "Release key fingerprint: $public_key_fingerprint"