#!/usr/bin/env python3
"""Exercise the actual installed gh parser without authenticating or networking."""
from pathlib import Path
import shlex
import shutil
import subprocess
import unittest

SCRIPT = Path(__file__).resolve().parents[1] / "ops/register-runner-gh.sh"

@unittest.skipUnless(shutil.which("gh"), "requires the Debian gh package")
class GitHubCliCompatibility(unittest.TestCase):
    def login_command(self):
        lines = SCRIPT.read_text().splitlines()
        line = next(line for line in lines if " gh auth login " in line)
        argv = shlex.split(line)
        return argv[argv.index("gh"):]
    def test_login_flags_accepted_by_installed_cli(self):
        result = subprocess.run(self.login_command() + ["--help"], text=True, capture_output=True)
        self.assertEqual(result.returncode, 0, result.stderr)
    def test_invalid_flag_is_rejected(self):
        result = subprocess.run(self.login_command() + ["--ptl-unsupported-test-flag", "--help"], text=True, capture_output=True)
        self.assertNotEqual(result.returncode, 0)
        self.assertIn("unknown flag", result.stderr)

if __name__ == "__main__":
    unittest.main()
