#!/usr/bin/env bash
set -Eeuo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
# shellcheck source=build/lib/common.sh
source "${SCRIPT_DIR}/lib/common.sh"

require_cmd lb
require_cmd git
require_cmd base64
require_cmd sha256sum

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
if [[ ! -d /usr/share/live/build/bootloaders ]]; then
  echo "live-build bootloader templates are missing" >&2
  exit 1
fi
mkdir -p config/bootloaders
cp -a /usr/share/live/build/bootloaders/. config/bootloaders/

if [[ -d "${PTL_CONFIG_DIR}/live-build" ]]; then
  cp -a "${PTL_CONFIG_DIR}/live-build/." config/
fi

# Branding asset policy:
# - source is a compact repository-tracked base64 representation, so no network
#   fetch is needed during the build;
# - exact decoded SHA-256 is checked before use;
# - branding is cosmetic only: ordinary menu entries remain the boot authority.
BOOT_SPLASH_B64="${PTL_ROOT}/assets/blackcat-boot-160x120.png.b64"
BOOT_SPLASH_SHA256="24d03a442c3f7f04a060d30fbf8710f8b8cd2b5495caffdeea7cc47fcbc546ea"
if [[ ! -f "$BOOT_SPLASH_B64" ]]; then
  echo "Black Cat boot splash source is missing: $BOOT_SPLASH_B64" >&2
  exit 1
fi

mkdir -p config/bootloaders/grub-pc config/bootloaders/syslinux_common
if ! base64 --decode "$BOOT_SPLASH_B64" > config/bootloaders/grub-pc/blackcat.png; then
  echo "Black Cat boot splash source is not valid base64" >&2
  exit 1
fi
cp config/bootloaders/grub-pc/blackcat.png config/bootloaders/syslinux_common/splash.png

splash_sha="$(sha256sum config/bootloaders/grub-pc/blackcat.png | awk '{print $1}')"
if [[ "$splash_sha" != "$BOOT_SPLASH_SHA256" ]]; then
  echo "Black Cat boot splash checksum mismatch" >&2
  echo "Expected: $BOOT_SPLASH_SHA256" >&2
  echo "Observed: $splash_sha" >&2
  exit 1
fi
log "Black Cat boot splash verified: ${splash_sha}"

# Canonical design-folder derivatives. These are repository-tracked, compact
# build inputs; no network fetch or generative step occurs during construction.
DESIGN_ASSET_DIR="${PTL_ROOT}/assets/design"
DESIGN_CHROOT_DIR="config/includes.chroot/usr/share/blackcat"
mkdir -p "${DESIGN_CHROOT_DIR}/icons" "config/includes.chroot/usr/share/backgrounds/blackcat"

decode_design_asset() {
  local source="$1" destination="$2" expected="$3" observed
  base64 --decode "${DESIGN_ASSET_DIR}/${source}.b64" > "$destination"
  observed="$(sha256sum "$destination" | awk '{print $1}')"
  if [[ "$observed" != "$expected" ]]; then
    echo "Black Cat design asset checksum mismatch: $source" >&2
    exit 1
  fi
}

decode_design_asset blackcat-design-wallpaper.jpg \
  config/includes.chroot/usr/share/backgrounds/blackcat/blackcat-design-wallpaper.jpg \
  8d5b81b1c103a256f7aaa77a75559a44ec762ca8410d44754fb30bab0f5c63d7
decode_design_asset browser.png "${DESIGN_CHROOT_DIR}/icons/browser.png" 4d2c5e99807009bdf2b8ce8b501ce39eef0f4b24564c4ce46d89f946c9152159
decode_design_asset network.png "${DESIGN_CHROOT_DIR}/icons/network.png" 51e42791d12eaeda6d7675926e24a26c2b7d7397ee961082b4829567391fc247
decode_design_asset office.png "${DESIGN_CHROOT_DIR}/icons/office.png" 82a540fe42aade67f517d3b0420a473e9cf0d66acff37a5e1a31aa6901a2bdd9
decode_design_asset ai.png "${DESIGN_CHROOT_DIR}/icons/ai.png" 40fabf8912638d79f66a02e4b3ea6e095bfe80ac18af319d5da1e388a3cf8180
decode_design_asset developer.png "${DESIGN_CHROOT_DIR}/icons/developer.png" 63ae07a805f7321529c7b60fe217bdd25b2e5a57f916aa5c903a14e36114ac83
decode_design_asset security.png "${DESIGN_CHROOT_DIR}/icons/security.png" 44946505ca058d50ebbde72b86424caa0bb36d877cb3d7381363b6d8335f888c
log "Canonical Black Cat wallpaper and six interface icons verified"

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
  "boot_splash_sha256": "${splash_sha}",
  "prepared_utc": "$(date -u '+%Y-%m-%dT%H:%M:%SZ')",
  "repository_commit": "$(git -C "${PTL_ROOT}" rev-parse HEAD 2>/dev/null || printf 'unknown')"
}
EOF

log "Black Cat live-build configuration prepared."
log "The image includes branded GRUB/Syslinux menus plus a no-typing System Check entry."
