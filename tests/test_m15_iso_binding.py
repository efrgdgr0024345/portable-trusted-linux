#!/usr/bin/env python3
"""Read-back controls on a synthetic NOT BOOTABLE hybrid ISO; no device mounts."""
import contextlib
import importlib.util
import io
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
sys.path.insert(0, str(ROOT / "tests"))
import test_m15_uki_verifier as fixture

spec = importlib.util.spec_from_file_location("ptl_iso_check", ROOT / "build/52-verify-development-uki-iso.py")
iso_check = importlib.util.module_from_spec(spec)
spec.loader.exec_module(iso_check)
MBR = Path(os.environ.get("PTL_TEST_GRUB_MBR", "/usr/lib/grub/i386-pc/boot_hybrid.img"))
READY = fixture.READY and MBR.exists() and all(shutil.which(x) for x in ("xorriso", "mcopy", "mmd", "mkfs.vfat", "sfdisk"))


@unittest.skipUnless(READY, "requires actual ISO/FAT/partition tools and crypto fixture tooling")
class IsoBindingTests(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        fixture.UkiVerifierTests.setUpClass()
        cls.addClassCleanup(fixture.UkiVerifierTests.doClassCleanups)
        cls.temp = tempfile.TemporaryDirectory(prefix="ptl-synthetic-iso-")
        cls.addClassCleanup(cls.temp.cleanup)
        cls.root = Path(cls.temp.name)
        cls.binary, cls.bundle = cls.root / "binary", cls.root / "evidence"
        cls.bundle.mkdir()
        crypto = fixture.UkiVerifierTests
        for directory in ("live", "boot/grub", "EFI/Linux"):
            (cls.binary / directory).mkdir(parents=True)
        shutil.copyfile(crypto.root / "linux", cls.binary / "live/vmlinuz")
        shutil.copyfile(crypto.root / "initrd", cls.binary / "live/initrd.img")
        shutil.copyfile(crypto.signed, cls.binary / "EFI/Linux/blackcat-development.efi")
        shutil.copyfile(crypto.cert, cls.bundle / "development-cert.pem")
        (cls.bundle / "inputs.json").write_text(json.dumps(crypto.manifest))
        cls.esp = cls.binary / "boot/grub/efi.img"
        with cls.esp.open("wb") as stream:
            stream.truncate(64 * 1024 * 1024)
        cls.command(["mkfs.vfat", "-F", "32", cls.esp])
        cls.command(["mmd", "-i", cls.esp, "::EFI", "::EFI/BOOT"])
        cls.command(["mcopy", "-i", cls.esp, crypto.signed, "::EFI/BOOT/BOOTX64.EFI"])
        # Intentionally non-bootable BIOS payload: exercise the real packager only.
        (cls.binary / "boot/grub/grub_eltorito").write_bytes(b"\0" * 4096)
        provenance = {"source_commit": "synthetic-not-acceptance",
                      "signed_uki_sha256": iso_check.v.digest(crypto.signed),
                      "esp_sha256": iso_check.v.digest(cls.esp),
                      "certificate_der_sha256": crypto.fingerprint}
        (cls.bundle / "provenance.json").write_text(json.dumps(provenance))
        cls.iso = cls.root / "hybrid.iso"
        cls.make_iso(cls.iso, True)

    @classmethod
    def command(cls, args):
        p = subprocess.run([str(x) for x in args], stdout=subprocess.PIPE, stderr=subprocess.STDOUT)
        if p.returncode:
            raise RuntimeError(p.stdout.decode(errors="replace"))
        return p.stdout

    @classmethod
    def make_iso(cls, destination, hybrid):
        argv = ["xorriso", "-as", "mkisofs", "-R", "-r", "-J", "-iso-level", "3",
                "--grub2-boot-info", "--grub2-mbr", MBR,
                "-b", "boot/grub/grub_eltorito", "-no-emul-boot",
                "-boot-load-size", "4", "-boot-info-table", "-eltorito-alt-boot",
                "-e", "boot/grub/efi.img", "-no-emul-boot"]
        if hybrid:
            argv += ["-efi-boot-part", "--efi-boot-image", "-isohybrid-gpt-basdat", "-isohybrid-apm-hfsplus"]
        cls.command(argv + ["-o", destination, cls.binary])

    def run_check(self, image):
        with contextlib.redirect_stdout(io.StringIO()):
            iso_check.check(image, self.bundle)

    def test_positive_actual_hybrid_layout_and_signature(self):
        self.run_check(self.iso)
        report = json.loads((self.bundle / "iso-verification.json").read_text())
        self.assertEqual(report["status"], "VERIFIED")
        self.assertEqual(report["signature"]["status"], "VERIFIED")

    def test_modified_actual_efi_extent_refused(self):
        self.run_check(self.iso)
        report = json.loads((self.bundle / "iso-verification.json").read_text())
        altered = self.root / "tampered-esp.iso"
        shutil.copyfile(self.iso, altered)
        with altered.open("r+b") as stream:
            stream.seek(report["el_torito_uefi_lba"] * 2048 + 4096)
            byte = stream.read(1)
            stream.seek(-1, 1)
            stream.write(bytes([byte[0] ^ 1]))
        with self.assertRaisesRegex(ValueError, "ISO ESP differs"):
            self.run_check(altered)

    def test_missing_hybrid_efi_partition_refused(self):
        nonhybrid = self.root / "no-efi-partition.iso"
        self.make_iso(nonhybrid, False)
        with self.assertRaisesRegex(ValueError, "sfdisk failed|Require one hybrid-media EFI partition"):
            self.run_check(nonhybrid)


if __name__ == "__main__":
    unittest.main()
