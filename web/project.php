<?php
declare(strict_types=1);

/*
 * Portable Trusted Linux — Living Project Viewer
 *
 * Canonical source copy for:
 *   https://easyai.com.au/USB/project.php
 *
 * The private GitHub repository stores the content. The production server
 * should provide a READ-ONLY fine-grained GitHub token through either:
 *   PTL_GITHUB_TOKEN environment variable (preferred), or
 *   the fallback constant below for simple shared-hosting deployments.
 */

const GITHUB_OWNER  = 'efrgdgr0024345';
const GITHUB_REPO   = 'portable-trusted-linux';
const GITHUB_BRANCH = 'main';
const CONTENT_FILE  = 'project_content.json';
const CACHE_SECONDS = 60;

// Simple-hosting fallback. Prefer setting PTL_GITHUB_TOKEN outside webroot.
const GITHUB_TOKEN_FALLBACK = 'PASTE_READ_ONLY_GITHUB_TOKEN_HERE';

$cacheDir  = __DIR__ . '/project_cache';
$cacheFile = $cacheDir . '/project_content.json';
$metaFile  = $cacheDir . '/project_meta.json';

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: camera=(), microphone=(), geolocation=()');

function h(string $v): string {
    return htmlspecialchars($v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function githubToken(): string {
    $env = getenv('PTL_GITHUB_TOKEN');
    if (is_string($env) && trim($env) !== '') return trim($env);
    return GITHUB_TOKEN_FALLBACK;
}

function tokenConfigured(): bool {
    $t = githubToken();
    return $t !== '' && $t !== 'PASTE_READ_ONLY_GITHUB_TOKEN_HERE';
}

function githubApiUrl(): string {
    $parts = array_map('rawurlencode', explode('/', CONTENT_FILE));
    return 'https://api.github.com/repos/' . rawurlencode(GITHUB_OWNER) . '/' . rawurlencode(GITHUB_REPO) . '/contents/' . implode('/', $parts) . '?ref=' . rawurlencode(GITHUB_BRANCH);
}

function ensureDir(string $dir): void {
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
}

function readJson(string $file): ?array {
    if (!is_file($file)) return null;
    $raw = @file_get_contents($file);
    if ($raw === false || trim($raw) === '') return null;
    $data = json_decode($raw, true);
    return is_array($data) ? $data : null;
}

function writeJson(string $file, array $data): bool {
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    return $json !== false && @file_put_contents($file, $json, LOCK_EX) !== false;
}

function fetchProject(): array {
    if (!tokenConfigured()) return ['ok'=>false, 'error'=>'GitHub read-only token is not configured.'];
    if (!function_exists('curl_init')) return ['ok'=>false, 'error'=>'PHP cURL is unavailable.'];

    $ch = curl_init(githubApiUrl());
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_CONNECTTIMEOUT => 7,
        CURLOPT_TIMEOUT => 12,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_HTTPHEADER => [
            'Accept: application/vnd.github+json',
            'Authorization: Bearer ' . githubToken(),
            'X-GitHub-Api-Version: 2022-11-28',
            'User-Agent: Portable-Trusted-Linux-Viewer/2.0'
        ]
    ]);
    $response = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);

    if ($response === false) return ['ok'=>false, 'error'=>'GitHub connection failed: ' . $err];
    if ($code !== 200) return ['ok'=>false, 'error'=>'GitHub returned HTTP ' . $code];

    $api = json_decode($response, true);
    if (!is_array($api) || !isset($api['content'])) return ['ok'=>false, 'error'=>'Unexpected GitHub API response.'];
    $decoded = base64_decode(str_replace(["\r","\n"], '', (string)$api['content']), true);
    if ($decoded === false) return ['ok'=>false, 'error'=>'Could not decode repository content.'];
    $project = json_decode($decoded, true);
    if (!is_array($project)) return ['ok'=>false, 'error'=>'project_content.json is invalid.'];

    return ['ok'=>true, 'project'=>$project, 'sha'=>(string)($api['sha'] ?? '')];
}

ensureDir($cacheDir);
$project = null;
$source = 'none';
$error = '';
$githubSha = '';

