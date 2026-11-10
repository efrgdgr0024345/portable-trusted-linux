# Public project viewer review — 10 November 2026 (AWST)

> 11 November 2026 source-remediation update: preserve this review as the historical observed failure record. The fixes and main-only source routing are merged in PR #10 at 29cd6ba203e9da012480a5bf65a2557becd34bf5 and passed exact-source PHP/browser CI. This does not establish production installation/remediation: operator upload and actual HTTPS verification remain pending. Current plan and checked artifacts: [main-only consolidation](27-main-only-consolidation.md).

Author: Codex, GPT-6 family; exact deployed variant unknown. Scope: the PHP copy supplied by the operator, canonical `web/project.php`, and index at main `6bf3711820cf6fe55c1dbef86a34a586bd49243d`. Status: **WARNING — reproducible open findings**. This is a review and metadata-correction proposal, not approval to replace production PHP or change its configured feed. The existing JSON-only publication plan in docs/21 is preserved.

## Main diagnosis

The feedback loop spans different independently changing things: technical evidence, repeated status fields, repository viewer code, deployed viewer code and the deployed feed. A merged repository does not deploy PHP. A successful GitHub fetch does not establish synchronization, schema validity, artifact availability or security assurance. We need explicit version/contract checks across these boundaries, not a stronger-looking green badge.

The uploaded source SHA-256 is `d64501a25ecbe79f05e80f998b9e9cc1f1d1b9c9c96b7adbf5485d691500fa1c`; its Git blob is `7c8a74178e60f1f0b6b57d83a9d18fcc7fe054a7`. Canonical source SHA-256 is `4314b172b1d5634030f7903f0ee68eaaa6ce4de293c3c4737b5eb0b469be2f3b`. The supplied copy differs from canonical main. It reads `codex/remove-chroot-systemd-boot`; canonical source reads `project-status-live`. At review time both feeds and main returned the same JSON blob `d7d2f0937e9130274e14acd076cca802352cf614`. Therefore the old branch name alone is not evidence of current data staleness. It remains a maintenance/deployment boundary requiring accurate reporting.

The PHP file cannot cause this assistant's Linux proxy denial: the failing CONNECT happens before a request reaches PHP. The independent web tool also could not obtain the page. Live server response, deployed byte identity, PHP extensions, credentials and web-server rules remain **UNKNOWN**; supplied source plus local tests do not substitute for a live HTTPS inspection.

## Prioritized findings and proposed corrections

| ID | Priority | Finding and evidence | Proposed improvement |
| --- | --- | --- | --- |
| V1 | P1 | Uploaded lines 235/307 invent 0% when current JSON deliberately has no overall percentage; ten capabilities are omitted. Line 321 changes M15 `development-verified` to `planned`. Actual rendering reproduced these problems. Canonical renderer already fixes these display cases. | Render the capability/evidence contract, preserve exact status labels and use UNKNOWN for missing fields. Never infer an overall assurance percentage. |
| V2 | P1 | Uploaded lines 255–266 accept any HTTPS candidate download. A synthetic unrelated URL was rendered with an enabled button and “Ready for physical testing,” despite declared run/artifact IDs. Canonical source shares this issue. The stronger fixture helper in tests/04 is not the production renderer's gate. | Enforce exact expected GitHub owner/repo/run/artifact URL identity in production, plus evidence/publication state and expiry/availability policy. Exercise the actual renderer for negative fixtures rather than a duplicated predicate. This addresses trusted-metadata mistakes; no anonymous exploitation is established. |
| V3 | P1 | The actual fetch/parser accepts `[]` as project data and renders a green LIVE badge. Uploaded “SYNCHRONISED” checks no comparison with an expected publication. Canonical source also accepts the empty array. | Validate object/schema/field types before rendering. Separate fetched successfully, schema valid, expected publication matched and evidence verified. Show retrieval time, source/blob and independently comparable viewer version. Downloads fail closed on invalid state. |
| V4 | P2 | Our latest JSON left milestone M15 `merged_to_main:false` while the top-level M15/public/handoff records say true and actual main ancestry proves the merge. | Correct that field on this review branch; record the oversight. Derive repeated status fields from one structured record or validate cross-field invariants before JSON publication. Preserve original source/image evidence. |
| V5 | P2 | Builder-status filtering suppresses secret-looking keys but displays arbitrary generic-key values. A year-2000 observation is still “Status file available” with no stale classification. The path is under the viewer directory; raw HTTP access protection is not known. | Publish an allowlist of typed, bounded public telemetry fields with observation time/TTL; put raw status outside the web root or verify explicit server denial. A key blacklist is not proof that values contain no secrets. |
| V6 | P2 | `mb_substr` is called unconditionally. Actual PHP without mbstring and with string telemetry exits 255 with undefined-function failure. Live extension state is unknown. | Declare/check the dependency or use a bounded UTF-8-safe fallback. Test the supported extension/runtime combinations. |
| V7 | P2 | Real Chromium with actual emitted headers blocks the inline Print/PDF handler. A false-initialized hook/direct-invocation positive control confirms the refusal. Both source copies are affected. | Keep CSP and use a same-origin external script/event listener, or rely on browser print controls. Do not relax the script policy to unsafe-inline. |

