# Hardened viewer acceptance evidence

Local source/renderer checks are VERIFIED within the stated scope. Live hosting/deployment is UNKNOWN. See report.json, static-and-renderer.log, browser-results.json and docs/26-viewer-hardening-and-deployment.md.

The actual single PHP file is exercised by tests/06-project-viewer-check.py via the compatibility tests/04 PHP entry point in tests/00. Twenty-two test groups include positive controls and required negative cases. Real Chromium tests the nonce print listener, rejects unauthorized inline handlers/scripts, checks fresh nonces/no-store and mobile width. Test credentials and API responses are synthetic. A temporary loopback-only server is stopped after use. Local PHP packages were extracted without root; exact provenance is retained with the earlier review evidence.

The source keeps the existing supplied deployed branch and protected token path. No PHP deployment, main merge, publication-ref movement, credential change, ISO promotion or platform action has occurred. .htaccess is only an Apache 2.4 deployment aid; actual legacy-cache denial remains UNKNOWN until checked on the host. The file checks metadata and reported evidence, not whole-host or target-machine security.
