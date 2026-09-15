# Modular Runtime and Untrusted AI Workshop

**Status:** Accepted design direction / future implementation after core trust chain

This document captures the proposed Black Cat software-module model and the external AI build-workshop architecture. It extends `docs/15-black-cat-ai-project-automation.md` without changing the current M14–M25 engineering priority.

## 1. Goal

Keep the trusted Black Cat core small, immutable and independently verifiable while allowing the user to select only the software and capabilities needed for a particular boot/session.

Non-essential applications should be pre-built as ready-to-run modules rather than permanently installed into the trusted core.

A future AI assistant may help users create new tools in natural language, but the AI must not receive authority to modify the trusted core or decide that its own output is trustworthy.

## 2. Three trust zones

Black Cat should separate three roles:

### Zone A — Trusted Black Cat core

The trusted core contains only the components required to establish and maintain the platform trust boundary, including boot, integrity verification, module verification/loading, capability enforcement, essential UI and evidence collection.

The core must not expose a general AI build authority. It must remain immutable under the same signed-boot/dm-verity direction already defined by the project.

### Zone B — Selected session modules

Pre-built application modules are stored outside the immutable core and are activated only when the user selects them for the current boot/session.

Examples may include:

- web browser;
- office/document tools;
- PDF tools;
- AI assistant;
- local AI runtime/models;
- media tools;
- developer tools;
- Git/Python;
- diagnostics;
- forensics/recovery tools;
- hardware capabilities such as camera, microphone or Bluetooth support.

An unselected module should not merely be hidden from menus; where practical, it should not be mounted into or exposed to the running session at all.

### Zone C — Untrusted AI build workshop

Natural-language software construction should preferably happen outside the trusted core in a separate disposable build environment, such as a dedicated VPS/VM or equivalent sandbox.

The workshop is treated as potentially compromised infrastructure. It may compile, package and test software, but it receives no Black Cat production signing keys, trusted-core write access, target-machine raw disk access, firmware authority or user secrets by default.

## 3. User-facing module selector

A future boot/session UI should present simple icon-and-checkbox choices and presets.

Conceptual example:

```text
BLACK CAT — Choose tools for this session

INTERNET
[ ] Web Browser
[ ] Wi-Fi / Internet
[ ] VPN

AI
[ ] Black Cat AI Assistant
[ ] Voice / Speech
[ ] Local AI Models

OFFICE
[ ] Document Editor
[ ] Spreadsheet
[ ] PDF Viewer

FILES
[ ] File Manager
[ ] External USB Storage
[ ] Archive Tools

DEVELOPMENT
[ ] Terminal
[ ] Git
[ ] Python
[ ] Developer Tools

SECURITY
[ ] Diagnostics
[ ] Forensics
[ ] Encryption Tools

HARDWARE
[ ] Bluetooth
[ ] Microphone
[ ] Webcam
[ ] Printer Support
```

Presets may include MINIMAL, WEB, AI, OFFICE, DEVELOPER, SECURITY and CUSTOM.

The exact GUI is future work. Security semantics matter more than presentation.

## 4. Pre-built module model

A Black Cat module should be fully built before activation. Boot/session startup should not perform general package installation or dependency solving.

Conceptually a module package may include:

```text
browser.bcmod
browser.manifest.json
browser.signature
```

The exact container/filesystem format remains an implementation decision. Candidate Linux mechanisms may include immutable filesystem images mounted read-only and layered into a session namespace.

Each module manifest should eventually bind at least:

- module ID/name;
- exact version;
- content digest;
- build/source provenance where available;
- compatibility with specific Black Cat core/API versions;
- required capabilities;
- optional capabilities;
- explicitly forbidden/high-risk capabilities;
- module trust class;
- signature/trust authority where applicable.

## 5. Capability model

Module selection should also select the minimum capabilities required by that module.

Example:

```text
Web Browser
  graphics: yes
  network: yes
  downloads-folder: yes
  microphone: no by default
  webcam: no by default
  raw block devices: no
  firmware: no
  root: no
```

The operating system, not the model or application, enforces these boundaries.

A module asking for additional authority does not grant itself that authority. Undeclared or high-impact capabilities fail closed or become an explicit human gate according to project policy.

## 6. AI-assisted tool creation

The user should be able to describe a tool in natural language, for example:

> Create a photo organiser that reads one selected folder and writes organised copies to another selected folder. It does not need the Internet.

The Black Cat AI interface may translate that request into a build specification, but actual software construction should occur in the untrusted workshop whenever practical.

Reference flow:

