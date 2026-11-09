# Builder Access & Recovery Runbook

## Purpose

This runbook records how to regain administrative access to the Portable Trusted Linux remote builder without storing any private credential in GitHub.

It exists because the project temporarily lost the exact SSH connection recipe even though the correct key pair still existed. The goal is for future recovery to take one documented command rather than reconstructing the setup from memory.

## Current builder identity

- DigitalOcean Droplet name: `ptl-builder-01`
- DigitalOcean Droplet ID: `599550415`
- Public IPv4: `170.64.224.131`
- Region: `syd1`
- OS baseline: Debian 13 / trixie amd64
- GitHub Actions service account: `github-runner`
- Runner directory: `/home/github-runner/actions-runner`

## SSH credential mapping

The administrative SSH key is intentionally split across systems:

- DigitalOcean stores the **public key only**.
- cPanel host `learning@s03fe` stores the matching **private key**.
- GitHub stores only this runbook, key metadata, file locations and verification instructions.

Current DigitalOcean key metadata:

- DigitalOcean key name: `1stkey`
- Public-key comment: `portable-trusted-linux-digitalocean`
- MD5 fingerprint: `eb:d0:eb:9f:e1:d9:74:97:03:d2:47:eb:df:fe:e0:25`

Current cPanel key locations:

- Private key: `~/.ssh/ptl_digitalocean`
- Public key: `~/.ssh/ptl_digitalocean.pub`

**Never commit, paste, upload or otherwise copy the contents of `~/.ssh/ptl_digitalocean` into this repository, an issue, a log, a chat transcript or a build artifact.**

The `.pub` file and its fingerprint are not secret, but the private file is.

## Known-working administrative connection

From the cPanel shell (`learning@s03fe`):

```bash
chmod 700 ~/.ssh
chmod 600 ~/.ssh/ptl_digitalocean
chmod 644 ~/.ssh/ptl_digitalocean.pub
ssh -i ~/.ssh/ptl_digitalocean root@170.64.224.131
```

A successful login should reach the host named `ptl-builder-01`.

## Recommended cPanel SSH alias

To avoid having to remember the IP address, username or key path, add this entry to `~/.ssh/config` on the cPanel host:

```sshconfig
Host ptl-builder
  HostName 170.64.224.131
  User root
  IdentityFile ~/.ssh/ptl_digitalocean
  IdentitiesOnly yes
```

Then protect the config:

```bash
chmod 600 ~/.ssh/config
```

Future administrative login becomes:

```bash
ssh ptl-builder
```

The GitHub Actions runner remains a separate unprivileged execution path under the `github-runner` account. Root SSH access is an administrative/bootstrap mechanism and must not be confused with the normal CI runner privilege model.

## Verify the key before troubleshooting SSH

If login unexpectedly fails, first confirm that the cPanel private key still matches its public half:

```bash
ssh-keygen -y -f ~/.ssh/ptl_digitalocean
```

The derived public key must correspond to `~/.ssh/ptl_digitalocean.pub` and to the DigitalOcean key whose comment is `portable-trusted-linux-digitalocean`.

For an exact fingerprint comparison using the same MD5-style format recorded by DigitalOcean:

```bash
ssh-keygen -E md5 -lf ~/.ssh/ptl_digitalocean.pub
```

Expected fingerprint:

```text
MD5:eb:d0:eb:9f:e1:d9:74:97:03:d2:47:eb:df:fe:e0:25
```

## Recovery checklist for `Permission denied (publickey)`

1. Confirm the target is `170.64.224.131` and the intended host is `ptl-builder-01`.
2. Confirm `~/.ssh/ptl_digitalocean` exists on the cPanel host.
3. Confirm its mode is `600`.
4. Run `ssh-keygen -y -f ~/.ssh/ptl_digitalocean` to prove the private key is readable.
5. Run `ssh-keygen -E md5 -lf ~/.ssh/ptl_digitalocean.pub` and compare the fingerprint with this runbook.
6. Use `ssh -i ~/.ssh/ptl_digitalocean root@170.64.224.131` rather than relying on SSH to guess the key.
7. If needed, use verbose diagnostics without exposing the private-key contents:

```bash
ssh -vvv -i ~/.ssh/ptl_digitalocean root@170.64.224.131
```

Do not paste a private key into troubleshooting output. SSH verbose logs are normally safe from private-key disclosure, but review them before publishing because they can reveal hostnames, usernames and paths.

## If the private key is actually lost

The private key cannot be recovered from DigitalOcean or GitHub. A public key is not reversible into its private key.

If `~/.ssh/ptl_digitalocean` is lost:

1. Generate a new key pair on a trusted administration host.
2. Regain console/recovery access to the Droplet using an approved DigitalOcean recovery mechanism or another already-authorized administrator key.
3. Add the new public key to the intended account's `~/.ssh/authorized_keys`.
4. Verify access before removing the old public key.
5. Update this runbook with the new fingerprint and key location.
6. Record the credential rotation in `docs/07-lessons-learned.md`.

Creating a new key in the DigitalOcean account does **not** by itself guarantee that an already-existing Droplet will accept it; the public key must be present in the target account's `authorized_keys` or otherwise installed through an approved recovery path.

## Security boundary

This is a Prototype 1 bootstrap arrangement, not the final credential architecture.

Current trust assumption: the cPanel account securely holds the builder administrative private key.

Current compromise path: compromise of that cPanel account could expose a credential with administrative access to the disposable builder.

Temporary justification: it provides a simple, recoverable administration path while the core build and attestation workflow is being proven.

