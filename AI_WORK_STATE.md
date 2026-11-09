# Black Cat AI Work State

## Current project state
- Active objective: M15 signed UKI integration, ephemeral DEVELOPMENT ONLY keys. Explicitly authorized by operator on 2026-11-10.
- Development branch: codex/m15-signed-uki-development; based on completed runner recovery cd3966b805c7376d327154d332b6e217af41a452. No main merge.
- Runner execution/sudo checks passed in runs 42691361475 and 42691382043. Toolchain restore/probe run 42699169946 passed.
- Toolchain: Debian 13.7, systemd-ukify 257.13, sbsigntool 0.9.4, live-build 20250505.
- Implementation IN PROGRESS: mandatory binary hook constructs/signs exact live kernel+initrd and replaces development ESP fallback; post-ISO checks read actual filesystem/boot extents. No completed M15 build evidence yet. First run 42700951403 failed before keys due to hard-link alias handling. Corrected source also prevents objcopy from rewriting signed inputs; 19 local M15 tests passed. Retained-input diagnostic run 42704330214 then failed at ukify inspect argument ordering, before verification. Corrected inspector command passed two actual ukify 257.13 tests locally; run 42706245365 then caught a missing SBAT input header. Header generation is now tested with a real ukify build; retry diagnostic and fresh normal pipeline. Diagnostic output is preserved separately from clean acceptance evidence.
- No production keys, firmware enrollment, TPM ownership changes, physical device writes or UI features are authorized by this task.

## Current exact test candidate
The earlier graphical candidate is historical for this task: SHA-256 f498776780c543013e437161a878e22ca4fc40a66f47fc271d6fd6a964c29d75, 770703360 bytes, source ed5d80ac09c3f1650d67594242643d27efcf15e9, build 35179377345, artifact 10480220416. Availability after builder rebuild is UNKNOWN. Do not transfer its VM/physical observations to M15 artifacts. No approved Black Cat ISO exists.

## Next automatic actions
Run the normal build pipeline on the trusted dedicated-label runner; inspect positive/tamper/wrong-key/unsigned tests, embedded-input bindings and final ISO read-back. Fix bounded failures, preserve failure evidence and continue. Preserve public evidence locally and upload the UKI bundle. Update technical docs, canonical lessons and this checkpoint, then project_content.json LAST. JSON updates are routine authorized work without per-write approval.

## Next genuine human gate
Production signing/custody, real firmware enrollment, platform ownership changes, physical media writes/tests, main merges and release promotion remain gates. Ephemeral M15 development keys are explicitly authorized. A blocked tool is a capability/approval-review boundary, not a reason to broaden GitHub account OAuth. Broad CLI OAuth was cancelled and its helper is retired.

## Context health
AMBER heuristic after tool-heavy recovery and substantial selective reads; no exact context percentage is known. Context pressure itself is never a human-approval gate. Keep this checkpoint compact and load only needed source/evidence.

## Recovery sequence
Read this file, inspect latest M15 workflow runs on the active branch, then docs/17-signed-uki-development-plan.md and the relevant build/verifier scripts. Never equate local signature verification with firmware enforcement or authenticated root; dm-verity binding remains M17. Read only relevant lessons. The live viewer reads project-status-live, not this development branch; do not claim public synchronization without checking it.
