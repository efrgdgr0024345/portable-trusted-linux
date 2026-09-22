#!/usr/bin/env bash
set -Eeuo pipefail
umask 077

# Black Cat Trust Audit
# Read-only-by-design system/firmware/TPM evidence collector.
#
# This script DOES NOT:
# - clear or modify the TPM
# - change Secure Boot keys
# - flash firmware
# - partition/format disks
# - mount internal disks
# - enable network services
#
# Usage:
#   sudo bash blackcat-trust-audit.sh
#   sudo bash blackcat-trust-audit.sh --online
#   sudo bash blackcat-trust-audit.sh --output /path/to/output
#
# Offline is the default. --online adds TLS/fwupd observations only.

MODE="offline"
OUT=""

while [[ $# -gt 0 ]]; do
  case "$1" in
    --online) MODE="online"; shift ;;
    --output) OUT="${2:-}"; shift 2 ;;
    -h|--help) sed -n '1,35p' "$0"; exit 0 ;;
    *) echo "Unknown argument: $1" >&2; exit 2 ;;
  esac
done

if [[ ${EUID:-$(id -u)} -ne 0 ]]; then
  echo "Run as root: sudo bash $0" >&2
  exit 1
fi

TS="$(date -u +%Y%m%dT%H%M%SZ 2>/dev/null || echo unknown-time)"
OUT="${OUT:-/tmp/blackcat-audit-$TS}"

mkdir -p "$OUT"/{raw,tpm,uefi,network,storage,hardware,firmware}
SUMMARY="$OUT/summary.txt"
LOG="$OUT/audit.log"

exec > >(tee -a "$LOG") 2>&1

pass(){ printf '[PASS] %s\n' "$*" | tee -a "$SUMMARY" >/dev/null; }
warn(){ printf '[WARN] %s\n' "$*" | tee -a "$SUMMARY" >/dev/null; }
info(){ printf '[INFO] %s\n' "$*" | tee -a "$SUMMARY" >/dev/null; }
have(){ command -v "$1" >/dev/null 2>&1; }

run_capture() {
  local file="$1"
  shift
  {
    printf '$'
    printf ' %q' "$@"
    printf '\n'
    "$@"
  } >"$file" 2>&1 || true
}

cat >"$SUMMARY" <<EOF
BLACK CAT TRUST AUDIT
UTC: $TS
Mode: $MODE

EOF

echo "BLACK CAT TRUST AUDIT"
echo "UTC: $TS"
echo "Mode: $MODE"
echo "Output: $OUT"
echo

run_capture "$OUT/raw/uname.txt" uname -a
[[ -r /proc/cmdline ]] && cp /proc/cmdline "$OUT/raw/cmdline.txt" || true
[[ -r /etc/os-release ]] && cp /etc/os-release "$OUT/raw/os-release.txt" || true

if [[ -d /sys/firmware/efi ]]; then pass "Booted in UEFI mode"; else warn "UEFI runtime interface not present"; fi

if have mokutil; then
  run_capture "$OUT/uefi/mokutil-sb-state.txt" mokutil --sb-state
  if grep -qi "SecureBoot enabled" "$OUT/uefi/mokutil-sb-state.txt"; then pass "Secure Boot enabled"; else warn "Secure Boot not confirmed enabled by mokutil"; fi
else
  warn "mokutil not installed; Secure Boot state not decoded by mokutil"
fi

