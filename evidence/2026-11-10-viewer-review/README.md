# Viewer review evidence

See [the review](../../docs/25-public-viewer-review.md) and report.json. Results describe source at main 6bf3711820cf6fe55c1dbef86a34a586bd49243d and the operator-supplied PHP; they do not establish the live server's deployed bytes or configuration. Original diagnostic output is preserved before the proposed metadata correction. No real credentials are used or stored.

- render-results.json: actual PHP parser/renderer with synthetic local token and mocked HTTP responses; both supplied and canonical source.
- browser-results.json: actual headers and Chromium CSP refusal, with false-initial/direct-call controls; loopback server stopped after use.
- package-provenance.json: exact Debian package versions/hashes; packages extracted locally without root. Signed metadata and package hashes checked.
- reproduce.py / browser-reproduce.py: diagnostic helpers, not acceptance gates; output zero is not evidence of security readiness. They preserve the viewer logic and replace only the credential path/network transport with synthetic fixtures.

Example on a development machine with PHP CLI and mbstring available (set PHP_BIN and MBSTRING_SO to its real paths; ordinary installed dependencies need no --library-dir):

```bash
git show 6bf3711820cf6fe55c1dbef86a34a586bd49243d:project_content.json > /tmp/blackcat-review-index.json
python3 evidence/2026-11-10-viewer-review/reproduce.py \
  --php "$PHP_BIN" --mbstring "$MBSTRING_SO" \
  --canonical web/project.php --index /tmp/blackcat-review-index.json \
  --uploaded /path/to/operator-supplied-project.php \
  --output /tmp/blackcat-render-results.json
```

The helper prints its temporary synthetic fixture directory. With Python Playwright and Chromium, pass that directory to browser-reproduce.py using --fixtures, along with --php, --mbstring and --output. It binds only loopback and stops the server after the test. The optional --library-dir supports locally extracted packages. Supplied PHP is not republished in this evidence directory; its full hash identifies it.

Current main, project-status-live and codex/remove-chroot-systemd-boot JSON blobs matched at the review baseline. The proposed branch changes one stale M15 merged flag and appends review status; it does not deploy PHP, move publication refs or enable downloads. Original M15 and combined-main build identities remain unchanged.
