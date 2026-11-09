# Black Cat AI Work State

## Immediate objective
Restore the disposable ptl-builder-01 GitHub runner so unattended engineering can resume. Operator authorized rebuild and temporary passwordless sudo. The clean OS rebuild itself is not independently verified.

## Branch and recovery state
- Recovery branch: ops/runner-recovery-20261110
- Base: project-status-live at 973ce3e68c80aa1bea0c7776dcdcab5f6f34fefe; main is unchanged.
- Workflow/test source: 7479923b2eec4d9e7cc834fda018d50255dd5635.
- Five local routing checks passed; PR events, wrong refs and other repositories rejected.
- Operator supplied successful runner 2.338.2 archive checksum and version output.
- Runner registration, service startup, sudo grant and remote smoke are pending.
- Required name: ptl-builder-01-recovery.
- Required custom label: ptl-trusted-20261110, with --no-default-labels.
- Existing generic main PR workflows must not route to this root-capable runner.
- Recovery smoke runs 42691361475 and 42691382043 were queued/pending. Recheck before startup.

## Next action
Operator enters a fresh GitHub runner registration token directly in the builder console using the runbook command. Do not request or print tokens. Verify labels, then configure the authorized root-owned sudoers rule, validate it with visudo, install/start service as github-runner and inspect the recovery smoke. Existing available tools do not provide a shell on this droplet; console execution is a capability boundary.

## Trust boundary
Unrestricted passwordless sudo is root-equivalent. Repository writers can target custom labels; scheduling labels do not authenticate workflows. Do not put production keys or unrelated workloads here. Full dependency restoration, actual clean rebuild, image reproducibility and hostile-PR scheduler behavior remain UNKNOWN.

## Core priority and preserved history
Operator directive selects M15 signed UKI with development keys only after recovery. M13B/M14 status is recorded on project-status-live but main is older; avoid claiming branch convergence.
Historical graphical candidate: SHA-256 f498776780c543013e437161a878e22ca4fc40a66f47fc271d6fd6a964c29d75, 770703360 bytes, source ed5d80ac09c3f1650d67594242643d27efcf15e9, build 35179377345, artifact 10480220416. Its continuing availability after rebuild is UNKNOWN. Old VM/physical observations do not apply to a new build. No approved release exists; live-page identity verification remains pending.

## Human gates and context
Physical writes/testing, production keys, platform trust changes, main merges and release approval retain established gates. Document lessons and affected technical files, checkpoint before project_content.json, JSON last.
Context health: AMBER heuristic after substantial source/policy reads; no exact context percentage is known. On recovery read this checkpoint, then only the relevant runbook and live CI facts.
