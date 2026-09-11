#!/usr/bin/env bash
set -Eeuo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(cd "${SCRIPT_DIR}/.." && pwd)"
WORK_DIR="${ROOT_DIR}/work/live-build"
OUT_DIR="${ROOT_DIR}/out"

mkdir -p "$OUT_DIR"

command -v lb >/dev/null 2>&1 || {
  echo "live-build is missing. Run sudo bash build/01-install-deps.sh" >&2
  exit 1
}

bash "${SCRIPT_DIR}/00-host-check.sh"
bash "${SCRIPT_DIR}/10-prepare-live-build.sh"

cd "$WORK_DIR"

printf 'Starting Debian Live build in %s\n' "$WORK_DIR"
sudo lb build 2>&1 | tee "${OUT_DIR}/live-build.log"

image="$(find . -maxdepth 1 -type f \( -name '*.iso' -o -name '*.img' \) | sort | head -n 1 || true)"

if [[ -z "$image" ]]; then
  echo "Build completed without a discoverable ISO/IMG artifact." >&2
  exit 1
fi

cp -f "$image" "$OUT_DIR/"
artifact="${OUT_DIR}/$(basename "$image")"
sha256sum "$artifact" | tee "${artifact}.sha256"

printf '\nBuild artifact: %s\n' "$artifact"
printf 'Next: bash build/30-artifact-manifest.sh\n'
