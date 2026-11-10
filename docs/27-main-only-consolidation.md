# Main-only consolidation — 11 November 2026 (AWST)

Author: Codex, GPT-6 family; exact deployed variant unknown. Authority: the operator's explicit five-task directive to consolidate main, migrate/deploy the viewer to main, then delete superseded branches after live verification. This is not a release/key/platform authorization.

## Task 1 completed before code changes

Inventory source: `git ls-remote --heads origin` with exact-SHA ancestry and unique-commit counts against main `6bf3711820cf6fe55c1dbef86a34a586bd49243d`. There are 20 remote heads. Eighteen are already contained in main. The two remaining refs have the same tip and the same six valuable viewer review/hardening/status commits, so they are one integration stream, not two independent implementations. Full machine-readable inventory: evidence/2026-11-11-main-only/branch-inventory.json. Historical labels do not by themselves authorize deletion; exact ancestry is recorded separately.

| Branch | Tip commit | Classification | Unique commits |
| --- | --- | --- | --- |
| archive/2026-09-16-pre-integration-118224d5 | `118224d5b696e5e8cc0ec45841adbb2930febb3b` | historical_superseded | 0 |
| build/graphical-iso-2026-09-16 | `86ddbb8eac1a0dbf912c035d5b916b70453dff88` | historical_superseded | 0 |
| codex/automatic-boot-recovery | `ab5ebe0093bc78b9145fb670090520168be24e04` | historical_superseded | 0 |
| codex/candidate-malware-scan | `3d0098b00f041a71f2ddc8fff0cb3d3a0331d6aa` | historical_superseded | 0 |
| codex/dependency-verification | `cc79c94934dd9e991161ef369c38d561618746ca` | historical_superseded | 0 |
| codex/first-live-build | `c0a302b663ad9b875016abbb51f6f36e8c4f4e02` | historical_superseded | 0 |
| codex/graphical-evidence-review | `df46ad1504815d9799e2009aa3706b25fcb72f68` | historical_superseded | 0 |
| codex/live-build-attempt-2 | `b2eb461e5c4581b391a83dd50f84de7e2be223f8` | historical_superseded | 0 |
| codex/m15-signed-uki-development | `64dbd49a3de8103de5b163c808f46e975372b561` | fully_contained_in_main | 0 |
| codex/remove-chroot-systemd-boot | `6bf3711820cf6fe55c1dbef86a34a586bd49243d` | historical_superseded | 0 |
| codex/runner-smoke-test | `8a47e7de4530ecc1600f716e72763d0bae6a287d` | historical_superseded | 0 |
| codex/systemd-boot-state-diagnostic | `5e6adb7edf61abf24cd0a1071d557d4831d6091c` | historical_superseded | 0 |
| fix/project-viewer-hardening-20261110 | `6615900a4d32f8201c5cc87b5ac28943ee188d99` | unique_commits_need_evaluation | 6 |
| integration/main-20261110 | `f2bca2c189328f492c3a0c6be18d63fd3e8dc36a` | fully_contained_in_main | 0 |
| main | `6bf3711820cf6fe55c1dbef86a34a586bd49243d` | fully_contained_in_main | 0 |
| ops/makephone-viewer-20260920 | `8d9a196f089930751ca6bcb7b903d2fb6ad32c7d` | historical_superseded | 0 |
| ops/runner-recovery-20261110 | `cd3966b805c7376d327154d332b6e217af41a452` | fully_contained_in_main | 0 |
| project-status-live | `6bf3711820cf6fe55c1dbef86a34a586bd49243d` | fully_contained_in_main | 0 |
| recovery-verify-ubuntu-26041 | `d27c70b08a5c39d3974ca03a328c8d40dcd27718` | historical_superseded | 0 |
| review/viewer-feedback-20261110 | `6615900a4d32f8201c5cc87b5ac28943ee188d99` | unique_commits_need_evaluation | 6 |

No runtime code or workflow is changed in this inventory/lessons commit. Existing PR #10 will be the single final integration PR; no new long-lived branch is created.

## Why the branches existed and what drift resulted

Repository documents record temporary branch-scoped build workarounds during PR conflicts, graphical/recovery experiments, isolated runner restoration before enabling root-capable CI, development-only M15 work pending a main merge, a separate deployed JSON feed, and historical preservation refs. Some original authors' unstated intent is UNKNOWN; these reasons are limited to recorded history.

The earlier main consolidation preserved histories but retained all refs. Later viewer review and hardening stayed off main pending its merge gate and unavailable hosting access. Production PHP, repository PHP and data feeds then represented different stages: one viewer read codex/remove-chroot-systemd-boot, repository source targeted project-status-live, and the tested replacement was still a side-branch artifact. Matching JSON across old feeds did not deploy the PHP. The supplied older renderer could show 0%, omit capabilities and recast development-verified as planned. We also missed a duplicated M15 merged flag in JSON. These are process/contract/deployment failures, not a reversal of the scoped M15 signature evidence.

