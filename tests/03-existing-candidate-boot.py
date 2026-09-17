#!/usr/bin/env python3
"""Observe one preserved development ISO; no sudo, raw disks or guest network."""
import base64
import hashlib
import json
import os
from pathlib import Path
import re
import shutil
import socket
import struct
import subprocess
import tempfile
import time
import zlib

ISO = Path(os.environ.get(
    'BLACKCAT_EVIDENCE_ISO',
    '/home/github-runner/blackcat-builds/run-35179377345-attempt-1/live-image-amd64.hybrid.iso'))
SHA = os.environ.get(
    'BLACKCAT_EVIDENCE_SHA',
    'f498776780c543013e437161a878e22ca4fc40a66f47fc271d6fd6a964c29d75')
SOURCE = os.environ.get(
    'BLACKCAT_EVIDENCE_SOURCE',
    'ed5d80ac09c3f1650d67594242643d27efcf15e9')
SIZE = int(os.environ.get('BLACKCAT_EVIDENCE_SIZE', '770703360'))
BUILD_RUN = int(os.environ.get('BLACKCAT_EVIDENCE_BUILD_RUN', '35179377345'))
OUT = Path.home() / 'blackcat-evidence' / ('run-' + os.environ.get('GITHUB_RUN_ID', 'manual'))


def run(args, **kw):
    return subprocess.run(args, check=True, text=True, **kw)


def tools_local(work):
    """Extract authenticated APT downloads into a task directory, without installing."""
    root = work / 'tools'
    root.mkdir()
    debs = work / 'debs'
    debs.mkdir()
    wanted = ['qemu-system-x86', 'qemu-system-data', 'seabios', 'ovmf', 'tesseract-ocr', 'tesseract-ocr-eng']
    dep = run(['apt-cache', 'depends', '--recurse', '--important', '--no-recommends', '--no-suggests', '--no-conflicts', '--no-breaks', '--no-replaces', '--no-enhances', *wanted], capture_output=True).stdout
    names = {line.strip() for line in dep.splitlines() if re.fullmatch(r'[a-z0-9][a-z0-9+.-]*(?::amd64)?', line)}
    names.update(wanted)
    installed = run(['dpkg-query', '-W', '-f=${binary:Package} ${db:Status-Status}\n'], capture_output=True).stdout
    installed = {line.split()[0].split(':')[0] for line in installed.splitlines() if line.endswith(' installed')}
    wanted_missing = sorted(p for p in names if p.split(':')[0] not in installed)
    print('TASK_LOCAL_PACKAGES=' + json.dumps(wanted_missing), flush=True)
    # APT verifies downloads against its existing authenticated repository indexes.
    # No apt install, maintainer scripts, system package edits or privilege changes.
    if wanted_missing:
        run(['apt-get', 'download', *wanted_missing], cwd=debs, timeout=240)
    for deb in sorted(debs.glob('*.deb')):
        run(['dpkg-deb', '-x', str(deb), str(root)], timeout=30)
    env = os.environ.copy()
    env['LD_LIBRARY_PATH'] = ':'.join(str(root / p) for p in ['usr/lib/x86_64-linux-gnu', 'lib/x86_64-linux-gnu'])
    env['QEMU_MODULE_DIR'] = str(root / 'usr/lib/x86_64-linux-gnu/qemu')
    def exe(name):
        local = root / 'usr/bin' / name
        return str(local) if local.exists() else shutil.which(name)
    qemu, tess = exe('qemu-system-x86_64'), exe('tesseract')
    assert qemu and tess, 'Missing VM or OCR executable'
    run([qemu, '--version'], env=env)
    tdata = next(iter(root.glob('usr/share/tesseract-ocr/*/tessdata')), None)
    if tdata:
        env['TESSDATA_PREFIX'] = str(tdata)
    code = root / 'usr/share/OVMF/OVMF_CODE_4M.fd'
    variables = root / 'usr/share/OVMF/OVMF_VARS_4M.fd'
    assert code.is_file() and variables.is_file(), 'Matching OVMF pair unavailable'
    return root, env, qemu, tess, code, variables


def png_from_ppm(ppm, png):
    with ppm.open('rb') as f:
        assert f.readline().strip() == b'P6'
        line = f.readline()
        while line.startswith(b'#'):
            line = f.readline()
        width, height = map(int, line.split())
        assert f.readline().strip() == b'255'
        pixels = f.read()
    assert len(pixels) == width * height * 3
    def chunk(kind, data):
        return struct.pack('!I', len(data)) + kind + data + struct.pack('!I', zlib.crc32(kind + data))
    raw = b''.join(b'\x00' + pixels[y*width*3:(y+1)*width*3] for y in range(height))
    png.write_bytes(b'\x89PNG\r\n\x1a\n' + chunk(b'IHDR', struct.pack('!2I5B', width, height, 8, 2, 0, 0, 0)) + chunk(b'IDAT', zlib.compress(raw, 9)) + chunk(b'IEND', b''))


