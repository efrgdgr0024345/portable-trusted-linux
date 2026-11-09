# Current recovery procedure — repository-scoped token only

**The GitHub CLI OAuth procedure below is superseded and must not be followed.** The operator cancelled the device authorization after seeing broad repository, workflow, gist and organization scopes. Temporary local storage and a short-lived output token did not make the upstream OAuth grant repository-scoped.

The current ops/register-runner-gh.sh refuses execution without invoking GitHub CLI. Two local tests verified refusal and that a fake gh executable was never invoked. Older pinned commits remain available for history; do not rerun those earlier script links.

## Register without a general GitHub login
Open https://github.com/efrgdgr0024345/portable-trusted-linux/settings/actions/runners/new?arch=x64&os=linux and obtain a repository runner registration token. Enter only that token at the runner's interactive console prompt; never send it to chat. The registration token expires after one hour and permits runner registration for this repository; it is not a general repository-content or account OAuth credential.

In the already-authorized builder root console, run:

```bash
runuser -l github-runner -c 'cd /home/github-runner/actions-runner && ./config.sh --url https://github.com/efrgdgr0024345/portable-trusted-linux --name ptl-builder-01-recovery --no-default-labels --labels ptl-trusted-20261110 --work _work'
```

Enter the token at its prompt, accept the default runner group if asked, and stop after registration. Do not start run.sh or the service yet. Registration does not itself prove the runner is online or sudo is operational. The registered listener will later receive separate execution credentials; the registration token's limited scope does not make a root-capable runner safe against untrusted jobs.

The operator reported cancellation; authorization revocation and local cleanup were not independently verified. If authorization was actually approved, review the GitHub CLI grant in GitHub's authorized OAuth application settings; deleting local files alone does not revoke it.

---

# Historical procedure — superseded, not an instruction

# Console-only automated runner registration

Use ops/register-runner-gh.sh to retrieve a short-lived runner registration token through GitHub CLI instead of copying it from a web page.

## Procedure and boundary
Install Debian's gh package, run the checksum-pinned helper in the authorized builder root console, and approve GitHub CLI device login in your browser. The account must have administration rights on the repository. The script refuses non-root execution, an unexpected hostname or an existing registration before authentication.

Authentication is held in a temporary root-only CLI configuration directory. The helper POSTs to the official repository runner-registration-token endpoint, captures the token without printing it and passes it using the runner's supported ACTIONS_RUNNER_INPUT_TOKEN input. Environment-input support was checked against actions/runner v2.338.2 CommandSettings.cs. The service remains stopped and sudo is not granted by this helper.

Local CLI configuration is removed on normal exit and handled errors; a crash can leave files behind. File cleanup does not revoke the GitHub OAuth application grant. Do not put credentials in chat, source, command history or logs.

## Source and verification
- Current helper source: ff6c9707dcd51ee14b1ca3473a5f27a39631c512
- Script SHA-256: feda754b526a500a4cee7068f475934c87873fafc7c28d3412aed26c24f4909b
- Shell syntax validation: passed locally.
- Negative non-root invocation: refused before authentication.
- Browser approval, API permission, actual registration and remote cleanup: UNKNOWN / PENDING.
- The prepared recovery workflow still requires dedicated labels without default labels and remote host/sudo checks before recovery is complete.

Manual token copying is not inherently safer than authenticated API retrieval. Scope, exposure and retention are the relevant controls.

An attempted append to the older access runbook was rejected by automatic approval review because that file contains existing public infrastructure and SSH key-location metadata. This separate guide records only the new authentication procedure and does not reproduce those details.


## 2026-11-10 — Debian CLI flag compatibility fix

The original helper at 274a712492781aa956ab64b6cb8c805cafd8ef8b failed on the operator's builder with unknown flag --skip-ssh-key. That flag was unnecessary because the helper selects HTTPS. It was removed; all authentication, dedicated labels, cleanup and stopped-service boundaries remain as designed.

The corrected login flags were exercised using the actual Debian gh 2.46.0-3 parser with --help, without authentication or network use. tests/test_runner_cli_compatibility.py contains a positive parser test and a negative unsupported-flag test; both passed locally. Bash syntax also passed. These checks do not prove device login, API permissions, registration or remote cleanup. The target's exact gh version was not reported, although its displayed flag list supports the corrected options.

Use the current pinned source and checksum above. The earlier checksum identified the failed helper and must not be used for the corrected file.
