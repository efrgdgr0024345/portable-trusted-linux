#!/usr/bin/env bash
set -Eeuo pipefail

# Black Cat recovery bootstrap payload.
# Intended to be run from an official Ubuntu live session booted with "toram".
# Default mode is inspection only. Destructive write requires --write and
# an explicit typed confirmation.

RELEASE="26.04"
ISO="ubuntu-26.04.1-desktop-amd64.iso"
BASE="https://releases.ubuntu.com/${RELEASE}"
EXPECTED_SHA="601e30fbf5d97759367c632e2c33630665039b7e2158fd068403da3ccf1bda1f"
EXPECTED_SIZE="6482409472"
EXPECTED_CDIMAGE_FPR="843938DF228D22F7B3742BC0D94AA3F0EFE21092"

MODE="inspect"
if [[ "${1:-}" == "--write" ]]; then
  MODE="write"
elif [[ -n "${1:-}" ]]; then
  echo "Usage: $0 [--write]" >&2
  exit 2
fi

if [[ "${EUID}" -ne 0 ]]; then
  exec sudo -- "$0" "$@"
fi

need() {
  command -v "$1" >/dev/null 2>&1 || {
    echo "ERROR: required command missing: $1" >&2
    exit 1
  }
}
for cmd in awk blockdev curl findmnt gpg gpgv grep lsblk sha256sum stat sync tee umount; do
  need "$cmd"
done

echo "=== Recovery bootstrap preflight ==="

if ! grep -qw toram /proc/cmdline; then
  echo "ERROR: this live session was not booted with the 'toram' kernel option." >&2
  echo "Refusing to overwrite the boot USB while the live system may still depend on it." >&2
  exit 1
fi
echo "PASS: toram present in kernel command line"

mapfile -t CANDIDATES < <(
  lsblk -dpno NAME,TYPE,RM,SIZE,MODEL,TRAN |
  awk '$2=="disk" && $3==1 && $6=="usb" {print $1}'
)

if [[ "${#CANDIDATES[@]}" -ne 1 ]]; then
  echo "ERROR: expected exactly one removable USB disk; found ${#CANDIDATES[@]}." >&2
  lsblk -dpno NAME,TYPE,RM,SIZE,MODEL,TRAN >&2
  exit 1
fi

TARGET="${CANDIDATES[0]}"
TARGET_BYTES="$(blockdev --getsize64 "$TARGET")"

# Accept nominal 16 GB sticks, but reject obviously different devices.
MIN_BYTES=$((14 * 1000 * 1000 * 1000))
MAX_BYTES=$((18 * 1000 * 1000 * 1000))
if (( TARGET_BYTES < MIN_BYTES || TARGET_BYTES > MAX_BYTES )); then
  echo "ERROR: candidate USB size is outside the expected 14-18 GB range." >&2
  echo "TARGET=$TARGET bytes=$TARGET_BYTES" >&2
  exit 1
fi

echo "Target USB:"
lsblk -d -o NAME,SIZE,MODEL,SERIAL,TRAN,RM "$TARGET"

echo
echo "Internal disks protected from writes:"
lsblk -dpno NAME,TYPE,RM,SIZE,MODEL,TRAN |
  awk '$2=="disk" && $1 !~ /^$/ && $3==0 {print}'

if [[ "$TARGET" == /dev/nvme* ]]; then
  echo "ERROR: target unexpectedly resolves to NVMe. Refusing." >&2
  exit 1
fi

# The target must not contain the running root filesystem.
ROOT_SOURCE="$(findmnt -rn -o SOURCE / || true)"
if [[ "$ROOT_SOURCE" == "$TARGET"* ]]; then
  echo "ERROR: target appears to back the running root filesystem. Refusing." >&2
  exit 1
fi

WORK="/dev/shm/blackcat-recovery"
mkdir -p "$WORK"
chmod 700 "$WORK"
cd "$WORK"

echo
echo "=== Authenticating Canonical metadata ==="
curl --fail --location --proto '=https' --tlsv1.2   -o ubuntu-archive-keyring.gpg   "https://archive.ubuntu.com/ubuntu/project/ubuntu-archive-keyring.gpg"
curl --fail --location --proto '=https' --tlsv1.2 -O "${BASE}/SHA256SUMS"
curl --fail --location --proto '=https' --tlsv1.2 -O "${BASE}/SHA256SUMS.gpg"

ACTUAL_FPRS="$(
  gpg --batch --no-default-keyring     --keyring ./ubuntu-archive-keyring.gpg     --with-colons --fingerprint 2>/dev/null |
  awk -F: '$1=="fpr"{print $10}'
)"
if ! grep -qx "$EXPECTED_CDIMAGE_FPR" <<<"$ACTUAL_FPRS"; then
  echo "ERROR: expected Canonical CD image signing key fingerprint not present." >&2
  exit 1
