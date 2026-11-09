#!/usr/bin/env python3
"""Exercise the real ukify inspector using the same command constructor as build."""
import importlib.util
import os
from pathlib import Path
import shutil
import subprocess
import sys
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


if __name__ == "__main__":
    unittest.main()
