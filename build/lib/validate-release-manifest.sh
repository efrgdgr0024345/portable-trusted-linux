#!/usr/bin/env bash
set -Eeuo pipefail

[[ $# -eq 1 ]] || { echo "Usage: $0 <release-manifest.json>" >&2; exit 2; }
MANIFEST="$1"

[[ -f "$MANIFEST" ]] || { echo "Manifest not found: $MANIFEST" >&2; exit 1; }
command -v jq >/dev/null || { echo 'jq is required' >&2; exit 1; }

canonical="$(mktemp)"
trap 'rm -f "$canonical"' EXIT

jq -e -S -c -s '
  if length == 1 and (.[0] | type == "object") then .[0]
  else error("expected exactly one manifest object") end
' "$MANIFEST" > "$canonical" 2>/dev/null || {
  echo 'MANIFEST JSON: FAILED' >&2
  exit 1
}

cmp -s "$MANIFEST" "$canonical" || {
  echo 'MANIFEST CANONICAL FORM: FAILED' >&2
  exit 1
}

jq -e '
  (keys == ["artifact","build","created_utc","release_id","schema","signing","source"]) and
  .schema == "blackcat-release-manifest-v0.1" and
  (.release_id | type == "string" and test("^[A-Za-z0-9][A-Za-z0-9._-]{0,127}$")) and
  (.created_utc | type == "string" and test("^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}Z$")) and
  (.artifact | keys == ["filename","sha256","size_bytes"]) and
  (.artifact.filename | type == "string" and length > 0 and length <= 255 and test("^[^/\\\\[:cntrl:]]+$")) and
  (.artifact.size_bytes | type == "number" and . > 0 and floor == .) and
  (.artifact.sha256 | type == "string" and test("^[a-f0-9]{64}$")) and
  (.source | keys == ["commit","ref"]) and
  (.source.commit | type == "string" and test("^[a-f0-9]{40}$")) and
  (.source.ref | type == "string" and length > 0 and length <= 255 and test("^[^[:cntrl:]]+$")) and
  (.build | keys == ["run_id"]) and
  (.build.run_id | type == "string" and length > 0 and length <= 128 and test("^[^[:cntrl:]]+$")) and
  (.signing | keys == ["algorithm","key_fingerprint_sha256","manifest_encoding","signature_format"]) and
  .signing.algorithm == "Ed25519" and
  (.signing.key_fingerprint_sha256 | type == "string" and test("^[a-f0-9]{64}$")) and
  .signing.signature_format == "raw-detached" and
  .signing.manifest_encoding == "jq-S-c-utf8-newline"
' "$MANIFEST" >/dev/null || {
  echo 'MANIFEST SCHEMA: FAILED' >&2
  exit 1
}

echo 'MANIFEST CANONICAL FORM: VERIFIED'
echo 'MANIFEST SCHEMA: VERIFIED'
