#!/usr/bin/env bash
set -Eeuo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
UI="$ROOT/config/live-build/includes.chroot/usr/local/bin/blackcat-session-ui"
AUTOSTART="$ROOT/config/live-build/includes.chroot/etc/xdg/autostart/blackcat-session-ui.desktop"
SEED="$ROOT/config/packages.list"
[[ -f "$UI" && -f "$AUTOSTART" ]] || { echo 'UI/autostart missing' >&2; exit 1; }
for pkg in xfce4 lightdm python3-tk xserver-xorg; do
  grep -qx "$pkg" "$SEED" || { echo "Missing GUI dependency: $pkg" >&2; exit 1; }
done
for term in 'blackcat.check=1' 'NOT activated' 'NOT VERIFIED' 'command=root.destroy'; do
  grep -Fq "$term" "$UI" || { echo "UI safety text missing: $term" >&2; exit 1; }
done
if grep -Eq '(^|[^[:alnum:]_])(subprocess|os\.system|os\.popen|mount|sudo|pkexec|urllib|requests)([^[:alnum:]_]|$)' "$UI"; then
  echo 'UI prototype contains a disallowed execution/network/mount primitive' >&2
  exit 1
fi
if command -v python3 >/dev/null; then
  # Compiles to an ephemeral directory, never writes bytecode into source checkout.
  TMP="$(mktemp -d)"
  trap 'rm -rf "$TMP"' EXIT
  PYTHONPYCACHEPREFIX="$TMP" python3 -m py_compile "$UI"
fi
grep -Fq 'Exec=/usr/bin/python3 /usr/local/bin/blackcat-session-ui' "$AUTOSTART"
echo 'BLACK CAT UI DEVELOPMENT STATIC CHECK: PASS (not a boot or module-loading test)'
