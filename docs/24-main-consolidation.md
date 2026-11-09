# Main consolidation — 10 November 2026 (AWST)

Author: Codex, GPT-6 (platform-provided family; exact deployed variant unknown). Authority: operator explicitly instructed "proceed ... get this all onto one main" after the divergence review. Status: IN PROGRESS; merge and combined-source CI are not yet observed.

## Scope and history

Main was 15697b2fb4261cc2785082550c9aeae3fa4668dc. M15 head 64dbd49a3de8103de5b163c808f46e975372b561 was 240 commits ahead and 8 behind. Further branch inspection found independently unmerged automatic boot recovery, malware/publication evidence, M14 single-object parser correction, standalone recovery helpers and historical makephone preview source. All 17 pre-integration branch tips are recorded in evidence/2026-11-10-main-integration/branch-tips.json and retained as ancestors of the integration history. Branches are not deleted or force-pushed.

Conflicts preserve both append-only lesson sequences, current M15 evidence and superseded-candidate state; historical status snapshots remain in Git. The newer M14 validator execute mode and single-object rejection are retained. An early /var/tmp builder change is superseded by the current stronger /var/tmp plus exact-source provenance configuration. Early build-trigger additions are both retained.

Importing existing source does not execute the standalone USB writer, firewall helper, preview server or their workflows. No physical device, firmware, key enrollment, TPM ownership, listener or production secret is changed. Old recovery/bootstrap work is distinct from the current Prototype 1 M15 path.

## Authoritative automation

.github/workflows/m15-development.yml is the privileged normal-pipeline entry point on main and the explicitly authorized integration/main-20261110 branch only, using the existing dedicated ptl-trusted-20261110 label, contents:read, exact SHA checkout and no persisted credentials. Pull-request events/refs and other repositories are rejected. Historical generic-label self-hosted jobs are explicitly disabled, preserving their source for audit instead of broadening root-capable PR routing. The legacy direct-kernel smoke is manual-only and does not prove UKI firmware enforcement. Ordinary hosted source checks remain available for PRs and main.

Run the normal pipeline with sudo bash build/01-install-deps.sh, a fresh absolute PTL_BUILD_WORK_DIR, bash build/20-build-live.sh, then bash build/30-artifact-manifest.sh. CI also runs static, M14 positive/negative, UI, boot-recovery source, publication/PHP, runner, routing and actual-tool M15 checks. Retained failed-input diagnostics are removed from the active workflow; their evidence/history is preserved. A new combined-source normal build is required because imported recovery/config changes alter the image. No new GUI/boot-recovery acceptance claim follows from source consolidation.

## Publication and evidence boundaries

The binding docs/21 plan records the last successfully inspected deployed feed as codex/remove-chroot-systemd-boot; repository PHP targets project-status-live. The earlier M15 cycle did not inspect that newer divergent plan and therefore did not establish the deployed feed. Preserve the plan; do not migrate PHP or endpoints. After integration, fast-forward both existing publication branches to the accepted main history, with identical JSON as the final file write. This is JSON publication to existing endpoints, not deployment. Live rendering remains UNKNOWN while HTTPS access is blocked; physical-candidate handoff stays closed.

Original M15 development acceptance remains attached to source 3125a1c99342b961ac614878d3ce9b3f874de33e, run 42711645617 and artifact 13716513159. Never transfer its physical/firmware assurance to the combined source. There is no approved ISO or new physical-test candidate. Production keys, platform state, release and physical media remain separate human gates. This explicit merge authorization is session-specific, not general delegation of future main merges.

## Validation record

Local combined static checks, 14 M14 positive/negative assertions, boot-recovery source checks and five routing guards pass. Local PHP fixture execution is unavailable because PHP and apt metadata are absent; the configured CI installs PHP and must run those fixtures before merge. Full combined-source build and CI results are pending. Firmware enforcement, physical boot, exact-source GUI/recovery VM behavior and root authentication remain NOT VERIFIED.
