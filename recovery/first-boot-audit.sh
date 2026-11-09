#!/usr/bin/env bash
set -Eeuo pipefail

OUT="/dev/shm/blackcat-first-boot-audit.txt"
exec > >(tee "$OUT") 2>&1

echo "BLACK CAT FIRST-BOOT AUDIT"
echo "Generated: $(date --iso-8601=seconds 2>/dev/null || date)"
echo

echo "=== OS / KERNEL ==="
uname -a
printf 'cmdline: '; cat /proc/cmdline
echo

echo "=== UEFI / SECURE BOOT ==="
if [[ -d /sys/firmware/efi ]]; then
  echo "UEFI boot: YES"
else
  echo "UEFI boot: NO"
fi
if command -v mokutil >/dev/null 2>&1; then
  mokutil --sb-state || true
  echo "--- Platform Key (PK) ---"
  mokutil --pk 2>/dev/null || true
  echo "--- Key Exchange Keys (KEK) ---"
  mokutil --kek 2>/dev/null || true
  echo "--- Allowed signatures (db) ---"
  mokutil --db 2>/dev/null || true
  echo "--- Revoked signatures (dbx) ---"
  mokutil --dbx 2>/dev/null || true
else
  echo "mokutil not installed"
fi
echo

echo "=== TPM ==="
ls -l /dev/tpm* 2>/dev/null || echo "No /dev/tpm* device nodes found"
if command -v tpm2_getcap >/dev/null 2>&1; then
  tpm2_getcap properties-fixed 2>/dev/null || true
fi
echo

echo "=== STORAGE (READ-ONLY INSPECTION) ==="
lsblk -e7 -o NAME,PATH,TYPE,SIZE,RM,RO,TRAN,MODEL,SERIAL,FSTYPE,MOUNTPOINTS
echo
echo "Mounted filesystems:"
findmnt
echo

echo "=== PCI / USB HARDWARE ==="
if command -v lspci >/dev/null 2>&1; then lspci -nnk; fi
if command -v lsusb >/dev/null 2>&1; then lsusb; fi
echo

echo "=== NETWORK CONFIGURATION ==="
ip -br link || true
ip -br addr || true
ip route || true
echo
if command -v resolvectl >/dev/null 2>&1; then
  resolvectl status || true
fi
echo

echo "=== PROXY ENVIRONMENT ==="
env | grep -Ei '^(http|https|all|no)_proxy=' || echo "No proxy environment variables set"
echo

echo "=== LISTENING SOCKETS ==="
ss -lntup 2>/dev/null || ss -lnt 2>/dev/null || true
echo

echo "=== FIREWALL ==="
if command -v ufw >/dev/null 2>&1; then
  ufw status verbose || true
else
  echo "ufw not installed"
fi
if command -v nft >/dev/null 2>&1; then
  nft list ruleset 2>/dev/null || true
fi
echo

echo "=== TIME / CLOCK ==="
timedatectl 2>/dev/null || true
echo

echo "=== KERNEL SECURITY SIGNALS ==="
dmesg 2>/dev/null | grep -Ei 'secure boot|lockdown|tpm|ima|apparmor|integrity' | tail -n 200 || true
echo

echo "=== TLS CONNECTIVITY (NO BYPASS) ==="
for u in   https://releases.ubuntu.com/   https://archive.ubuntu.com/   https://github.com/; do
  echo "--- $u"
  curl --fail --silent --show-error --location --proto '=https' --tlsv1.2     --connect-timeout 10 --max-time 20 --head "$u" | head -n 12 || true
done

echo
echo "AUDIT COMPLETE"
echo "Report stored in RAM only: $OUT"
echo "Nothing in this script mounts or writes the internal disks."