if [[ -d /sys/firmware/efi/efivars ]]; then
  find /sys/firmware/efi/efivars -maxdepth 1 -type f -printf '%f\n' 2>/dev/null | sort >"$OUT/uefi/efivars-list.txt" || true
  for name in SecureBoot SetupMode PK KEK db dbx; do
    shopt -s nullglob
    files=(/sys/firmware/efi/efivars/"$name"-*)
    shopt -u nullglob
    if ((${#files[@]})); then
      printf '%s\n' "${files[@]}" >"$OUT/uefi/${name}-paths.txt"
      for f in "${files[@]}"; do
        base="$(basename "$f")"
        cp "$f" "$OUT/uefi/$base.bin" 2>/dev/null || true
        sha256sum "$f" >>"$OUT/uefi/${name}-sha256.txt" 2>/dev/null || true
      done
      info "UEFI variable present: $name"
    else
      warn "UEFI variable not found: $name"
    fi
  done
else
  warn "efivarfs is not available"
fi

if have efi-readvar; then run_capture "$OUT/uefi/efi-readvar-all.txt" efi-readvar; fi

[[ -e /dev/tpm0 ]] && pass "TPM device /dev/tpm0 present" || warn "TPM device /dev/tpm0 missing"
[[ -e /dev/tpmrm0 ]] && pass "TPM resource manager /dev/tpmrm0 present" || warn "TPM resource manager /dev/tpmrm0 missing"

if [[ -r /sys/class/tpm/tpm0/tpm_version_major ]]; then
  TPMVER="$(cat /sys/class/tpm/tpm0/tpm_version_major 2>/dev/null || true)"
  printf '%s\n' "$TPMVER" >"$OUT/tpm/tpm-version-major.txt"
  [[ "$TPMVER" == "2" ]] && pass "TPM major version 2" || warn "TPM major version is '$TPMVER'"
else
  warn "TPM version sysfs entry missing"
fi

[[ -r /sys/class/tpm/tpm0/device/uevent ]] && cp /sys/class/tpm/tpm0/device/uevent "$OUT/tpm/device-uevent.txt" || true
have tpm2_getcap && run_capture "$OUT/tpm/properties-fixed.txt" tpm2_getcap properties-fixed || info "tpm2_getcap not installed"
have tpm2_pcrread && run_capture "$OUT/tpm/pcrs-tpm2-tools.txt" tpm2_pcrread || true

PCRDIR="/sys/class/tpm/tpm0/pcr-sha256"
if [[ -d "$PCRDIR" ]]; then
  : >"$OUT/tpm/pcr-sha256.txt"
  for p in $(seq 0 23); do
    if [[ -r "$PCRDIR/$p" ]]; then
      printf 'PCR %02d: ' "$p" >>"$OUT/tpm/pcr-sha256.txt"
      cat "$PCRDIR/$p" >>"$OUT/tpm/pcr-sha256.txt"
    fi
  done
  [[ -s "$OUT/tpm/pcr-sha256.txt" ]] && pass "SHA-256 PCR bank readable" || warn "SHA-256 PCR directory exists but no values were captured"
else
  warn "SHA-256 PCR sysfs bank not exposed"
fi

if ! mountpoint -q /sys/kernel/security 2>/dev/null; then
  mount -t securityfs securityfs /sys/kernel/security 2>/dev/null || true
fi

EVENTLOG="/sys/kernel/security/tpm0/binary_bios_measurements"
if [[ -r "$EVENTLOG" ]]; then
  cp "$EVENTLOG" "$OUT/tpm/binary_bios_measurements.bin" 2>/dev/null || true
  wc -c <"$EVENTLOG" >"$OUT/tpm/eventlog-bytes.txt" 2>/dev/null || true
  sha256sum "$EVENTLOG" >"$OUT/tpm/eventlog.sha256" 2>/dev/null || true
  BYTES="$(cat "$OUT/tpm/eventlog-bytes.txt" 2>/dev/null || echo 0)"
  if [[ "$BYTES" =~ ^[0-9]+$ ]] && (( BYTES > 0 )); then pass "TPM measured-boot event log readable ($BYTES bytes)"; else warn "TPM event log path exists but returned zero bytes"; fi
else
  warn "TPM measured-boot event log not readable"
fi

if have dmidecode; then
  run_capture "$OUT/firmware/dmidecode-bios.txt" dmidecode -t bios
  run_capture "$OUT/firmware/dmidecode-system.txt" dmidecode -t system
  run_capture "$OUT/firmware/dmidecode-baseboard.txt" dmidecode -t baseboard
else
  warn "dmidecode not installed"
fi

for f in bios_vendor bios_version bios_date sys_vendor product_name product_version board_vendor board_name board_version; do
  path="/sys/class/dmi/id/$f"
  [[ -r "$path" ]] && cat "$path" >"$OUT/firmware/$f.txt" || true
done

if have fwupdmgr; then
  run_capture "$OUT/firmware/fwupd-devices.txt" fwupdmgr get-devices
  [[ "$MODE" == "online" ]] && run_capture "$OUT/firmware/fwupd-updates.txt" fwupdmgr get-updates || true
fi

have lsblk && run_capture "$OUT/storage/lsblk.txt" lsblk -o NAME,PATH,SIZE,MODEL,SERIAL,TRAN,TYPE,FSTYPE,RO,RM,MOUNTPOINTS
have findmnt && run_capture "$OUT/storage/findmnt.txt" findmnt
have blkid && run_capture "$OUT/storage/blkid.txt" blkid

if have lsblk; then
  mapfile -t mounted_internal < <(lsblk -nrpo NAME,TYPE,TRAN,MOUNTPOINTS 2>/dev/null | awk '$2=="part" && $3!="usb" && $4!="" {print}')
  (("${#mounted_internal[@]}" > 0)) && warn "One or more non-USB disk partitions are mounted; inspect storage/lsblk.txt" || pass "No non-USB disk partitions appear mounted"
fi

have lspci && run_capture "$OUT/hardware/lspci-nnk.txt" lspci -nnk
have lsusb && run_capture "$OUT/hardware/lsusb.txt" lsusb
[[ -r /proc/modules ]] && cp /proc/modules "$OUT/hardware/proc-modules.txt" || true
have lsmod && run_capture "$OUT/hardware/lsmod.txt" lsmod
have dmesg && dmesg >"$OUT/hardware/dmesg.txt" 2>&1 || true

have ip && run_capture "$OUT/network/ip-link.txt" ip -br link
have ip && run_capture "$OUT/network/ip-addr.txt" ip -br addr
have ip && run_capture "$OUT/network/ip-route.txt" ip route
have nmcli && run_capture "$OUT/network/nmcli-device.txt" nmcli device status
have rfkill && run_capture "$OUT/network/rfkill.txt" rfkill list
have ss && run_capture "$OUT/network/listeners.txt" ss -lntup

{ env | grep -Ei '^(http|https|ftp|all|no)_proxy=' || true; } >"$OUT/network/proxy-env.txt"

if [[ "$MODE" == "online" ]] && have openssl; then
  { echo | openssl s_client -connect releases.ubuntu.com:443 -servername releases.ubuntu.com -verify_return_error 2>&1; } >"$OUT/network/tls-releases-ubuntu.txt" || true
  grep -q "Verify return code: 0 (ok)" "$OUT/network/tls-releases-ubuntu.txt" && pass "TLS certificate verification succeeded for releases.ubuntu.com" || warn "TLS verification for releases.ubuntu.com did not produce a clean verify result"
fi

if have systemctl; then
  run_capture "$OUT/raw/systemd-enabled-services.txt" systemctl list-unit-files --state=enabled
  run_capture "$OUT/raw/systemd-running-services.txt" systemctl --type=service --state=running
fi

(
  cd "$OUT"
  find . -type f ! -name SHA256SUMS -print0 | sort -z | xargs -0 sha256sum > SHA256SUMS
)
sha256sum "$OUT/SHA256SUMS" >"$OUT/SHA256SUMS.sha256"

echo
echo "Audit complete."
echo "Summary:  $SUMMARY"
echo "Evidence: $OUT"
echo
cat "$SUMMARY"
