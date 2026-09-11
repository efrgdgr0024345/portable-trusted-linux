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
  build/00-host-check.sh \
  build/01-install-deps.sh \
  build/10-prepare-live-build.sh \
  build/20-build-live.sh \
  build/30-artifact-manifest.sh; do
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

for script in "${ROOT_DIR}"/build/*.sh "${ROOT_DIR}"/tests/*.sh; do
  [[ -f "$script" ]] || continue
  if bash -n "$script"; then
    printf '[OK]      syntax %s\n' "${script#${ROOT_DIR}/}"
  else
    printf '[FAILED]  syntax %s\n' "${script#${ROOT_DIR}/}"
    failures=1
  fi
done

if [[ "$failures" -ne 0 ]]; then
  echo "Static checks failed." >&2
  exit 1
fi

echo "Static checks passed."
