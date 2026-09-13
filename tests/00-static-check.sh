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
  AI_AUTONOMY_POLICY.md \
  AI_WORK_STATE.md \
  project_content.json \
  web/project.php \
  docs/05-architecture-decisions.md \
  docs/06-prototype-build-specification-v0.1.md \
  docs/07-lessons-learned.md \
  docs/11-automation-manifesto.md \
  docs/12-high-security-controller-architecture.md \
  docs/13-ai-continuity-and-context-recovery.md \
  docs/14-context-health-calibration-log.md \
  docs/15-black-cat-ai-project-automation.md \
  schema/ai-project-policy.schema.json \
  templates/ai-project/README.md \
  templates/ai-project/PROJECT_RULES.md \
  templates/ai-project/AI_AUTONOMY_POLICY.md \
  templates/ai-project/AI_WORK_STATE.md \
  templates/ai-project/project-policy.json \
  templates/ai-project/docs/architecture-decisions.md \
  templates/ai-project/docs/lessons-learned.md \
  assets/blackcat-boot-160x120.png.b64 \
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

  if jq -e . "${ROOT_DIR}/schema/ai-project-policy.schema.json" >/dev/null && \
     jq -e . "${ROOT_DIR}/templates/ai-project/project-policy.json" >/dev/null; then
    printf '[OK]      AI project policy schema/template parse as JSON\n'
  else
    printf '[FAILED]  AI project policy schema or template is invalid JSON\n'
    failures=1
  fi

  if jq -e '
      .schema_version == "0.1" and
      .default_action_class == "HUMAN_GATE" and
      .network.mode == "disabled" and
      .network.inbound_listeners == false and
      .capabilities.raw_block_devices == false and
      .capabilities.root == false and
      .capabilities.credentials == "none" and
      .audit.record_action_summary == true and
      .audit.record_chain_of_thought == false and
      ([.human_gates[].id | select(. == "credential.new" or . == "storage.raw_write" or . == "trust.change" or . == "network.inbound")] | length) == 4 and
      ([.forbidden[].id | select(. == "secret.expose" or . == "safety.bypass" or . == "authority.self_expand")] | length) == 3
    ' "${ROOT_DIR}/templates/ai-project/project-policy.json" >/dev/null; then
    printf '[OK]      AI project template retains fail-closed capability defaults\n'
  else
    printf '[FAILED]  AI project template lost one or more fail-closed capability defaults\n'
    failures=1
  fi

  if jq -e '
      if .candidate.status == "test" then
        (.candidate.filename | type == "string" and length > 0) and
        (.candidate.size_bytes | type == "number" and . > 0) and
        (.candidate.sha256 | type == "string" and test("^[a-f0-9]{64}$")) and
        (.candidate.source_commit | type == "string" and test("^[A-Fa-f0-9]{40}$")) and
        (.candidate.build_run | tostring | test("^[0-9]+$")) and
        (.candidate.artifact_id | tostring | test("^[0-9]+$")) and
        (.candidate.artifact_name | type == "string" and length > 0) and
        (.candidate.smoke_test == "passed") and
        (.candidate.download_url | type == "string" and startswith("https://"))
      else
        true
      end
    ' "${ROOT_DIR}/project_content.json" >/dev/null; then
    printf '[OK]      Current Test Candidate metadata is complete when enabled\n'
  else
    printf '[FAILED]  enabled Current Test Candidate metadata is incomplete\n'
    failures=1
  fi

  if jq -e '
      ([.milestones[] | select(.id == "M13B" and .status == "current")] | length) == 0 or
      (.candidate.status == "test" or .candidate.status == "superseded")
    ' "${ROOT_DIR}/project_content.json" >/dev/null; then
    printf '[OK]      M13B cannot silently lose its candidate record\n'
  else
    printf '[FAILED]  M13B is current but the candidate record was removed\n'
    failures=1
  fi

  if jq -e '
      ([.documents[] | select(.url | endswith("/AI_AUTONOMY_POLICY.md"))] | length) == 1 and
      ([.sections[] | select(.title == "AI autonomy policy — when GPT continues automatically")] | length) == 1
    ' "${ROOT_DIR}/project_content.json" >/dev/null; then
    printf '[OK]      project viewer index surfaces the root AI autonomy policy\n'
  else
    printf '[FAILED]  project_content.json does not surface the root AI autonomy policy\n'
    failures=1
  fi

  if jq -e '
      ([.documents[] | select(.url | endswith("/AI_WORK_STATE.md"))] | length) == 1 and
      ([.documents[] | select(.url | endswith("/docs/13-ai-continuity-and-context-recovery.md"))] | length) == 1 and
      ([.documents[] | select(.url | endswith("/docs/14-context-health-calibration-log.md"))] | length) == 1 and
      ([.sections[] | select(.title == "Recoverable AI chat continuity and context health")] | length) == 1
    ' "${ROOT_DIR}/project_content.json" >/dev/null; then
    printf '[OK]      project viewer index surfaces compact AI recovery/context documentation\n'
  else
    printf '[FAILED]  project_content.json does not surface AI recovery/context documentation\n'
    failures=1
  fi

  if jq -e '
      ([.documents[] | select(.url | endswith("/docs/15-black-cat-ai-project-automation.md"))] | length) == 1 and
      ([.milestones[] | select(.id == "M26" and .status == "planned")] | length) == 1 and
      ([.sections[] | select(.title == "Future native AI project automation in Black Cat Linux")] | length) == 1
    ' "${ROOT_DIR}/project_content.json" >/dev/null; then
    printf '[OK]      project plan preserves post-core native AI project automation milestone\n'
  else
    printf '[FAILED]  project_content.json lost the future native AI project automation plan\n'
    failures=1
  fi
