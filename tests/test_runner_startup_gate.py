#!/usr/bin/env python3
"""Check the actual startup script's confirmation gate without changing the host."""
from pathlib import Path
import os
import subprocess
import unittest
SCRIPT = Path(__file__).resolve().parents[1] / "ops/start-recovery-runner.sh"
class GateTests(unittest.TestCase):
    def check_gate(self, approval=None, labels=None):
        env = dict(os.environ)
        for key in ("PTL_OUTSIDE_APPROVAL_CONFIRMED", "PTL_LABELS_CONFIRMED"):
            env.pop(key, None)
        if approval is not None: env["PTL_OUTSIDE_APPROVAL_CONFIRMED"] = approval
        if labels is not None: env["PTL_LABELS_CONFIRMED"] = labels
        return subprocess.run(["bash", str(SCRIPT), "--check-confirmations"], env=env, capture_output=True, text=True)
    def test_both_required(self):
        self.assertEqual(self.check_gate("yes", "yes").returncode, 0)
    def test_missing_both(self):
        self.assertNotEqual(self.check_gate().returncode, 0)
    def test_missing_approval(self):
        self.assertNotEqual(self.check_gate(labels="yes").returncode, 0)
    def test_missing_labels(self):
        self.assertNotEqual(self.check_gate(approval="yes").returncode, 0)
    def test_other_values_refused(self):
        self.assertNotEqual(self.check_gate("no", "yes").returncode, 0)
        self.assertNotEqual(self.check_gate("yes", "no").returncode, 0)
if __name__ == "__main__":
    unittest.main()
