#!/usr/bin/env python3
"""Lint viewer/console PHP; behavioural cases run through tests/00 -> tests/04 PHP -> tests/06."""
import os
from pathlib import Path
import shutil
import subprocess
php=os.environ.get('PTL_PHP_BINARY') or shutil.which('php')
if not php:raise SystemExit('PHP CLI is required; CI installs php-cli explicitly.')
for path in [Path('web/project.php'),*Path('apps/blackcat-console').glob('*.php')]:
    subprocess.run([php,'-n','-l',str(path)],check=True)
print('PHP LINT PASS; actual-renderer positive/negative contract tests are required by tests/00-static-check.sh')
