#!/usr/bin/env python3
"""Lint integrated PHP and exercise evidence rendering without credentials/network."""
import base64
import json
import os
from pathlib import Path
import re
import shutil
import subprocess
import tempfile


def run(args, **kw):
    return subprocess.run(args, check=True, text=True, **kw)


with tempfile.TemporaryDirectory(prefix='bc-php-') as directory:
    work = Path(directory)
    php = shutil.which('php')
    env = os.environ.copy()
    if not php:
        deps = run(['apt-cache', 'depends', '--recurse', '--important', '--no-recommends', '--no-suggests', '--no-conflicts', '--no-breaks', '--no-replaces', '--no-enhances', 'php8.4-cli'], capture_output=True).stdout
        names = {s for s in deps.splitlines() if re.fullmatch(r'[a-z0-9][a-z0-9+.-]*(?::amd64)?', s)}
        installed = run(['dpkg-query', '-W', '-f=${binary:Package} ${db:Status-Status}\n'], capture_output=True).stdout
        installed = {s.split()[0].split(':')[0] for s in installed.splitlines() if s.endswith(' installed')}
        packages = sorted(p for p in names if p.split(':')[0] not in installed)
        run(['apt-get', 'download', *packages], cwd=work, timeout=180)
        root = work / 'tools'
        for deb in work.glob('*.deb'):
            run(['dpkg-deb', '-x', str(deb), str(root)])
        php = str(root / 'usr/bin/php8.4')
        env['LD_LIBRARY_PATH'] = str(root / 'usr/lib/x86_64-linux-gnu')
    for path in [Path('web/project.php'), *Path('apps/blackcat-console').glob('*.php')]:
        run([php, '-n', '-l', str(path)], env=env)
    source = Path('web/project.php').read_text()
    fixture = {'title': 'Black Cat', 'capabilities': [{'id': 'GUI', 'title': '<script>fixture</script>', 'stage': 'implemented-not-booted', 'scope': 'Exact image only'}], 'milestones': [{'id': 'GUI', 'title': 'Graphical evidence', 'status': 'implemented-not-booted'}], 'release': {'status': 'none', 'download_url': 'javascript:alert(1)'}}
    encoded = base64.b64encode(json.dumps(fixture).encode()).decode()
    replaced = source.replace('$result = fetchProject();', "$result = ['ok' => true, 'project' => json_decode(base64_decode('" + encoded + "'), true), 'sha' => 'fixture'];")
    assert replaced != source
    test = work / 'viewer-fixture.php'
    test.write_text(replaced)
    rendered = run([php, '-n', str(test)], env=env, capture_output=True).stdout
    assert '&lt;script&gt;fixture&lt;/script&gt;' in rendered
    assert '<script>fixture</script>' not in rendered
    assert 'implemented-not-booted' in rendered
    assert 'javascript:alert' not in rendered
    assert 'Capability by capability' in rendered
    assert 'class="label">Progress' not in rendered
    assert 'downloadbtn disabled' in rendered
    failed = source.replace('$result = fetchProject();', "$result = ['ok' => false, 'error' => 'Fixture unavailable'];")
    test.write_text(failed)
    rendered = run([php, '-n', str(test)], env=env, capture_output=True).stdout
    assert 'Fixture unavailable' in rendered
    assert 'href="javascript:' not in rendered
    print('REVIEW PHP LINT / ESCAPING / STATUS / FAIL-CLOSED FIXTURES: PASS')
