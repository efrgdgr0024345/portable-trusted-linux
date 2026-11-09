# Black Cat AI Work State

## Immediate objective
Restore the disposable ptl-builder-01 GitHub runner so unattended engineering can resume. Operator authorized rebuild and temporary passwordless sudo. The clean OS rebuild itself is not independently verified.

## Branch and recovery state
- Recovery branch: ops/runner-recovery-20261110
- Base: project-status-live at 973ce3e68c80aa1bea0c7776dcdcab5f6f34fefe; main is unchanged.
- Workflow/test source: 7479923b2eec4d9e7cc834fda018d50255dd5635.
- Five local routing checks passed; PR events, wrong refs and other repositories rejected.
- Operator supplied successful runner 2.338.2 archive checksum and version output.
- Operator supplied successful registration and Settings Saved output. Service startup, sudo grant and remote smoke remain pending.
- Required name: ptl-builder-01-recovery.
- Required custom label: ptl-trusted-20261110, with --no-default-labels.
- Existing generic main PR workflows must not route to this root-capable runner.
- Recovery smoke runs 42691361475 and 42691382043 were queued/pending. Recheck before startup.

## Next action
Registration succeeded according to the operator's console transcript. Before starting the root-capable service, operator must set GitHub Actions approval for ALL outside collaborators and verify custom-only label ptl-trusted-20261110 with no default labels.
Then run checksum-pinned ops/start-recovery-runner.sh (source a201500b5b5da4fa561c2423a472dce8808a3e6b; SHA-256 4de1bda46395ceef3d752c9b7545bc435f889b6f53ab6d35c45b138597e3893c) with PTL_OUTSIDE_APPROVAL_CONFIRMED=yes and PTL_LABELS_CONFIRMED=yes after those checks. Script syntax and five local confirmation-gate tests passed. These tests do not verify actual GitHub settings. Root-equivalent sudo is already explicitly authorized on this disposable host; no broad GitHub OAuth grant is authorized.
Inspect the independent recovery workflow logs after service startup. Console execution remains a capability boundary. Do not rerun old OAuth helpers; current helper is retired.

## Historical CLI compatibility fix — approach now retired
The first helper failed before authentication: Debian gh rejected --skip-ssh-key. Removed that unnecessary option. Current script SHA-256: feda754b526a500a4cee7068f475934c87873fafc7c28d3412aed26c24f4909b. Bash syntax and two actual-parser tests passed on local Debian gh 2.46.0-3 (positive accepted flags, negative invalid flag). Target's exact CLI version is unknown. Operator must retry the corrected pinned helper; no device login/registration success is yet reported. Earlier source/checksum remain historical only.
Final JSON update was rejected by automatic approval review for retaining existing public infrastructure/runbook references; separate approval is pending. Lessons append is authorized and records this failure. Do not call this documentation cycle complete until JSON-last follow-through succeeds.

## Latest authorization lesson
The CLI device screen requested broad repository/workflow/gist/organization authority. Temporary local credentials and a short-lived output token did not constrain that upstream OAuth grant. The operator rejected it and reported cancellation. No authorization, registration or remote sudo success is reported. Repository registration-token scope and runner execution privileges remain separate boundaries. JSON remains stale and blocked pending the previously requested separate approval; do not describe this cycle or public synchronization as complete.

## Trust boundary
Unrestricted passwordless sudo is root-equivalent. Repository writers can target custom labels; scheduling labels do not authenticate workflows. Do not put production keys or unrelated workloads here. Full dependency restoration, actual clean rebuild, image reproducibility and hostile-PR scheduler behavior remain UNKNOWN.

## Core priority and preserved history
Operator directive selects M15 signed UKI with development keys only after recovery. M13B/M14 status is recorded on project-status-live but main is older; avoid claiming branch convergence.
Historical graphical candidate: SHA-256 f498776780c543013e437161a878e22ca4fc40a66f47fc271d6fd6a964c29d75, 770703360 bytes, source ed5d80ac09c3f1650d67594242643d27efcf15e9, build 35179377345, artifact 10480220416. Its continuing availability after rebuild is UNKNOWN. Old VM/physical observations do not apply to a new build. No approved release exists; live-page identity verification remains pending.

## Documentation follow-through
Canonical token-automation lesson appended with explicit operator approval at 48beb13290491a9c0ff5b992b13a3e4e79160a3b. The older access-runbook append was rejected by automatic approval review and remains unapplied; the separate CLI guide provides the new procedure without reproducing its access metadata. No authentication code/token was received in chat. Await the operator's console result; do not claim recovery complete.

## Human gates and context
Physical writes/testing, production keys, platform trust changes, main merges and release approval retain established gates. Document lessons and affected technical files, checkpoint before project_content.json, JSON last.
Context health: AMBER heuristic after substantial source/policy reads; no exact context percentage is known. On recovery read this checkpoint, then only the relevant runbook and live CI facts.
