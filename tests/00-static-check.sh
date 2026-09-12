#!/usr/bin/env bash
set -Eeuo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

failures=0

check_file() {
  local path="$1"
  if [[ -f "${ROOT_DIR}/${path}" ]]; then
    printf '[OK]      %s\n' "$path"
  else
    printf '[MISSING] %s\n' "$path"
    failures=1
  fi
}

for path in \
  PROJECT_RULES.md \
  project_content.json \
  docs/06-prototype-build-specification-v0.1.md \
  docs/07-lessons-learned.md \
  docs/11-automation-manifesto.md \
  build/00-host-check.sh \
  build/01-install-deps.sh \
  build/10-prepare-live-build.sh \
  build/20-build-live.sh \
  build/30-artifact-manifest.sh \
  config/live-build/includes.chroot/usr/local/bin/blackcat-check \
  config/live-build/includes.chroot/etc/profile.d/blackcat-auto-check.sh \
  config/live-build/bootloaders/grub-pc/grub.cfg \
  config/live-build/bootloaders/syslinux_common/live.cfg.in; do
  check_file "$path"
done

if command -v jq >/dev/null 2>&1; then
  if jq -e . "${ROOT_DIR}/project_content.json" >/dev/null; then
    printf '[OK]      project_content.json parses as JSON\n'
  else
    printf '[FAILED]  project_content.json is invalid JSON\n'
    failures=1
  fi
else
  printf '[SKIP]    jq unavailable; JSON parse check skipped\n'
fi

for script in \
  "${ROOT_DIR}"/build/*.sh \
  "${ROOT_DIR}"/tests/*.sh \
  "${ROOT_DIR}"/config/live-build/includes.chroot/usr/local/bin/blackcat-check \
  "${ROOT_DIR}"/config/live-build/includes.chroot/etc/profile.d/blackcat-auto-check.sh; do
  [[ -f "$script" ]] || continue
  if bash -n "$script"; then
    printf '[OK]      syntax %s\n' "${script#${ROOT_DIR}/}"
  else
    printf '[FAILED]  syntax %s\n' "${script#${ROOT_DIR}/}"
    failures=1
  fi
done

profile_hook="${ROOT_DIR}/config/live-build/includes.chroot/etc/profile.d/blackcat-auto-check.sh"
if grep -Eq '(^|[[:space:]])/usr/local/bin/blackcat-check([[:space:]]|$)' "$profile_hook"; then
  printf '[FAILED]  login hook must not execute blackcat-check; systemd is the sole report producer\n'
  failures=1
else
  printf '[OK]      login hook is display-only; systemd remains sole report producer\n'
fi

if ! grep -Fq '/run/blackcat/quick-check.txt' "$profile_hook"; then
  printf '[FAILED]  login hook does not reference the root-owned text report\n'
  failures=1
else
  printf '[OK]      login hook displays the root-owned text report\n'
fi

if ! grep -Fq 'Automation Manifesto and mandatory pre-pause check' "${ROOT_DIR}/PROJECT_RULES.md"; then
  printf '[FAILED]  PROJECT_RULES.md is missing the mandatory Automation Manifesto pre-pause rule\n'
  failures=1
else
  printf '[OK]      Automation Manifesto pre-pause rule is mandatory\n'
fi

if ! grep -Fq 'If no genuine human-gate condition applies' "${ROOT_DIR}/docs/11-automation-manifesto.md"; then
  printf '[FAILED]  Automation Manifesto is missing its no-routine-pause rule\n'
  failures=1
else
  printf '[OK]      Automation Manifesto forbids routine permission pauses\n'
fi

if [[ "$failures" -ne 0 ]]; then
  echo "Static checks failed." >&2
  exit 1
fi

echo "Static checks passed."
