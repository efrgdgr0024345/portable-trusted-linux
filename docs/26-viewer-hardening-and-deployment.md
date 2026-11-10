# Hardened living viewer — implementation and deployment

Author: Codex, GPT-6 family; exact deployed variant unknown. Date: 10 November 2026 (AWST).
Authority: the operator explicitly requested a new updated project.php addressing the review findings. This authorizes the bounded PHP replacement; it is not approval for a release ISO, platform changes or a different feed. docs/21's existing-feed JSON-only publication rule remains intact.

## Later main-only migration — 11 November 2026

This document records the earlier 2026-11-10.1 delivery and its original configured-feed decision. The operator has now explicitly superseded that feed decision: the production viewer must read main. The current replacement is 2026-11-11.1; use docs/27-main-only-consolidation.md for its exact accepted source/delivery and live migration state. Earlier hashes/results below stay attached to their original source; they are not claims of current deployment. Credential handling, strict candidate/release gates and no-overclaim limits remain in force.

## Scope and current state

`web/project.php` is the deployable single-file replacement, viewer version `2026-11-10.1`. It preserves the supplied deployed feed `codex/remove-chroot-systemd-boot` and the sole protected token path `/home/learning/.blackcat/github_token`. The previous repository-only `project-status-live` target was not the supplied deployed PHP's target. The static guard now follows the actual configured feed and binding docs/21 plan. `main` is used for a separate per-request JSON-identity comparison, not a publication-source migration. Routine index changes still do not require replacing PHP.

Implementation/test evidence is on `fix/project-viewer-hardening-20261110` and the existing review PR. Main merge is a separate existing policy gate; this task's PHP deployment authorization does not imply future blanket merge authority. Live deployment remains UNKNOWN until the file is installed and externally inspected. No authenticated cPanel/SFTP/SSH hosting connection is available in this session; Linux proxy CONNECT 403 and the independent web tool both prevented public-page inspection. Do not claim a live replacement or verification from a repository write or local preview.

## Bounded checks, not a host-security guarantee

- Actual JSON parsing requires a nonempty object, bounded payload/response, typed known rendered fields/lists and supported contract. Empty/list/malformed input fails closed with HTTP 503. The standard Git blob identifier must match the returned bytes; this is an object-identity check, not a new signing scheme or external trust root.
- The configured feed and canonical main JSON are separately fetched/revalidated. The page reports the two blobs and a narrow match result. Main failure, mismatch or repeated M15 merge-field conflict blocks downloads while readable feed data can remain visible with warnings.
- Exact milestone labels/capability stages are preserved; there is no aggregate assurance percentage. The source panel says fetched data, not that all repository/deployment/runtime state is synchronized.
- Active candidate metadata requires the exact owner/repo/run/artifact URL, separate ISO and artifact-ZIP SHA-256 values, a matching completed successful run and source, matching nonexpired artifact/name/digest/workflow metadata. Missing evidence or API denial disables the link. The recorded ISO hash and smoke outcome remain publisher records: this viewer does not download a ZIP, verify its internal ISO, independently repeat a boot test or authorize a physical write.
- An approved-release link additionally needs explicit publisher-recorded authenticated-manifest identity, pinned release/asset/tag/run/source and matching uploaded asset byte count/digest/URL. These are checked publication/asset records, not local ISO signature verification or production-key provisioning. No approved release is created.
- A protected-file token remains mandatory. Location outside the web root, private directory/file modes, immediate symlink refusal and available ownership checks are enforced. No environment token, hard-coded token or unauthenticated credential fallback was added. Token scope and host compromise are not attested. Live Actions metadata reads may need repository-scoped read permission; no permission or credential changes are made by this file, and denied reads fail closed.
- An optional private ETag cache only reuses a response after GitHub returns 304 for that request. A timeout, 403/404, rate limit, invalid response or unusable cache never becomes cached success. Responses remain bounded; redirects are refused and TLS verification stays enabled. No API URLs are accepted from query parameters or JSON link values.
- Public telemetry is read only from private `/home/learning/.blackcat/builder_status.json`, with restrictive permissions, a typed allowlist and a 15-minute observation TTL. Unknown/free-form fields are ignored; no mbstring dependency remains. Missing/invalid/future telemetry is UNKNOWN; old telemetry is visibly STALE. It never controls downloads. Generic strings and secret-key blacklists are not used as confidentiality guarantees.
- CSP keeps inline event handlers and non-nonce scripts blocked. A random per-response nonce permits only the built-in print listener. HTML is no-store, all data is escaped, and source version/digest are displayed for comparison with the delivery artifact. A page cannot independently attest its own source/host. Long hashes wrap on mobile.

## Runtime requirements and data contracts

PHP 8.1+ with cURL, trusted HTTPS CA configuration, and the existing readable protected token are required. mbstring is not required. The JSON shape is backwards-compatible with the existing 3.8 index, with the known duplicated M15 flag conflict shown as a warning/download block until the existing correction is published. New index revisions can explicitly declare `viewer_schema_version: blackcat-project-index-v1`.

