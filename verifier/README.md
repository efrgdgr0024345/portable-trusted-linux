# Independent Verifier

The verifier is intentionally separate from the attester/runtime UI.

## Prototype responsibilities

The first verifier will:

1. receive a verifier-generated nonce/challenge;
2. validate the attestation payload digest;
3. validate report/envelope signatures where configured;
4. validate the TPM Quote signature;
5. validate exact nonce binding;
6. validate the requested PCR selection;
7. evaluate PCR/event-log consistency where supported;
8. evaluate known reference policy without assuming arbitrary hosts share identical PCRs;
9. validate dm-verity/root-image identities against authenticated build metadata;
10. return granular statuses rather than a single SAFE result.

## Initial result vocabulary

- VERIFIED
- ATTESTED
- OBSERVED
- WARNING
- UNKNOWN
- NOT VERIFIABLE
- FAILED

## Important trust distinction

Cryptographically verifying a Quote proves that the supplied Quote was signed by the corresponding TPM-held attestation key and includes the expected nonce/PCR evidence. It does not by itself establish that the attestation key belongs to a universally trusted physical platform. AK provenance/trust is reported separately.

## Development order

The verifier implementation begins only after the build pipeline can produce stable artifact identities and real TPM/event-log samples. Test vectors should then be committed without private keys or sensitive machine identifiers.