if (is_file($cacheFile) && (time() - (int)filemtime($cacheFile)) < CACHE_SECONDS) {
    $project = readJson($cacheFile);
    if ($project !== null) $source = 'cache';
}

if ($project === null) {
    $r = fetchProject();
    if (($r['ok'] ?? false) === true) {
        $project = $r['project'];
        $githubSha = (string)($r['sha'] ?? '');
        $source = 'github';
        writeJson($cacheFile, $project);
        writeJson($metaFile, ['last_success'=>date(DATE_ATOM), 'github_sha'=>$githubSha]);
    } else {
        $error = (string)($r['error'] ?? 'Unknown error');
        $project = readJson($cacheFile);
        if ($project !== null) $source = 'offline-cache';
    }
}

$meta = readJson($metaFile) ?? [];
if ($githubSha === '') $githubSha = (string)($meta['github_sha'] ?? '');

$title = (string)($project['title'] ?? 'Portable Trusted Linux');
$version = (string)($project['version'] ?? '');
$phase = (string)($project['phase'] ?? '');
$updated = (string)($project['updated'] ?? '');
$summary = (string)($project['summary'] ?? '');
$progress = max(0, min(100, (int)($project['progress'] ?? 0)));
$sections = is_array($project['sections'] ?? null) ? $project['sections'] : [];
$documents = is_array($project['documents'] ?? null) ? $project['documents'] : [];
$milestones = is_array($project['milestones'] ?? null) ? $project['milestones'] : [];
$decisions = is_array($project['decisions'] ?? null) ? $project['decisions'] : [];
$changelog = is_array($project['changelog'] ?? null) ? $project['changelog'] : [];
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= h($title) ?></title>
<style>
:root{--bg:#050706;--panel:#0d120f;--panel2:#121914;--text:#f4f7f5;--muted:#a7b3ab;--muted2:#758078;--green:#67f58e;--greenB:rgba(103,245,142,.22);--border:rgba(255,255,255,.075);--yellow:#f0cc70;--red:#ff8080;--max:1180px}*{box-sizing:border-box}html{scroll-behavior:smooth}body{margin:0;font-family:Inter,ui-sans-serif,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;background:radial-gradient(circle at 50% -10%,rgba(70,255,125,.09),transparent 35%),linear-gradient(180deg,#070a08,#040504 45%,#070907);color:var(--text);line-height:1.65}.wrap{width:min(var(--max),calc(100% - 40px));margin:auto}nav{position:sticky;top:0;z-index:30;background:rgba(5,7,6,.82);backdrop-filter:blur(18px);border-bottom:1px solid var(--border)}.nav{min-height:68px;display:flex;align-items:center;justify-content:space-between;gap:20px}.brand{font-weight:850;display:flex;align-items:center;gap:11px}.logo{width:34px;height:34px;border:1px solid var(--greenB);border-radius:10px;display:grid;place-items:center;color:var(--green);font-size:12px}.navlinks{display:flex;gap:16px;align-items:center}.navlinks a,.navlinks button{color:var(--muted);font:inherit;font-size:12px;text-decoration:none;background:none;border:0;cursor:pointer}.navlinks a:hover,.navlinks button:hover{color:#fff}.hero{padding:92px 0 64px}.kicker{color:var(--green);font-size:11px;font-weight:900;letter-spacing:.15em;text-transform:uppercase}.hero h1{font-size:clamp(48px,7vw,86px);letter-spacing:-.055em;line-height:.96;margin:12px 0 0;max-width:930px}.summary{font-size:19px;color:#bec8c1;max-width:850px;margin-top:26px}.cards{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-top:38px}.card{border:1px solid var(--border);background:var(--panel);border-radius:15px;padding:19px}.label{color:var(--muted2);font-size:10px;text-transform:uppercase;letter-spacing:.1em;font-weight:900}.value{font-weight:800;font-size:18px;margin-top:5px}.bar{height:8px;border:1px solid var(--border);background:#111713;border-radius:99px;overflow:hidden;margin-top:17px}.bar>div{height:100%;background:linear-gradient(90deg,#2ec75e,var(--green));width:<?= $progress ?>%}.docs{padding:68px 0;border-top:1px solid var(--border)}h2{font-size:clamp(32px,4.5vw,52px);letter-spacing:-.045em;line-height:1.05;margin:8px 0 20px}.docgrid{display:grid;grid-template-columns:repeat(3,1fr);gap:14px}.doc{display:flex;flex-direction:column;min-height:220px;padding:22px;border:1px solid var(--border);border-radius:17px;background:linear-gradient(180deg,rgba(255,255,255,.03),rgba(255,255,255,.015));text-decoration:none}.doc:hover{border-color:var(--greenB);transform:translateY(-1px)}.doctype{color:var(--green);font-size:10px;text-transform:uppercase;letter-spacing:.11em;font-weight:900}.doc h3{font-size:19px;margin:9px 0}.doc p{color:var(--muted);font-size:14px;margin:0 0 18px}.doclink{margin-top:auto;color:var(--green);font-size:13px;font-weight:800}.section{padding:64px 0;border-top:1px solid rgba(255,255,255,.045)}.sectiongrid{display:grid;grid-template-columns:220px 1fr;gap:50px}.index{position:sticky;top:96px;align-self:start;color:var(--green);font-size:10px;text-transform:uppercase;letter-spacing:.12em;font-weight:900}.prose{color:#bbc5be;font-size:17px;max-width:900px}.prose p{margin:0 0 18px}.milestones{display:grid;gap:9px}.mile{display:grid;grid-template-columns:65px 1fr auto;gap:15px;align-items:center;padding:14px 17px;border:1px solid var(--border);background:var(--panel);border-radius:12px}.status{padding:5px 8px;border-radius:99px;font-size:9px;text-transform:uppercase;font-weight:900;letter-spacing:.08em}.complete{color:var(--green);background:rgba(103,245,142,.08)}.current{color:var(--yellow);background:rgba(240,204,112,.08)}.planned{color:var(--muted);background:rgba(255,255,255,.04)}.timeline{border-left:1px solid var(--greenB);margin-left:6px;padding-left:24px}.event{position:relative;margin:0 0 28px}.event:before{content:"";position:absolute;left:-30px;top:8px;width:10px;height:10px;border-radius:50%;background:var(--green)}.date{color:var(--muted2);font-size:10px;text-transform:uppercase;letter-spacing:.09em}.event h3{margin:3px 0 5px}.event p{color:var(--muted);margin:0;max-width:850px}.system{font:11px ui-monospace,monospace;color:var(--muted2);padding:16px;border:1px solid var(--border);border-radius:12px;background:rgba(255,255,255,.018);margin-top:35px}.errorbox{max-width:720px;margin:100px auto;padding:35px;border:1px solid rgba(255,128,128,.2);border-radius:18px;background:var(--panel)}footer{padding:40px 0 60px;border-top:1px solid var(--border);color:var(--muted2);font-size:12px}@media(max-width:900px){.cards,.docgrid{grid-template-columns:1fr 1fr}.sectiongrid{grid-template-columns:1fr}.index{position:static}}@media(max-width:620px){.wrap{width:calc(100% - 26px)}.navlinks a{display:none}.cards,.docgrid{grid-template-columns:1fr}.mile{grid-template-columns:52px 1fr}.mile .status{grid-column:2;justify-self:start}.hero{padding-top:65px}.hero h1{font-size:48px}}@media print{nav{display:none}.docgrid{grid-template-columns:1fr 1fr}body{background:#fff;color:#111}.summary,.prose,.event p{color:#333}.card,.doc,.mile{background:#fff;border-color:#ccc}}
</style>
</head>
<body>
<nav><div class="wrap nav"><div class="brand"><span class="logo">PT</span>Portable Trusted Linux</div><div class="navlinks"><a href="#documents">Documents</a><a href="#progress">Progress</a><a href="#decisions">Decisions</a><button onclick="window.print()">Print / PDF</button></div></div></nav>
<?php if ($project === null): ?>
<div class="errorbox"><div class="kicker">Project connection</div><h2>Living project document unavailable.</h2><p><?= h($error ?: 'No project content is available.') ?></p><?php if (!tokenConfigured()): ?><p>Add the read-only GitHub token to <code>PTL_GITHUB_TOKEN</code> or the fallback constant in this PHP file.</p><?php endif; ?></div>
<?php else: ?>
<header class="hero"><div class="wrap"><div class="kicker">Living technical project record</div><h1><?= h($title) ?></h1><div class="summary"><?= nl2br(h($summary)) ?></div><div class="cards"><div class="card"><div class="label">Version</div><div class="value"><?= h($version) ?></div></div><div class="card"><div class="label">Phase</div><div class="value"><?= h($phase) ?></div></div><div class="card"><div class="label">Progress</div><div class="value"><?= $progress ?>%</div></div><div class="card"><div class="label">Updated</div><div class="value"><?= h($updated) ?></div></div></div><div class="bar"><div></div></div></div></header>

<section class="docs" id="documents"><div class="wrap"><div class="kicker">Project library</div><h2>Documents & project files</h2><div class="docgrid">
<?php foreach ($documents as $doc): $url=(string)($doc['url']??'#'); ?>
<a class="doc" href="<?= h($url) ?>" target="_blank" rel="noopener noreferrer"><div class="doctype"><?= h((string)($doc['type']??'Document')) ?></div><h3><?= h((string)($doc['title']??'Untitled')) ?></h3><p><?= h((string)($doc['description']??'')) ?></p><div class="doclink">Open document →</div></a>
<?php endforeach; ?>
</div></div></section>

<?php foreach ($sections as $i=>$section): ?><section class="section"><div class="wrap sectiongrid"><aside class="index">Section <?= str_pad((string)($i+1),2,'0',STR_PAD_LEFT) ?></aside><div><h2><?= h((string)($section['title']??'')) ?></h2><div class="prose"><?php $paras=preg_split('/\R{2,}/',trim((string)($section['content']??''))); if(is_array($paras)) foreach($paras as $p) if(trim($p)!=='') echo '<p>'.nl2br(h(trim($p))).'</p>'; ?></div></div></div></section><?php endforeach; ?>

<section class="section" id="progress"><div class="wrap"><div class="kicker">Project progress</div><h2>Milestones</h2><div class="milestones"><?php foreach($milestones as $m): $s=strtolower((string)($m['status']??'planned')); if(!in_array($s,['complete','current','planned'],true))$s='planned'; ?><div class="mile"><div class="date"><?= h((string)($m['id']??'')) ?></div><div><?= h((string)($m['title']??'')) ?></div><div class="status <?= h($s) ?>"><?= h($s) ?></div></div><?php endforeach; ?></div></div></section>

<section class="section" id="decisions"><div class="wrap"><div class="kicker">Architecture record</div><h2>Decisions</h2><div class="timeline"><?php foreach(array_reverse($decisions) as $d): ?><div class="event"><div class="date"><?= h((string)($d['date']??'')) ?></div><h3><?= h((string)($d['title']??'')) ?></h3><p><?= h((string)($d['text']??'')) ?></p></div><?php endforeach; ?></div></div></section>

<section class="section"><div class="wrap"><div class="kicker">Historical record</div><h2>Version history</h2><div class="timeline"><?php foreach(array_reverse($changelog) as $c): ?><div class="event"><div class="date"><?= h((string)($c['date']??'')) ?></div><h3>Version <?= h((string)($c['version']??'')) ?></h3><p><?= h((string)($c['summary']??'')) ?></p></div><?php endforeach; ?></div><div class="system">Repository: <?= h(GITHUB_OWNER.'/'.GITHUB_REPO) ?><br>Content: <?= h(CONTENT_FILE) ?><br>Git blob: <?= h($githubSha!==''?substr($githubSha,0,12):'unknown') ?><br>Page source: <?= h($source) ?><?php if($source==='offline-cache' && $error!==''): ?><br>Sync warning: <?= h($error) ?><?php endif; ?></div></div></section>
<?php endif; ?>
<footer><div class="wrap"><strong style="color:#fff">Portable Trusted Linux</strong><br>Living project record · Git-backed history · Independent evidence architecture</div></footer>
</body>
</html>
