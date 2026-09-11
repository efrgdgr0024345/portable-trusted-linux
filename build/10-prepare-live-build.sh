#!/usr/bin/env bash
set -Eeuo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
# shellcheck source=build/lib/common.sh
source "${SCRIPT_DIR}/lib/common.sh"

require_cmd lb
require_cmd git

BUILD_ID="${BUILD_ID:-$(new_build_id)}"
# CI uses a unique directory outside the repository checkout so root-owned
# live-build scratch files cannot poison a later git clean/checkout.
WORK_DIR="${PTL_BUILD_WORK_DIR:-${PTL_ROOT}/work/live-build}"
ARTIFACT_DIR="${PTL_ARTIFACTS_DIR}/${BUILD_ID}"

ensure_dir "$WORK_DIR"
ensure_dir "$ARTIFACT_DIR"

log "Preparing Debian Live configuration"
log "Build ID: $BUILD_ID"
log "Work dir: $WORK_DIR"

cd "$WORK_DIR"

# Preparation only writes configuration files and does not require root.
lb config \
  --mode debian \
  --distribution trixie \
  --architectures amd64 \
  --binary-images iso-hybrid \
  --archive-areas "main" \
  --apt-recommends true \
  --security true \
  --updates true \
  --debian-installer none \
  --bootappend-live "boot=live components quiet"

mkdir -p config/package-lists
cp "${PTL_CONFIG_DIR}/packages.list" "config/package-lists/ptl.list.chroot"

cat > "${ARTIFACT_DIR}/prepare.json" <<EOF
{
  "build_id": "${BUILD_ID}",
  "suite": "trixie",
  "architecture": "amd64",
  "work_dir": "${WORK_DIR}",
  "prepared_utc": "$(date -u '+%Y-%m-%dT%H:%M:%SZ')",
  "repository_commit": "$(git -C "${PTL_ROOT}" rev-parse HEAD 2>/dev/null || printf 'unknown')"
}
EOF

log "Initial live-build configuration prepared."
log "Next stage will build and inspect the base live filesystem before adding verity/UKI integration."
