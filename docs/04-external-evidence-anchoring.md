# External Evidence Anchoring

## Purpose

Portable Trusted Linux does not only need to verify a machine at the time of inspection. It also needs a way to make later rewriting of historical evidence detectable.

The proposed solution is called **External Evidence Anchoring**.

## Core idea

For each attestation report:

1. produce a deterministic/canonical representation of the report;
2. calculate a cryptographic hash such as SHA-256;
3. digitally sign the report or commitment;
4. include the previous report hash where local chaining is enabled;
5. store the full report privately;
6. publish only a cryptographic commitment to an external witness or transparency system.

## Why a local hash chain is useful

```text
Report 1
hash = AAAA

Report 2
previous = AAAA
hash = BBBB

Report 3
previous = BBBB
hash = CCCC
```

If Report 1 is modified later, its hash changes and the linkage from Report 2 no longer agrees.

A local hash chain therefore makes casual retrospective editing detectable.

## Why local-only history is not enough

An attacker with complete control over local storage could theoretically rewrite every report and regenerate all local links.

An external commitment creates an independent historical witness. Once a previously published commitment exists outside the USB, a rewritten local history can be compared against that commitment.

## Transparency log

A cryptographic transparency log is a strong primary fit for the project because its purpose is an append-only, independently auditable history of cryptographic evidence.

The desired properties are:

- append-only behaviour;
- inclusion proofs;
- consistency proofs or equivalent history-integrity guarantees;
- multiple independent auditors or mirrors where practical;
- no dependency on the log for trusted boot itself.

## Blockchain

A public blockchain can optionally be used as an additional commitment/timestamp layer.

Its useful claim is narrow:

> A particular cryptographic commitment was recorded in the public ledger by a particular point in its history.

It does **not** prove that statements inside the original attestation report were true.

## Privacy rule

The project should avoid putting the full attestation report on a public immutable ledger.

Potentially sensitive fields may include:

- machine identifiers;
- firmware versions;
- TPM metadata;
- PCR values;
- event-log details;
- timestamps;
- network/device information.

The normal public object should therefore be a hash or other cryptographic commitment, not the private report itself.

## Offline-first workflow

```text
Boot offline
    ↓
Verify portable OS
    ↓
Collect TPM / boot evidence
    ↓
Create attestation report
    ↓
Canonicalise and hash
    ↓
Sign and store pending anchor
    ↓
Later enable approved networking
    ↓
Publish commitment
    ↓
Store returned anchor receipt
```

External publication must not become part of the trusted boot dependency chain.

## Backend abstraction

The core report format should not depend on a single service or cryptocurrency.

```text
External Evidence Anchoring
    ├── transparency log
    ├── public blockchain
    ├── independent organisational witness
    └── future compatible backend
```

The report can store one or more anchor receipts with backend identifier, commitment, time/evidence and verification metadata.

## Security statement

External anchoring protects **history integrity**. TPM and system verification protect **measurement/evidence quality**. Neither substitutes for the other.