An active candidate needs existing identity fields plus `artifact_sha256` (the ZIP digest, not the ISO digest). GitHub run/artifact APIs provide availability and source binding. An approved release additionally needs `release_id`, `asset_id`, `tag` and `authentication` with `status: VERIFIED`, `manifest_sha256` and `public_key_spki_sha256`; these fields record prior publisher verification, never perform it. Existing `candidate: superseded` and `release: none` remain closed. Unknown states remain visible as unknown and do not enable links.

Private telemetry contract example (replace the timestamp with a real observation, never manufacture a fresh timestamp for old data):

```json
{
  "schema": "blackcat-public-telemetry-v1",
  "observed_at": "REPLACE_WITH_ACTUAL_OBSERVATION_UTC",
  "runner_status": "unknown",
  "service_status": "unknown",
  "last_build_conclusion": "unknown"
}
```

Optional numeric fields: `disk_free_bytes` (nonnegative integer) and `last_build_run` (positive numeric ID). This schema intentionally does not accept generic messages, host details, tokens or arbitrary nested fields. Any publisher migration to this private file is separately observable; absent telemetry is not a failure of the project trust chain.

## Deployment and rollback

1. In the existing hosting File Manager, identify the actual `/USB/project.php` file and save its current contents to a nonpublic backup or local download. Keep the existing protected token in place; do not upload a token with the viewer.
2. Validate PHP 8.1+/cURL for that directory and existing token access. Upload the tested `project.php` as `/USB/project.php`. Keep the configured feed unchanged. If the replacement reports an error, use the nonpublic backup to restore the prior PHP; do not enable a stale download as a workaround.
3. Remove/move any old `/USB/project_cache/builder_status.json` from public storage, or explicitly deny web access to that directory. The supplied `web/project_cache/.htaccess` contains Apache 2.4 `Require all denied`, scoped to that cache directory. Upload it there only on Apache-compatible hosting and verify it is honored. Nginx/disabled overrides need the hosting provider's equivalent rule. PHP cannot protect an independent static URL, and the presence of .htaccess is not proof of enforcement.
4. Load `https://easyai.com.au/USB/project.php`. Compare displayed viewer version and source SHA-256 with the delivered artifact, feed/main blob observations and recorded candidate/release state. A data success badge is not a hosting-security audit. Validate response headers, not just body text.
5. Verify the legacy cache URL returns 403 or 404 without exposing a JSON body. Confirm missing/unsafe credentials and invalid data refuse, print works, and there is no browser CSP violation for the intended print script. Server-added CSP can be stricter; do not weaken it to hide a mismatch.
6. With the current project index, no test/approved ISO link should be enabled. The current index's repeated-field warning can be resolved through the already prepared JSON correction; never invent a candidate merely to obtain a green page. Physical handoff still needs Rule 0's actual live inspection and separate device authorization.

The existing host/network/TLS/ownership state and actual PHP installation cannot be proved from this file. Installation is authorized by the user, but execution requires the existing hosting connection/operator. No live success is claimed until steps 4–6 are observed.

## Validation

`tests/04-candidate-publication-contract.php` now launches `tests/06-project-viewer-check.py`, which executes the actual single-file production parser/renderer with synthetic protected files and mocked cURL responses. It no longer tests a separate duplicated gate. Twenty-two test groups include valid inputs, invalid/empty/schema/blob/size/URL/run/artifact/expiry/release/credential/cache/telemetry cases. All normal PHP cases run with `php -n`, establishing that mbstring is unnecessary. Transport controls assert TLS and no redirects.

`tests/07-project-viewer-browser.py` runs real Chromium against a temporary loopback PHP server. It checks the valid nonce print listener, rejects an inline event handler and non-nonce script, checks nonce changes/no-store headers and mobile width. The nonce is inspected through its IDL property because browsers hide its HTML attribute; CLI fixture diagnostics are stripped from the served document so they do not create a false layout failure. Servers are stopped after tests. Hosted source CI adds the same browser gate with pinned Playwright 1.62.0 and installed Chrome/Chromium.

Local tests are evidence for the tested package/runtime/source only. Apache override enforcement, actual hosting PHP/token scope, external live page identity and full host/runtime security remain UNKNOWN until observed. Original M15 and image acceptance identities remain unchanged. No build pipeline, signing key, firmware, TPM ownership, target disk or new public service was changed. Lessons and evidence precede final JSON maintenance.

## Observed hosted acceptance and exact delivery

Hosted run 42771083159/job 129678782976 passed at source 452ee2c15128d73cff5d797eb7f8c6276e3a3497, including the actual-renderer suite and the real-browser CSP/print negative step. This is separate from local package/runtime evidence. The delivered PHP SHA-256 is `c626ec161163ced601ba2db6b41ab5d8bf38652a663bd923cfa553c38789f7cc`; its viewer version is `2026-11-10.1`. The deployment ZIP contains only `project.php` and `project_cache/.htaccess`, both public application/configuration files with 0644 archive permissions, no credentials, and a fixed source-date timestamp. ZIP SHA-256: `1b38975a0c2ab4368d26cb2214d0bf9a4a27a58b7c4f70fca31d743fe98b086c`. See the report for all file digests. Hosting upload, actual server extension/permission state, legacy cache denial and live response remain UNKNOWN.
