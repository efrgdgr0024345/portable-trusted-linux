#!/usr/bin/env bash
set -Eeuo pipefail

PTL_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
PTL_ARTIFACTS_DIR="${PTL_ROOT}/artifacts"
PTL_CONFIG_DIR="${PTL_ROOT}/config"

log() {
  printf '[PTL] %s\n' "$*"
}

warn() {
  printf '[PTL][WARN] %s\n' "$*" >&2
}

fail() {
  printf '[PTL][FAIL] %s\n' "$*" >&2
  exit 1
}

require_cmd() {
  local cmd="$1"
  command -v "$cmd" >/dev/null 2>&1 || fail "Required command not found: $cmd"
}

require_root() {
  [[ "${EUID}" -eq 0 ]] || fail "This stage must be run as root."
}

sha256_file() {
  local file="$1"
  require_cmd sha256sum
  sha256sum -- "$file" | awk '{print $1}'
}

new_build_id() {
  date -u '+%Y%m%dT%H%M%SZ'
}

ensure_dir() {
  mkdir -p -- "$1"
}
