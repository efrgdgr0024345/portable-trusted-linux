#!/usr/bin/env bash
set -Eeuo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
CHROOT="$ROOT/config/live-build/includes.chroot"
DIAG="$CHROOT/usr/local/sbin/blackcat-diagnostics"
MONITOR="$CHROOT/usr/local/sbin/blackcat-boot-monitor"
RECOVERY="$CHROOT/usr/local/sbin/blackcat-recovery"
EXPORT="$CHROOT/usr/local/sbin/blackcat-export-report"
READY="$CHROOT/usr/local/bin/blackcat-session-ready"
MONITOR_UNIT="$CHROOT/etc/systemd/system/blackcat-boot-monitor.service"
RECOVERY_UNIT="$CHROOT/etc/systemd/system/blackcat-recovery.service"
READY_DESKTOP="$CHROOT/etc/xdg/autostart/blackcat-session-ready.desktop"

for file in "$DIAG" "$MONITOR" "$RECOVERY" "$EXPORT" "$READY"; do
  [[ -s "$file" ]] || { echo "Missing recovery component: $file" >&2; exit 1; }
  bash -n "$file"
done

for pkg in procps kbd x11-utils; do
  grep -qx "$pkg" "$ROOT/config/packages.list" || { echo "Missing recovery dependency: $pkg" >&2; exit 1; }
done

grep -Fq 'ConditionKernelCommandLine=!blackcat.check=1' "$MONITOR_UNIT"
grep -Fq 'Conflicts=getty@tty1.service' "$RECOVERY_UNIT"
grep -Fq 'TTYPath=/dev/tty1' "$RECOVERY_UNIT"
grep -Fq 'Exec=/usr/local/bin/blackcat-session-ready' "$READY_DESKTOP"
grep -Fq 'xdpyinfo' "$READY"
grep -Fq 'xfwm4' "$READY"
grep -Fq 'xfce4-panel' "$READY"
grep -Fq 'lightdm-failed' "$MONITOR"
grep -Fq 'desktop-readiness-timeout' "$MONITOR"
grep -Fq 'session-heartbeat-stale' "$MONITOR"
grep -Fq 'Retry the desktop once' "$RECOVERY"
grep -Fq 'Export full report to a separate USB' "$RECOVERY"
grep -Fq '/sys/class/block/$disk/removable' "$EXPORT"
grep -Fq 'MANIFEST.sha256' "$EXPORT"
grep -Fq 'No internal disk was mounted by this collector.' "$DIAG"
grep -Fq 'payload_sha256' "$DIAG"
grep -Fq 'Black Cat - Basic graphics (temporary)' "$ROOT/config/live-build/bootloaders/grub-pc/grub.cfg"
grep -Fq 'nomodeset blackcat.basic_graphics=1' "$ROOT/config/live-build/bootloaders/grub-pc/grub.cfg"
grep -Fq 'blackcat-basic-graphics' "$ROOT/config/live-build/bootloaders/syslinux_common/live.cfg.in"

tmp="$(mktemp -d)"
trap 'rm -rf "$tmp"' EXIT
BLACKCAT_DIAGNOSTICS_DIR="$tmp/diagnostics" BLACKCAT_COLLECTOR_TIMEOUT=0.2 \
  "$DIAG" --reason source-test > "$tmp/path.txt"
report_dir="$(cat "$tmp/path.txt")"
test -s "$report_dir/report.json"
test -s "$report_dir/report-envelope.json"
test -s "$report_dir/summary.txt"
grep -Fq 'source-test' "$report_dir/report.json"
grep -Fq 'cause remains UNKNOWN' "$report_dir/summary.txt"

if command -v jq >/dev/null 2>&1; then
  jq -e '.evidence_class == "OBSERVED" and .trigger == "source-test"' "$report_dir/report.json" >/dev/null
  jq -e '.payload_sha256 | length == 64' "$report_dir/report-envelope.json" >/dev/null
fi

echo 'BLACK CAT AUTOMATIC RECOVERY SOURCE CHECK: PASS (physical/VM boot still required)'
