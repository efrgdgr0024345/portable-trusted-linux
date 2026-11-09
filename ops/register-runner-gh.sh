#!/usr/bin/env bash
# Retired: default GitHub CLI OAuth requested excessive account permissions.
set -eu
printf '%s\n' \
  'STOP: GitHub CLI OAuth registration is retired for this recovery.' \
  'Use a repository-specific, short-lived runner registration token.' \
  'See docs/runner-registration-cli.md. No login or registration was attempted.' >&2
exit 1
