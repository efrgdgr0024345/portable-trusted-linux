#!/usr/bin/env python3
"""Exercise actual build wrapper failure propagation without running lb or sudo."""
import os
from pathlib import Path
import shutil
import subprocess
import tempfile
import unittest

SCRIPT = Path(__file__).resolve().parents[1] / "build/20-build-live.sh"


class PipelineFailureTests(unittest.TestCase):
    def fixture(self, root):
        (root / "build").mkdir()
        script = root / "build/20-build-live.sh"
        shutil.copyfile(SCRIPT, script)
        work = root / "scratch"
        work.mkdir()
        return script, work

    def invoke(self, script, work, sudo_body, tee_body, extra_functions=""):
        # Functions intercept privileged and build commands. Only the actual
        # wrapper is sourced; preflight/preparation are not part of this fixture.
        harness = """
lb() { :; }
sudo() { %s; }
tee() { %s; }
bash() {
  case "$1" in
    */00-host-check.sh|*/10-prepare-live-build.sh) return 0 ;;
    *) return 88 ;;
  esac
}
source "$PTL_TEST_SCRIPT"
""" % (sudo_body.strip(), tee_body.strip())
        harness = extra_functions + "\n" + harness
        env = dict(os.environ, PTL_BUILD_WORK_DIR=str(work), PTL_TEST_SCRIPT=str(script))
        return subprocess.run(["bash", "-c", harness], env=env, capture_output=True, text=True)

    @unittest.skipIf(os.geteuid() == 0, "non-root wrapper path; never run real privileged lb")
    def test_successful_lb_but_failed_log_write_fails_closed(self):
        with tempfile.TemporaryDirectory() as name:
            root = Path(name)
            script, work = self.fixture(root)
            p = self.invoke(script, work, "return 0", "return 42")
            self.assertEqual(p.returncode, 42, p.stdout + p.stderr)
            self.assertFalse((root / "out/m15").exists())

    @unittest.skipIf(os.geteuid() == 0, "non-root wrapper path; never run real privileged lb")
    def test_failed_lb_fails_closed(self):
        with tempfile.TemporaryDirectory() as name:
            script, work = self.fixture(Path(name))
            p = self.invoke(script, work, "return 43", "cat >/dev/null; return 0")
            self.assertEqual(p.returncode, 43, p.stdout + p.stderr)

    @unittest.skipIf(os.geteuid() == 0, "requires real unprivileged write refusal")
    def test_readback_reports_use_a_writable_public_evidence_copy(self):
        with tempfile.TemporaryDirectory() as name:
            root = Path(name)
            script, work = self.fixture(root)
            source = work / "m15-evidence"
            # The simulated privileged build creates this after fresh-work checks.
            build_body = '''
mkdir -p "$PTL_BUILD_WORK_DIR/m15-evidence"
printf 'public evidence fixture' > "$PTL_BUILD_WORK_DIR/m15-evidence/public.txt"
chmod 0555 "$PTL_BUILD_WORK_DIR/m15-evidence"
printf 'NOT A REAL ISO' > "$PTL_BUILD_WORK_DIR/live-image-amd64.hybrid.iso"
'''
            verifier = '''
python3() {
  local bundle=""
  while [[ $# -gt 0 ]]; do
    if [[ "$1" == --bundle ]]; then bundle="$2"; shift 2; else shift; fi
  done
  printf 'report' > "$bundle/el-torito.log"
}
'''
            p = self.invoke(script, work, build_body, "cat >/dev/null; return 0", verifier)
            self.assertEqual(p.returncode, 0, p.stdout + p.stderr)
            self.assertTrue((root / "out/m15/el-torito.log").exists())
            self.assertFalse((source / "el-torito.log").exists())

    def test_cached_work_is_refused_before_build(self):
        with tempfile.TemporaryDirectory() as name:
            script, work = self.fixture(Path(name))
            (work / "m15-evidence").mkdir()
            p = subprocess.run(["bash", str(script)], env=dict(os.environ, PTL_BUILD_WORK_DIR=str(work)),
                               capture_output=True, text=True)
            self.assertNotEqual(p.returncode, 0)
            self.assertIn("fresh PTL_BUILD_WORK_DIR", p.stderr)

    def test_stale_evidence_is_refused_before_build(self):
        with tempfile.TemporaryDirectory() as name:
            root = Path(name)
            script, work = self.fixture(root)
            (root / "out/m15").mkdir(parents=True)
            p = subprocess.run(["bash", str(script)], env=dict(os.environ, PTL_BUILD_WORK_DIR=str(work)),
                               capture_output=True, text=True)
            self.assertNotEqual(p.returncode, 0)
            self.assertIn("stale out/m15", p.stderr)


if __name__ == "__main__":
    unittest.main()
