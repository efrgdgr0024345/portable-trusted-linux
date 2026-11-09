#!/usr/bin/env python3
"""Actual crypto/binding tests on a synthetic, NOT BOOTABLE signed PE fixture.
Private fixture keys stay in anonymous Linux memfd files, never repository files.
Real live-kernel UKI/ISO acceptance is a separate pipeline integration test.
"""
from contextlib import ExitStack
import importlib.util
import json
import os
from pathlib import Path
import shutil
import subprocess
import sys
import tempfile
import unittest

sys.dont_write_bytecode = True
ROOT = Path(__file__).resolve().parents[1]
SPEC = importlib.util.spec_from_file_location("ptl_uki_verify", ROOT / "verifier/verify-development-uki.py")
V = importlib.util.module_from_spec(SPEC)
SPEC.loader.exec_module(V)
STUB = Path(os.environ.get("PTL_TEST_STUB", "/usr/lib/systemd/boot/efi/linuxx64.efi.stub"))
READY = hasattr(os, "memfd_create") and STUB.exists() and all(shutil.which(x) for x in ("openssl", "objcopy", "sbsign", "sbverify"))


@unittest.skipUnless(READY, "requires Linux memfd, systemd stub, binutils, openssl and sbsigntool")
class UkiVerifierTests(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        cls.stack = ExitStack()
        cls.addClassCleanup(cls.stack.close)
        cls.root = Path(cls.stack.enter_context(tempfile.TemporaryDirectory(prefix="ptl-synthetic-pe-")))
        cls.keys = []
        for label in ("expected", "wrong"):
            fd = os.memfd_create("ptl-development-test-key", 0)
            cls.stack.callback(os.close, fd)
            cls.keys.append(fd)
            cls.command(["openssl", "req", "-new", "-x509", "-newkey", "rsa:3072", "-nodes",
                         "-sha256", "-days", "1", "-subj", "/CN=M15 TEST FIXTURE DEVELOPMENT ONLY/",
                         "-keyout", f"/proc/self/fd/{fd}", "-out", cls.root / (label + ".pem")], (fd,))
        cls.cert = cls.root / "expected.pem"
        cls.fingerprint = V.certificate_digest(cls.cert)
        cls.row = "blackcat,1,Black Cat,blackcat-development,1,https://example.invalid/"
        payload = {
            ".linux": b"NOT A BOOTABLE KERNEL\n" * 512,
            ".initrd": b"NOT A REAL INITRD\n" * 256,
            ".cmdline": b"boot=live components quiet",
            ".osrel": b"ID=blackcat\n",
            ".uname": b"development-fixture",
            ".sbat": ("sbat,1,SBAT Version,sbat,1,https://example.invalid/\n" + cls.row + "\n").encode(),
        }
        cls.manifest = {"inputs": {}, "texts": {}, "sbat_row": cls.row}
        argv = ["objcopy"]
        for index, (section, raw) in enumerate(payload.items()):
            path = cls.root / section[1:]
            path.write_bytes(raw)
            if section == ".sbat":
                argv += ["--update-section", f"{section}={path}"]
            else:
                argv += ["--add-section", f"{section}={path}",
                         "--change-section-vma", f"{section}=0x{0x150000000 + index * 0x10000:x}",
                         "--set-section-flags", f"{section}=contents,alloc,load,readonly,data"]
            if section in (".linux", ".initrd"):
                cls.manifest["inputs"][section] = {"sha256": V.digest(path)}
            elif section != ".sbat":
                cls.manifest["texts"][section] = raw.decode()
        cls.unsigned, cls.signed = cls.root / "unsigned.efi", cls.root / "signed.efi"
        cls.command(argv + [STUB, cls.unsigned])
        cls.sign(cls.unsigned, cls.signed)

    @classmethod
    def command(cls, args, fds=()):
        p = subprocess.run([str(x) for x in args], pass_fds=fds, stdout=subprocess.PIPE, stderr=subprocess.STDOUT)
        if p.returncode:
            raise RuntimeError(p.stdout.decode(errors="replace"))
        return p.stdout

    @classmethod
    def sign(cls, source, destination):
        cls.command(["sbsign", "--key", f"/proc/self/fd/{cls.keys[0]}", "--cert", cls.cert,
                     "--output", destination, source], (cls.keys[0],))

    def test_positive(self):
        self.assertEqual(V.verify(self.signed, self.cert, self.manifest, self.fingerprint)["status"], "VERIFIED")

    def test_verification_preserves_signed_bytes(self):
        before = V.digest(self.signed)
        V.verify(self.signed, self.cert, self.manifest, self.fingerprint)
        self.assertEqual(V.digest(self.signed), before)
        self.assertIn(b"Signature verification OK", self.command(["sbverify", "--cert", self.cert, self.signed]))

    def test_tampered_kernel_rejected_by_signature(self):
        image = self.root / "tampered.efi"
        shutil.copyfile(self.signed, image)
        with image.open("r+b") as stream:
            stream.seek(V.section_table(image)[".linux"]["offset"] + 50)
            byte = stream.read(1)
            stream.seek(-1, 1)
            stream.write(bytes([byte[0] ^ 1]))
        with self.assertRaisesRegex(ValueError, "sbverify failed"):
            V.verify(image, self.cert, self.manifest, self.fingerprint)

    def test_wrong_key_rejected_by_signature(self):
        wrong = self.root / "wrong.pem"
        with self.assertRaisesRegex(ValueError, "sbverify failed"):
            V.verify(self.signed, wrong, self.manifest, V.certificate_digest(wrong))

    def test_unsigned_rejected_by_signature(self):
        with self.assertRaisesRegex(ValueError, "sbverify failed"):
            V.verify(self.unsigned, self.cert, self.manifest, self.fingerprint)

    def test_wrong_pinned_certificate_rejected(self):
        with self.assertRaisesRegex(ValueError, "explicit trust input"):
            V.verify(self.signed, self.cert, self.manifest, "0" * 64)

    def test_changed_expected_commandline_rejected(self):
        manifest = json.loads(json.dumps(self.manifest))
        manifest["texts"][".cmdline"] += " changed-policy=1"
        with self.assertRaisesRegex(ValueError, "Embedded metadata mismatch: .cmdline"):
            V.verify(self.signed, self.cert, manifest, self.fingerprint)

    def test_changed_expected_kernel_rejected(self):
        manifest = json.loads(json.dumps(self.manifest))
        manifest["inputs"][".linux"]["sha256"] = "0" * 64
        with self.assertRaisesRegex(ValueError, "Embedded input digest mismatch: .linux"):
            V.verify(self.signed, self.cert, manifest, self.fingerprint)

    def test_missing_required_section_rejected_even_when_resigned(self):
        unsigned, signed = self.root / "missing-initrd.efi", self.root / "missing-initrd-signed.efi"
        self.command(["objcopy", "--remove-section", ".initrd", self.unsigned, unsigned])
        self.sign(unsigned, signed)
        with self.assertRaisesRegex(ValueError, "Missing mandatory UKI sections"):
            V.verify(signed, self.cert, self.manifest, self.fingerprint)


if __name__ == "__main__":
    unittest.main()
