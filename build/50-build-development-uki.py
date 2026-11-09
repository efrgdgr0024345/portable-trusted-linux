#!/usr/bin/env python3
"""M15 binary hook: exact live inputs -> UKI -> ephemeral development signature.
Writes only build-tree files; never enrolls keys or touches firmware/TPM/devices.
"""
import argparse
from datetime import datetime, timezone
import hashlib
import importlib.util
import json
import os
from pathlib import Path
import shlex
import shutil
import signal
import subprocess
import sys
import tempfile

sys.dont_write_bytecode = True
SOURCE = Path(__file__).resolve().parents[1]
spec = importlib.util.spec_from_file_location("ptl_uki_verify", SOURCE / "verifier/verify-development-uki.py")
verify_module = importlib.util.module_from_spec(spec)
spec.loader.exec_module(verify_module)
digest = verify_module.digest
CMDLINE = "boot=live components quiet"
SBAT = "blackcat,1,Black Cat,blackcat-development,1,https://github.com/efrgdgr0024345/portable-trusted-linux"


def unique_files(paths):
    return sorted({p.resolve() for p in paths if p.is_file()})


def build(work, output):
    work = work.resolve()
    output.mkdir(parents=True, exist_ok=False)
    ledger = output / "commands.jsonl"

    def run(argv, name, expected=0):
        argv = [str(x) for x in argv]
        p = subprocess.run(argv, stdout=subprocess.PIPE, stderr=subprocess.STDOUT)
        log = p.stdout.decode(errors="replace")
        (output / (name + ".log")).write_text(log)
        with ledger.open("a") as stream:
            stream.write(json.dumps({"argv": argv, "shell_display": shlex.join(argv), "exit_code": p.returncode, "log": name + ".log"}) + "\n")
        if expected == 0 and p.returncode != 0:
            raise RuntimeError(f"{name} failed ({p.returncode}): {log}")
        if expected != 0 and p.returncode == 0:
            raise RuntimeError(name + ": intentionally broken input was accepted")
        return p.stdout

    for tool in ("ukify", "sbsign", "sbverify", "objcopy", "objdump", "openssl", "mcopy", "mmd", "mkfs.vfat", "git"):
        if not shutil.which(tool):
            raise RuntimeError("Missing tool: " + tool)
    if run(["stat", "-f", "-c", "%T", "/dev/shm"], "key-storage-type").strip() != b"tmpfs":
        raise RuntimeError("Ephemeral keys require tmpfs at /dev/shm")
    git = ["git", "-c", f"safe.directory={SOURCE}", "-C", SOURCE]
    commit = run(git + ["rev-parse", "HEAD"], "source-commit").decode().strip()
    if run(git + ["status", "--porcelain", "--untracked-files=no"], "tracked-source-state").strip():
        raise RuntimeError("Refuse dirty tracked source")
    if os.environ.get("PTL_SOURCE_COMMIT", commit) != commit:
        raise RuntimeError("Build source does not match requested commit")
    binary = work / "binary"
    kernels = unique_files((binary / "live").glob("vmlinuz*"))
    initrds = unique_files((binary / "live").glob("initrd*"))
    if len(kernels) != 1 or len(initrds) != 1:
        raise RuntimeError("Require exactly one live kernel and initrd, not an arbitrary first match")
    kernel, initrd = kernels[0], initrds[0]
    matches = [p for p in (work / "chroot/boot").glob("vmlinuz-*") if p.is_file() and digest(p) == digest(kernel)]
    if len(matches) != 1:
        raise RuntimeError("Cannot bind one exact chroot kernel version to the live kernel")
    version = matches[0].name.removeprefix("vmlinuz-")
    osrel_path = work / "chroot/etc/os-release"
    if not osrel_path.resolve().is_relative_to((work / "chroot").resolve()):
        raise RuntimeError("os-release escapes build chroot")
    osrel = osrel_path.read_text().rstrip("\n") + "\nIMAGE_ID=blackcat\nIMAGE_VERSION=prototype1-m15-development\n"
    for name, value in (("cmdline.txt", CMDLINE), ("os-release", osrel), ("sbat.csv", SBAT + "\n")):
        (output / name).write_text(value)
    manifest = {
        "schema": "blackcat-m15-inputs-v1", "key_policy": "EPHEMERAL DEVELOPMENT ONLY",
        "source_commit": commit,
        "inputs": {".linux": {"path": str(kernel), "sha256": digest(kernel), "size": kernel.stat().st_size},
                   ".initrd": {"path": str(initrd), "sha256": digest(initrd), "size": initrd.stat().st_size}},
        "texts": {".cmdline": CMDLINE, ".osrel": osrel, ".uname": version},
        "sbat_row": SBAT,
        "limits": ["Development certificate is not firmware-enrolled or a production trust root.",
                   "Root squashfs is not authenticated by M15; dm-verity root binding remains M17.",
                   "Legacy BIOS is not a signed-UKI or Secure Boot enforcement path."]
    }
    manifest_path = output / "inputs.json"
    manifest_path.write_text(json.dumps(manifest, indent=2) + "\n")
    signed = output / "blackcat-development.efi"
    results = {}
    with tempfile.TemporaryDirectory(prefix="ptl-m15-keys-", dir="/dev/shm") as private, \
         tempfile.TemporaryDirectory(prefix="ptl-m15-fixtures-", dir=work) as fixtures:
        os.chmod(private, 0o700)
        private, fixtures = Path(private), Path(fixtures)
        for name in ("development", "wrong-key"):
            key, cert = private / (name + ".key"), output / (name + "-cert.pem")
            run(["openssl", "req", "-new", "-x509", "-newkey", "rsa:3072", "-sha256",
                 "-nodes", "-days", "1", "-subj", f"/CN=Black Cat M15 {name} DEVELOPMENT ONLY/",
                 "-addext", "basicConstraints=critical,CA:FALSE",
                 "-addext", "keyUsage=critical,digitalSignature",
                 "-addext", "extendedKeyUsage=codeSigning",
                 "-keyout", key, "-out", cert], name + "-certificate")
        cert = output / "development-cert.pem"
        pubkey = run(["openssl", "x509", "-in", cert, "-pubkey", "-noout"], "public-key")
        (output / "development-public-key.pem").write_bytes(pubkey)
        cert_hash = verify_module.certificate_digest(cert)
        (output / "certificate-der-sha256.txt").write_text(cert_hash + "\n")
        public_der = run(["openssl", "pkey", "-pubin", "-in", output / "development-public-key.pem",
                          "-outform", "DER"], "public-key-der")
        # This command's log is binary DER public data; retain it as such.
        (output / "public-key-der.log").write_bytes(public_der)
        public_hash = hashlib.sha256(public_der).hexdigest()
        (output / "public-key-spki-sha256.txt").write_text(public_hash + "\n")
        unsigned = fixtures / "unsigned.efi"
        run(["ukify", "build", "--config=/dev/null", "--efi-arch=x64",
             "--stub=/usr/lib/systemd/boot/efi/linuxx64.efi.stub",
             "--no-sign-kernel", "--no-measure", "--linux", kernel, "--initrd", initrd,
             "--cmdline=@" + str(output / "cmdline.txt"),
             "--os-release=@" + str(output / "os-release"), "--uname", version,
             "--sbat=@" + str(output / "sbat.csv"), "--output", unsigned], "ukify-build")
        run(["sbsign", "--key", private / "development.key", "--cert", cert,
             "--output", signed, unsigned], "sbsign")
        run(["ukify", "inspect", "--all", signed], "ukify-inspect")
        verifier = [sys.executable, SOURCE / "verifier/verify-development-uki.py",
                    "--manifest", manifest_path]
        def check(label, image, trust, expected, expected_failure=False):
            args = verifier + ["--uki", image, "--certificate", trust, "--expected-cert-sha256", expected]
            raw = run(args, label, expected=1 if expected_failure else 0)
            if expected_failure:
                if b"FAILED: sbverify failed" not in raw:
                    raise RuntimeError(label + ": refusal was not an actual signature-verification failure")
                results[label] = {"status": "VERIFIED", "meaning": "Expected signature rejection", "fixture_sha256": digest(image)}
            else:
                results[label] = json.loads(raw)
        check("positive", signed, cert, cert_hash)
        tampered = fixtures / "tampered.efi"
        shutil.copyfile(signed, tampered)
        offset = verify_module.section_table(signed)[".linux"]["offset"] + 4096
        with tampered.open("r+b") as stream:
            stream.seek(offset)
            value = stream.read(1)
            if len(value) != 1:
                raise RuntimeError("Cannot tamper a byte within embedded kernel")
            stream.seek(offset)
            stream.write(bytes([value[0] ^ 1]))
        check("negative-tampered", tampered, cert, cert_hash, True)
        wrong = output / "wrong-key-cert.pem"
        check("negative-wrong-key", signed, wrong, verify_module.certificate_digest(wrong), True)
        check("negative-unsigned", unsigned, cert, cert_hash, True)
        changed_manifest = json.loads(json.dumps(manifest))
        changed_manifest["texts"][".cmdline"] = CMDLINE + " changed-policy=1"
        altered = fixtures / "wrong-command-line.json"
        altered.write_text(json.dumps(changed_manifest))
        raw = run([sys.executable, SOURCE / "verifier/verify-development-uki.py",
                   "--manifest", altered, "--uki", signed, "--certificate", cert,
                   "--expected-cert-sha256", cert_hash], "negative-bound-command-line", expected=1)
        if b"Embedded metadata mismatch: .cmdline" not in raw:
            raise RuntimeError("Command-line negative test did not exercise the binding check")
        results["negative-bound-command-line"] = {"status": "VERIFIED", "meaning": "Expected command-line binding rejection"}
    # Only now can normal key deletion be reported. This is not secure RAM erasure.
    results["private_key_cleanup"] = {"status": "OBSERVED", "meaning": "TemporaryDirectory exited; private key paths unlinked from tmpfs; not proof of RAM erasure"}
    (output / "tests.json").write_text(json.dumps(results, indent=2) + "\n")

    # Replace the development UEFI entry rather than ship an unused signed sidecar.
    # Do not install an unsigned GRUB/shim fallback in this generated ESP.
    esp = binary / "boot/grub/efi.img"
    if not esp.is_file():
        raise RuntimeError("Expected live-build UEFI ESP file boot/grub/efi.img is missing")
    esp.unlink()
    megabytes = max(64, ((signed.stat().st_size + 16 * 1024 * 1024) // (1024 * 1024)) + 1)
    with esp.open("wb") as stream:
        stream.truncate(megabytes * 1024 * 1024)
    run(["mkfs.vfat", "-F", "32", "-n", "BLACKCATDEV", esp], "esp-format")
    run(["mmd", "-i", esp, "::EFI", "::EFI/BOOT"], "esp-directories")
    run(["mcopy", "-i", esp, signed, "::EFI/BOOT/BOOTX64.EFI"], "esp-uki")
    fallback_dir = binary / "EFI/boot"
    if fallback_dir.exists():
        shutil.rmtree(fallback_dir)
    fallback_dir.mkdir(parents=True)
    shutil.copyfile(signed, fallback_dir / "bootx64.efi")
    linux_dir = binary / "EFI/Linux"
    linux_dir.mkdir(parents=True, exist_ok=True)
    shutil.copyfile(signed, linux_dir / signed.name)
    for path in (fallback_dir / "bootx64.efi", linux_dir / signed.name):
        if digest(path) != digest(signed):
            raise RuntimeError("Copied UKI differs from verified image")
    # Keep small public verification inputs on the ISO; large test fixtures are transient.
    public_evidence = binary / "blackcat/m15"
    public_evidence.mkdir(parents=True)
    for name in ("development-cert.pem", "development-public-key.pem", "certificate-der-sha256.txt",
                 "public-key-spki-sha256.txt", "inputs.json", "tests.json"):
        shutil.copyfile(output / name, public_evidence / name)
    run(["dpkg-query", "-W"], "builder-package-versions")
    run(["uname", "-a"], "builder-kernel")
    run(["ukify", "--version"], "ukify-version")
    run(["openssl", "version"], "openssl-version")
    provenance = {"status": "VERIFIED", "scope": "UKI construction, signature and negative controls; final ISO extraction check is separate",
                  "key_policy": "EPHEMERAL DEVELOPMENT ONLY; NEVER ENROLLED",
                  "created_utc": datetime.now(timezone.utc).isoformat(), "source_commit": commit,
                  "builder": os.uname().nodename, "kernel_version": version,
                  "signed_uki_sha256": digest(signed), "signed_uki_size": signed.stat().st_size,
                  "certificate_der_sha256": cert_hash, "public_key_spki_sha256": public_hash,
                  "esp_sha256": digest(esp), "commands": "commands.jsonl",
                  "github_run_id": os.environ.get("GITHUB_RUN_ID", "unknown"),
                  "firmware_enforcement": "NOT VERIFIED", "measured_boot": "NOT VERIFIED",
                  "protected_root_binding": "NOT IMPLEMENTED (M17)",
                  "private_key_policy": "Fresh one-day development keys in tmpfs, unlinked before success; no private key retained"}
    (output / "provenance.json").write_text(json.dumps(provenance, indent=2) + "\n")
    shutil.copyfile(SOURCE / "verifier/verify-development-uki.py", output / "verify-development-uki.py")
    (output / "README.txt").write_text(
        "EPHEMERAL DEVELOPMENT ONLY. This certificate is not a production or firmware trust root.\n"
        "Pin the certificate fingerprint from the reviewed evidence record, not only this bundle.\n"
        "Offline verification (Python 3, openssl, binutils, sbsigntool):\n"
        "python3 verify-development-uki.py --uki blackcat-development.efi "
        "--certificate development-cert.pem --manifest inputs.json "
        "--expected-cert-sha256 " + cert_hash + "\n"
        "The manifest is public build provenance, not independently signed release metadata.\n"
        "A self-consistent replacement image+certificate+manifest does not establish authenticity.\n"
        "A signed UKI needs the live medium for its root; it is not a standalone full OS.\n")
    print("M15_UKI_SIGNATURE_AND_NEGATIVE_TESTS=VERIFIED")
    print(json.dumps(provenance, indent=2))


def main():
    p = argparse.ArgumentParser(description=__doc__)
    p.add_argument("--work-dir", type=Path, required=True)
    p.add_argument("--output", type=Path, required=True)
    args = p.parse_args()
    try:
        build(args.work_dir, args.output.resolve())
    except Exception as error:
        print("M15_FAILED: " + str(error), file=sys.stderr)
        return 1
    return 0


if __name__ == "__main__":
    # Unwind TemporaryDirectory on ordinary CI termination; SIGKILL/power loss
    # still cannot promise cleanup or secure RAM erasure.
    signal.signal(signal.SIGTERM, lambda number, frame: sys.exit(128 + number))
    raise SystemExit(main())
