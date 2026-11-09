"""Evaluate privileged-build event/ref guards; this is not scheduler attestation."""
import ast
import json
from pathlib import Path
import re
import unittest

ROOT=Path(__file__).resolve().parents[1]
WORKFLOW=json.loads((ROOT/'.github/workflows/m15-development.yml').read_text())
REPO='efrgdgr0024345/portable-trusted-linux'

def permits(event,ref,repository=REPO):
    expression=WORKFLOW['jobs']['m15']['if'].removeprefix('${{').removesuffix('}}').strip()
    for field,value in [('repository',repository),('ref',ref),('event_name',event)]:
        expression=expression.replace('github.'+field,repr(value))
    tree=ast.parse(expression.replace('&&',' and ').replace('||',' or '),mode='eval')
    allowed=(ast.Expression,ast.BoolOp,ast.And,ast.Or,ast.Compare,ast.Eq,ast.Constant)
    if any(not isinstance(n,allowed) for n in ast.walk(tree)):raise ValueError('Unsupported guard')
    return eval(compile(tree,'<guard>','eval'),{'__builtins__':{}},{})

class IntegrationRouting(unittest.TestCase):
    def test_trusted_main_and_integration(self):
        for branch in ('main','integration/main-20261110'):
            for event in ('push','workflow_dispatch'):
                self.assertTrue(permits(event,'refs/heads/'+branch))
    def test_reject_pr_events_even_on_trusted_ref(self):
        for event in ('pull_request','pull_request_target','workflow_run'):
            self.assertFalse(permits(event,'refs/heads/main'))
    def test_reject_untrusted_ref_and_repository(self):
        for ref in ('refs/pull/9/merge','refs/heads/main-evil','refs/heads/codex/automatic-boot-recovery'):
            self.assertFalse(permits('push',ref))
        self.assertFalse(permits('push','refs/heads/main','someone/fork'))
    def test_least_privilege_and_exact_checkout(self):
        self.assertEqual(WORKFLOW['permissions'],{'contents':'read'})
        self.assertEqual(set(WORKFLOW['on']),{'push','workflow_dispatch'})
        self.assertEqual(WORKFLOW['jobs']['m15']['runs-on'],['ptl-trusted-20261110'])
        checkout=WORKFLOW['jobs']['m15']['steps'][0]['with']
        self.assertIs(checkout['persist-credentials'],False)
        self.assertEqual(checkout['ref'],'${{ github.sha }}')
    def test_legacy_generic_routes_cannot_execute(self):
        for path in (ROOT/'.github/workflows').glob('*.yml'):
            lines=path.read_text().splitlines()
            for i,line in enumerate(lines):
                if 'runs-on: [self-hosted, Linux, X64]' in line:
                    self.assertEqual(lines[i-1].strip(),'if: ${{ false }}',str(path))
