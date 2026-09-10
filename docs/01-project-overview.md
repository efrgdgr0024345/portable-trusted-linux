# Portable Trusted Linux — Project Overview

## The problem

A normal security scan usually runs inside the operating system already installed on a computer. If that operating system is deeply compromised, asking it whether the machine is trustworthy creates a circular trust problem.

Portable Trusted Linux is designed to reduce that dependence by booting an independently verified Linux environment from USB, without first starting the installed Windows or Linux system.

## The goal

The project aims to:

- authenticate the portable operating-system image;
- verify the USB storage against an authenticated reference;
- use UEFI Secure Boot where practical;
- collect TPM 2.0 measured-boot evidence and the TCG event log;
- use a signed Unified Kernel Image (UKI);
- protect the Linux root filesystem with dm-verity;
- keep normal writable state temporary in RAM;
- generate a structured attestation report;
- verify that report independently; and
- preserve report history through cryptographic hashing and external evidence anchoring.

## What the project will not claim

The system must never reduce all security evidence to an unsupported statement such as “this computer is 100% safe.”

A correct SHA-256 proves data identity relative to a reference, not that the reference itself is benevolent. Secure Boot proves that a configured signature policy was satisfied, not that every authorised component is vulnerability-free. A TPM Quote protects evidence about selected PCR measurements, not every component in the machine. RAM-backed operation reduces persistence, but does not defeat every physical, firmware or live-session attack.

The intended user-visible states are therefore things such as:

- VERIFIED
- WARNING
- UNKNOWN
- NOT VERIFIABLE

## Security levels

The working model uses progressive assurance levels:

- **Level 0 — Unverified:** meaningful cryptographic assurance was not established.
- **Level 1 — USB verified:** the portable image matches an authenticated reference.
- **Level 2 — OS verified:** boot components and immutable Linux root are verified.
- **Level 3 — Measured boot:** TPM measurements and event-log evidence are available.
- **Level 4 — Attested:** TPM evidence is independently verified against appropriate reference data.
- **Level 5 — Hardware-rooted:** stronger assurance is available on supported hardware with tighter firmware/root-of-trust control.

## Current project stage

The project is currently in architecture and pre-prototype work. The next major engineering deliverable is a Prototype Build Specification that turns the design into exact build choices, tests and pass/fail gates.
