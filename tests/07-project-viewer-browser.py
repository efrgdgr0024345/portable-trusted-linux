#!/usr/bin/env python3
"""Real CSP/print controls using local synthetic PHP fixtures; no hosting credentials."""
import importlib.util
import json
import os
from pathlib import Path
import re
import shutil
import socket
import subprocess
import tempfile
import time
from playwright.sync_api import sync_playwright

ROOT=Path(__file__).resolve().parents[1]
spec=importlib.util.spec_from_file_location('viewer_fixtures',ROOT/'tests/06-project-viewer-check.py')
m=importlib.util.module_from_spec(spec);spec.loader.exec_module(m)
if not m.PHP:raise SystemExit('PHP CLI required')
f=m.Fixture();f.write(m.routes(m.BASE))
# CLI test diagnostics are not part of the deployed document.
f.path.write_text(f.path.read_text().rsplit('\n<?php echo ',1)[0])
s=socket.socket();s.bind(('127.0.0.1',0));port=s.getsockname()[1];s.close()
chrome=os.environ.get('PTL_CHROMIUM_BINARY') or shutil.which('chromium') or shutil.which('google-chrome')
if not chrome:raise SystemExit('Chromium/Chrome required')
with tempfile.TemporaryFile(mode='w+') as log:
    proc=subprocess.Popen([m.PHP,'-n','-S',f'127.0.0.1:{port}','-t',str(f.public)],stdout=log,stderr=log,env=os.environ.copy())
    try:
        for _ in range(80):
            try:
                with socket.create_connection(('127.0.0.1',port),timeout=.2):break
            except OSError:time.sleep(.05)
        with sync_playwright() as p:
            browser=p.chromium.launch(executable_path=chrome,headless=True,args=['--no-sandbox'])
            page=browser.new_page(viewport={'width':1280,'height':1000});messages=[]
            page.on('console',lambda message:messages.append(message.text))
            page.route('**/*',lambda r:r.continue_() if r.request.url.startswith(f'http://127.0.0.1:{port}/') else r.abort())
            response=page.goto(f'http://127.0.0.1:{port}/project.php')
            assert response.status==200
            csp=response.headers['content-security-policy'];nonce=re.search(r"script-src 'nonce-([a-f0-9]+)'",csp).group(1)
            assert page.locator('script[nonce]').evaluate('(element) => element.nonce')==nonce
            assert "script-src-attr 'none'" in csp and "script-src 'unsafe-inline'" not in csp
            initial=page.evaluate('() => {window.__printed=false;window.__unsafe=false;window.__injected=false;window.print=()=>{window.__printed=true;};return window.__printed;}')
            assert initial is False
            page.get_by_role('button',name='Print / PDF').click();assert page.evaluate('window.__printed') is True
            assert not any('Content Security Policy' in line for line in messages)
            page.evaluate("() => {document.getElementById('viewer-print').setAttribute('onclick','window.__unsafe=true');}")
            page.get_by_role('button',name='Print / PDF').click();assert page.evaluate('window.__unsafe') is False
            page.evaluate("() => {const s=document.createElement('script');s.textContent='window.__injected=true';document.body.appendChild(s);}")
            assert page.evaluate('window.__injected') is False
            assert any('Content Security Policy' in line for line in messages)
            response2=page.reload();nonce2=re.search(r"script-src 'nonce-([a-f0-9]+)'",response2.headers['content-security-policy']).group(1)
            assert nonce!=nonce2
            assert 'no-store' in response2.headers['cache-control']
            page.set_viewport_size({'width':390,'height':844})
            page.evaluate('() => new Promise(resolve => requestAnimationFrame(() => requestAnimationFrame(resolve)))')
            assert page.evaluate('document.documentElement.scrollWidth <= window.innerWidth'), page.evaluate("JSON.stringify({scroll:document.documentElement.scrollWidth,inner:window.innerWidth,elements:[...document.querySelectorAll('body *')].filter(e=>e.scrollWidth>e.clientWidth+1).map(e=>({tag:e.tagName,cls:e.className,scroll:e.scrollWidth,client:e.clientWidth,text:e.textContent.slice(0,60)})).slice(0,12)})")
            screenshot=os.environ.get('PTL_VIEWER_SCREENSHOT')
            if screenshot:page.screenshot(path=screenshot)
            browser.close()
        result={'status':'VERIFIED','scope':'Local synthetic renderer browser controls only; not live hosting','valid_nonce_print_listener':'passed','inline_event_injection':'blocked','non_nonce_script_injection':'blocked','nonce_changes_each_response':'passed','no_store_html':'passed','mobile_width_390':'passed','live_deployment':'UNKNOWN'}
        output=os.environ.get('PTL_VIEWER_BROWSER_RESULT')
        if output:Path(output).write_text(json.dumps(result,indent=2)+'\n')
        print(json.dumps(result))
    finally:
        proc.terminate();proc.wait(timeout=5);f.close()
