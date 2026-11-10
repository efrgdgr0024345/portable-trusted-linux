#!/usr/bin/env python3
"""Actual single-file PHP renderer tests; synthetic credentials and network only."""
import base64
import copy
from datetime import datetime,timedelta,timezone
import hashlib
import json
import os
from pathlib import Path
import re
import shutil
import subprocess
import tempfile
import unittest

ROOT=Path(__file__).resolve().parents[1]
PHP=os.environ.get('PTL_PHP_BINARY') or shutil.which('php')
SOURCE=(ROOT/'web/project.php').read_text()
NOW=datetime.now(timezone.utc)
STAMP=lambda delta: (NOW+timedelta(seconds=delta)).strftime('%Y-%m-%dT%H:%M:%SZ')
BASE=json.loads((ROOT/'project_content.json').read_text())
BASE['viewer_schema_version']='blackcat-project-index-v1'
for m in BASE['milestones']:
    if m['id']=='M15':m['merged_to_main']=True
BASE['m15']['merged_to_main']=True
REPO='efrgdgr0024345/portable-trusted-linux'
FEED='/contents/project_content.json?ref=codex%2Fremove-chroot-systemd-boot'
MAIN='/contents/project_content.json?ref=main'
SRC='b'*40


def api_content(project):
    data=json.dumps(project,ensure_ascii=False,separators=(',',':')).encode()
    return {'encoding':'base64','content':base64.b64encode(data).decode(),'sha':hashlib.sha1(b'blob '+str(len(data)).encode()+b'\0'+data).hexdigest()}


def candidate():
    return {'status':'test','filename':'test.iso','size_bytes':4096,'sha256':'a'*64,'source_commit':SRC,
            'build_run':'123','artifact_id':'456','artifact_name':'synthetic-test','artifact_sha256':'c'*64,
            'smoke_test':'passed','download_url':f'https://github.com/{REPO}/actions/runs/123/artifacts/456'}


def release():
    return {'status':'approved','filename':'test.iso','size_bytes':4096,'sha256':'a'*64,'source_commit':SRC,
            'build_run':'123','release_id':'789','asset_id':'987','tag':'v-test','smoke_test':'passed',
            'download_url':f'https://github.com/{REPO}/releases/download/v-test/test.iso',
            'authentication':{'status':'VERIFIED','manifest_sha256':'d'*64,'public_key_spki_sha256':'e'*64}}


def routes(project,main=None):
    r={FEED:{'status':200,'data':api_content(project),'etag':'"feed-test"'},MAIN:{'status':200,'data':api_content(project if main is None else main),'etag':'"main-test"'}}
    r['/actions/runs/123']={'status':200,'data':{'id':123,'status':'completed','conclusion':'success','head_sha':SRC,'repository':{'full_name':REPO}}}
    r['/actions/artifacts/456']={'status':200,'data':{'id':456,'name':'synthetic-test','size_in_bytes':5120,'expired':False,'expires_at':STAMP(3600),'digest':'sha256:'+'c'*64,'workflow_run':{'id':123,'head_sha':SRC}}}
    r['/releases/789']={'status':200,'data':{'id':789,'tag_name':'v-test','draft':False,'prerelease':False,'assets':[{'id':987,'name':'test.iso','state':'uploaded','size':4096,'digest':'sha256:'+'a'*64,'browser_download_url':f'https://github.com/{REPO}/releases/download/v-test/test.iso'}]}}
    r['/commits/v-test']={'status':200,'data':{'sha':SRC}}
    return r


