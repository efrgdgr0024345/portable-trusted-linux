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