else
  printf '[SKIP]    jq unavailable; JSON/candidate/policy checks skipped\n'
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

if ! grep -Fq 'Root AI autonomy decision table is mandatory' "${ROOT_DIR}/PROJECT_RULES.md"; then
  printf '[FAILED]  PROJECT_RULES.md is missing the root AI autonomy policy rule\n'
  failures=1
else
  printf '[OK]      root AI autonomy decision table is mandatory\n'
fi

if ! grep -Fq 'Compact AI work-state recovery and context-health checkpoint are mandatory' "${ROOT_DIR}/PROJECT_RULES.md"; then
  printf '[FAILED]  PROJECT_RULES.md is missing the compact AI recovery/context-health rule\n'
  failures=1
else
  printf '[OK]      compact AI recovery/context-health checkpoint is mandatory\n'
fi

if ! grep -Fq 'If no genuine human-gate condition applies' "${ROOT_DIR}/docs/11-automation-manifesto.md"; then
  printf '[FAILED]  Automation Manifesto is missing its no-routine-pause rule\n'
  failures=1
else
  printf '[OK]      Automation Manifesto forbids routine permission pauses\n'
fi

if ! grep -Fq 'AI_AUTONOMY_POLICY.md' "${ROOT_DIR}/docs/11-automation-manifesto.md"; then
  printf '[FAILED]  Automation Manifesto does not point to the root autonomy policy\n'
  failures=1
else
  printf '[OK]      Automation Manifesto points to the root autonomy policy\n'
fi

autonomy_policy="${ROOT_DIR}/AI_AUTONOMY_POLICY.md"
for required in \
  'Default = continue automatically' \
  '### AUTO' \
  '### AUTO_WITH_CHECKS' \
  '### HUMAN_GATE' \
  '### FORBIDDEN_BY_DEFAULT' \
  'capability boundary, not an approval gate' \
  'Fresh-session and context-recovery rule' \
  'Context Health rule' \
  'project_content.json` **last**'; do
  if grep -Fq "$required" "$autonomy_policy"; then
    printf '[OK]      autonomy policy retains: %s\n' "$required"
  else
    printf '[FAILED]  autonomy policy is missing: %s\n' "$required"
    failures=1
  fi
done

work_state="${ROOT_DIR}/AI_WORK_STATE.md"
for required in \
  '## Current project state' \
  '## Current exact test candidate' \
  '## Next automatic actions' \
  '## Next genuine human gate' \
  '## Context health' \
  '## Recovery sequence' \
  'Context pressure itself is never a human-approval gate'; do
  if grep -Fq "$required" "$work_state"; then
    printf '[OK]      AI work state retains: %s\n' "$required"
  else
    printf '[FAILED]  AI work state is missing: %s\n' "$required"
    failures=1
  fi
