<?php
declare(strict_types=1);
session_start();
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');

const D=__DIR__.'/data'; const P=D.'/projects'; const U=D.'/api-usage.jsonl';
function envv($k,$d=''){ $v=getenv($k); return ($v===false||$v==='')?$d:(string)$v; }
function esc($s){ return htmlspecialchars((string)$s,ENT_QUOTES,'UTF-8'); }
function pid($id){ if(!preg_match('/^[a-z0-9][a-z0-9-]{0,63}$/',$id)) throw new Exception('Bad project id'); return P.'/'.$id; }
function txt($p){ return is_file($p)?(string)file_get_contents($p):''; }
function wr($p,$s){ $t=$p.'.tmp'; if(file_put_contents($t,$s,LOCK_EX)===false||!rename($t,$p)) throw new Exception('Write failed'); }
function clean($s,$n=600){ $s=preg_replace('/\s+/',' ',trim((string)$s))??''; return mb_substr($s,0,$n); }
function slug($s){ $s=strtolower(clean($s,100)); $s=preg_replace('/[^a-z0-9]+/','-',$s)??''; return trim($s,'-')?:'project-'.date('YmdHis'); }
function defaults($id,$name){
  $m=['id'=>$id,'name'=>$name,'status'=>'Active','milestone'=>'Define the current milestone','next'=>'Define the smallest next action','blocker'=>'','scope_lock'=>true,'created'=>date(DATE_ATOM),'updated'=>date(DATE_ATOM)];
  if($id==='portable-trusted-linux'){$m['name']='USB / Black Cat Linux';$m['status']='Blocked';$m['milestone']='M13 — Build and fingerprint first Debian Live reference artifact';$m['next']="Clean the builder's leftover systemd-boot host integration state, then rerun preflight";$m['blocker']="Builder preflight refuses live-build while systemd-boot 'config-files' state remains";}
  return $m;
}
function ensure_project($id,$name){
  $d=pid($id); if(!is_dir($d)) mkdir($d,0700,true);
  if(!is_file($d.'/meta.json')) wr($d.'/meta.json',json_encode(defaults($id,$name),JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
  foreach(['BACKLOG.md'=>"# Backlog\n\n",'STATE.md'=>"# Current State\n\n",'AI_RULES.md'=>"# AI Rules\n\n1. Keep one current milestone and one next action.\n2. New ideas go to backlog unless the user explicitly changes focus.\n3. Do not silently promote backlog items into active work.\n"] as $f=>$b) if(!is_file($d.'/'.$f)) wr($d.'/'.$f,$b);
}
function meta($id){
  ensure_project($id,$id); $p=pid($id).'/meta.json'; $m=json_decode(txt($p),true); if(!is_array($m))$m=defaults($id,$id); $x=defaults($id,$m['name']??$id);
  foreach($x as $k=>$v) if(!array_key_exists($k,$m))$m[$k]=$v; save($id,$m); return $m;
}
function save($id,$m){ $m['updated']=date(DATE_ATOM); wr(pid($id).'/meta.json',json_encode($m,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)); }
function event($id,$type,$text){ file_put_contents(pid($id).'/activity.jsonl',json_encode(['time'=>date(DATE_ATOM),'type'=>$type,'text'=>clean($text,1200)],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n",FILE_APPEND|LOCK_EX); }
function activity($id){ $p=pid($id).'/activity.jsonl'; if(!is_file($p))return[]; $a=[]; foreach(file($p,FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES)?:[] as $l){$j=json_decode($l,true);if(is_array($j))$a[]=$j;} return array_slice(array_reverse($a),0,12); }
function projects(){ $a=[]; foreach(glob(P.'/*',GLOB_ONLYDIR)?:[] as $d)$a[]=meta(basename($d)); usort($a,fn($x,$y)=>(($x['status']==='Blocked')?0:1)<=>(($y['status']==='Blocked')?0:1) ?: strcmp($y['updated'],$x['updated'])); return $a; }
function spend(){ $month=date('Y-m');$c=0;$calls=0;if(is_file(U))foreach(file(U,FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES)?:[] as $l){$j=json_decode($l,true);if(is_array($j)&&str_starts_with($j['time']??'',$month)){$c+=(float)($j['estimated_cost_usd']??0);$calls++;}} return [$c,$calls,(float)envv('BLACKCAT_MONTHLY_API_BUDGET','30')]; }

@mkdir(D,0700,true); @mkdir(P,0700,true); ensure_project('portable-trusted-linux','USB / Black Cat Linux');
$pw=envv('BLACKCAT_CONSOLE_PASSWORD');
if(isset($_POST['login'])){if($pw!==''&&hash_equals($pw,(string)$_POST['login'])){$_SESSION['ok']=1;header('Location: focus.php');exit;}$err='Wrong password';}
if(isset($_GET['logout'])){session_destroy();header('Location: focus.php');exit;}
$locked=$pw==='';$ok=!empty($_SESSION['ok']);
if($ok&&$_SERVER['REQUEST_METHOD']==='POST'&&!isset($_POST['login'])){
  try{
    $a=$_POST['action']??'';$id=$_POST['id']??'';
    if($a==='create'){ $name=clean($_POST['name']??'',120); if($name==='')throw new Exception('Name required');$id=slug($name);ensure_project($id,$name);event($id,'created',$name); }
    elseif($a==='focus'){ $m=meta($id);$ms=clean($_POST['milestone']??'');$nx=clean($_POST['next']??'');if($ms===''||$nx==='')throw new Exception('Milestone and next action required');$m['milestone']=$ms;$m['next']=$nx;$m['blocker']='';$m['status']='Active';save($id,$m);event($id,'focus',"$ms | $nx"); }
    elseif($a==='done'){ $m=meta($id);$done=$m['next'];$note=clean($_POST['note']??'',800);file_put_contents(pid($id).'/STATE.md','- '.date('Y-m-d H:i').' — Completed: '.$done.($note?' — '.$note:'')."\n",FILE_APPEND|LOCK_EX);event($id,'done',$done.($note?' — '.$note:''));$m['next']='Choose the next smallest action for this milestone';$m['status']='Active';$m['blocker']='';save($id,$m); }
    elseif($a==='block'){ $m=meta($id);$b=clean($_POST['blocker']??'',800);if($b==='')throw new Exception('Blocker required');$m['blocker']=$b;$m['status']='Blocked';save($id,$m);event($id,'blocked',$b); }
    elseif($a==='unblock'){ $m=meta($id);event($id,'unblocked',$m['blocker']??'');$m['blocker']='';$m['status']='Active';save($id,$m); }
    elseif($a==='idea'){ $idea=clean($_POST['idea']??'',800);if($idea==='')throw new Exception('Idea required');file_put_contents(pid($id).'/BACKLOG.md','- ['.date('Y-m-d').'] '.$idea."\n",FILE_APPEND|LOCK_EX);event($id,'idea',$idea); }
    header('Location: focus.php?p='.rawurlencode($id));exit;
  }catch(Throwable $e){$err=$e->getMessage();}
}
$sel=($ok&&isset($_GET['p']))?meta((string)$_GET['p']):null; [$cost,$calls,$budget]=spend();
?>
<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover"><meta name="theme-color" content="#050805"><title>Black Cat Focus</title><style>
:root{color-scheme:dark;--g:#64ff8a;--m:#91a899;--l:#173c21;--p:#0a100b;--w:#ffd166}*{box-sizing:border-box}body{margin:0;background:#050805;color:#effff2;font-family:system-ui,-apple-system,sans-serif}.app{max-width:720px;margin:auto;padding:18px 14px 50px}.tag{color:var(--g);font-size:11px;letter-spacing:.14em}h1{margin:3px 0 15px}.card{background:var(--p);border:1px solid var(--l);border-radius:16px;padding:14px;margin:10px 0}.blocked{border-color:#75472d}.name{font-size:18px;font-weight:800}.small{font-size:12px;color:var(--m)}.line{margin-top:8px}.line b{color:var(--g)}.warn{color:var(--w)}.badge{display:inline-block;border:1px solid var(--l);border-radius:99px;padding:3px 7px;font-size:11px;margin:5px 0}.grid{display:grid;grid-template-columns:repeat(3,1fr);gap:7px}.metric{background:#071009;border:1px solid var(--l);border-radius:10px;padding:9px}.metric strong{display:block;color:var(--g);font-size:18px}button,input,textarea{font:inherit}button{border:1px solid #285d34;background:#102617;color:#fff;border-radius:11px;padding:10px 12px;font-weight:700}button.primary{background:var(--g);color:#041007}input,textarea{width:100%;background:#050805;color:#fff;border:1px solid #285d34;border-radius:10px;padding:10px;margin:5px 0 9px}.row{display:flex;gap:7px;flex-wrap:wrap}.lock{border-left:3px solid var(--g);padding-left:9px}.backlog{white-space:pre-wrap;max-height:220px;overflow:auto}.act{border-top:1px solid #14291a;padding:7px 0}.act:first-child{border-top:0}a{color:var(--g);text-decoration:none}@media(max-width:500px){.grid{grid-template-columns:1fr 1fr}}
</style></head><body><main class="app">
<?php if($locked):?><div class="card"><h1>Black Cat Focus</h1><p class="warn">Setup locked.</p><p>Set <code>BLACKCAT_CONSOLE_PASSWORD</code> on the server.</p></div>
<?php elseif(!$ok):?><form class="card" method="post"><h1>Black Cat Focus</h1><?php if(!empty($err)):?><p class="warn"><?=esc($err)?></p><?php endif;?><input type="password" name="login" placeholder="Password"><button class="primary">Unlock</button></form>
<?php else:?><div class="tag">FOCUS · FINISH · LEARN</div><h1>Black Cat Focus</h1><div class="small"><a href="focus.php">Projects</a> · <a href="?logout=1">Lock</a></div><?php if(!empty($err)):?><div class="card warn"><?=esc($err)?></div><?php endif;?>
<?php if(!$sel): $ps=projects();$active=count(array_filter($ps,fn($p)=>$p['status']==='Active'));$blocked=count(array_filter($ps,fn($p)=>$p['status']==='Blocked'));?>
<div class="card lock"><b>Scope lock:</b> one milestone + one next action. New ideas are parked in backlog and cannot change focus automatically.</div><div class="card"><div class="grid"><div class="metric"><strong><?=$active?></strong><span class="small">ACTIVE</span></div><div class="metric"><strong><?=$blocked?></strong><span class="small">BLOCKED</span></div><div class="metric"><strong>$<?=number_format($cost,2)?></strong><span class="small">API THIS MONTH</span></div></div><p class="small">$<?=number_format($cost,2)?> of $<?=number_format($budget,2)?> planning budget · <?=$calls?> API calls</p></div>
<?php foreach($ps as $p):?><a href="?p=<?=rawurlencode($p['id'])?>"><div class="card <?=$p['status']==='Blocked'?'blocked':''?>"><div class="name"><?=esc($p['name'])?></div><span class="badge"><?=esc($p['status'])?></span><div class="line"><b>Milestone:</b> <?=esc($p['milestone'])?></div><div class="line"><b>Next:</b> <?=esc($p['next'])?></div><?php if($p['blocker']):?><div class="line warn"><b>Blocked:</b> <?=esc($p['blocker'])?></div><?php endif;?></div></a><?php endforeach;?>
<form class="card" method="post"><b>New project</b><input name="name" placeholder="Project name"><input type="hidden" name="action" value="create"><button>Create</button></form>
<?php else:$id=$sel['id'];?>
<div class="card <?=$sel['status']==='Blocked'?'blocked':''?>"><div class="name"><?=esc($sel['name'])?></div><span class="badge"><?=esc($sel['status'])?></span><div class="line"><b>Milestone:</b> <?=esc($sel['milestone'])?></div><div class="line"><b>Next:</b> <?=esc($sel['next'])?></div><?php if($sel['blocker']):?><div class="line warn"><b>Blocker:</b> <?=esc($sel['blocker'])?></div><?php endif;?></div>
<form class="card" method="post"><b>Edit focus</b><input name="milestone" value="<?=esc($sel['milestone'])?>"><textarea name="next"><?=esc($sel['next'])?></textarea><input type="hidden" name="id" value="<?=esc($id)?>"><input type="hidden" name="action" value="focus"><button>Save focus</button></form>
<form class="card" method="post"><b>✓ Mark next action done</b><textarea name="note" placeholder="Optional result/evidence note"></textarea><input type="hidden" name="id" value="<?=esc($id)?>"><input type="hidden" name="action" value="done"><button class="primary">Done</button></form>
<?php if($sel['blocker']):?><form class="card" method="post"><input type="hidden" name="id" value="<?=esc($id)?>"><input type="hidden" name="action" value="unblock"><button>Clear blocker</button></form><?php else:?><form class="card" method="post"><b>⛔ Block current work</b><textarea name="blocker" placeholder="What is stopping the next action?"></textarea><input type="hidden" name="id" value="<?=esc($id)?>"><input type="hidden" name="action" value="block"><button>Mark blocked</button></form><?php endif;?>
<form class="card" method="post"><b>💡 Park an idea</b><p class="small">This records the idea without changing the active milestone or next action.</p><textarea name="idea" placeholder="Idea"></textarea><input type="hidden" name="id" value="<?=esc($id)?>"><input type="hidden" name="action" value="idea"><button>Park in backlog</button></form>
<div class="card"><b>Backlog</b><pre class="backlog"><?=esc(txt(pid($id).'/BACKLOG.md'))?></pre></div><div class="card"><b>Recent activity</b><?php foreach(activity($id) as $a):?><div class="act"><b><?=esc(strtoupper($a['type']))?></b><div><?=esc($a['text'])?></div><div class="small"><?=esc($a['time'])?></div></div><?php endforeach;?></div>
<?php endif;endif;?></main></body></html>
