#!/usr/bin/env bash
set -Eeuo pipefail

if [[ ${EUID} -ne 0 ]]; then
  echo "Run as root: sudo bash build/01-install-deps.sh" >&2
  exit 1
fi

if [[ ! -r /etc/os-release ]]; then
  echo "Cannot identify build host." >&2
  exit 1
fi

# shellcheck disable=SC1091
source /etc/os-release

if [[ ${ID:-} != debian || ${VERSION_CODENAME:-} != trixie ]]; then
  echo "WARNING: Prototype 1 is specified for Debian 13/trixie." >&2
  echo "Detected: ${PRETTY_NAME:-unknown}" >&2
  echo "Continue only if this deviation will be recorded in docs/07-lessons-learned.md." >&2
fi

export DEBIAN_FRONTEND=noninteractive
apt-get update
apt-get install -y --no-install-recommends \
  live-build \
  live-boot \
  live-config \
  live-tools \
  systemd \
  systemd-ukify \
  systemd-boot-efi \
  systemd-cryptsetup \
  cryptsetup-bin \
  tpm2-tools \
  sbsigntool \
  xorriso \
  dosfstools \
  gdisk \
  parted \
  debootstrap \
  squashfs-tools \
  jq \
  openssl \
  ca-certificates \
  curl \
  git \
  rsync \
  file

echo
printf 'Installed tool versions:\n'
for cmd in lb ukify veritysetup tpm2_getcap tpm2_pcrread tpm2_quote sbsign xorriso jq openssl; do
  if command -v "$cmd" >/dev/null 2>&1; then
    printf '  [OK] %-16s %s\n' "$cmd" "$(command -v "$cmd")"
  elif [[ -x "/usr/sbin/$cmd" ]]; then
    printf '  [OK] %-16s %s\n' "$cmd" "/usr/sbin/$cmd"
  elif [[ -x "/sbin/$cmd" ]]; then
    printf '  [OK] %-16s %s\n' "$cmd" "/sbin/$cmd"
  else
    printf '  [MISSING] %s\n' "$cmd"
  fi
done

echo
printf 'Package versions:\n'
for pkg in live-build systemd systemd-ukify systemd-boot-efi systemd-cryptsetup cryptsetup-bin tpm2-tools sbsigntool xorriso; do
  version="$(dpkg-query -W -f='${Version}' "$pkg" 2>/dev/null || true)"
  printf '  %-20s %s\n' "$pkg" "${version:-NOT INSTALLED}"
done

echo
printf 'Dependency installation complete. Run: bash build/00-host-check.sh\n'
