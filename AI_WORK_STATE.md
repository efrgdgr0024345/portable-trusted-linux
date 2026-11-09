# Black Cat AI Work State

## Current project state
- M15 requested DEVELOPMENT ONLY acceptance is VERIFIED at implementation source 3125a1c99342b961ac614878d3ce9b3f874de33e.
- Fresh normal run 42711645617, job 129482434136: success, including 28 M15 regressions, 12 runner regressions, actual signing/rejection checks, final ISO read-back and artifact upload.
- Branch: codex/m15-signed-uki-development; based on runner recovery cd3966b805c7376d327154d332b6e217af41a452. Not merged to main. No other milestone started.
- Durable public evidence: evidence/2026-11-10-m15/report.json plus raw public results, key/certificate and 22 exact command records.
- No production key, firmware enrollment, TPM ownership/PCR-policy action or target-device write was introduced. The disposable builder's authorized unrestricted sudo remains root-equivalent, not a production trust boundary.

## Current exact test candidate
M15 artifacts are verification evidence, NOT an operator physical-test handoff or approved release. UKI: 67962376 bytes, SHA-256 e42219c658b6cfe285c02f1c667ce505768dcf32bfd7c78fdf10570e27cfddeb. Certificate DER SHA-256 af0794b4e3d1e1830f3610cf4532ed362df639864225ab45d654e4b0c9949193. ISO: 990097408 bytes, SHA-256 bd94942c1b1abc67b1e3148822cb64e0c38001a3af4a4e50ac07213d1832cbe8. Live kernel 6.12.116+deb13-amd64.
Artifact 13716513159: blackcat-m15-development-evidence-42711645617; inspect m15/, not prior-image m15-iso-diagnostic/. Expires 2026-11-16T22:38:36Z (17 Nov 06:38 AWST). Builder recovery copy: /home/github-runner/blackcat-builds/m15/run-42711645617-attempt-1/. Archive binary evidence before expiry/deleting that host; fresh keys cannot recreate these signed bytes from source.
Old graphical identity f498776780c543013e437161a878e22ca4fc40a66f47fc271d6fd6a964c29d75 remains historical; its run 35179377345 returns no GitHub artifacts, and builder-copy availability is UNKNOWN. Remove stale physical-test readiness without erasing old observations. No approved Black Cat ISO exists.

## Next automatic actions
Finalize affected docs, canonical lessons and public evidence, then project_content.json LAST. Routine JSON updates and retention of existing public project references are authorized without per-write approval. Target index version 3.7 must identify run 42711645617 and the exact implementation source. On recovery verify actual index state on both this branch and project-status-live before reporting synchronization; complete that standard update if pending. Do not claim the deployed page was checked: cloud proxy CONNECT 403 blocked its inspection.
After this closure, await the operator's next concrete project task or merge review. No M16/M17 implementation is authorized by this M15-only cycle.

## Next genuine human gate
Main merge, production authority/key custody, platform firmware enrollment/ownership, physical media actions and release promotion remain HUMAN_GATE. Ephemeral M15 keys were explicitly authorized and unlinked from tmpfs before success; secure RAM erasure is NOT VERIFIED. No broad GitHub CLI OAuth grant is needed; that cancelled helper remains retired.

## Context health
AMBER operational estimate after extended tool-heavy integration, large logs and a brief workspace restart. State was recovered selectively; no precise context percentage or loss measurement is known. Context pressure itself is never a human-approval gate. Keep this checkpoint compact.

## Recovery sequence
Read this checkpoint and evidence/2026-11-10-m15/report.json; verify only volatile GitHub/index facts. Review docs/17 and the relevant build/verifier source if needed. Private keys are not retained. Raw provenance's github_run_id remains unknown because sudo filtered it; outer run/job/source identity is bound by GitHub metadata, not silently patched into raw evidence. Firmware enforcement, physical boot, root-squashfs authentication, legacy BIOS security and bit-for-bit reproducibility are NOT VERIFIED. Never transfer old image observations or promote the ISO from a successful development signature check.