done

work_state_lines="$(wc -l < "$work_state" | tr -d '[:space:]')"
if [[ "$work_state_lines" =~ ^[0-9]+$ ]] && (( work_state_lines <= 120 )); then
  printf '[OK]      AI_WORK_STATE.md remains compact (%s lines)\n' "$work_state_lines"
else
  printf '[FAILED]  AI_WORK_STATE.md is too large for a compact recovery checkpoint (%s lines)\n' "$work_state_lines"
  failures=1
fi

if grep -Fq 'The repository is the durable project memory' "${ROOT_DIR}/docs/13-ai-continuity-and-context-recovery.md" && \
   grep -Fq 'selective reconstruction' "${ROOT_DIR}/docs/13-ai-continuity-and-context-recovery.md" && \
   grep -Fq 'does not provide this workflow with a trustworthy live value' "${ROOT_DIR}/docs/13-ai-continuity-and-context-recovery.md"; then
  printf '[OK]      reusable AI continuity guide retains checkpoint/selective-reload boundaries\n'
else
  printf '[FAILED]  reusable AI continuity guide is missing required recovery/context boundaries\n'
  failures=1
fi

if grep -Fq 'v0.1 baseline established' "${ROOT_DIR}/docs/14-context-health-calibration-log.md" && \
   grep -Fq 'do not reconstruct a false precision' "${ROOT_DIR}/docs/14-context-health-calibration-log.md"; then
  printf '[OK]      context-health calibration log preserves empirical/no-false-precision rules\n'
else
  printf '[FAILED]  context-health calibration log is missing its empirical/no-false-precision rule\n'
  failures=1
fi

if grep -Fq 'The repository owns project state' "${ROOT_DIR}/docs/15-black-cat-ai-project-automation.md" && \
   grep -Fq '## 19. Proposed implementation phases' "${ROOT_DIR}/docs/15-black-cat-ai-project-automation.md" && \
   grep -Fq '## 22. Design scaffold now baked into the repository' "${ROOT_DIR}/docs/15-black-cat-ai-project-automation.md" && \
   grep -Fq 'templates/ai-project/' "${ROOT_DIR}/docs/15-black-cat-ai-project-automation.md" && \
   grep -Fq 'schema/ai-project-policy.schema.json' "${ROOT_DIR}/docs/15-black-cat-ai-project-automation.md" && \
   grep -Fq 'after the first usable core prototype' "${ROOT_DIR}/docs/15-black-cat-ai-project-automation.md"; then
  printf '[OK]      future Black Cat AI project automation design retains authority, scaffold and roadmap boundaries\n'
else
  printf '[FAILED]  future Black Cat AI project automation design is missing required scaffold/boundaries\n'
  failures=1
fi

template_root="${ROOT_DIR}/templates/ai-project"
if grep -Fq 'The repository owns project state' "${template_root}/README.md" && \
   grep -Fq '## 7. Untrusted-data boundary' "${template_root}/PROJECT_RULES.md" && \
   grep -Fq '### FORBIDDEN_BY_DEFAULT' "${template_root}/AI_AUTONOMY_POLICY.md" && \
   grep -Fq 'Context pressure itself is never a human-approval gate' "${template_root}/AI_WORK_STATE.md"; then
  printf '[OK]      reusable AI project template retains durable-state/untrusted-data/autonomy/recovery boundaries\n'
else
  printf '[FAILED]  reusable AI project template is missing a required policy/recovery boundary\n'
  failures=1
fi

template_state_lines="$(wc -l < "${template_root}/AI_WORK_STATE.md" | tr -d '[:space:]')"
if [[ "$template_state_lines" =~ ^[0-9]+$ ]] && (( template_state_lines <= 100 )); then
  printf '[OK]      template AI_WORK_STATE.md remains compact (%s lines)\n' "$template_state_lines"
else
  printf '[FAILED]  template AI_WORK_STATE.md is too large (%s lines)\n' "$template_state_lines"
  failures=1
fi

viewer="${ROOT_DIR}/web/project.php"
if grep -Fq "const TOKEN_FILE = '/home/learning/.blackcat/github_token';" "$viewer"; then
  printf '[OK]      viewer uses the protected token-file source\n'