## Authorized decision and order

Main becomes the only long-lived branch and the only production JSON source. Long-lived status/ops branches require a separate HUMAN_GATE exception. Preserve immutable evidence/history and use bounded short-lived work only when needed. The current directive explicitly authorizes this integration/main merge, production-feed migration and later branch deletion; do not ask again for those authorized actions.

1. Inventory and canonical lessons first (this commit), before code.
2. Evaluate/integrate the six unique viewer commits using existing PR #10; make source and checks main-only, preserve strict download gates, and correct current status data.
3. Install the exact accepted main PHP through authorized hosting access, with backup and rollback, and verify the real HTTPS response reports main and truthful closed download gates.
4. Only after step 3 passes, recheck each current branch tip against actual main before deleting it. A branch name or historical label is not sufficient evidence. Preserve explicitly archived unique material if any appears.
5. Record policy, outcome/limits and lessons. Update project_content.json LAST.

No approved ISO or active physical test candidate is introduced. M15 remains verified only within its recorded development-key/source/image limitations. Production key custody, platform enrollment/ownership and release approval remain HUMAN_GATE.

## Current execution state

Task 1 inventory/lesson was committed at becd42bc0e453831ad175e78a406ce474eda3892 before any code/workflow edit. Viewer and active privileged build/source/recovery routing are narrowed to main and integrated through PR #10 at 29cd6ba203e9da012480a5bf65a2557becd34bf5. Exact-head PR run 42879650576 and merged-main source/browser run 42879857672 passed. All 20 initial and all 20 current tips are contained in that main commit, with zero unique commits outside main. Live deployment and deletion remain pending. The environment still reports restricted enforced network access with no configured hosting identity; deployment/live verification will be attempted using available authorized capabilities. A capability boundary must be reported honestly, not relabeled as deployment success. Branch cleanup remains explicitly conditional on observed live-main success.

## Current main-only deployment contract

The updated PHP keeps the existing protected credential location and strict schema/blob/run/artifact/expiry/release/telemetry/CSP gates, but hard-codes GITHUB_BRANCH to main. It no longer fetches or compares a mutable side feed, and it does not claim that comparing a ref to itself is an independent synchronization check. The source panel identifies Repository branch = main and the returned main Git blob. Invalid/unavailable main data fails closed; unknown/conflicting status and missing publication evidence never enable downloads. No production-source comment names a temporary/status branch.

Use the exact accepted main web/project.php, with a nonpublic backup/rollback of the deployed file. Install the unchanged scoped cache-denial aid only on compatible hosting and verify it; source does not establish .htaccess enforcement. Hosting requirements, protected credential handling and PHP 8.1+/cURL remain as documented in docs/26; its old branch choice is explicitly historical. Compare actual response source version/digest, main JSON blob, closed candidate/release state and headers with the accepted artifact. Print/nonce behaviour and failure paths must remain intact. Public page URL: https://easyai.com.au/USB/project.php.

The cloud desktop had no authenticated tabs. Attempting the public URL through browser automation failed with “Browser Use could not read requirements”; the connection cannot be used as a verified live browser. Linux reports restricted enforced networking and no configured hosting identity. These are concrete capability facts, not proof of the public server's current state. The operator confirmed they will upload the completed file after review. Provide an exact downloadable artifact and checksums; do not request hosting secrets or infer that file delivery is installation. No branch deletion occurs until actual live main-source success is observed.

## Checked source and operator delivery (before integration)

Viewer 2026-11-11.1 SHA-256: `91dd5349537955c5f82ce11b19885be74713c89cd3f6f070f3e0bc3631512cd1` (45,937 bytes). The exact source passed 22 actual-PHP positive/negative groups, five privileged integration-routing regressions, twelve runner regressions, M14 signature negatives, static/recovery/UI checks and real Chromium nonce/print/injection/no-store/mobile controls. Commands, bounded scope and logs are in evidence/2026-11-11-main-only/source-report.json. Main API failures 403/404/500/transport error reject the page with HTTP 503 and both download gates closed. Full existing candidate/release identity, smoke/run, artifact/digest/expiry and publisher-recorded authentication conditions remain required; fetching JSON is not binary verification. No M15 crypto source changed.

The optional delivery archive contains only project.php, INSTALL.txt and the unchanged project_cache/.htaccess denial aid; it contains no token or JSON/cache contents. ZIP SHA-256: `4be43f16d7d307eb4270a0b8ae5696492447ed5b008b4dded1736c6a9c82b514`. Token/cache paths remain protected outside the web root. Before replacement, retain the deployed PHP backup outside the public tree. PHP 8.1+/cURL, private ownership/permissions and actual server denial rules must work; do not weaken them to obtain a green page. The operator explicitly chose to upload after reviewing the completed file. File delivery is not deployment.