Planned stronger replacement: use a dedicated non-root administrative account, narrowly scoped privilege escalation, stronger credential custody such as hardware-backed or short-lived SSH authentication, and removal or restriction of direct root SSH once the bootstrap phase no longer requires it.

## GitHub recording rule for credentials

Safe to record in the private project repository:

- host names and IP addresses;
- public SSH keys;
- public-key fingerprints;
- key comments and DigitalOcean key IDs/names;
- the filesystem **path** where a private key is expected to exist;
- connection commands that reference a private-key path;
- account names and recovery procedures.

Never record in GitHub:

- private SSH-key contents;
- passphrases;
- API tokens;
- GitHub personal access tokens;
- DigitalOcean API tokens;
- recovery codes or passwords.

If a secret is accidentally committed, treat it as compromised, rotate it, and do not rely on deleting the Git history as the only remediation.


## 2026-11-10 — Disposable builder runner recovery (in progress)

### Evidence and scope

The operator supplied a root-console transcript identifying ptl-builder-01, and later supplied successful package setup, runner archive checksum verification and Runner.Listener version 2.338.2. This is **OBSERVED operator-supplied evidence**, not independent proof of a clean rebuild or a working CI connection. The OS rebuild, absence of other workloads, registration, service, administrative execution and full PTL toolchain remain **UNKNOWN** until checked.

Recovery development branch: `ops/runner-recovery-20261110`, based on project-status-live commit `973ce3e68c80aa1bea0c7776dcdcab5f6f34fefe`. Main and the published status branch are not changed by this recovery preparation.

### Reconstruct the installation

On the disposable Debian 13 amd64 builder, as root:

1. Refuse to overwrite an existing `/home/github-runner/actions-runner/.runner`; investigate its registration first.
2. Run `apt-get update`, then install `ca-certificates curl git jq sudo tar gzip libicu76 libssl3t64 libkrb5-3 zlib1g`.
3. Create github-runner with `useradd --create-home --shell /bin/bash github-runner` if absent.
4. Create its runner directory with owner/group github-runner and mode 0755.
5. As github-runner, download the official HTTPS archive:
   https://github.com/actions/runner/releases/download/v2.338.2/actions-runner-linux-x64-2.338.2.tar.gz
6. Require SHA-256 `baebf204307bac6193480852732f0aba2ae97b6c5597c244f4d5558390147c40` before extracting. This value was retrieved from the official GitHub release API and release notes, not an independent signing authority.
7. Extract as github-runner and require `./bin/Runner.Listener --version` to report 2.338.2.

Do not reconstruct credentials from GitHub. Obtain a short-lived registration token through the repository's Settings / Actions / Runners / New self-hosted runner interface and enter it only in the builder's interactive console. Do not put the token in chat, Git, shell command history or workflow logs.

### Registration isolation

Register interactively, without starting a listener:

```bash
runuser -l github-runner -c 'cd /home/github-runner/actions-runner && ./config.sh --url https://github.com/efrgdgr0024345/portable-trusted-linux --name ptl-builder-01-recovery --no-default-labels --labels ptl-trusted-20261110 --work _work'
```

Use the console-only token at its prompt. Keep the default runner group and the specified work directory when prompted. If an existing runner name conflicts, investigate rather than silently replacing it.

The restored runner must have custom label `ptl-trusted-20261110` and **no default self-hosted/Linux/X64 labels**. Current main workflows execute pull-request code on the generic labels; restoring those labels together with sudo would expose the privileged builder to that code. Do not start `run.sh` or the service before verifying registration labels.

The recovery workflow is restricted to push/workflow_dispatch on the exact recovery branch and repository. It does not check out PR code. Its JSON serialization is valid YAML and permits standard-library Python regression checks. Run `python3 tests/test_runner_recovery_policy.py` from the repository checkout. Five local tests passed during preparation, including negative event/ref/repository cases. These are configuration regression checks, not an end-to-end hostile-PR execution test or proof of GitHub scheduler isolation.

### Administrative-access boundary

The operator explicitly authorized passwordless sudo on this disposable builder for unattended development. It is **root-equivalent**, even while the listener runs as github-runner. This is a temporary control, not a hardened production builder.

Before enabling it and starting the service: verify dedicated label routing, review pending jobs, validate any root-owned sudoers file with visudo, and confirm the service uses github-runner. The smoke workflow then checks exact hostname, non-root account, x86-64 and `sudo -n /usr/bin/id -u` returning zero. No sudoers rule or service startup has yet been confirmed.

Trust assumption: repository writers, GitHub Actions, runner distribution and build inputs are trusted for this disposable environment. A repository writer can add another workflow targeting the custom label; labels are scheduling controls, not an authorization boundary against repository writers. Compromise of a trusted job/dependency can obtain root. Do not keep production signing material or unrelated workloads here. The stronger replacement is isolated per-job builders with bounded administration and verified trusted-workflow controls; delete this transitional host when no longer needed.

### Pending verification

Recovery workflow source commit: 7479923b2eec4d9e7cc834fda018d50255dd5635.
Runs 42691361475 and 42691382043 were queued/pending before registration. They run the same bounded host/sudo checks and do not build or write devices. Recheck queue state immediately before service startup.

Registration, online status, sudo smoke evidence, full PTL dependencies and negative routing behavior on GitHub remain pending. Historical ISO bytes and development private keys must not be assumed recoverable from source. If a rebuild destroyed an unarchived artifact, record that loss and produce a newly identified candidate; never transfer old-image evidence to a new build.
