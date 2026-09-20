#!/usr/bin/env python3
"""Read-only Makephone preview. No PHP, credentials, listing, or arbitrary files.
A one-time upload accepts ONLY the exact SHA-256-pinned public asset bundle.
This is an HTTP development preview, not a production HTTPS deployment.
"""
import gzip
import hashlib
import hmac
import json
import os
from pathlib import Path
import threading
from http.server import BaseHTTPRequestHandler, HTTPServer
from socketserver import ThreadingMixIn
from urllib.parse import urlsplit

EXPECTED = '28a95a2c91b355ee886dec191ed21acc219452ad1a57b88a701e52da7578c0d8'
BUNDLE_SIZE = 17981
ASSET_HASHES = {
    'index.html': 'c3ea20535016d2290405eefc685961d42c29e1791ed4ee3b0d6599fb85885668',
    'enclosure.scad': 'e6b364456b2e05bf13ae6e2007ad0565394871e39423b88d22cfc0611d945852',
}
BASE = Path(__file__).resolve().parent
PUBLIC = BASE / 'public'
BUNDLE = BASE / 'assets.json.gz'
LOCK = threading.Lock()
ASSETS = None


def decode_bundle(raw):
    if len(raw) != BUNDLE_SIZE or not hmac.compare_digest(hashlib.sha256(raw).hexdigest(), EXPECTED):
        raise ValueError('Asset bundle does not match the authorised release')
    obj = json.loads(gzip.decompress(raw))
    if not isinstance(obj, dict) or set(obj) != set(ASSET_HASHES):
        raise ValueError('Unexpected asset manifest')
    result = {}
    for name, digest in ASSET_HASHES.items():
        if not isinstance(obj[name], str):
            raise ValueError('Invalid asset value')
        value = obj[name].encode('utf-8')
        if not hmac.compare_digest(hashlib.sha256(value).hexdigest(), digest):
            raise ValueError('Asset digest mismatch')
        result[name] = value
    return result


def persist(raw, values):
    PUBLIC.mkdir(mode=0o755, exist_ok=True)
    for name, value in values.items():
        target = PUBLIC / name
        if target.is_symlink():
            raise ValueError('Refusing a symlink')
        tmp = PUBLIC / (name + '.new')
        with tmp.open('xb') as handle:
            handle.write(value)
            handle.flush()
            os.fsync(handle.fileno())
        os.chmod(tmp, 0o644)
        os.replace(tmp, target)
    temp = BASE / 'assets.json.gz.new'
    with temp.open('xb') as handle:
        handle.write(raw)
    os.replace(temp, BUNDLE)


class Handler(BaseHTTPRequestHandler):
    server_version = 'BlackCatPreview/0.1'
    sys_version = ''
    protocol_version = 'HTTP/1.0'

    def setup(self):
        self.request.settimeout(8)
        super().setup()

    def log_message(self, fmt, *args):
        print('request completed', flush=True)

    def reply(self, status, body=b'', mime='text/plain; charset=utf-8', extra=None):
        self.send_response(status)
        self.send_header('Content-Type', mime)
        self.send_header('Content-Length', str(len(body)))
        self.send_header('Connection', 'close')
        self.send_header('Cache-Control', 'no-store')
        self.send_header('X-Content-Type-Options', 'nosniff')
        self.send_header('Referrer-Policy', 'no-referrer')
        self.send_header('X-Frame-Options', 'DENY')
        self.send_header('X-Robots-Tag', 'noindex, nofollow, noarchive')
        self.send_header('Permissions-Policy', 'camera=(), microphone=(), geolocation=()')
        self.send_header('Content-Security-Policy', "default-src 'none'; script-src 'unsafe-inline'; style-src 'unsafe-inline'; img-src data: blob:; connect-src 'none'; object-src 'none'; base-uri 'none'; form-action 'none'; frame-ancestors 'none'")
        for k, v in (extra or {}).items():
            self.send_header(k, v)
        self.end_headers()
        self.close_connection = True
        if self.command != 'HEAD':
            self.wfile.write(body)

    def do_GET(self):
        path = urlsplit(self.path).path
        if path in ('/', '/makephone'):
            self.reply(302, extra={'Location': '/makephone/'})
        elif path == '/makephone/health.json':
            value = {'service': 'makephone-static-preview', 'ready': ASSETS is not None,
                     'version': '0.1.0', 'transport': 'HTTP', 'bundle_sha256': EXPECTED,
                     'viewer_sha256': ASSET_HASHES['index.html']}
            self.reply(200, json.dumps(value).encode(), 'application/json')
        elif path == '/robots.txt':
            self.reply(200, b'User-agent: *\nDisallow: /\n')
        elif path in ('/makephone/', '/makephone/index.html', '/makephone/enclosure.scad'):
            if ASSETS is None:
                self.reply(503, b'Viewer deployment is not complete.\n')
                return
            name = 'enclosure.scad' if path.endswith('enclosure.scad') else 'index.html'
            mime = 'application/octet-stream' if name.endswith('.scad') else 'text/html; charset=utf-8'
            extra = {'Content-Disposition': 'attachment; filename="enclosure.scad"'} if name.endswith('.scad') else None
            self.reply(200, ASSETS[name], mime, extra)
        else:
            self.reply(404, b'Not found\n')

    do_HEAD = do_GET

    def do_POST(self):
        global ASSETS
        if self.path != '/makephone/_install' or ASSETS is not None:
            self.reply(404, b'Not found\n')
            return
        if self.headers.get('Transfer-Encoding') or self.headers.get_all('Content-Length') != [str(BUNDLE_SIZE)]:
            self.reply(400, b'Invalid package length\n')
            return
        try:
            raw = self.rfile.read(BUNDLE_SIZE)
            values = decode_bundle(raw)
            with LOCK:
                if ASSETS is not None:
                    self.reply(409, b'Already installed\n')
                    return
                persist(raw, values)
                ASSETS = values
            self.reply(201, b'Installed exact approved viewer assets. Upload is now disabled.\n')
        except (ValueError, OSError, json.JSONDecodeError):
            self.reply(400, b'Package not accepted\n')

    def do_PUT(self):
        self.reply(405, b'Method not allowed\n', extra={'Allow': 'GET, HEAD'})

    do_DELETE = do_PUT
    do_PATCH = do_PUT
    do_OPTIONS = do_PUT


class Server(ThreadingMixIn, HTTPServer):
    daemon_threads = True
    allow_reuse_address = True
    request_queue_size = 12
    slots = threading.BoundedSemaphore(12)

    def process_request(self, request, address):
        if not self.slots.acquire(blocking=False):
            self.shutdown_request(request)
            return
        try:
            super().process_request(request, address)
        except Exception:
            self.slots.release()
            raise

    def process_request_thread(self, request, address):
        try:
            super().process_request_thread(request, address)
        finally:
            self.slots.release()


if __name__ == '__main__':
    os.umask(0o077)
    if BUNDLE.is_file():
        ASSETS = decode_bundle(BUNDLE.read_bytes())
    print('Makephone read-only HTTP preview listening on port 8080', flush=True)
    Server(('0.0.0.0', 8080), Handler).serve_forever()