After upload, verify the actual HTTPS response: version/digest above, Repository branch = main, current main JSON Git blob, development-only M15 limits, no approved/current ISO, both download controls disabled, nonce CSP/no-store headers and working Print. Legacy cache content must not be publicly readable. A self-reported digest is only a consistency aid, not an independent host-security claim. Do not alter live credentials or deliberately corrupt production data to rerun negative fixtures. If the new file fails, restore the nonpublic backup through the host's normal replacement mechanism and record that migration remains incomplete.

No obsolete ref has been deleted. Live verification and subsequent exact-tip deletion checks are still outstanding. The initial 20-head inventory remains unmodified; add later observations separately. Main-only policy is written in PROJECT_RULES.md, AI_AUTONOMY_POLICY.md and AI_WORK_STATE.md, while old recovery/deployment conclusions retain dated supersession notes. Current JSON and navigation will be corrected last; immutable evidence URLs and historical source/image identities remain attached to their original observations.

## Integration outcome and remaining definition of done

The one final integration PR #10 was merged with a merge commit, preserving the six unique viewer commits, inventory-first commit, checked implementation and JSON-last preparation. Main merge: `29cd6ba203e9da012480a5bf65a2557becd34bf5`. Hosted source/actual-renderer/real-browser checks passed for both exact PR head `1951c50ee7cf9a72a86d3cf8c747e318d202b74e` (run 42879650576/job 130052186389) and merged main (run 42879857672/job 130052902239). Post-integration exact-tip proof: evidence/2026-11-11-main-only/post-integration-ancestry.json. All 20 original tips and every postmerge head are ancestors of the recorded main; zero useful commits are stranded. The fresh automatic main M15 run 42879857403 is recorded separately in consolidation-report.json and does not create an active candidate.

| Task | Result |
| --- | --- |
| 1: inventory and first lessons | VERIFIED, committed before new code |
| 2: complete main source | VERIFIED source/history integration and main-only source/browser gates; no approved ISO/current candidate |
| 3: deploy and inspect real HTTPS | UNKNOWN; operator will upload after reviewing the supplied exact file |
| 4: delete obsolete refs | PENDING Task 3; zero refs deleted, all 20 remain |
| 5: policy and record | Written, including the incomplete outcome and JSON-last closeout; post-deployment outcome remains to append |

This is partial consolidation, not the full definition of done. User execution is an agreed hosting capability handoff, not a fresh request for merge/source-migration permission. No broad credential grant is needed. On upload notification, verify actual HTTPS branch/version/hash/blob, scoped M15 language, no current candidate/release, closed gates and headers/cache denial. Only then re-inventory every exact current head, stop on moved/unmerged content, delete obsolete contained refs through available authorized capabilities, and append final lessons/records with project_content.json last. Current ancestry is not permission to skip this live gate.

A bookkeeping harness initially checked a stale local origin/main because the checkout inherited a single-ref ops fetch configuration. The missing new inventory file exposed it before any ancestry result was accepted. Explicitly fetched main, asserted the exact GitHub merge SHA and repeated all checks successfully. A generic fetch or local branch label is not authoritative freshness evidence. No source history or inventory conclusion was overwritten.

The canonical review/download source is https://github.com/efrgdgr0024345/portable-trusted-linux/blob/main/web/project.php (raw: https://raw.githubusercontent.com/efrgdgr0024345/portable-trusted-linux/main/web/project.php). The accepted source is also immutably available at commit 29cd6ba203e9da012480a5bf65a2557becd34bf5. Verify the recorded SHA-256 before installation; the main URL can advance with later authorized work.

Fresh main normal pipeline run 42879857403/job 130052901271 passed at exact merge source. All 28 actual-tool M15 regressions passed, correct-key and bound-input verification succeeded, tampered/wrong-key/unsigned/changed-command-line fixtures were rejected as expected, and final ISO filesystem/boot-extent binding passed. The public evidence artifact is 13795286780, ZIP SHA-256 20b55d474fd717b7f04109855e08dbd377d74a91dde71191787e3dad15e54846, expires 2026-11-17T19:14:31Z. New UKI SHA-256: `85128fb26559da34d47f09ef5146df1fa812821dca0ac07f7f2ccb4074f8316d`. These distinct bytes and certificate/ISO identities are in fresh-main-m15-summary.json; they do not replace historical hashes or designate a current test candidate. GitHub reports/CI performed these binary checks; no new local binary download, physical boot, firmware enforcement or release approval is claimed.