class Fixture:
    def __init__(self):
        self.tmp=tempfile.TemporaryDirectory(prefix='blackcat-viewer-test-')
        self.root=Path(self.tmp.name);self.public=self.root/'public';self.private=self.root/'private'
        self.public.mkdir(mode=0o700);self.private.mkdir(mode=0o700)
        self.token=self.private/'token';self.token.write_text('SYNTHETIC_VIEWER_TEST_TOKEN');self.token.chmod(0o600)
        self.status=self.private/'builder_status.json';self.path=self.public/'project.php'
    def close(self):self.tmp.cleanup()
    def write(self,routes_,token=None,status=None):
        if status is not None:self.status.write_text(json.dumps(status));self.status.chmod(0o600)
        constants=sorted(set(re.findall(r'\b(?:CURLOPT_\w+|CURLINFO_\w+)\b',SOURCE)))
        stub='\n'.join(f"define('{c}',{i+1});" for i,c in enumerate(constants))
        data=base64.b64encode(json.dumps(routes_).encode()).decode()
        stub+=f"\n$GLOBALS['routes']=json_decode(base64_decode('{data}'),true);\n$GLOBALS['requests']=[];\n$_SERVER['DOCUMENT_ROOT']={json.dumps(str(self.public))};\n"
        stub+='''
function curl_init($url){return (object)['url'=>$url,'options'=>[],'status'=>0];}
function curl_setopt_array($c,$o){$c->options=$o;return true;}
function curl_exec($c){
 $prefix='https://api.github.com/repos/efrgdgr0024345/portable-trusted-linux';
 if (!str_starts_with($c->url,$prefix.'/')) throw new RuntimeException('Unexpected destination');
 $path=substr($c->url,strlen($prefix));$o=$c->options;
 $GLOBALS['requests'][]=['path'=>$path,'follow'=>$o[CURLOPT_FOLLOWLOCATION]??null,'tls_peer'=>$o[CURLOPT_SSL_VERIFYPEER]??null,'tls_host'=>$o[CURLOPT_SSL_VERIFYHOST]??null,'conditional'=>count(array_filter($o[CURLOPT_HTTPHEADER],fn($h)=>str_starts_with($h,'If-None-Match:'))) > 0];
 $r=$GLOBALS['routes'][$path]??['status'=>404,'data'=>['message'=>'synthetic route missing']];
 $c->status=$r['status'];if ($c->status===0)return false;
 $header=$o[CURLOPT_HEADERFUNCTION];$header($c,'HTTP/1.1 '.$c->status."\\r\\n");
 if (isset($r['etag']))$header($c,'ETag: '.$r['etag']."\\r\\n");
 $body=$r['body']??json_encode($r['data']??[]);$write=$o[CURLOPT_WRITEFUNCTION];
 if ($write($c,$body)!==strlen($body))return false;
 return true;
}
function curl_getinfo($c,$i){return $c->status;}
function curl_close($c){}
'''
        source=SOURCE.replace('declare(strict_types=1);','declare(strict_types=1);\n'+stub,1)
        source,n=re.subn(r"const TOKEN_FILE = '[^']*';","const TOKEN_FILE = '"+str(token or self.token)+"';",source);assert n==1
        source,n=re.subn(r"const BUILDER_STATUS_FILE = '[^']*';","const BUILDER_STATUS_FILE = '"+str(self.status)+"';",source);assert n==1
        source+='''\n<?php echo "\\nVIEWER_TEST_RESULT=".json_encode(['project_ok'=>$project!==null,'publication_ok'=>$publicationOkay,'main_match'=>$result['main_match']??false,'candidate_enabled'=>$candidateEnabled,'candidate_reason'=>$candidateCheck['reason'],'release_enabled'=>$downloadEnabled,'release_reason'=>$releaseCheck['reason'],'telemetry'=>$builderStatus,'http_status'=>http_response_code(),'requests'=>$GLOBALS['requests']]); ?>\n'''
        self.path.write_text(source)
    def run(self,routes_,**kw):
        self.write(routes_,**kw)
        p=subprocess.run([PHP,'-n',str(self.path)],capture_output=True,text=True,env=os.environ.copy())
        if p.returncode:raise AssertionError(p.stderr+p.stdout[-1000:])
        html,data=p.stdout.rsplit('VIEWER_TEST_RESULT=',1)
        return html,json.loads(data)


