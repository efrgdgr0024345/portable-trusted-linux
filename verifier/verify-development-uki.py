#!/usr/bin/env python3
"""Offline UKI verification relative to an explicitly supplied development certificate.
No firmware, TPM, network or block-device operations. This is not release approval.
"""
import argparse
import hashlib
import json
from pathlib import Path
import subprocess
import sys
import tempfile

sys.dont_write_bytecode = True


def digest(path):
    h = hashlib.sha256()
    with Path(path).open("rb") as stream:
        for block in iter(lambda: stream.read(1024 * 1024), b""):
            h.update(block)
    return h.hexdigest()


def command(argv):
    p = subprocess.run([str(x) for x in argv], stdout=subprocess.PIPE, stderr=subprocess.STDOUT)
    if p.returncode:
        raise ValueError(f"{argv[0]} failed ({p.returncode}): {p.stdout.decode(errors='replace')}")
    return p.stdout


def certificate_digest(cert):
    return hashlib.sha256(command(["openssl", "x509", "-in", cert, "-outform", "DER"])).hexdigest()


def section_table(uki):
    """Use mature BFD tooling rather than implementing an Authenticode/PE parser."""
    result = {}
    for line in command(["objdump", "-h", uki]).decode().splitlines():
        fields = line.split()
        if len(fields) >= 7 and fields[0].isdigit():
            name, size, offset = fields[1], int(fields[2], 16), int(fields[5], 16)
            if name in result:
                raise ValueError("Duplicate PE section: " + name)
            if offset + size > Path(uki).stat().st_size:
                raise ValueError("Out-of-bounds PE section: " + name)
            result[name] = {"size": size, "offset": offset}
    return result


def verify(uki, certificate, manifest, expected_certificate_sha256):
    before = digest(uki)
    if certificate_digest(certificate) != expected_certificate_sha256.lower().replace(":", ""):
        raise ValueError("Certificate DER SHA-256 does not match explicit trust input")
    # --list is NOT signature verification. Check the actual signature and digest.
    output = command(["sbverify", "--cert", certificate, uki]).decode()
    if "Signature verification OK" not in output:
        raise ValueError("No positive signature verification result")
    table = section_table(uki)
    required = {".linux", ".initrd", ".cmdline", ".osrel", ".uname", ".sbat"}
    if not required.issubset(table):
        raise ValueError("Missing mandatory UKI sections: " + str(required - set(table)))
    sections = {}
    with tempfile.TemporaryDirectory(prefix="ptl-uki-verify-") as temp:
        for name in sorted(required):
            extracted = Path(temp) / name[1:]
            # Without an explicit output, objcopy rewrites the input PE and may
            # discard its certificate table even for --dump-section. Never
            # mutate the object whose signature we just verified.
            command(["objcopy", "--dump-section", f"{name}={extracted}", uki,
                     Path(temp) / "discarded-rewrite.efi"])
            if extracted.stat().st_size == 0:
                raise ValueError("Empty mandatory section: " + name)
            sections[name] = {"sha256": digest(extracted), "size": extracted.stat().st_size}
            if name in (".linux", ".initrd"):
                if digest(extracted) != manifest["inputs"][name]["sha256"]:
                    raise ValueError("Embedded input digest mismatch: " + name)
            elif name == ".sbat":
                rows = extracted.read_bytes().rstrip(b"\0").decode().splitlines()
                if not any(r.startswith("sbat,1,") for r in rows):
                    raise ValueError("Missing SBAT header")
                if manifest["sbat_row"] not in rows:
                    raise ValueError("Missing expected Black Cat SBAT row")
            else:
                raw = extracted.read_bytes().rstrip(b"\0")
                expected = manifest["texts"][name].encode()
                if raw != expected:
                    raise ValueError("Embedded metadata mismatch: " + name)
    if digest(uki) != before:
        raise ValueError("UKI changed during verification")
    return {"status": "VERIFIED", "scope": "Signature and bound inputs relative to supplied development certificate and manifest; not firmware enforcement",
            "uki_sha256": before, "certificate_der_sha256": certificate_digest(certificate),
            "sections": sections, "signature_output": output.strip()}


def main():
    p = argparse.ArgumentParser(description=__doc__)
    p.add_argument("--uki", type=Path, required=True)
    p.add_argument("--certificate", type=Path, required=True)
    p.add_argument("--manifest", type=Path, required=True)
    p.add_argument("--expected-cert-sha256", required=True)
    args = p.parse_args()
    try:
        result = verify(args.uki, args.certificate, json.loads(args.manifest.read_text()), args.expected_cert_sha256)
    except (ValueError, OSError, KeyError) as error:
        print("FAILED: " + str(error), file=sys.stderr)
        return 1
    print(json.dumps(result, indent=2))
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
