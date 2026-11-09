# Disposable runner service startup

## Reported state
The operator supplied Connected to GitHub, Runner successfully added and Settings Saved output on 2026-11-10. This is OBSERVED registration evidence, not proof of online service or root execution.

## Required operator checks
In repository Settings / Actions / General, set Fork pull request workflows from outside collaborators to Require approval for all outside collaborators, then Save. Do not approve outside-contributor workflows on this privileged persistent runner unless their code is explicitly trusted and reviewed.

In Settings / Actions / Runners, confirm the recovery runner has only ptl-trusted-20261110, without self-hosted, Linux or X64 default labels. These confirmations are operator assertions; the script cannot authenticate settings through the available connector. A custom label is scheduling metadata, not authorization against repository writers or malicious modified PR workflows. The approval policy remains necessary.

Current queue inspection returned only recovery workflow push runs 42691361475 (queued) and 42691382043 (pending), both on the recovery branch. No in-progress or waiting runs were returned. Recheck if time or workflow state materially changes.

## Startup implementation
ops/start-recovery-runner.sh is intended only for the already-authorized disposable project builder. It requires both PTL_OUTSIDE_APPROVAL_CONFIRMED=yes and PTL_LABELS_CONFIRMED=yes after the checks above. It verifies root console, hostname, registered repository/name, no existing service and no existing policy file. It validates a root-owned sudoers file, grants github-runner unrestricted passwordless sudo, checks effective root through sudo, installs the GitHub service using github-runner, confirms its configured account and starts it.

This sudo grant is explicitly authorized and ROOT-EQUIVALENT. It is temporary progressive hardening, not a hardened production runner. Any trusted job/dependency compromise can take over the builder. Repository writers and approved external code are trusted by this arrangement. Do not retain production keys or unrelated workloads. Use isolated per-job builders and stronger workflow access restrictions as the next hardening step. A runner job-start hook was considered and rejected as an adequate isolation boundary.

- Pinned source: a201500b5b5da4fa561c2423a472dce8808a3e6b
- Script SHA-256: 4de1bda46395ceef3d752c9b7545bc435f889b6f53ab6d35c45b138597e3893c
- Bash syntax: passed locally.
- Five local confirmation-gate tests: passed, including missing/negative approvals or labels.
- Actual GitHub approval setting, registered labels, sudo configuration and service execution: UNKNOWN until operator output or independent CI evidence.

The local gate tests validate refusal/acceptance of explicit confirmations, not the truth of those confirmations, GitHub scheduler security or the absence of hostile code.

After startup inspect PTL Runner Recovery logs independently. The marker RUNNER_RECOVERY=VERIFIED_FOR_THIS_RUN is scoped only to that run's identity and sudo check; it does not establish full build readiness, M15 completion or release approval.

No general GitHub OAuth login or account token is needed on the builder. If any pre-existing service or sudoers policy is found, inspect it rather than overwriting or rerunning blindly. On a later failure a validated sudo policy may already be installed; do not assume rollback occurred.