Additional bounded concerns: every page view makes a synchronous GitHub request with a 12-second timeout, without ETag/conditional caching or rate-aware handling. A cache proposal must explicitly label stale information and keep downloads disabled until current gates pass; do not silently replace the current fail-closed policy. Both source copies call the repository private, while the current API reports public. The read-token dependency and actual minimum scope should be reviewed deliberately, without broadening OAuth or silently adding credential fallbacks. Release links also accept generic HTTPS rather than a bound release/artifact identity; a viewer should consume an evidence-bound publication record, not pretend to cryptographically verify an ISO it has not read.

## What is working

Both sources pass actual PHP syntax lint. Escaping, TLS verification, redirect refusal, protected token-file mode checks, no credential fallback and separation of operational telemetry from download authority are useful controls. The actual HTTP-error fixture returns 503 without a green source badge. The current superseded candidate stays disabled, and no approved release is displayed. These positives do not erase the open negative cases.

## Validation and limits

`evidence/2026-11-10-viewer-review/` retains machine-readable render/browser results, this review's source identities, package provenance and diagnostic reproducers. PHP 8.4.24 packages were extracted without root; package hashes and cached signed Debian index chain were checked using the installed archive keyring. No real token was read: actual source functions ran with synthetic local token files and mocked cURL responses. Chromium used a temporary loopback PHP server and no external page requests. The server was stopped afterward. This is not a live deployment audit or a claim that real secrets were disclosed. Diagnostic scripts report findings; exit zero does not mean security acceptance.

## Recommended order

1. Review the existing-source differences and correct status-contract/data invariants first; avoid restoring a fake percentage or erasing exact status vocabulary.
2. Prepare bounded PHP schema/download-gate fixes with actual-renderer positive and negative fixtures. Add invalid/empty data, unknown statuses, wrong URL/run/artifact, stale/expired evidence and missing-extension cases.
3. Add explicit viewer revision and source/publication observations; make readiness/verification claims reflect precisely what was checked.
4. Test the exact deployment artifact and its rollback. Any PHP or source-branch migration remains a separate explicitly approved change under docs/21; routine JSON updates do not need PHP deployment.
5. After an authorized deployment, inspect the actual live HTTPS response and compare its source/blob, viewer identity, status text and disabled download state against evidence. Until that succeeds, live rendering remains UNKNOWN and physical handoff stays closed.

No PHP source, credential, platform state, build pipeline or live deployment was changed by this review. The proposed JSON correction is on `review/viewer-feedback-20261110`; main and both publication feeds are unchanged. No approved ISO or new runtime trust claim follows. Technical records and lessons precede the final JSON write on the review branch.

## Subsequent authorized remediation

The operator then explicitly requested an updated project.php addressing this review. Implementation and deployment limits are recorded in docs/26-viewer-hardening-and-deployment.md. Historical findings above remain unchanged as evidence for their original source hashes. The replacement preserves the actual supplied feed rather than migrating it, replaces the duplicated test predicate with actual-renderer negative tests, and does not claim to attest the host. Live deployment and legacy static-cache denial still require independent observation.
