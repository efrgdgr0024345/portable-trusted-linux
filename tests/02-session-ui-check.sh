#!/usr/bin/env bash
set -Eeuo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
UI="$ROOT/config/live-build/includes.chroot/usr/local/bin/blackcat-session-ui"
AUTOSTART="$ROOT/config/live-build/includes.chroot/etc/xdg/autostart/blackcat-session-ui.desktop"
PLYMOUTH_DROPIN="$ROOT/config/live-build/includes.chroot/etc/systemd/system/plymouth-quit-wait.service.d/blackcat-check.conf"
WALLPAPER_CONFIG="$ROOT/config/live-build/includes.chroot/etc/xdg/xfce4/xfconf/xfce-perchannel-xml/xfce4-desktop.xml"
DESIGN_ASSETS="$ROOT/assets/design"
SEED="$ROOT/config/packages.list"
[[ -f "$UI" && -f "$AUTOSTART" ]] || { echo 'UI/autostart missing' >&2; exit 1; }
for pkg in xfce4 lightdm python3-tk xserver-xorg; do
  grep -qx "$pkg" "$SEED" || { echo "Missing GUI dependency: $pkg" >&2; exit 1; }
done
for term in 'blackcat.check=1' 'NOT activated' 'NOT VERIFIED' 'command=root.destroy' 'Stealth. Intelligence. Independence.' 'tk.PhotoImage'; do
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
grep -Fq 'ConditionKernelCommandLine=!blackcat.check=1' "$PLYMOUTH_DROPIN"
grep -Fq '/usr/share/backgrounds/blackcat/blackcat-design-wallpaper.jpg' "$WALLPAPER_CONFIG"
for asset in blackcat-design-wallpaper.jpg browser.png network.png office.png ai.png developer.png security.png; do
  [[ -s "$DESIGN_ASSETS/$asset.b64" ]] || { echo "Missing design asset: $asset" >&2; exit 1; }
  base64 --decode "$DESIGN_ASSETS/$asset.b64" >/dev/null
 done
echo 'BLACK CAT UI DEVELOPMENT STATIC CHECK: PASS (not a boot or module-loading test)'
