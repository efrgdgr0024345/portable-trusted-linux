#!/usr/bin/env bash
# Console-only bootstrap. GitHub authentication is temporary; service stays stopped.
set -Eeuo pipefail
set +x
[ "$(id -u)" -eq 0 ] || { echo "Run from the builder root console." >&2; exit 1; }
[ "$(hostname)" = ptl-builder-01 ] || { echo "Unexpected hostname." >&2; exit 1; }
cd /home/github-runner/actions-runner
[ ! -f .runner ] || { echo "Existing registration found; inspect it first." >&2; exit 1; }
command -v gh >/dev/null || { echo "Install Debian's gh package first." >&2; exit 1; }

ptl_auth_dir="$(mktemp -d /tmp/ptl-gh-auth.XXXXXX)"
trap 'unset ACTIONS_RUNNER_INPUT_TOKEN; rm -rf -- "$ptl_auth_dir"' EXIT
echo "Authorize the GitHub CLI device login in your browser."
GH_CONFIG_DIR="$ptl_auth_dir" gh auth login --hostname github.com --git-protocol https --web --skip-ssh-key

# Capture only: do not print the registration token or put it in command arguments.
ACTIONS_RUNNER_INPUT_TOKEN="$(GH_CONFIG_DIR="$ptl_auth_dir" gh api --hostname github.com --method POST repos/efrgdgr0024345/portable-trusted-linux/actions/runners/registration-token --jq .token)"
[ -n "$ACTIONS_RUNNER_INPUT_TOKEN" ] || { echo "No registration token returned." >&2; exit 1; }
export ACTIONS_RUNNER_INPUT_TOKEN
runuser -u github-runner -- ./config.sh --unattended \
    --url https://github.com/efrgdgr0024345/portable-trusted-linux \
    --name ptl-builder-01-recovery \
    --runnergroup Default --no-default-labels \
    --labels ptl-trusted-20261110 --work _work
unset ACTIONS_RUNNER_INPUT_TOKEN
echo "REGISTERED: service remains stopped; temporary CLI credentials will be removed."