class Monitor:
    def __init__(self, path):
        self.sock = socket.socket(socket.AF_UNIX)
        self.sock.settimeout(20)
        self.sock.connect(str(path))
        self.io = self.sock.makefile('rwb')
        self.io.readline()
        self.command('qmp_capabilities')

    def command(self, execute, arguments=None):
        self.io.write((json.dumps({'execute': execute, 'arguments': arguments or {}}) + '\n').encode())
        self.io.flush()
        while True:
            result = json.loads(self.io.readline())
            if 'error' in result:
                raise RuntimeError(result['error'])
            if 'return' in result:
                return result['return']

    def close(self):
        self.io.close()
        self.sock.close()


def observe(work, env, qemu, tess, code, variables, toolroot, mode):
    vardisk = work / (mode + '-vars.fd')
    shutil.copyfile(variables, vardisk)
    qmp = work / (mode + '.sock')
    serial = OUT / (mode + '-serial.log')
    args = [qemu, '-machine', 'q35,accel=tcg', '-m', '2048', '-smp', '2',
            '-drive', 'if=pflash,format=raw,readonly=on,file=' + str(code),
            '-drive', 'if=pflash,format=raw,file=' + str(vardisk),
            '-cdrom', str(ISO), '-boot', 'd', '-nic', 'none', '-display', 'none',
            '-vga', 'none', '-device', 'VGA,romfile=' + str(toolroot / 'usr/share/seabios/vgabios-stdvga.bin'),
            '-device', 'qemu-xhci,id=xhci', '-device', 'usb-tablet,bus=xhci.0',
            '-serial', 'file:' + str(serial), '-qmp', 'unix:' + str(qmp) + ',server=on,wait=off',
            '-no-reboot', '-L', str(toolroot / 'usr/share/qemu')]
    last = None
    evidence = {}
    with (OUT / (mode + '-qemu.log')).open('w') as log:
        proc = subprocess.Popen(args, stdout=log, stderr=log, env=env)
        mon = None
        try:
            for _ in range(30):
                if proc.poll() is not None:
                    raise RuntimeError('QEMU exited: ' + (OUT / (mode + '-qemu.log')).read_text())
                if qmp.exists():
                    try:
                        mon = Monitor(qmp)
                        break
                    except (ConnectionRefusedError, FileNotFoundError):
                        pass
                time.sleep(1)
            if mon is None:
                raise RuntimeError('QEMU monitor never became ready')
            def capture(label):
                nonlocal last
                ppm, png = OUT / f'{mode}-{label}.ppm', OUT / f'{mode}-{label}.png'
                mon.command('screendump', {'filename': str(ppm)})
                png_from_ppm(ppm, png)
                last = png
                observed = run([tess, str(png), 'stdout'], env=env, capture_output=True).stdout
                (OUT / f'{mode}-{label}.txt').write_text(observed)
                print(f'FRAME_OCR {mode} {label}: ' + json.dumps(observed), flush=True)
                return png, observed

            def key(name, pause=0.35):
                mon.command('human-monitor-command', {'command-line': 'sendkey ' + name})
                time.sleep(pause)

            def locate_word(png, target):
                tsv = run([tess, str(png), 'stdout', '--psm', '6', 'tsv'],
                          env=env, capture_output=True).stdout
                for line in tsv.splitlines()[1:]:
                    fields = line.split('\t')
                    if len(fields) == 12 and fields[11].strip().casefold() == target.casefold():
                        left, top, width, height = map(int, fields[6:10])
                        return left + width // 2, top + height // 2
                raise RuntimeError(f'Visible control word not located: {target}')

            def click(x, y, pause=0.8):
                # The guest framebuffer is 1280x800. A USB tablet gives stable
                # absolute coordinates under headless QEMU.
                position = [
                    {'type': 'abs', 'data': {'axis': 'x', 'value': round(x * 32767 / 1279)}},
                    {'type': 'abs', 'data': {'axis': 'y', 'value': round(y * 32767 / 799)}},
                ]
                mon.command('input-send-event', {'events': position})
                time.sleep(0.2)
                mon.command('input-send-event', {'events': [
                    {'type': 'btn', 'data': {'button': 'left', 'down': True}}]})
                time.sleep(0.2)
                mon.command('input-send-event', {'events': [
                    {'type': 'btn', 'data': {'button': 'left', 'down': False}}]})
                time.sleep(pause)

            start = time.monotonic()
            selected = mode == 'gui'
            schedule = [5, 10, 20, 40, 80, 140, 220, 320] if mode == 'gui' else [5, 10, 20, 40, 80, 140, 220, 320, 400, 480]
            for elapsed in schedule:
                time.sleep(max(0, elapsed - (time.monotonic() - start)))
                png, text = capture(str(elapsed))
                if mode == 'check' and not selected and 'System Check' in text:
                    key('down')
                    key('ret')
                    selected = True
                if mode == 'gui' and 'DEVELOPMENT PREVIEW' in text:
                    # The first checkbox has been located in the actual frame.
                    click(*locate_word(png, 'Browser'))
                    selected_png, selected_text = capture('selected')
                    evidence['selected'] = selected_png
                    if 'Requested (NOT activated): Browser' not in selected_text:
                        return {'status': 'GUI_INTERACTION_FAILED', 'frame': selected_png.name,
                                'failure': 'checkbox summary did not show Browser'}

                    # UI source: six focusable checkboxes, then CLEAR SELECTION
                    # and OPEN DESKTOP. Avoid fragile full-screen OCR of button
                    # words; verify the actual resulting visible UI state.
                    for _ in range(6):
                        key('tab', 0.15)
                    key('spc', 0.8)
                    clear_png, clear_text = capture('cleared')
                    evidence['cleared'] = clear_png
                    if 'Requested (NOT activated): none' not in clear_text:
                        return {'status': 'GUI_INTERACTION_FAILED', 'frame': clear_png.name,
                                'failure': 'CLEAR action did not restore none; possible clipped/unfocusable button'}

                    key('tab', 0.3)
                    key('spc', 1.5)
                    desktop_png, desktop_text = capture('desktop')
                    evidence['desktop'] = desktop_png
                    if 'DEVELOPMENT PREVIEW' in desktop_text or 'OPEN DESKTOP' in desktop_text:
                        return {'status': 'GUI_INTERACTION_FAILED', 'frame': desktop_png.name,
                                'failure': 'preview remained after OPEN DESKTOP action'}
                    time.sleep(3)
                    stable_png, stable_text = capture('desktop-settled')
                    evidence['desktop_settled'] = stable_png
                    if 'DEVELOPMENT PREVIEW' in stable_text or 'OPEN DESKTOP' in stable_text:
                        return {'status': 'GUI_INTERACTION_FAILED', 'frame': stable_png.name,
                                'failure': 'preview reappeared or remained after settling'}
                    return {'status': 'GUI_INTERACTIONS_OBSERVED',
                            'frames': {name: frame.name for name, frame in evidence.items()},
                            'checkbox': 'Browser summary observed',
                            'clear': 'none summary observed',
                            'open_desktop': 'preview absent from two successive frames; screenshots retained'}
                if mode == 'check' and 'PHOTO THIS SCREEN' in text and 'Press Enter' in text:
                    evidence['settled'] = png
                    return {'status': 'QUICK_CHECK_SETTLED_PROMPT_OBSERVED', 'frame': png.name,
                            'selected_menu': selected, 'elapsed_seconds': elapsed}
            return {'status': 'EXPECTED_SCREEN_NOT_OBSERVED', 'frame': last.name if last else None}
        finally:
            if mon:
                try:
                    mon.command('quit')
                except Exception:
                    pass
                mon.close()
            try:
                proc.wait(timeout=10)
            except subprocess.TimeoutExpired:
                proc.terminate()
                proc.wait(timeout=10)
            print('QEMU_LOG ' + mode + ' ' + (OUT / (mode + '-qemu.log')).read_text(), flush=True)
            if last and not evidence:
                evidence['last'] = last
            for label, frame in evidence.items():
                # Only isolated guest framebuffers, never host files/secrets.
                print('FRAME_PNG_BASE64 ' + mode + '-' + label + ' ' + base64.b64encode(frame.read_bytes()).decode(), flush=True)


