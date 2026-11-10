#!/usr/bin/env python3
"""Routing regression checks; these do not prove GitHub runner isolation end to end."""
import ast
import json
from pathlib import Path
import unittest

ROOT = Path(__file__).resolve().parents[1]
WORKFLOW = json.loads((ROOT / ".github/workflows/runner-recovery.yml").read_text())
BRANCH = "main"
REPO = "efrgdgr0024345/portable-trusted-linux"

def permits(event, ref, repository=REPO):
    expression = WORKFLOW["jobs"]["recovery"]["if"].removeprefix("${{").removesuffix("}}").strip()
    for field, value in (("repository", repository), ("ref", ref), ("event_name", event)):
        expression = expression.replace("github." + field, repr(value))
    expression = expression.replace("&&", " and ").replace("||", " or ")
    tree = ast.parse(expression, mode="eval")
    allowed = (ast.Expression, ast.BoolOp, ast.And, ast.Or, ast.Compare, ast.Eq, ast.Constant)
    if any(not isinstance(node, allowed) for node in ast.walk(tree)):
        raise ValueError("Unsupported guard expression")
    return eval(compile(tree, "<workflow guard>", "eval"), {"__builtins__": {}}, {})

class RoutingTests(unittest.TestCase):
    def test_trusted_main_dispatch_only(self):
        self.assertTrue(permits("workflow_dispatch", "refs/heads/" + BRANCH))
        self.assertFalse(permits("push", "refs/heads/" + BRANCH))
    def test_reject_untrusted_events(self):
        for event in ("pull_request", "pull_request_target", "workflow_run"):
            self.assertFalse(permits(event, "refs/heads/" + BRANCH))
    def test_reject_other_refs_and_repositories(self):
        for ref in ("refs/heads/ops/runner-recovery-20261110", "refs/pull/1/merge", "refs/heads/" + BRANCH + "-lookalike"):
            self.assertFalse(permits("workflow_dispatch", ref))
        self.assertFalse(permits("workflow_dispatch", "refs/heads/" + BRANCH, "someone/fork"))
    def test_dedicated_label_and_no_pull_request_trigger(self):
        self.assertEqual(WORKFLOW["jobs"]["recovery"]["runs-on"], ["ptl-trusted-20261110"])
        self.assertEqual(set(WORKFLOW["on"]), {"workflow_dispatch"})
    def test_smoke_requires_nonroot_runner_and_root_sudo(self):
        script = WORKFLOW["jobs"]["recovery"]["steps"][0]["run"]
        self.assertIn('test "$(id -un)" = github-runner', script)
        self.assertIn('test "$(id -u)" -ne 0', script)
        self.assertIn('test "$(sudo -n /usr/bin/id -u)" = 0', script)
        self.assertEqual(WORKFLOW["permissions"], {"contents": "read"})

if __name__ == "__main__":
    unittest.main()
