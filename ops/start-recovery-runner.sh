#!/usr/bin/env bash
# Disposable PTL builder only. Account-scoped GitHub OAuth is not required.
set -Eeuo pipefail
PATH=/usr/sbin:/usr/bin:/sbin:/bin
export PATH

check_confirmations() {
    [ "${PTL_OUTSIDE_APPROVAL_CONFIRMED:-}" = yes ] || {
        echo "Confirm GitHub requires approval for ALL outside collaborators before startup." >&2
        return 1
    }
    [ "${PTL_LABELS_CONFIRMED:-}" = yes ] || {
        echo "Confirm the runner has only ptl-trusted-20261110 and no default labels." >&2
        return 1
    }
}
if [ "${1:-}" = --check-confirmations ]; then
    check_confirmations
    exit
fi

[ "$(id -u)" = 0 ] || { echo "Root console required." >&2; exit 1; }
[ "$(hostname)" = ptl-builder-01 ] || { echo "Unexpected host." >&2; exit 1; }
check_confirmations
cd /home/github-runner/actions-runner
[ -f .runner ] || { echo "Registration missing." >&2; exit 1; }
[ ! -f .service ] || { echo "Service already configured; inspect before retrying." >&2; exit 1; }
jq -e '.agentName == "ptl-builder-01-recovery" and .gitHubUrl == "https://github.com/efrgdgr0024345/portable-trusted-linux"' .runner >/dev/null
command -v visudo >/dev/null
ptl_sudo_file=/etc/sudoers.d/90-ptl-disposable-runner
[ ! -e "$ptl_sudo_file" ] || { echo "Existing sudoers policy; inspect before retrying." >&2; exit 1; }
ptl_sudo_tmp="$(mktemp /etc/sudoers.d/.ptl-runner.XXXXXX)"
trap 'rm -f -- "$ptl_sudo_tmp"' EXIT
printf '%s\n' 'github-runner ALL=(ALL:ALL) NOPASSWD: ALL' > "$ptl_sudo_tmp"
/usr/sbin/visudo -cf "$ptl_sudo_tmp"
/usr/bin/install -o root -g root -m 0440 "$ptl_sudo_tmp" "$ptl_sudo_file"
/usr/sbin/visudo -c
test "$(runuser -u github-runner -- sudo -n /usr/bin/id -u)" = 0

./svc.sh install github-runner
ptl_service="$(cat .service)"
case "$ptl_service" in
    actions.runner.*.service) ;;
    *) echo "Unexpected service name; service not started." >&2; exit 1 ;;
esac
test "$(systemctl show --property=User --value "$ptl_service")" = github-runner
./svc.sh start
systemctl is-active "$ptl_service"
./svc.sh status
printf '%s\n' 'SERVICE_STARTED: independent GitHub recovery smoke verification is still required.'
