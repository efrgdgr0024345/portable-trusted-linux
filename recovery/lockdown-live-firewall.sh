#!/usr/bin/env bash
set -Eeuo pipefail

if [[ "${EUID}" -ne 0 ]]; then
  exec sudo -- "$0" "$@"
fi

if ! command -v ufw >/dev/null 2>&1; then
  echo "ERROR: ufw is not installed; no firewall changes made." >&2
  exit 1
fi

echo "Applying live-session firewall:"
echo "  incoming: DENY"
echo "  outgoing: ALLOW"
echo "  routed:   DENY"
ufw --force reset
ufw default deny incoming
ufw default allow outgoing
ufw default deny routed
ufw --force enable
ufw status verbose