else
  printf '[FAILED]  viewer is not pinned to the protected token-file source\n'
  failures=1
fi

for forbidden in 'PTL_GITHUB_TOKEN' 'GITHUB_TOKEN_FALLBACK' 'offline-cache'; do
  if grep -Fq "$forbidden" "$viewer"; then
    printf '[FAILED]  viewer contains forbidden fallback/cache token: %s\n' "$forbidden"
    failures=1
  else
    printf '[OK]      viewer excludes %s fallback/cache behavior\n' "$forbidden"
  fi
done

if grep -Fq 'Current Test Candidate' "$viewer" && grep -Fq 'TEST CANDIDATE — NOT AN APPROVED RELEASE' "$viewer"; then
  printf '[OK]      viewer retains the separate Current Test Candidate panel\n'
else
  printf '[FAILED]  viewer lost the Current Test Candidate panel or warning label\n'
  failures=1
fi

if grep -Fq "const GITHUB_BRANCH = 'codex/remove-chroot-systemd-boot';" "$viewer"; then
  printf '[OK]      viewer is temporarily pinned to the authoritative M13B branch\n'
else
  printf '[FAILED]  viewer is not pinned to the current authoritative M13B branch\n'
  failures=1
fi

if grep -Fq 'project_cache/builder_status.json' "$viewer" && grep -Fq 'Builder / Server Status' "$viewer" && grep -Fq 'display-only operational telemetry' "$viewer"; then
  printf '[OK]      viewer retains display-only builder/server status telemetry\n'
else
  printf '[FAILED]  viewer lost the builder/server status panel or authority boundary\n'
  failures=1
fi

if grep -Fq 'Project Data Source' "$viewer" && grep -Fq 'LIVE / SYNCHRONISED' "$viewer" && grep -Fq 'Live GitHub API only' "$viewer"; then
  printf '[OK]      viewer clearly identifies authoritative live project data\n'
else
  printf '[FAILED]  viewer lost the clear Project Data Source panel\n'
  failures=1
fi

if grep -Fq "preg_match('/token|secret|password|passwd|private|authorization|credential|api[_-]?key|ssh[_-]?key/i'" "$viewer"; then
  printf '[OK]      viewer filters secret-like builder-status keys\n'
else
  printf '[FAILED]  builder-status display is missing its secret-like key filter\n'
  failures=1
fi

splash_b64="${ROOT_DIR}/assets/blackcat-boot-160x120.png.b64"
if command -v base64 >/dev/null 2>&1 && command -v sha256sum >/dev/null 2>&1; then
  splash_tmp="$(mktemp)"
  trap 'rm -f "$splash_tmp"' EXIT
  if base64 --decode "$splash_b64" > "$splash_tmp" 2>/dev/null; then
    splash_sha="$(sha256sum "$splash_tmp" | awk '{print $1}')"
    if [[ "$splash_sha" == "24d03a442c3f7f04a060d30fbf8710f8b8cd2b5495caffdeea7cc47fcbc546ea" ]]; then
      printf '[OK]      Black Cat boot splash decodes to expected SHA-256\n'
    else
      printf '[FAILED]  Black Cat boot splash SHA-256 mismatch: %s\n' "$splash_sha"
      failures=1
    fi
  else
    printf '[FAILED]  Black Cat boot splash base64 does not decode\n'
    failures=1
  fi
else
  printf '[SKIP]    base64/sha256sum unavailable; boot splash decode check skipped\n'
fi

if grep -Fq 'background_image /boot/grub/blackcat.png' "${ROOT_DIR}/config/live-build/bootloaders/grub-pc/grub.cfg"; then
  printf '[OK]      GRUB menu references Black Cat boot splash\n'
else
  printf '[FAILED]  GRUB menu does not reference Black Cat boot splash\n'
  failures=1
fi

if grep -Fq 'menu background splash.png' "${ROOT_DIR}/config/live-build/bootloaders/syslinux_common/live.cfg.in"; then
  printf '[OK]      Syslinux menu references Black Cat boot splash\n'
else
  printf '[FAILED]  Syslinux menu does not reference Black Cat boot splash\n'
  failures=1
fi

if [[ "$failures" -ne 0 ]]; then
  echo "Static checks failed." >&2
  exit 1
fi

echo "Static checks passed."