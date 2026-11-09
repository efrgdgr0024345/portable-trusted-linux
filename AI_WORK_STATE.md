# Black Cat AI Work State

## Current objective and branch
Disposable builder runner recovery is technically complete relative to successful GitHub Actions smoke checks. Branch: ops/runner-recovery-20261110, based on project-status-live 973ce3e68c80aa1bea0c7776dcdcab5f6f34fefe. Main and the live status source have not been updated. Initial operator priority remains M15 development-key signed UKI after infrastructure recovery.

## Verified execution evidence
- Runs 42691361475 and 42691382043: success.
- Jobs 129411622420 and 129433909368: logs retrieved and inspected.
- Sources: ccc96dc2f171c4cd217937f13ad10bf23ee88220 and 7479923b2eec4d9e7cc834fda018d50255dd5635.
- Runner 2.338.2, ptl-builder-01-recovery; reported host ptl-builder-01.
- github-runner UID 1001, x86-64, Debian 13.7.
- Non-root account and sudo -n /usr/bin/id -u == 0 checks passed.
- Evidence: evidence/runner-recovery-20261110.json.
This is workflow/log evidence, not independent hardware attestation. Local Codex shell remains unprivileged; administrative execution is through this runner.

## Execution controls and assumptions
Only custom label ptl-trusted-20261110, no default labels, and approval for ALL outside collaborators were required operator confirmations in startup. Those settings were not independently read through an administration API. Custom labels are routing, not authorization against writers or modified PR workflows. Do not approve untrusted external jobs on this root-capable persistent host.
Passwordless unrestricted sudo is root-equivalent and explicitly authorized only for this disposable builder. Trusted jobs/dependencies and repository writers can compromise it. No production keys or unrelated workloads belong here. Stronger replacement: isolated per-job build environments and bounded administration.

## Authentication history
Operator cancelled broad GitHub CLI OAuth after seeing excessive account scopes. Do not rerun earlier helper links. Current ops/register-runner-gh.sh is retired and refuses execution without invoking gh. Registration succeeded using a repository-specific short-lived registration token. No general GitHub OAuth credential is needed for runner execution. Cancellation/cleanup/revocation were not independently verified.

## Routine JSON maintenance and next action
The operator explicitly clarified that JSON updates are standard authorized project work and require no per-update approval. This resolves the previously reported authorization issue about retaining existing public infrastructure/runbook references; retry through the same reviewed tool, never bypass a rejection. Secret disclosure, broad account OAuth, releases, main merges and other genuine gates remain separate.
Technical evidence, affected docs and canonical lessons are updated. Write project_content.json last to record successful runner execution plus remaining unknowns; verify the write result before calling this cycle complete.
Runner setup is ready for the next operator project task. Initial M15 development-key UKI priority remains context, but no M15 implementation, key generation or full toolchain restoration was performed in this recovery cycle. Safe dependency inventory can follow within the next task.

## Preserved candidate history and gates
Historical graphical candidate: SHA-256 f498776780c543013e437161a878e22ca4fc40a66f47fc271d6fd6a964c29d75; 770703360 bytes; source ed5d80ac09c3f1650d67594242643d27efcf15e9; build 35179377345; artifact 10480220416. Availability after rebuild UNKNOWN. Never transfer old image observations to a new build. M13B/M14 are recorded further along on project-status-live than on main. No approved ISO exists. Live-page identity reconciliation remains pending.
Human gates remain physical writes/testing, platform trust ownership, production keys, main merge and release approval. Broad GitHub OAuth is not authorized.

## Context health
AMBER heuristic after tool-heavy recovery and substantial history. No exact context percentage is known. Recover from this checkpoint and relevant evidence only; do not reload complete history. Record lessons and checkpoint before JSON-last.