```text
User request
    -> Black Cat AI interface
    -> constrained build specification
    -> disposable external workshop
    -> source + build output + manifest + test evidence
    -> Black Cat independent verification
    -> capability summary shown to user
    -> optional session activation
```

## 7. The workshop is not trusted

A clean or disposable VPS is useful for containment, but it does not make generated software trustworthy.

Black Cat must assume anything returned by the workshop can be malicious or defective.

The workshop therefore must not hold the final authority that causes its own output to become an official trusted module.

At minimum, Black Cat should independently verify structural rules, exact digests, declared capabilities and applicable signatures before activation.

Future stronger review may include deterministic builds, static analysis, sandbox tests, policy checks and separate signing/review infrastructure.

## 8. Signing/trust classes

Official Black Cat modules and user-generated modules should not be presented as equivalent.

Possible trust classes:

- **CORE/OFFICIAL** — produced and signed under the Black Cat release/module process;
- **VERIFIED THIRD PARTY** — signed under an explicitly configured external trust policy;
- **LOCAL USER MODULE** — built for the user, not an official Black Cat component, restricted by capability policy;
- **UNVERIFIED CANDIDATE** — may be inspected/tested in a stronger sandbox but not loaded into a normal trusted session.

The external workshop must not possess the Black Cat production release or Secure Boot private keys merely for convenience.

## 9. Pre-desktop AI interface

A future lightweight AI interface may be available before the full desktop/session is assembled.

Important distinction: "before the full desktop" does not mean firmware-level or pre-kernel execution. The AI interface should run only after a minimal authenticated Black Cat environment has booted and the core policy/capability broker is active.

This preserves ADR-021's principle that boot/pre-OS choices are for consent/mode selection rather than arbitrary LLM execution.

The assistant can then help the user choose modules or formulate a workshop build request without granting the model trusted-core authority.

## 10. Security value

The modular approach can reduce attack surface because unused software and services are absent from the active session rather than merely unused.

Potential benefits include:

- fewer executable components exposed per session;
- fewer network-capable applications present when networking is not needed;
- smaller trusted core to audit;
- easier per-module updates/revocation;
- clearer capability declarations;
- easier separation of risky developer/recovery tooling from ordinary sessions;
- disposable session configurations that reset on reboot.

These are risk reductions, not guarantees. They do not solve compromised firmware/hardware, vulnerabilities in selected modules, malicious trusted signing authorities, or flaws in the capability broker itself.

## 11. Privacy/network boundary

A remote workshop or remote AI provider introduces privacy and network risks.

A future implementation must clearly distinguish:

- local-only module selection and deterministic operations;
- local-model reasoning;
- remote model requests;
- remote workshop builds.

The UI must not silently transmit user files/source/private prompts merely because a remote workshop is available.

Network destinations and credentials should be brokered narrowly and kept outside model prompts/logs wherever possible.

## 12. No new inbound service by default

This design does not authorize an Internet-facing listener on the Black Cat core or the existing project infrastructure.

Where remote build services are used, prefer outbound requests from Black Cat to an authenticated service and narrowly scoped return channels. The remote service itself must be separately hardened and must not become a path back into the trusted core.

## 13. Suggested future implementation phases

After the core M14–M25 roadmap is complete:

1. define `.bcmod` format and compatibility/version rules;
2. implement read-only module mounting in an isolated session namespace;
3. implement module manifest and capability schema;
4. implement icon/checkbox selector and safe presets;
5. enforce capability policy independently of module/application logic;
6. add local user-module trust class;
7. build a disposable workshop prototype using development-only credentials;
8. connect the Black Cat AI interface to the workshop through a constrained build-request schema;
9. add independent output verification and negative tests;
10. only then consider an official module-signing/promotion path.

## 14. Mandatory negative tests

Future implementation should prove at least that:

- an unselected module is not exposed in the normal session namespace;
- a modified module fails digest/signature validation;
- a module cannot grant itself undeclared network/root/raw-device access;
- malicious module metadata cannot change core policy;
- malicious source/README text cannot grant the AI new capabilities;
- a compromised workshop cannot write the Black Cat core;
- workshop credentials cannot access production signing keys;
- a locally built user module cannot masquerade as an official module;
- a module incompatible with the running core is refused;
- denied capability requests are visible in the audit trail.

## 15. Core principle

> **The trusted core verifies and enforces. Modules provide optional functionality. The AI may design/build, but neither the AI nor its workshop may grant themselves trust.**

This architecture should be treated as an extension of the existing Black Cat least-privilege and repository-governed AI direction, not as permission to expand the current Prototype 1 trusted computing base before the core trust-chain milestones are complete.
