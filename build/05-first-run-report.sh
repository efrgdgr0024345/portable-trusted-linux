#!/usr/bin/env bash
set -Eeuo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_DIR="$(cd "${SCRIPT_DIR}/.." && pwd)"
STAMP="$(date -u +%Y%m%dT%H%M%SZ)"
OUT_DIR="${REPO_DIR}/artifacts/host-checks"
OUT_FILE="${OUT_DIR}/host-check-${STAMP}.txt"

mkdir -p "${OUT_DIR}"

{
  echo "PORTABLE TRUSTED LINUX — FIRST REAL BUILDER REPORT"
  echo "=================================================="
  echo "UTC: $(date -u --iso-8601=seconds)"
  echo "Hostname: $(hostname 2>/dev/null || echo unknown)"
  echo "Architecture: $(uname -m)"
  echo "Kernel: $(uname -srvo)"
  echo

  echo "--- /etc/os-release ---"
  cat /etc/os-release 2>/dev/null || echo "UNAVAILABLE"
  echo

  echo "--- Debian version ---"
  cat /etc/debian_version 2>/dev/null || echo "UNAVAILABLE"
  echo

  echo "--- Git project state ---"
  if command -v git >/dev/null 2>&1 && git -C "${REPO_DIR}" rev-parse --is-inside-work-tree >/dev/null 2>&1; then
    echo "Commit: $(git -C "${REPO_DIR}" rev-parse HEAD)"
    echo "Branch: $(git -C "${REPO_DIR}" branch --show-current)"
    echo "Working tree:"
    git -C "${REPO_DIR}" status --short || true
  else
    echo "Git repository information unavailable."
  fi
  echo

  echo "--- Static project checks ---"
  if [[ -x "${REPO_DIR}/tests/00-static-check.sh" ]]; then
    "${REPO_DIR}/tests/00-static-check.sh" || echo "STATIC_CHECK_EXIT=$?"
  else
    bash "${REPO_DIR}/tests/00-static-check.sh" || echo "STATIC_CHECK_EXIT=$?"
  fi
  echo

  echo "--- Host preflight ---"
  if [[ -x "${SCRIPT_DIR}/00-host-check.sh" ]]; then
    "${SCRIPT_DIR}/00-host-check.sh" || echo "HOST_CHECK_EXIT=$?"
  else
    bash "${SCRIPT_DIR}/00-host-check.sh" || echo "HOST_CHECK_EXIT=$?"
  fi
  echo

  echo "--- Storage summary ---"
  df -hT 2>/dev/null || true
  echo

  echo "--- Memory summary ---"
  free -h 2>/dev/null || true
  echo

  echo "--- CPU summary ---"
  if command -v lscpu >/dev/null 2>&1; then
    lscpu | sed -n '1,35p'
  else
    echo "lscpu unavailable"
  fi
  echo

  echo "--- UEFI exposure on this builder ---"
  if [[ -d /sys/firmware/efi ]]; then
    echo "UEFI_RUNTIME=present"
  else
    echo "UEFI_RUNTIME=not_present_or_vm_not_exposing_uefi"
  fi
  echo

  echo "--- TPM exposure on this builder ---"
  if [[ -e /dev/tpmrm0 || -e /dev/tpm0 ]]; then
    ls -l /dev/tpm* 2>/dev/null || true
  else
    echo "TPM_DEVICE=not_present_or_not_exposed"
  fi
  echo

  echo "END OF REPORT"
} 2>&1 | tee "${OUT_FILE}"

echo
echo "Report saved to: ${OUT_FILE}"
echo "Send this file/output back for the next engineering step."
