# Console-only automated runner registration

Use ops/register-runner-gh.sh to retrieve a short-lived runner registration token through GitHub CLI instead of copying it from a web page.

## Procedure and boundary
Install Debian's gh package, run the checksum-pinned helper in the authorized builder root console, and approve GitHub CLI device login in your browser. The account must have administration rights on the repository. The script refuses non-root execution, an unexpected hostname or an existing registration before authentication.

Authentication is held in a temporary root-only CLI configuration directory. The helper POSTs to the official repository runner-registration-token endpoint, captures the token without printing it and passes it using the runner's supported ACTIONS_RUNNER_INPUT_TOKEN input. Environment-input support was checked against actions/runner v2.338.2 CommandSettings.cs. The service remains stopped and sudo is not granted by this helper.

Local CLI configuration is removed on normal exit and handled errors; a crash can leave files behind. File cleanup does not revoke the GitHub OAuth application grant. Do not put credentials in chat, source, command history or logs.

## Source and verification
- Helper source: 274a712492781aa956ab64b6cb8c805cafd8ef8b
- Script SHA-256: ecd41ef1b76883674508f558b69c7a8cd320a41e7b629f3714090ae3e1de5048
- Shell syntax validation: passed locally.
- Negative non-root invocation: refused before authentication.
- Browser approval, API permission, actual registration and remote cleanup: UNKNOWN / PENDING.
- The prepared recovery workflow still requires dedicated labels without default labels and remote host/sudo checks before recovery is complete.

Manual token copying is not inherently safer than authenticated API retrieval. Scope, exposure and retention are the relevant controls.

An attempted append to the older access runbook was rejected by automatic approval review because that file contains existing public infrastructure and SSH key-location metadata. This separate guide records only the new authentication procedure and does not reproduce those details.
