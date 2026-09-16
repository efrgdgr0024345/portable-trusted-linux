<?php
/**
 * Black Cat Console MVP
 * Mobile-first project brain + AI coaching interface.
 *
 * Server requirements: PHP 8.1+, cURL, writable app directory.
 * Optional environment variables:
 *   OPENAI_API_KEY
 *   BLACKCAT_CONSOLE_PASSWORD
 *   BLACKCAT_COACH_MODEL   (default gpt-5.6-terra)
 *   BLACKCAT_CHAT_MODEL    (default gpt-5.6-sol)
 */
declare(strict_types=1);

session_start();
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
header("Permissions-Policy: camera=(), geolocation=(), microphone=(self)");

const APP_NAME = 'Black Cat Console';
const DATA_DIR = __DIR__ . '/data';
const PROJECTS_DIR = DATA_DIR . '/projects';

function envv(string $name, string $default = ''): string {
    $v = getenv($name);
    return ($v === false || $v === '') ? $default : (string)$v;
}
function json_out(array $data, int $status = 200): never {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}
function ensure_dirs(): void {
    foreach ([DATA_DIR, PROJECTS_DIR] as $d) {
        if (!is_dir($d) && !mkdir($d, 0700, true) && !is_dir($d)) {
            throw new RuntimeException('Could not create data directory: ' . $d);
        }
    }
    $deny = DATA_DIR . '/.htaccess';
    if (!file_exists($deny)) @file_put_contents($deny, "Require all denied\nDeny from all\n");
}
function slug(string $s): string {
    $s = strtolower(trim($s));
    $s = preg_replace('/[^a-z0-9]+/', '-', $s) ?? '';
    return trim($s, '-') ?: 'project-' . date('YmdHis');
}
function project_dir(string $id): string {
    if (!preg_match('/^[a-z0-9][a-z0-9-]{0,63}$/', $id)) throw new InvalidArgumentException('Invalid project id');
    return PROJECTS_DIR . '/' . $id;
}
function read_text(string $path): string { return is_file($path) ? (string)file_get_contents($path) : ''; }
function write_text(string $path, string $content): void {
    $tmp = $path . '.tmp-' . bin2hex(random_bytes(4));
    if (file_put_contents($tmp, $content, LOCK_EX) === false || !rename($tmp, $path)) throw new RuntimeException('Write failed');
}
function seed_project(): void {
    $d = project_dir('portable-trusted-linux');
    if (is_dir($d)) return;
    mkdir($d, 0700, true);
    $meta = [
        'id' => 'portable-trusted-linux',
        'name' => 'USB / Black Cat Linux',
        'status' => 'Prototype engineering',
        'next' => 'Continue the current evidence-backed build milestone',
        'repo' => 'efrgdgr0024345/portable-trusted-linux',
        'created' => date(DATE_ATOM),
        'updated' => date(DATE_ATOM),
    ];
    write_text($d . '/meta.json', json_encode($meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    $files = [
        'PROJECT.md' => "# Project\n\nBuild Black Cat as a trustworthy, portable, user-controlled Linux environment with explicit evidence and privacy boundaries.\n",
        'STATE.md' => "# Current State\n\nImported as the first Black Cat Console project. The GitHub repository remains the canonical engineering workspace.\n",
        'PLAN.md' => "# Plan\n\n1. Keep progressing the core Portable Trusted Linux milestones.\n2. Prototype Black Cat Console remotely.\n3. Preserve portability so the Console can later run locally from the USB system.\n",
        'DECISIONS.md' => "# Decisions\n\n- Local-first project state; remote AI is optional.\n- Human-readable Markdown is the durable project brain.\n- Deep-review mode is read-only by default.\n",
        'LESSONS_LEARNED.md' => "# Lessons Learned\n\n",
        'EVIDENCE.md' => "# Evidence\n\nClaims should be labelled VERIFIED, ATTESTED, OBSERVED, WARNING, UNKNOWN, NOT VERIFIABLE, or FAILED where applicable.\n",
        'BACKLOG.md' => "# Backlog\n\n- GitHub synchronisation\n- Deep-review workflow\n- Local model routing\n- Explicit outbound-data preview\n",
        'AI_RULES.md' => "# AI Rules\n\n1. Diagnose before rewriting.\n2. Preserve known-working behaviour.\n3. Separate facts, evidence, assumptions and suggestions.\n4. Prefer the smallest useful next action.\n5. Do not change production code during a review unless explicitly authorised.\n",
    ];
    foreach ($files as $name => $body) write_text($d . '/' . $name, $body);
}
function list_projects(): array {
    $out = [];
    foreach (glob(PROJECTS_DIR . '/*', GLOB_ONLYDIR) ?: [] as $d) {
        $m = json_decode(read_text($d . '/meta.json'), true);
        if (is_array($m)) $out[] = $m;
    }
    usort($out, fn($a,$b) => strcmp((string)($b['updated']??''),(string)($a['updated']??'')));
    return $out;
}
function get_project(string $id): array {
    $d = project_dir($id);
    if (!is_dir($d)) throw new RuntimeException('Project not found');
    $meta = json_decode(read_text($d . '/meta.json'), true) ?: [];
    $brain = [];
    foreach (['PROJECT.md','STATE.md','PLAN.md','DECISIONS.md','LESSONS_LEARNED.md','EVIDENCE.md','BACKLOG.md','AI_RULES.md'] as $f) $brain[$f] = read_text($d . '/' . $f);
    return ['meta'=>$meta,'brain'=>$brain];
}
function add_history(string $id, string $type, array $payload): void {
    $row = ['time'=>date(DATE_ATOM),'type'=>$type] + $payload;
    file_put_contents(project_dir($id) . '/history.jsonl', json_encode($row, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)."\n", FILE_APPEND|LOCK_EX);
}
function extract_output_text(array $r): string {
    $parts = [];
    foreach (($r['output'] ?? []) as $item) {
        if (($item['type'] ?? '') !== 'message') continue;
        foreach (($item['content'] ?? []) as $c) if (($c['type'] ?? '') === 'output_text' && isset($c['text'])) $parts[] = (string)$c['text'];
    }
    return trim(implode("\n", $parts));
}
function openai(array $payload): array {
    $key = envv('OPENAI_API_KEY');
    if ($key === '') throw new RuntimeException('OPENAI_API_KEY is not configured on the server.');
    $ch = curl_init('https://api.openai.com/v1/responses');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 180,
        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $key, 'Content-Type: application/json'],
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_SLASHES),
    ]);
    $raw = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    if ($raw === false) throw new RuntimeException('OpenAI connection failed: ' . $err);
    $j = json_decode($raw, true);
    if (!is_array($j)) throw new RuntimeException('OpenAI returned invalid JSON.');
    if ($code >= 400) throw new RuntimeException((string)($j['error']['message'] ?? ('OpenAI HTTP ' . $code)));
    return $j;
}
function local_coach(string $text): array {
    return [
        'intent' => 'general',
        'goal' => trim($text),
        'current_state' => 'Not explicitly stated',
        'constraints' => [],
        'success' => 'Define an observable result that proves the task is complete.',
        'improved_prompt' => "Goal: {$text}\n\nBefore acting, identify the current state, important constraints, the smallest useful next action, and the observable result that would prove success. Preserve known-working behaviour and clearly label assumptions.",
        'coaching_notes' => ['You stated the request naturally.', 'Adding current state, constraints and a success condition will make the AI easier to steer.'],
        'mode' => 'local-fallback'
    ];
}
function coach(string $id, string $text): array {
    $text = trim($text);
    if ($text === '') throw new InvalidArgumentException('Please enter or speak a request.');
    $project = get_project($id);
    if (envv('OPENAI_API_KEY') === '') return local_coach($text);
    $context = implode("\n\n", array_map(fn($k,$v)=>"## {$k}\n{$v}", array_keys($project['brain']), array_values($project['brain'])));
    $schema = [
        'type'=>'object','additionalProperties'=>false,
        'properties'=>[
            'intent'=>['type'=>'string'], 'goal'=>['type'=>'string'], 'current_state'=>['type'=>'string'],
            'constraints'=>['type'=>'array','items'=>['type'=>'string']], 'success'=>['type'=>'string'],
            'improved_prompt'=>['type'=>'string'], 'coaching_notes'=>['type'=>'array','items'=>['type'=>'string']]
        ],
        'required'=>['intent','goal','current_state','constraints','success','improved_prompt','coaching_notes']
    ];
    $r = openai([
        'model'=>envv('BLACKCAT_COACH_MODEL','gpt-5.6-terra'),
        'store'=>false,
        'reasoning'=>['effort'=>'medium'],
        'instructions'=>'You are the Black Cat AI Coach. Improve the user instruction without changing their intent. Extract Goal, Current State, Constraints and Success. Keep advice practical. Do not invent project facts. If information is missing, say so. The improved prompt should ask the downstream AI to preserve working behaviour, distinguish evidence from assumptions, and finish with a concrete next action when appropriate.',
        'input'=>"PROJECT CONTEXT:\n{$context}\n\nUSER INPUT:\n{$text}",
        'text'=>['format'=>['type'=>'json_schema','name'=>'blackcat_coach','strict'=>true,'schema'=>$schema]],
        'prompt_cache_key'=>'blackcat-coach-' . $id,
    ]);
    $parsed = json_decode(extract_output_text($r), true);
    if (!is_array($parsed)) throw new RuntimeException('Coach returned an unreadable result.');
    $parsed['mode'] = 'ai';
    $parsed['usage'] = $r['usage'] ?? null;
    add_history($id, 'coach', ['input'=>$text,'improved_prompt'=>$parsed['improved_prompt'] ?? '','usage'=>$parsed['usage']]);
    return $parsed;
}
function chat(string $id, string $prompt): array {
    $project = get_project($id);
    if (envv('OPENAI_API_KEY') === '') throw new RuntimeException('Configure OPENAI_API_KEY to send the approved prompt to AI. Coaching can still run locally.');
    $context = implode("\n\n", array_map(fn($k,$v)=>"## {$k}\n{$v}", array_keys($project['brain']), array_values($project['brain'])));
    $model = envv('BLACKCAT_CHAT_MODEL','gpt-5.6-sol');
    $r = openai([
        'model'=>$model,
        'store'=>false,
        'reasoning'=>['effort'=>'high'],
        'instructions'=>'You are Black Cat Project AI. Work from the supplied project brain. Preserve user agency. Distinguish known facts, evidence, assumptions, risks and suggestions. Prefer the smallest useful next action. Do not claim tests were run unless evidence is supplied. Do not silently rewrite project goals.',
        'input'=>"PROJECT BRAIN:\n{$context}\n\nAPPROVED USER REQUEST:\n{$prompt}",
        'prompt_cache_key'=>'blackcat-project-' . $id,
    ]);
    $answer = extract_output_text($r);
    add_history($id, 'chat', ['prompt'=>$prompt,'answer'=>$answer,'model'=>$model,'usage'=>$r['usage'] ?? null]);
    return ['answer'=>$answer,'model'=>$model,'usage'=>$r['usage'] ?? null];
}