@unittest.skipUnless(PHP,'PHP CLI required; CI installs it explicitly')
class ViewerTests(unittest.TestCase):
    def setUp(self):self.f=Fixture();self.addCleanup(self.f.close)
    def project(self,kind=None):
        d=copy.deepcopy(BASE)
        if kind:d[kind]=candidate() if kind=='candidate' else release()
        return d
    def test_current_schema_status_and_transport(self):
        html,d=self.f.run(routes(BASE));self.assertTrue(d['project_ok']);self.assertTrue(d['publication_ok'])
        self.assertFalse(d['candidate_enabled']);self.assertFalse(d['release_enabled'])
        self.assertIn('development-verified',html);self.assertIn('Capability by capability',html)
        self.assertNotIn('LIVE / SYNCHRONISED',html);self.assertNotIn('class="label">Progress',html)
        for request in d['requests']:
            self.assertIs(request['follow'],False);self.assertIs(request['tls_peer'],True);self.assertEqual(request['tls_host'],2)
    def test_malformed_empty_and_incomplete_project(self):
        for project in ([],{},[BASE],{'title':'only'},dict(BASE,sections='wrong'),dict(BASE,milestones=[None]),dict(BASE,summary=['wrong']),dict(BASE,viewer_schema_version='unsupported')):
            with self.subTest(project=str(project)[:60]):
                html,d=self.f.run(routes(project));self.assertFalse(d['project_ok']);self.assertEqual(d['http_status'],503);self.assertFalse(d['candidate_enabled']);self.assertNotIn('id="checks"',html)
    def test_payload_blob_and_encoding_binding(self):
        for key,value in [('sha','a'*40),('encoding','raw'),('content','%%%')]:
            with self.subTest(key=key):
                r=routes(BASE);r[FEED]['data'][key]=value;_,d=self.f.run(r);self.assertFalse(d['project_ok'])
    def test_malformed_upstream_and_project_json(self):
        r=routes(BASE);r[FEED]={'status':200,'body':'not-json'};_,d=self.f.run(r);self.assertFalse(d['project_ok'])
        raw=b'{';r=routes(BASE);r[FEED]['data']={'encoding':'base64','content':base64.b64encode(raw).decode(),'sha':hashlib.sha1(b'blob 1\0'+raw).hexdigest()}
        _,d=self.f.run(r);self.assertFalse(d['project_ok']);self.assertEqual(d['http_status'],503)
    def test_response_and_decoded_size_limits(self):
        r=routes(BASE);r[FEED]={'status':200,'body':'x'*(2097152+1)};_,d=self.f.run(r);self.assertFalse(d['project_ok'])
        p=dict(BASE,summary='x'*(1048576+1));_,d=self.f.run(routes(p));self.assertFalse(d['project_ok'])
    def test_main_mismatch_and_unavailable(self):
        p=self.project('candidate')
        for main in [dict(p,version='different'),None]:
            r=routes(p,main); 
            if main is None:r[MAIN]={'status':403,'data':{'message':'synthetic denied'}}
            _,d=self.f.run(r);self.assertTrue(d['project_ok']);self.assertFalse(d['publication_ok']);self.assertFalse(d['candidate_enabled'])
    def test_conflicting_merge_state_and_unknown_status_preservation(self):
        p=self.project('candidate')
        for m in p['milestones']:
            if m['id']=='M15':m['merged_to_main']=False
        _,d=self.f.run(routes(p));self.assertFalse(d['publication_ok']);self.assertFalse(d['candidate_enabled'])
        p=copy.deepcopy(BASE);p['milestones'][0]['status']='unrecognized-status-UNKNOWN'
        html,d=self.f.run(routes(p));self.assertTrue(d['project_ok']);self.assertTrue('unrecognized-status-UNKNOWN' in html, 'Unknown milestone status must be preserved exactly')
    def test_escape_and_non_https_document(self):
        p=copy.deepcopy(BASE);p['title']='<script>synthetic</script>';p['capabilities'][0]['title']='<script>capability</script>';p['documents'][0]['url']='javascript:alert(1)'
        html,_=self.f.run(routes(p));self.assertIn('&lt;script&gt;synthetic&lt;/script&gt;',html);self.assertNotIn('<script>synthetic</script>',html);self.assertIn('&lt;script&gt;capability&lt;/script&gt;',html);self.assertNotIn('href="javascript:',html)
    def test_valid_candidate_distinguishes_zip_and_iso(self):
        p=self.project('candidate');html,d=self.f.run(routes(p));self.assertTrue(d['candidate_enabled']);self.assertIn('Metadata checks VERIFIED',html);self.assertIn('href="'+candidate()['download_url']+'"',html);self.assertNotIn('Ready for physical testing',html)
    def test_candidate_wrong_url_run_or_artifact(self):
        p=self.project('candidate')
        for url in ['https://example.com/wrong',candidate()['download_url']+'?wrong=1',candidate()['download_url'].replace('/123/','/124/'),candidate()['download_url'].replace('/456','/457'),candidate()['download_url'].replace('github.com/','github.com.evil.invalid/'),'https://name:pass@github.com/'+REPO+'/actions/runs/123/artifacts/456']:
            with self.subTest(url=url):
                p['candidate']['download_url']=url;html,d=self.f.run(routes(p));self.assertFalse(d['candidate_enabled']);self.assertNotIn('href="'+url+'"',html);self.assertFalse(any('/actions/' in r['path'] for r in d['requests']))
    def test_candidate_incomplete_types_states_and_zip_digest(self):
        for key,value in [('sha256','bad'),('source_commit','bad'),('build_run','0'),('artifact_id','1/2'),('size_bytes','4096'),('smoke_test','pending'),('artifact_sha256',''),('filename','../test.iso'),('status','unexpected')]:
            with self.subTest(key=key):
                p=self.project('candidate');p['candidate'][key]=value;_,d=self.f.run(routes(p));self.assertFalse(d['candidate_enabled'])
    def test_candidate_artifact_identity_expiry_and_api_denial(self):
        mutations=[('expired',True),('expires_at',STAMP(-1)),('expires_at','2026-02-30T00:00:00Z'),('expires_at','not-a-date'),('name','wrong'),('id',999),('digest','sha256:'+'f'*64),('workflow_run',{'id':124,'head_sha':SRC}),('workflow_run',{'id':123,'head_sha':'f'*40})]
        for key,value in mutations:
            with self.subTest(key=key,value=value):
                p=self.project('candidate');r=routes(p);r['/actions/artifacts/456']['data'][key]=value;_,d=self.f.run(r);self.assertFalse(d['candidate_enabled'])
        for status in [404,403,429,500,0]:
            with self.subTest(status=status):
                p=self.project('candidate');r=routes(p);r['/actions/artifacts/456']={'status':status};_,d=self.f.run(r);self.assertFalse(d['candidate_enabled'])
    def test_run_identity_and_result(self):
        for key,value in [('head_sha','f'*40),('conclusion','failure'),('status','in_progress'),('repository',{'full_name':'someone/else'}),('id',124)]:
            with self.subTest(key=key):
                p=self.project('candidate');r=routes(p);r['/actions/runs/123']['data'][key]=value;_,d=self.f.run(r);self.assertFalse(d['candidate_enabled'])
    def test_valid_release_and_negative_publisher_fields(self):
        p=self.project('release');_,d=self.f.run(routes(p));self.assertTrue(d['release_enabled'])
        for key,value in [('status','unexpected'),('asset_id',''),('download_url','https://example.com/wrong'),('authentication',{}),('smoke_test','pending'),('size_bytes',0)]:
            with self.subTest(key=key):
                p=self.project('release');p['release'][key]=value;_,d=self.f.run(routes(p));self.assertFalse(d['release_enabled'])
    def test_release_asset_tag_and_visibility_binding(self):
        for key,value in [('draft',True),('prerelease',True),('tag_name','wrong')]:
            with self.subTest(key=key):
                p=self.project('release');r=routes(p);r['/releases/789']['data'][key]=value;_,d=self.f.run(r);self.assertFalse(d['release_enabled'])
        for key,value in [('id',999),('size',4097),('digest','sha256:'+'f'*64),('browser_download_url','https://example.com/wrong'),('name','wrong.iso'),('state','deleted')]:
            with self.subTest(key=key):
                p=self.project('release');r=routes(p);r['/releases/789']['data']['assets'][0][key]=value;_,d=self.f.run(r);self.assertFalse(d['release_enabled'])
        p=self.project('release');r=routes(p);r['/commits/v-test']['data']['sha']='f'*40;_,d=self.f.run(r);self.assertFalse(d['release_enabled'])
    def test_unknown_telemetry_is_allowlisted_without_mbstring(self):
        s={'schema':'blackcat-public-telemetry-v1','observed_at':STAMP(-10),'runner_status':'online','disk_free_bytes':4096,'last_build_run':'123','message':'SYNTHETIC_CANARY','token':'SYNTHETIC_CANARY','hostname':'SYNTHETIC_CANARY'}
        html,d=self.f.run(routes(BASE),status=s);self.assertEqual(d['telemetry']['status'],'OBSERVED');self.assertNotIn('SYNTHETIC_CANARY',html)
        s['runner_status']='SYNTHETIC_CANARY';html,d=self.f.run(routes(BASE),status=s);self.assertNotIn('SYNTHETIC_CANARY',html)
    def test_stale_future_and_invalid_telemetry(self):
        for observed,expected in [(STAMP(-901),'WARNING'),(STAMP(500),'UNKNOWN'),('yesterday','UNKNOWN')]:
            with self.subTest(observed=observed):
                html,d=self.f.run(routes(BASE),status={'schema':'blackcat-public-telemetry-v1','observed_at':observed,'runner_status':'online'});self.assertEqual(d['telemetry']['status'],expected)
    def test_unsafe_telemetry_permissions(self):
        self.f.write(routes(BASE),status={'schema':'blackcat-public-telemetry-v1','observed_at':STAMP(-1),'runner_status':'online'});self.f.status.chmod(0o644)
        _,d=self.f.run(routes(BASE));self.assertEqual(d['telemetry']['status'],'UNKNOWN')
    def test_credential_missing_permissions_symlink_and_public_location(self):
        self.f.token.chmod(0o644);_,d=self.f.run(routes(BASE));self.assertFalse(d['project_ok']);self.assertEqual(d['requests'],[])
        self.f.token.chmod(0o600);link=self.f.private/'linked';link.symlink_to(self.f.token);_,d=self.f.run(routes(BASE),token=link);self.assertFalse(d['project_ok'])
        public=self.f.public/'bad-token';public.write_text('SYNTHETIC_TOKEN');public.chmod(0o600);_,d=self.f.run(routes(BASE),token=public);self.assertFalse(d['project_ok'])
        self.f.token.unlink();_,d=self.f.run(routes(BASE));self.assertFalse(d['project_ok'])
    def test_invalid_token_contents_and_private_directory(self):
        for token in ['', 'x'*4097, 'bad\x00token', 'bad\ntoken', 'bad token']:
            with self.subTest(kind=repr(token[:15])):
                self.f.token.write_text(token);_,d=self.f.run(routes(BASE));self.assertFalse(d['project_ok']);self.assertEqual(d['requests'],[])
        self.f.token.write_text('SYNTHETIC_TOKEN');self.f.private.chmod(0o755)
        _,d=self.f.run(routes(BASE));self.assertFalse(d['project_ok'])
        self.f.private.chmod(0o700)
        linked=self.f.root/'linked-private';linked.symlink_to(self.f.private,target_is_directory=True)
        _,d=self.f.run(routes(BASE),token=linked/'token');self.assertFalse(d['project_ok'])
    def test_etag_revalidation_requires_private_cache_and_no_stale_fallback(self):
        r=routes(BASE);_,d=self.f.run(r);self.assertTrue(d['project_ok'])
        r[FEED]={'status':304};r[MAIN]={'status':304};_,d=self.f.run(r);self.assertTrue(d['project_ok']);self.assertTrue(all(x['conditional'] for x in d['requests']))
        for status in [403,404,429,500,0]:
            with self.subTest(status=status):
                r[FEED]={'status':status};_,d=self.f.run(r);self.assertFalse(d['project_ok']);self.assertEqual(d['http_status'],503)
        for f in (self.f.private/'viewer_http_cache').glob('*.json'):f.chmod(0o644)
        r[FEED]={'status':304};_,d=self.f.run(r);self.assertFalse(d['project_ok'])
    def test_304_without_cache_refuses(self):
        r=routes(BASE);r[FEED]={'status':304};_,d=self.f.run(r);self.assertFalse(d['project_ok'])

if __name__=='__main__':unittest.main(verbosity=2)
