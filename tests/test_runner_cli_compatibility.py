#!/usr/bin/env python3
"""Ensure the retired OAuth helper refuses execution and never invokes gh."""
from pathlib import Path
import os
import subprocess
import tempfile
import unittest

SCRIPT = Path(__file__).resolve().parents[1] / "ops/register-runner-gh.sh"

class RetiredHelperTests(unittest.TestCase):
    def test_refuses_execution(self):
        r = subprocess.run(["bash", str(SCRIPT)], text=True, capture_output=True)
        self.assertNotEqual(r.returncode, 0)
        self.assertIn("OAuth registration is retired", r.stderr)
        self.assertEqual(r.stdout, "")

    def test_does_not_invoke_github_cli(self):
        with tempfile.TemporaryDirectory() as directory:
            root = Path(directory)
            stub = root / "gh"
            stub.write_text('#!/bin/sh\nprintf called > "$PTL_GH_TEST_MARKER"\nexit 99\n')
            stub.chmod(0o700)
            marker = root / "called"
            env = dict(os.environ, PATH=directory + os.pathsep + os.environ.get("PATH", ""), PTL_GH_TEST_MARKER=str(marker))
            r = subprocess.run(["bash", str(SCRIPT)], env=env, text=True, capture_output=True)
            self.assertEqual(r.returncode, 1)
            self.assertFalse(marker.exists(), "retired helper attempted GitHub CLI authentication")

if __name__ == "__main__":
    unittest.main()
