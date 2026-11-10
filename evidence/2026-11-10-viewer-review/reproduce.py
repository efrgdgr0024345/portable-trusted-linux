#!/usr/bin/env python3
"""Run local synthetic diagnostics on actual PHP; never use real viewer credentials."""
import base64,copy,json,os,re,subprocess,tempfile,hashlib
from pathlib import Path
import argparse
parser=argparse.ArgumentParser(description='Diagnostic reproduction only; findings are not security-acceptance passes.')
parser.add_argument('--php',required=True,type=Path)
parser.add_argument('--mbstring',required=True,type=Path)
parser.add_argument('--library-dir',type=Path)
parser.add_argument('--uploaded',type=Path)
parser.add_argument('--canonical',required=True,type=Path)
parser.add_argument('--index',required=True,type=Path)
parser.add_argument('--output',required=True,type=Path)
args=parser.parse_args()
PHP=str(args.php.resolve());MODULE=str(args.mbstring.resolve())
env=os.environ.copy()
if args.library_dir:env['LD_LIBRARY_PATH']=str(args.library_dir.resolve())
index=json.loads(args.index.read_text())
base=Path(tempfile.mkdtemp(prefix='blackcat-viewer-fixtures-'))

def make(source,label,project,status=200,builder=None):
 d=base/label;d.mkdir(exist_ok=True,mode=0o700);token=d/'fixture-token';token.write_text('SYNTHETIC_LOCAL_REVIEW_TOKEN');token.chmod(0o600)
 if builder is not None:
  (d/'project_cache').mkdir(exist_ok=True);(d/'project_cache/builder_status.json').write_text(json.dumps(builder))
 api=json.dumps({'encoding':'base64','content':base64.b64encode(json.dumps(project).encode()).decode(),'sha':'synthetic-fixture-blob'})
 constants=sorted(set(re.findall(r'\b(?:CURLOPT_\w+|CURLINFO_\w+)\b',source)))
 stub='\n'.join(f"define('{c}',{i+1});" for i,c in enumerate(constants))+f"\n$GLOBALS['review_response']=base64_decode('{base64.b64encode(api.encode()).decode()}');\n"
 stub+='''function curl_init($u){return new stdClass();}
function curl_setopt_array($c,$o){return true;}
function curl_exec($c){return $GLOBALS['review_response'];}
function curl_getinfo($c,$i){return '''+str(status)+''';}
function curl_error($c){return '';}
function curl_close($c){}
'''
 s=source.replace('declare(strict_types=1);','declare(strict_types=1);\n'+stub,1)
 s,n=re.subn(r"const TOKEN_FILE = '[^']*';","const TOKEN_FILE = '"+str(token)+"';",s);assert n==1
 s+='\n<?php echo "\\nREVIEW_RESULT=".json_encode(["project_ok"=>$project!==null,"candidate_enabled"=>$candidateEnabled,"release_enabled"=>$downloadEnabled,"http_status"=>http_response_code()]); ?>\n'
 f=d/'viewer.php';f.write_text(s);return f

def render(f,mbstring=True):
 args=[PHP,'-n']
 if mbstring:args+=['-d','extension='+MODULE]
 return subprocess.run(args+[str(f)],env=env,capture_output=True,text=True)

records={}
sources=[('repository',args.canonical)]
if args.uploaded:sources.insert(0,('uploaded',args.uploaded))
for name,path in sources:
 source=Path(path).read_text();rows={};r=render(make(source,name+'-current',index));assert r.returncode==0,r.stderr
 rows['current_index']={'progress_0_displayed':'<div class="label">Progress</div><div class="value">0%</div>' in r.stdout,'capability_section_present':'id="capabilities"' in r.stdout,'M15_displayed_as_planned':bool(re.search(r'<div class="date">M15</div><div>[^<]*</div><div class="status [^"]*">planned</div>',r.stdout)),'M15_json_status':next(m['status'] for m in index['milestones'] if m['id']=='M15'),'M15_json_merged_to_main':next(m.get('merged_to_main') for m in index['milestones'] if m['id']=='M15'),'top_level_m15_merged_to_main':index['m15']['merged_to_main'],'candidate_disabled':json.loads(r.stdout.split('REVIEW_RESULT=')[-1])['candidate_enabled']==False}
 r=render(make(source,name+'-empty',[]));assert r.returncode==0,r.stderr
 rows['empty_project_array']={'accepted':json.loads(r.stdout.split('REVIEW_RESULT=')[-1])['project_ok'],'green_live_badge':('LIVE / SYNCHRONISED' in r.stdout or 'LIVE STATUS FEED' in r.stdout)}
 c=copy.deepcopy(index);c['candidate']={'status':'test','filename':'test.iso','size_bytes':4096,'sha256':'a'*64,'source_commit':'b'*40,'build_run':'123','artifact_id':'456','artifact_name':'test-artifact','smoke_test':'passed','download_url':'https://example.com/unrelated-download'}
 r=render(make(source,name+'-wrong-url',c));assert r.returncode==0,r.stderr
 rows['mismatched_candidate_artifact_url']={'download_enabled':json.loads(r.stdout.split('REVIEW_RESULT=')[-1])['candidate_enabled'],'unrelated_url_rendered':'href="https://example.com/unrelated-download"' in r.stdout,'ready_label_displayed':'Ready for physical testing' in r.stdout}
 r=render(make(source,name+'-http-error',index,status=503));assert r.returncode==0,r.stderr
 rows['upstream_failure']={'http_status':json.loads(r.stdout.split('REVIEW_RESULT=')[-1])['http_status'],'no_live_badge':('LIVE / SYNCHRONISED' not in r.stdout and 'LIVE STATUS FEED' not in r.stdout),'no_active_candidate_link':'href="https://example.com/unrelated-download"' not in r.stdout}
 f=make(source,name+'-telemetry',index,builder={'token':'SYNTHETIC_SECRET_UNDER_TOKEN_KEY','message':'SYNTHETIC_CANARY_IN_GENERIC_MESSAGE','observed_at':'2000-01-01T00:00:00Z'})
 r=render(f);assert r.returncode==0,r.stderr
 rows['builder_telemetry']={'secret_key_filtered':'SYNTHETIC_SECRET_UNDER_TOKEN_KEY' not in r.stdout,'generic_message_value_displayed':'SYNTHETIC_CANARY_IN_GENERIC_MESSAGE' in r.stdout,'stale_status_accepted':'Status file available' in r.stdout}
 r=render(f,mbstring=False)
 rows['without_mbstring']={'exit_code':r.returncode,'undefined_mb_substr':'undefined function mb_substr' in r.stdout+r.stderr}
 rows['source']={'sha256':hashlib.sha256(Path(path).read_bytes()).hexdigest(),'inline_print_handler':'onclick="window.print()"' in source,'script_src_explicitly_allows_inline':bool(re.search(r"script-src[^;]*'unsafe-inline'",source))}
 records[name]=rows
args.output.write_text(json.dumps(records,indent=2)+'\n')
print('Synthetic fixture directory (temporary, for optional browser check):',base)
print(json.dumps(records,indent=2))
