# JSON-only candidate publication recovery — plan v1

Author: Codex, GPT-5-based assistant. Exact deployed model/version is not exposed in this session; no stronger/lighter tier is claimed.
Authority: User instruction to rewrite the resolution plan and require human sign-off for lower-model deviations.
Status: COMPLETE — JSON-only publication accepted live at 2026-09-17T09:17:52Z.

## Supersession recorded — 11 November 2026 (AWST)

The operator explicitly authorized main-only consolidation, production PHP/data migration to main and later obsolete-branch deletion after live-main verification. Active policy/implementation is now PROJECT_RULES.md Rule 16 and docs/27-main-only-consolidation.md. The original September acceptance and ordered recovery below are retained as history; their branch-preservation instruction is no longer the future publication policy. Evidence identity, fail-closed behaviour, JSON-last maintenance, no alternative ISO handoff and real live verification remain required. This supersession is based on explicit human instruction, not inferred model rank or convenience.

## Binding execution rule

Any successor model, including a lower/lighter model, MUST follow this plan. It MUST NOT silently rewrite, skip, weaken or substitute its requirements. A proposed deviation must state the evidence, reason, risks and exact alternative and receive explicit human sign-off before implementation. If execution is impossible or unsafe, stop the affected action and report the blocker; do not blindly execute. This rule does not override platform safety or authorization requirements.

Every future plan or substantive suggestion must record the authoring model name/version when reliably exposed, otherwise mark it unknown; include date, plan version, evidence basis and whether human approval is required. Never infer model strength or identity from writing style.

## Corrected diagnosis

The deployed PHP reads project_content.json from codex/remove-chroot-systemd-boot. Updating JSON on another branch does not update that page. PHP replacement is not necessary for routine candidate publication. The prior project-status-live migration recommendation introduced an avoidable deployment dependency and is superseded for this recovery.

The inspected viewer requires candidate.status == "test". "ready-for-physical-testing" does not satisfy that existing contract. Repository source checks passing did not prove the live candidate could render.

## Ordered implementation

1. Re-read the live HTTPS page's source branch/blob and read its backing JSON at a fixed commit. Confirm the branch is still codex/remove-chroot-systemd-boot. If different, stop and reconcile evidence before selecting a write target.
2. Verify the intended candidate against its build artifact and VM evidence: filename live-image-amd64.hybrid.iso; size 770703360 bytes; SHA-256 f498776780c543013e437161a878e22ca4fc40a66f47fc271d6fd6a964c29d75; source ed5d80ac09c3f1650d67594242643d27efcf15e9; build 35179377345; artifact 10480220416, blackcat-graphical-development-35179377345; VM run 35180150392. Recheck artifact existence/expiry. Do not silently substitute a newer build.
3. Read the existing viewer contract, including candidate status, required fields and exact artifact URL validation. Prepare a narrow JSON patch with status "test", smoke_test "passed", physical validation pending and the exact verified identity. Preserve unrelated metadata, historical M13B evidence and release.status "none". Do not copy the whole review-branch index over the publication branch.
4. Add executable prepublication checks against the existing PHP using isolated fixtures: correct candidate renders the intended artifact link; missing hash, wrong status, mismatched artifact URL and incomplete identity disable handoff. Cross-check candidate identity against the evidence report. Tests must not expose or repurpose credentials. Add a guard for this plan and human-deviation rule; do not weaken existing tests to conceal incompatibility.
5. Record lessons and affected status/checkpoint documents first, then update project_content.json LAST on the branch the deployed page actually reads, using its freshly fetched blob SHA. Preserve prior JSON via Git history and record the commit. No PHP deployment, branch migration, main merge, ISO rebuild, credentials change or server access is required for this JSON-only repair.
6. Fetch https://easyai.com.au/USB/project.php and parse its Current Test Candidate section. Require an enabled link and exact matches for filename, integer byte size, full SHA-256, source commit, run, artifact ID/name and artifact URL. Check no approved release is implied. Verify the reported Git blob corresponds to the published JSON. Record timestamp and observations; a 200 response or LIVE badge alone is insufficient.
7. Only after that acceptance check passes report publication complete and direct the operator to the live page for testing. USB device approval remains separate. A failure must be reported as publication incomplete; no alternative chat link bypass.

## Recovery and boundaries

If the update fails or concurrent edits are detected, re-read and reconcile; do not force-push or overwrite unrelated changes. If live acceptance fails, investigate the contract or publication target. Do not restore a superseded ISO as a supposedly current candidate merely to regain a green display. A rollback must explicitly retain an unavailable/not-current handoff state.

Routine future ISO publication follows the same evidence -> compatible JSON update at the configured source -> live verification sequence. Retain the existing source branch as the publication endpoint for this repair despite its historical name. Any later branch migration or PHP change is a separate proposal requiring human sign-off.

## Definition of done

The live page itself displays and links the exact verified artwork candidate without replacing PHP; the compatibility and negative tests pass; historical evidence is retained; no approved-release or physical-test claims are fabricated. Until these conditions hold, status is NOT COMPLETE.

## Acceptance record

At 2026-09-17T09:17:52Z the live HTTPS page reported JSON blob f012581ef46bfa8b1977d82727c55b91c3ce864a from codex/remove-chroot-systemd-boot and displayed the exact intended filename, 770703360-byte size, full f4987767… SHA-256, ed5d80ac… source, build 35179377345, artifact name/ID and exact artifact URL. It showed Ready for physical testing while the separate release panel showed No approved ISO and Download unavailable. PHP was not replaced. Definition of done passed.
