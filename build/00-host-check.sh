#!/usr/bin/env bash
set -Eeuo pipefail

# Self-hosted service accounts may start with a reduced PATH that omits
# administrative binary directories such as /usr/sbin. Use a deterministic
# system PATH so capability checks reflect installed tools rather than the
# launching service's environment.
export PATH="/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin"

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
# shellcheck source=build/lib/common.sh
source "${SCRIPT_DIR}/lib/common.sh"

log "Portable Trusted Linux host preflight"

[[ "$(uname -m)" == "x86_64" ]] || fail "Prototype 1 currently requires an x86-64 build host."
[[ -r /etc/os-release ]] || fail "/etc/os-release is unavailable."

# shellcheck disable=SC1091
source /etc/os-release

log "Host: ${PRETTY_NAME:-unknown}"
log "Kernel: $(uname -sr)"

if [[ "${ID:-}" != "debian" ]]; then
  warn "Builder is not Debian. Prototype 1 is specified for a Debian 13/trixie builder."
fi

if [[ "${VERSION_CODENAME:-}" != "trixie" ]]; then
  warn "Builder codename is '${VERSION_CODENAME:-unknown}', not trixie. Do not claim reproducible baseline without documenting this difference."
fi

mandatory=(
  bash
  sha256sum
  awk
  sed
  grep
  find
  sort
  date
  jq
  openssl
)

build_tools=(
  lb
  xorriso
  veritysetup
  ukify
  sbsign
  tpm2_getcap
  tpm2_pcrread
  tpm2_quote
)

missing_mandatory=0
for cmd in "${mandatory[@]}"; do
  if command -v "$cmd" >/dev/null 2>&1; then
    printf '  [OK]      %s\n' "$cmd"
  else
    printf '  [MISSING] %s\n' "$cmd"
    missing_mandatory=1
  fi
done

printf '\nOptional/build-stage tools:\n'
for cmd in "${build_tools[@]}"; do
  if command -v "$cmd" >/dev/null 2>&1; then
    printf '  [OK]      %s (%s)\n' "$cmd" "$(command -v "$cmd")"
  else
    printf '  [MISSING] %s\n' "$cmd"
  fi
done

if [[ "$missing_mandatory" -ne 0 ]]; then
  fail "Mandatory host-preflight commands are missing."
fi

# Record host systemd-boot state as evidence, but do not fail on residual
# 'config-files' state. The failed live-build runs proved the actionable
# systemd-boot trigger was inside the live-build chroot because systemd-boot
# was explicitly seeded in config/packages.list. That package is now excluded
# from the live root; host package state is observational evidence only.
systemd_boot_state="$(dpkg-query -W -f='${db:Status-Status}' systemd-boot 2>/dev/null || true)"
printf '\nHost systemd-boot state: %s\n' "${systemd_boot_state:-absent from dpkg database}"
if [[ "$systemd_boot_state" == "installed" ]]; then
  warn "Full systemd-boot host integration is installed. This is unnecessary for the PTL builder and should be removed during builder hardening."
fi

printf '\nPackage/version evidence (when available):\n'
for pkg in live-build systemd systemd-ukify linux-image-amd64 cryptsetup-bin tpm2-tools sbsigntool xorriso jq openssl; do
  if dpkg-query -W -f='${Status}' "$pkg" 2>/dev/null | grep -q 'install ok installed'; then
    version="$(dpkg-query -W -f='${Version}' "$pkg" 2>/dev/null || true)"
    printf '  %-22s %s\n' "$pkg" "$version"
  else
    printf '  %-22s %s\n' "$pkg" 'NOT INSTALLED'
  fi
done

log "Preflight completed. Missing build-stage tools are expected until dependencies are installed."
log "Record any unexpected result in docs/07-lessons-learned.md."