try { ensure_dirs(); seed_project(); } catch (Throwable $e) { http_response_code(500); echo 'Black Cat Console setup error: ' . htmlspecialchars($e->getMessage()); exit; }

$password = envv('BLACKCAT_CONSOLE_PASSWORD');
if (isset($_POST['login_password'])) {
    if ($password !== '' && hash_equals($password, (string)$_POST['login_password'])) { $_SESSION['bc_auth'] = true; header('Location: ' . strtok($_SERVER['REQUEST_URI'],'?')); exit; }
    $login_error = 'Incorrect password.';
}
if (isset($_GET['logout'])) { session_destroy(); header('Location: ' . strtok($_SERVER['REQUEST_URI'],'?')); exit; }
$locked = $password === '';
$authed = !empty($_SESSION['bc_auth']);

if (isset($_GET['manifest'])) {
    header('Content-Type: application/manifest+json');
    echo json_encode(['name'=>APP_NAME,'short_name'=>'Black Cat','start_url'=>'./','display'=>'standalone','background_color'=>'#050805','theme_color'=>'#050805','icons'=>[]]); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['api'])) {
    if ($locked) json_out(['ok'=>false,'error'=>'Server setup is locked until BLACKCAT_CONSOLE_PASSWORD is configured.'],503);
    if (!$authed) json_out(['ok'=>false,'error'=>'Authentication required.'],401);
    $in = json_decode((string)file_get_contents('php://input'), true) ?: [];
    try {
        $action = (string)($in['action'] ?? '');
        if ($action === 'list') json_out(['ok'=>true,'projects'=>list_projects()]);
        if ($action === 'get') json_out(['ok'=>true,'project'=>get_project((string)$in['id'])]);
        if ($action === 'coach') json_out(['ok'=>true,'result'=>coach((string)$in['id'],(string)$in['text'])]);
        if ($action === 'chat') json_out(['ok'=>true,'result'=>chat((string)$in['id'],(string)$in['prompt'])]);
        if ($action === 'create') {
            $name = trim((string)($in['name'] ?? ''));
            if ($name === '') throw new InvalidArgumentException('Project name required');
            $id = slug($name); $d = project_dir($id); if (is_dir($d)) throw new RuntimeException('Project already exists'); mkdir($d,0700,true);
            $meta=['id'=>$id,'name'=>$name,'status'=>'New project','next'=>'Define goal and first success condition','repo'=>'','created'=>date(DATE_ATOM),'updated'=>date(DATE_ATOM)];
            write_text($d.'/meta.json',json_encode($meta,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
            foreach(['PROJECT.md'=>'# Project\n\nDefine the goal.\n','STATE.md'=>'# Current State\n\nNew project.\n','PLAN.md'=>'# Plan\n\n','DECISIONS.md'=>'# Decisions\n\n','LESSONS_LEARNED.md'=>'# Lessons Learned\n\n','EVIDENCE.md'=>'# Evidence\n\n','BACKLOG.md'=>'# Backlog\n\n','AI_RULES.md'=>'# AI Rules\n\n1. Preserve working behaviour.\n2. Separate evidence from assumptions.\n'] as $f=>$body) write_text($d.'/'.$f,str_replace('\\n',"\n",$body));
            json_out(['ok'=>true,'project'=>$meta]);
        }
        throw new RuntimeException('Unknown action');
    } catch (Throwable $e) { json_out(['ok'=>false,'error'=>$e->getMessage()],400); }
}
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover"><meta name="theme-color" content="#050805"><meta name="apple-mobile-web-app-capable" content="yes"><meta name="apple-mobile-web-app-status-bar-style" content="black-translucent"><link rel="manifest" href="?manifest=1"><title><?=htmlspecialchars(APP_NAME)?></title>
<style>
:root{color-scheme:dark;--bg:#050805;--panel:#0a100b;--line:#163b20;--green:#64ff8a;--muted:#8ba493;--text:#ecfff0;--warn:#ffd166}*{box-sizing:border-box}body{margin:0;background:radial-gradient(circle at top,#0b1b0f,var(--bg) 38%);color:var(--text);font-family:system-ui,-apple-system,Segoe UI,sans-serif;min-height:100vh}.app{max-width:760px;margin:auto;padding:calc(18px + env(safe-area-inset-top)) 14px calc(40px + env(safe-area-inset-bottom))}h1{font-size:24px;margin:0}.brand{display:flex;justify-content:space-between;align-items:center;margin-bottom:18px}.tag{font-size:12px;color:var(--green);letter-spacing:.14em}.card{background:rgba(10,16,11,.94);border:1px solid var(--line);border-radius:18px;padding:16px;margin:12px 0;box-shadow:0 12px 30px #0006}.project{cursor:pointer}.project:active{transform:scale(.99)}.name{font-weight:750;font-size:18px}.meta,.small{color:var(--muted);font-size:13px;margin-top:6px}.next{margin-top:10px}.btns{display:flex;gap:8px;flex-wrap:wrap;margin-top:12px}button,input,textarea{font:inherit}button{border:1px solid #245d31;background:#102617;color:var(--text);border-radius:12px;padding:11px 14px;font-weight:700}button.primary{background:var(--green);color:#041007;border-color:var(--green)}button.ghost{background:transparent}button.warn{border-color:#735f25;color:var(--warn)}textarea,input[type=text],input[type=password]{width:100%;background:#050805;border:1px solid #285635;color:var(--text);border-radius:14px;padding:13px;outline:none}textarea{min-height:130px;resize:vertical}.mic{width:64px;height:64px;border-radius:50%;font-size:26px}.mic.on{box-shadow:0 0 0 8px #64ff8a22;background:#183c22}.grid{display:grid;grid-template-columns:1fr 1fr;gap:8px}.kv{background:#071009;border:1px solid #15371d;padding:11px;border-radius:12px}.kv b{display:block;color:var(--green);font-size:11px;letter-spacing:.08em;margin-bottom:5px}.note{border-left:3px solid var(--green);padding-left:10px;color:#c9e9d0}.hidden{display:none!important}.topback{margin-bottom:10px}pre.answer{white-space:pre-wrap;font-family:inherit;line-height:1.5}.status{font-size:12px;color:var(--muted);margin:8px 0}.locked{max-width:460px;margin:12vh auto}.error{color:#ff9d9d}.warning{color:var(--warn)}
</style></head><body><main class="app">
<?php if ($locked): ?><div class="card locked"><h1>Black Cat Console</h1><p class="warning">Setup locked.</p><p>Set the server environment variable <code>BLACKCAT_CONSOLE_PASSWORD</code> before exposing this app. Optionally set <code>OPENAI_API_KEY</code> for AI coaching and project chat.</p><p class="small">No secrets should be placed in this PHP file or committed to GitHub.</p></div>
<?php elseif (!$authed): ?><form class="card locked" method="post"><h1>Black Cat Console</h1><p class="small">Private project control plane</p><?php if(!empty($login_error)):?><p class="error"><?=htmlspecialchars($login_error)?></p><?php endif;?><input type="password" name="login_password" placeholder="Console password" autocomplete="current-password"><div class="btns"><button class="primary" type="submit">Unlock</button></div></form>
<?php else: ?><div class="brand"><div><div class="tag">STEALTH · INTELLIGENCE · INDEPENDENCE</div><h1>Black Cat Console</h1></div><a href="?logout=1" class="small">Lock</a></div>
<section id="home"><div id="projects"></div><button onclick="newProject()">+ New project</button></section>
<section id="project" class="hidden"><button class="ghost topback" onclick="showHome()">← Projects</button><div class="card"><div class="name" id="pname"></div><div class="meta" id="pstatus"></div><div class="next" id="pnext"></div></div><div class="card"><h2>Talk to Black Cat</h2><p class="small">Speak naturally. The coach will improve the instruction before anything is sent to the project AI.</p><textarea id="input" placeholder="What are you thinking about, trying to build, fix, research, or decide?"></textarea><div class="btns"><button id="mic" class="mic" onclick="toggleMic()">🎤</button><button class="primary" onclick="runCoach()">Improve my request</button></div><div id="status" class="status"></div></div><div id="coach" class="card hidden"><h2>AI Coach</h2><div class="grid"><div class="kv"><b>GOAL</b><span id="goal"></span></div><div class="kv"><b>CURRENT STATE</b><span id="state"></span></div><div class="kv"><b>SUCCESS</b><span id="success"></span></div><div class="kv"><b>INTENT</b><span id="intent"></span></div></div><p><b>Improved request</b></p><textarea id="improved"></textarea><div id="coachnotes"></div><div class="btns"><button class="primary" onclick="sendImproved()">Send improved</button><button onclick="sendOriginal()">Send original</button><button class="ghost" onclick="document.getElementById('improved').focus()">Edit</button></div></div><div id="reply" class="card hidden"><h2>Project AI</h2><pre id="answer" class="answer"></pre></div><div class="card"><h2>Project brain</h2><div class="btns"><button onclick="showBrain('STATE.md')">State</button><button onclick="showBrain('PLAN.md')">Plan</button><button onclick="showBrain('EVIDENCE.md')">Evidence</button><button onclick="showBrain('LESSONS_LEARNED.md')">Lessons</button><button onclick="showBrain('BACKLOG.md')">Backlog</button></div><pre id="brain" class="answer small"></pre></div></section>
<script>
let current=null,brain={},recognition=null;
async function api(body){const r=await fetch('?api=1',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(body)});const j=await r.json();if(!j.ok)throw new Error(j.error||'Request failed');return j}
function esc(s){return String(s||'').replace(/[&<>"']/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]))}
async function load(){try{const j=await api({action:'list'});projects.innerHTML=j.projects.map(p=>`<div class="card project" onclick="openProject('${p.id}')"><div class="name">${esc(p.name)}</div><div class="meta">${esc(p.status)}</div><div class="next"><b>Next:</b> ${esc(p.next)}</div></div>`).join('')}catch(e){projects.innerHTML='<p class="error">'+esc(e.message)+'</p>'}}
async function openProject(id){const j=await api({action:'get',id});current=id;brain=j.project.brain;pname.textContent=j.project.meta.name;pstatus.textContent=j.project.meta.status;pnext.innerHTML='<b>Next:</b> '+esc(j.project.meta.next);home.classList.add('hidden');project.classList.remove('hidden');coach.classList.add('hidden');reply.classList.add('hidden');brainEl('STATE.md')}
function showHome(){project.classList.add('hidden');home.classList.remove('hidden');load()}
function brainEl(f){document.getElementById('brain').textContent=brain[f]||''} function showBrain(f){brainEl(f)}
async function runCoach(){const text=input.value.trim();if(!text)return;status.textContent='Improving your request…';try{const j=await api({action:'coach',id:current,text});const r=j.result;goal.textContent=r.goal;state.textContent=r.current_state;success.textContent=r.success;intent.textContent=r.intent;improved.value=r.improved_prompt;coachnotes.innerHTML=(r.coaching_notes||[]).map(n=>'<p class="note">'+esc(n)+'</p>').join('');coach.classList.remove('hidden');status.textContent=r.mode==='local-fallback'?'Local coaching used — OpenAI key is not configured.':'AI coaching complete.'}catch(e){status.textContent=e.message}}
async function sendPrompt(prompt){reply.classList.remove('hidden');answer.textContent='Thinking…';try{const j=await api({action:'chat',id:current,prompt});answer.textContent=j.result.answer}catch(e){answer.textContent='Error: '+e.message}}
function sendImproved(){sendPrompt(improved.value)} function sendOriginal(){sendPrompt(input.value)}
function toggleMic(){if(!('webkitSpeechRecognition'in window||'SpeechRecognition'in window)){status.textContent='Live browser transcription is not available here. You can still use the keyboard microphone/dictation.';return}if(recognition){recognition.stop();return}const SR=window.SpeechRecognition||window.webkitSpeechRecognition;recognition=new SR();recognition.continuous=true;recognition.interimResults=true;recognition.lang='en-AU';let finalText=input.value?input.value+' ':'';recognition.onstart=()=>{mic.classList.add('on');status.textContent='Listening…'};recognition.onresult=e=>{let interim='';for(let i=e.resultIndex;i<e.results.length;i++){if(e.results[i].isFinal)finalText+=e.results[i][0].transcript+' ';else interim+=e.results[i][0].transcript}input.value=finalText+interim};recognition.onend=()=>{mic.classList.remove('on');recognition=null;status.textContent='Voice captured. Review it, then improve the request.'};recognition.onerror=e=>{status.textContent='Microphone: '+e.error};recognition.start()}
async function newProject(){const name=prompt('Project name');if(!name)return;try{const j=await api({action:'create',name});await load();openProject(j.project.id)}catch(e){alert(e.message)}}
load();
</script><?php endif; ?></main></body></html>
