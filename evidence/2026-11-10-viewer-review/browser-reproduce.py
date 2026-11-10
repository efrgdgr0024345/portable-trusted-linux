#!/usr/bin/env python3
"""Check CSP with synthetic loopback fixtures and positive hook controls."""
import os,json,subprocess,time,socket
from pathlib import Path
from playwright.sync_api import sync_playwright
import argparse
parser=argparse.ArgumentParser(description='Local CSP diagnostic; serves synthetic fixtures on loopback only.')
parser.add_argument('--fixtures',required=True,type=Path)
parser.add_argument('--php',required=True,type=Path)
parser.add_argument('--mbstring',required=True,type=Path)
parser.add_argument('--library-dir',type=Path)
parser.add_argument('--chromium',default='/usr/bin/chromium')
parser.add_argument('--output',required=True,type=Path)
args=parser.parse_args()
root=args.fixtures.resolve();env=os.environ.copy()
if args.library_dir:env['LD_LIBRARY_PATH']=str(args.library_dir.resolve())

s=socket.socket();s.bind(('127.0.0.1',0));port=s.getsockname()[1];s.close()
with open(args.output.with_suffix('.server.log'),'w') as log:
 proc=subprocess.Popen([str(args.php.resolve()),'-n','-d','extension='+str(args.mbstring.resolve()),'-S',f'127.0.0.1:{port}','-t',str(root)],env=env,stdout=log,stderr=log)
 try:
  for i in range(40):
   try:
    with socket.create_connection(('127.0.0.1',port),timeout=.2):break
   except OSError:time.sleep(.05)
  result={}
  with sync_playwright() as p:
   browser=p.chromium.launch(executable_path=args.chromium,headless=True,args=['--no-sandbox'])
   for name in ['uploaded','repository']:
    if not (root/(name+'-current')/'viewer.php').is_file():continue
    page=browser.new_page();messages=[];page.on('console',lambda msg:messages.append(msg.text));page.route('**/*',lambda route:route.continue_() if route.request.url.startswith(f'http://127.0.0.1:{port}/') else route.abort())
    response=page.goto(f'http://127.0.0.1:{port}/{name}-current/viewer.php')
    initial=page.evaluate('() => { window.__reviewPrinted=false; window.print=()=>{window.__reviewPrinted=true;}; return window.__reviewPrinted; }')
    page.get_by_role('button',name='Print / PDF').click();clicked=page.evaluate('window.__reviewPrinted');page.evaluate('() => {window.print();}');control=page.evaluate('window.__reviewPrinted')
    result[name]={'http_status':response.status,'csp_header':response.headers.get('content-security-policy'),'test_hook_initial_value':initial,'inline_print_click_executed':clicked,'direct_test_hook_control_executed':control,'csp_violation_observed':any('Content Security Policy' in m and ('inline' in m.lower() or 'handler' in m.lower()) for m in messages),'console_messages':messages}
    page.close()
   browser.close()
  args.output.write_text(json.dumps(result,indent=2)+'\n');print(json.dumps(result,indent=2))
 finally:proc.terminate();proc.wait(timeout=5)
