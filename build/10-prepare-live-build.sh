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

log "Preparing Black Cat Debian Live configuration"
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
cp "${PTL_CONFIG_DIR}/packages.list" "config/package-lists/blackcat.list.chroot"

# Start from the exact bootloader templates supplied by the installed live-build
# package, then overlay the small Black Cat customisations kept in this repo.
# This avoids vendoring Debian's bootloader binaries/templates while still making
# the menu deterministic relative to the recorded live-build package version.
if [[ ! -d /usr/share/live/build/bootloaders ]]; then
  echo "live-build bootloader templates are missing" >&2
  exit 1
fi
mkdir -p config/bootloaders
cp -a /usr/share/live/build/bootloaders/. config/bootloaders/

if [[ -d "${PTL_CONFIG_DIR}/live-build" ]]; then
  cp -a "${PTL_CONFIG_DIR}/live-build/." config/
fi

# GitHub's contents API stores new text files without executable mode metadata.
# Apply runtime modes explicitly during preparation so the generated image does
# not depend on repository file-mode transport.
if [[ -f config/includes.chroot/usr/local/bin/blackcat-check ]]; then
  chmod 0755 config/includes.chroot/usr/local/bin/blackcat-check
fi
if [[ -f config/includes.chroot/etc/profile.d/blackcat-auto-check.sh ]]; then
  chmod 0644 config/includes.chroot/etc/profile.d/blackcat-auto-check.sh
fi
if [[ -f config/includes.chroot/etc/systemd/system/blackcat-check.service ]]; then
  chmod 0644 config/includes.chroot/etc/systemd/system/blackcat-check.service
  mkdir -p config/includes.chroot/etc/systemd/system/multi-user.target.wants
  ln -sfn ../blackcat-check.service \
    config/includes.chroot/etc/systemd/system/multi-user.target.wants/blackcat-check.service
fi

cat > "${ARTIFACT_DIR}/prepare.json" <<EOF
{
  "build_id": "${BUILD_ID}",
  "product": "Black Cat",
  "suite": "trixie",
  "architecture": "amd64",
  "work_dir": "${WORK_DIR}",
  "prepared_utc": "$(date -u '+%Y-%m-%dT%H:%M:%SZ')",
  "repository_commit": "$(git -C "${PTL_ROOT}" rev-parse HEAD 2>/dev/null || printf 'unknown')"
}
EOF

log "Black Cat live-build configuration prepared."
log "The image includes normal boot plus a no-typing System Check menu entry."