def observe_recovery(work, env, qemu, tess, toolroot):
    """Mask LightDM and require the automatic tty1 recovery interface."""
    kernel, initrd = work / 'vmlinuz', work / 'initrd.img'
    run(['xorriso', '-osirrox', 'on', '-indev', str(ISO), '-extract', '/live/vmlinuz', str(kernel)],
        capture_output=True)
    run(['xorriso', '-osirrox', 'on', '-indev', str(ISO), '-extract', '/live/initrd.img', str(initrd)],
        capture_output=True)
    qmp = work / 'recovery.sock'
    serial = OUT / 'recovery-serial.log'
    args = [qemu, '-machine', 'q35,accel=tcg', '-m', '2048', '-smp', '2',
            '-kernel', str(kernel), '-initrd', str(initrd), '-cdrom', str(ISO),
            '-append', ('boot=live components quiet console=tty0 '
                        'systemd.mask=lightdm.service blackcat.recovery_test=1'),
            '-boot', 'd', '-nic', 'none', '-display', 'none',
            '-vga', 'none', '-device', 'VGA,romfile=' + str(toolroot / 'usr/share/seabios/vgabios-stdvga.bin'),
            '-device', 'qemu-xhci,id=xhci', '-device', 'usb-tablet,bus=xhci.0',
            '-serial', 'file:' + str(serial), '-qmp', 'unix:' + str(qmp) + ',server=on,wait=off',
            '-no-reboot', '-L', str(toolroot / 'usr/share/qemu')]
    last = None
    with (OUT / 'recovery-qemu.log').open('w') as log:
        proc = subprocess.Popen(args, stdout=log, stderr=log, env=env)
        mon = None
        try:
            for _ in range(30):
                if proc.poll() is not None:
                    raise RuntimeError('Recovery QEMU exited early: ' + (OUT / 'recovery-qemu.log').read_text())
                if qmp.exists():
                    try:
                        mon = Monitor(qmp)
                        break
                    except (ConnectionRefusedError, FileNotFoundError):
                        pass
                time.sleep(1)
            if mon is None:
                raise RuntimeError('Recovery QEMU monitor never became ready')

            start = time.monotonic()
            for elapsed in [20, 40, 70, 100, 140]:
                time.sleep(max(0, elapsed - (time.monotonic() - start)))
                ppm, png = OUT / f'recovery-{elapsed}.ppm', OUT / f'recovery-{elapsed}.png'
                mon.command('screendump', {'filename': str(ppm)})
                png_from_ppm(ppm, png)
                last = png
                observed = run([tess, str(png), 'stdout', '--psm', '6'],
                               env=env, capture_output=True).stdout
                (OUT / f'recovery-{elapsed}.txt').write_text(observed)
                print(f'FRAME_OCR recovery {elapsed}: ' + json.dumps(observed), flush=True)
                folded = observed.casefold()
                if 'desktop recovery' in folded and 'export full report' in folded:
                    return {'status': 'AUTOMATIC_RECOVERY_MENU_OBSERVED',
                            'frame': png.name, 'elapsed_seconds': elapsed,
                            'injection': 'LightDM masked; short test-only readiness deadline',
                            'scope': 'VM failure-injection observation; not physical GPU compatibility'}
            return {'status': 'RECOVERY_MENU_NOT_OBSERVED',
                    'frame': last.name if last else None}
        finally:
            if mon:
                try:
                    mon.command('quit')
                except Exception:
                    pass
                mon.close()
            try:
                proc.wait(timeout=10)
            except subprocess.TimeoutExpired:
                proc.terminate()
                proc.wait(timeout=10)
            if last:
                print('FRAME_PNG_BASE64 recovery-last ' +
                      base64.b64encode(last.read_bytes()).decode(), flush=True)


