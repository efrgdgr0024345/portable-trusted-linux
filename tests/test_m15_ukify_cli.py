#!/usr/bin/env python3
"""Exercise the real ukify inspector using the same command constructor as build."""
import importlib.util
import os
from pathlib import Path
import shutil
import subprocess
import sys
import tempfile
import unittest

sys.dont_write_bytecode = True
ROOT = Path(__file__).resolve().parents[1]
spec = importlib.util.spec_from_file_location("ptl_uki_build", ROOT / "build/50-build-development-uki.py")
build = importlib.util.module_from_spec(spec)
spec.loader.exec_module(build)
UKIFY = os.environ.get("PTL_TEST_UKIFY") or shutil.which("ukify")
STUB = Path(os.environ.get("PTL_TEST_STUB", "/usr/lib/systemd/boot/efi/linuxx64.efi.stub"))


@unittest.skipUnless(UKIFY and STUB.exists(), "requires actual ukify and systemd stub")
class UkifyInspectorTests(unittest.TestCase):
    def invoke(self, image):
        argv = build.inspect_command(image)
        argv[0] = UKIFY
        return subprocess.run([str(x) for x in argv], text=True, capture_output=True)
    def test_actual_inspector_accepts_build_command(self):
        result = self.invoke(STUB)
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertIn(".sbat:", result.stdout)
    def test_actual_inspector_rejects_missing_image(self):
        result = self.invoke(ROOT / "no-such-development-image.efi")
        self.assertNotEqual(result.returncode, 0)
        self.assertNotIn("unrecognized arguments", result.stderr)

    def test_real_ukify_retains_sbat_row_from_build_input(self):
        # NOT BOOTABLE payload; this tests the actual metadata merger only.
        with tempfile.TemporaryDirectory() as name:
            root = Path(name)
            (root / "kernel").write_bytes(b"NOT A BOOTABLE KERNEL")
            (root / "initrd").write_bytes(b"NOT A REAL INITRD")
            (root / "sbat").write_text(build.sbat_text())
            image = root / "test.efi"
            argv = [UKIFY, "build", "--config=/dev/null", "--stub=" + str(STUB),
                    "--linux=" + str(root / "kernel"), "--initrd=" + str(root / "initrd"),
                    "--uname=development-fixture", "--no-sign-kernel", "--no-measure",
                    "--sbat=@" + str(root / "sbat"), "--output=" + str(image)]
            result = subprocess.run(argv, text=True, capture_output=True)
            self.assertEqual(result.returncode, 0, result.stderr)
            output = root / "sbat-extracted"
            subprocess.run(["objcopy", "--dump-section", ".sbat=" + str(output), str(image),
                            str(root / "discard.efi")], check=True, capture_output=True)
            rows = output.read_bytes().rstrip(b"\0").decode().splitlines()
            self.assertIn(build.SBAT, rows)


if __name__ == "__main__":
    unittest.main()
