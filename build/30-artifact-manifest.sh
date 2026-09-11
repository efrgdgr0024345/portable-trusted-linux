#!/usr/bin/env bash
set -Eeuo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
OUT_DIR="${ROOT_DIR}/out"
MANIFEST="${OUT_DIR}/build-manifest.json"

mkdir -p "$OUT_DIR"

artifact="$(find "$OUT_DIR" -maxdepth 1 -type f \( -name '*.iso' -o -name '*.img' \) | sort | head -n 1 || true)"
[[ -n "$artifact" ]] || { echo "No ISO/IMG artifact found in $OUT_DIR" >&2; exit 1; }

sha="$(sha256sum "$artifact" | awk '{print $1}')"
size="$(stat -c '%s' "$artifact")"
checked_out_commit="$(git -C "$ROOT_DIR" rev-parse HEAD 2>/dev/null || printf 'unknown')"
checked_out_ref="$(git -C "$ROOT_DIR" branch --show-current 2>/dev/null || true)"
source_commit="${PTL_SOURCE_COMMIT:-$checked_out_commit}"
source_ref="${PTL_SOURCE_REF:-${checked_out_ref:-unknown}}"
host="$(. /etc/os-release 2>/dev/null; printf '%s' "${PRETTY_NAME:-unknown}")"

pkg_json='{}'
for pkg in live-build systemd systemd-ukify cryptsetup-bin tpm2-tools sbsigntool xorriso; do
  version="$(dpkg-query -W -f='${Version}' "$pkg" 2>/dev/null || true)"
  pkg_json="$(jq --arg k "$pkg" --arg v "${version:-NOT INSTALLED}" '. + {($k): $v}' <<<"$pkg_json")"
done

jq -n \
  --arg schema "ptl-build-manifest-v0.2" \
  --arg created_utc "$(date -u +'%Y-%m-%dT%H:%M:%SZ')" \
  --arg artifact "$(basename "$artifact")" \
  --arg sha256 "$sha" \
  --argjson size_bytes "$size" \
  --arg source_commit "$source_commit" \
  --arg source_ref "$source_ref" \
  --arg checked_out_commit "$checked_out_commit" \
  --arg checked_out_ref "${checked_out_ref:-detached}" \
  --arg github_repository "${GITHUB_REPOSITORY:-unknown}" \
  --arg github_run_id "${GITHUB_RUN_ID:-unknown}" \
  --arg github_run_attempt "${GITHUB_RUN_ATTEMPT:-unknown}" \
  --arg build_host "$host" \
  --argjson packages "$pkg_json" \
  '{schema:$schema,created_utc:$created_utc,artifact:$artifact,sha256:$sha256,size_bytes:$size_bytes,git:{source_commit:$source_commit,source_ref:$source_ref,checked_out_commit:$checked_out_commit,checked_out_ref:$checked_out_ref},github:{repository:$github_repository,run_id:$github_run_id,run_attempt:$github_run_attempt},build_host:$build_host,packages:$packages}' \
  > "$MANIFEST"

jq . "$MANIFEST"
printf '\nManifest written to %s\n' "$MANIFEST"