def main():
    OUT.mkdir(parents=True, exist_ok=True)
    with ISO.open('rb') as f:
        if ISO.stat().st_size != SIZE or hashlib.file_digest(f, 'sha256').hexdigest() != SHA:
            raise RuntimeError('Candidate identity mismatch; refuse boot')
    report = {'iso_sha256': SHA, 'size_bytes': SIZE, 'source_commit': SOURCE,
              'build_run': BUILD_RUN, 'network': 'no guest NIC',
              'scope': 'development VM observations, no release approval'}
    try:
        with tempfile.TemporaryDirectory(prefix='bcvm-') as d:
            work = Path(d)
            root, env, qemu, tess, code, variables = tools_local(work)
            modes = [m.strip() for m in os.environ.get('BLACKCAT_EVIDENCE_MODES', 'gui,check').split(',') if m.strip()]
            if not modes or any(m not in {'gui', 'check', 'recovery'} for m in modes):
                raise RuntimeError('BLACKCAT_EVIDENCE_MODES must contain gui, check and/or recovery')
            for mode in modes:
                if mode == 'recovery':
                    report[mode] = observe_recovery(work, env, qemu, tess, root)
                else:
                    report[mode] = observe(work, env, qemu, tess, code, variables, root, mode)
    finally:
        (OUT / 'report.json').write_text(json.dumps(report, indent=2) + '\n')
        print('BOOT_REPORT=' + json.dumps(report), flush=True)
        print('EVIDENCE_DIR=' + str(OUT), flush=True)
    expected = {'gui': 'GUI_INTERACTIONS_OBSERVED',
                'check': 'QUICK_CHECK_SETTLED_PROMPT_OBSERVED',
                'recovery': 'AUTOMATIC_RECOVERY_MENU_OBSERVED'}
    for mode, wanted in expected.items():
        if mode in report and report[mode]['status'] != wanted:
            raise SystemExit(1)


if __name__ == '__main__':
    main()