fi
echo "PASS: Canonical signing key fingerprint"

gpgv --keyring ./ubuntu-archive-keyring.gpg ./SHA256SUMS.gpg ./SHA256SUMS

MANIFEST_LINE="$(grep -F " $ISO" SHA256SUMS || true)"
if [[ -z "$MANIFEST_LINE" ]]; then
  MANIFEST_LINE="$(grep -F "*$ISO" SHA256SUMS || true)"
fi
if [[ -z "$MANIFEST_LINE" ]]; then
  echo "ERROR: ISO not present in Canonical signed manifest." >&2
  exit 1
fi
SIGNED_SHA="$(awk '{print $1}' <<<"$MANIFEST_LINE")"

if [[ "$SIGNED_SHA" != "$EXPECTED_SHA" ]]; then
  echo "ERROR: Canonical signed hash does not match our independently established reference." >&2
  echo "signed=$SIGNED_SHA" >&2
  echo "fixed =$EXPECTED_SHA" >&2
  exit 1
fi
echo "PASS: Canonical signed SHA-256 matches fixed recovery reference"

if [[ "$MODE" == "inspect" ]]; then
  echo
  echo "INSPECTION PASS"
  echo "No disks were written."
  echo "Target candidate: $TARGET"
  echo "Expected ISO: $ISO"
  echo "Expected SHA-256: $EXPECTED_SHA"
  echo
  echo "Re-run with --write only when ready to erase the USB."
  exit 0
fi

# Ensure no partition on the target USB remains mounted.
echo
echo "=== Releasing target USB ==="
while read -r part; do
  [[ -n "$part" ]] || continue
  if findmnt -rn -S "$part" >/dev/null 2>&1; then
    umount "$part"
  fi
done < <(lsblk -lnpo NAME "$TARGET" | tail -n +2)

if findmnt -rn | grep -q "^$TARGET"; then
  echo "ERROR: target USB is still mounted. Refusing write." >&2
  exit 1
fi

echo
echo "DANGER: the following USB will be completely erased:"
lsblk -d -o NAME,SIZE,MODEL,SERIAL,TRAN "$TARGET"
echo
read -r -p "Type ERASE-VERIFIED-USB exactly to continue: " CONFIRM
if [[ "$CONFIRM" != "ERASE-VERIFIED-USB" ]]; then
  echo "Cancelled. No write performed."
  exit 1
fi

echo
echo "=== Streaming Canonical ISO directly to USB ==="
rm -f download.sha hashpipe
mkfifo hashpipe
sha256sum < hashpipe | awk '{print $1}' > download.sha &
HASH_PID=$!

set +e
curl --fail --location --proto '=https' --tlsv1.2 --retry 3 --retry-delay 5   "${BASE}/${ISO}" |
  tee hashpipe |
  dd of="$TARGET" bs=4M conv=fsync status=progress
PIPE_STATUS=("${PIPESTATUS[@]}")
set -e
wait "$HASH_PID"
rm -f hashpipe

for status in "${PIPE_STATUS[@]}"; do
  if [[ "$status" -ne 0 ]]; then
    echo "ERROR: download/write pipeline failed." >&2
    exit 1
  fi
done

DOWNLOADED_SHA="$(cat download.sha)"
if [[ "$DOWNLOADED_SHA" != "$EXPECTED_SHA" ]]; then
  echo "ERROR: streamed ISO hash mismatch. USB must not be booted." >&2
  echo "actual=$DOWNLOADED_SHA" >&2
  echo "expect=$EXPECTED_SHA" >&2
  exit 1
fi
echo "PASS: streamed ISO SHA-256"

sync
blockdev --flushbufs "$TARGET"

echo
echo "=== Raw physical USB read-back ==="
READBACK_SHA="$(
  head -c "$EXPECTED_SIZE" "$TARGET" |
  sha256sum |
  awk '{print $1}'
)"

if [[ "$READBACK_SHA" != "$EXPECTED_SHA" ]]; then
  echo "ERROR: physical USB read-back hash mismatch. Do not boot this USB." >&2
  echo "actual=$READBACK_SHA" >&2
  echo "expect=$EXPECTED_SHA" >&2
  exit 1
fi

echo
echo "=============================================="
echo "CANONICAL SIGNATURE       PASS"
echo "SIGNED MANIFEST HASH      PASS"
echo "STREAMED DOWNLOAD HASH    PASS"
echo "USB RAW READ-BACK HASH    PASS"
echo
echo "RECOVERY USB READY"
echo
echo "ISO: $ISO"
echo "SIZE: $EXPECTED_SIZE bytes"
echo "SHA256: $EXPECTED_SHA"
echo "=============================================="
echo
echo "Power the machine off. On the next boot, select this USB from the HP UEFI boot menu."
