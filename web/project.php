<?php
declare(strict_types=1);

/*
 * Black Cat / Portable Trusted Linux — Living Project Viewer
 * Canonical production source for https://easyai.com.au/USB/project.php
 *
 * Security model:
 * - private GitHub is authoritative for project/candidate/release data;
 * - exactly one repository-read token source outside public web root;
 * - no environment/hard-coded credential fallback;
 * - project-data failure fails closed;
 * - local builder status is display-only observational telemetry;
 * - test-candidate distribution is visibly separate from approved releases.
 *
 * TEMPORARY DEVELOPMENT REF:
 * PR #6 remains unmerged while M13B physical retest is pending, so production
 * reads the exact authoritative development branch. After merge, switch this
 * constant and its static regression check back to main in one reviewed cycle.
 */

const GITHUB_OWNER = 'efrgdgr0024345';
const GITHUB_REPO = 'portable-trusted-linux';
const GITHUB_BRANCH = 'codex/remove-chroot-systemd-boot';
const CONTENT_FILE = 'project_content.json';
const TOKEN_FILE = '/home/learning/.blackcat/github_token';
const BUILDER_STATUS_FILE = __DIR__ . '/project_cache/builder_status.json';

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: no-referrer');
header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header("Content-Security-Policy: default-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; connect-src 'none'; frame-ancestors 'self'; base-uri 'none'; form-action 'none'");

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function privateModeIsSafe(string $path): bool
{
    $mode = @fileperms($path);
    return $mode !== false && (($mode & 0077) === 0);
}

function githubToken(): string
{
    $directory = dirname(TOKEN_FILE);
    if (!is_dir($directory) || !privateModeIsSafe($directory)) {
        return '';
    }
    if (!is_file(TOKEN_FILE) || !is_readable(TOKEN_FILE) || !privateModeIsSafe(TOKEN_FILE)) {
        return '';
    }
    $raw = @file_get_contents(TOKEN_FILE);
    return is_string($raw) ? trim($raw) : '';
}

function githubApiUrl(): string
{
    $parts = array_map('rawurlencode', explode('/', CONTENT_FILE));
    return 'https://api.github.com/repos/' . rawurlencode(GITHUB_OWNER) . '/' . rawurlencode(GITHUB_REPO)
        . '/contents/' . implode('/', $parts) . '?ref=' . rawurlencode(GITHUB_BRANCH);
}

function safeHttpsUrl(string $url): string
{
    $url = trim($url);
    if ($url === '' || filter_var($url, FILTER_VALIDATE_URL) === false) {
        return '';
    }
    return strtolower((string)parse_url($url, PHP_URL_SCHEME)) === 'https' ? $url : '';
}

function formatBytes(int $bytes): string
{
    if ($bytes <= 0) {
        return '—';
    }
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $value = (float)$bytes;
    $unit = 0;
    while ($value >= 1024 && $unit < count($units) - 1) {
        $value /= 1024;
        $unit++;
    }
    return number_format($value, $unit === 0 ? 0 : 2) . ' ' . $units[$unit]
        . ' (' . number_format($bytes) . ' bytes)';
}

function fetchProject(): array
{
    $token = githubToken();
    if ($token === '') {
        return ['ok' => false, 'error' => 'Protected Black Cat credential is unavailable or unsafe. Failing closed.'];
    }
    if (!function_exists('curl_init')) {
        return ['ok' => false, 'error' => 'PHP cURL is unavailable. Failing closed.'];
    }

    $curl = curl_init(githubApiUrl());
    if ($curl === false) {
        return ['ok' => false, 'error' => 'Unable to initialise GitHub connection. Failing closed.'];
    }

    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_CONNECTTIMEOUT => 7,
        CURLOPT_TIMEOUT => 12,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_HTTPHEADER => [
            'Accept: application/vnd.github+json',
            'Authorization: Bearer ' . $token,
            'X-GitHub-Api-Version: 2022-11-28',
            'User-Agent: Black-Cat-PTL-Viewer/6.1'
        ]
    ]);

    $response = curl_exec($curl);
    $status = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $curlError = curl_error($curl);
    curl_close($curl);

    if ($response === false) {
        return ['ok' => false, 'error' => 'GitHub connection failed: ' . $curlError . '. Failing closed.'];
    }
    if ($status !== 200) {
        return ['ok' => false, 'error' => 'GitHub returned HTTP ' . $status . '. Failing closed.'];
    }

    $api = json_decode($response, true);
    if (!is_array($api) || ($api['encoding'] ?? '') !== 'base64' || !isset($api['content']) || !is_string($api['content'])) {
        return ['ok' => false, 'error' => 'GitHub response did not contain valid project content. Failing closed.'];
    }

    $decoded = base64_decode(str_replace(["\r", "\n"], '', $api['content']), true);
    if ($decoded === false) {
        return ['ok' => false, 'error' => 'Unable to decode GitHub project content. Failing closed.'];
    }

    $project = json_decode($decoded, true);
    if (!is_array($project)) {
        return ['ok' => false, 'error' => 'project_content.json is invalid. Failing closed.'];
    }

    return ['ok' => true, 'project' => $project, 'sha' => isset($api['sha']) ? (string)$api['sha'] : ''];
}

function readLocalStatus(string $path): ?array
{
    if (!is_file($path) || !is_readable($path)) {
        return null;
    }
    $raw = @file_get_contents($path);
    if (!is_string($raw) || trim($raw) === '') {
        return null;
    }
    $data = json_decode($raw, true);
    return is_array($data) ? $data : null;
}

function isSensitiveStatusKey(string $key): bool
{
    return preg_match('/token|secret|password|passwd|private|authorization|credential|api[_-]?key|ssh[_-]?key/i', $key) === 1;
}

function statusScalar($value): ?string
{
    if (is_bool($value)) {
        return $value ? 'Yes' : 'No';
    }
    if (is_int($value) || is_float($value)) {
        return (string)$value;
    }
    if (is_string($value)) {
        $value = trim($value);
        return $value === '' ? null : mb_substr($value, 0, 220);
    }
    return null;
}

function flattenStatus(array $data, string $prefix = '', int $depth = 0, int $limit = 36): array
{
    $rows = [];
    if ($depth > 2) {
        return $rows;
    }
    foreach ($data as $key => $value) {
        if (count($rows) >= $limit) {
            break;
        }
        $name = (string)$key;
        if (isSensitiveStatusKey($name)) {
            continue;
        }
        $label = $prefix === '' ? $name : $prefix . ' / ' . $name;
        $scalar = statusScalar($value);
        if ($scalar !== null) {
            $rows[] = ['label' => $label, 'value' => $scalar];
            continue;
        }
        if (is_array($value)) {
            foreach (flattenStatus($value, $label, $depth + 1, $limit - count($rows)) as $child) {
                $rows[] = $child;
                if (count($rows) >= $limit) {
                    break;
                }
            }
        }
    }
    return $rows;
}

$result = fetchProject();
$project = (($result['ok'] ?? false) === true && is_array($result['project'] ?? null)) ? $result['project'] : null;
$error = $project === null ? (string)($result['error'] ?? 'Project data unavailable. Failing closed.') : '';
$githubSha = $project !== null ? (string)($result['sha'] ?? '') : '';
if ($project === null) {
    http_response_code(503);
}

$builderStatus = readLocalStatus(BUILDER_STATUS_FILE);
$builderRows = is_array($builderStatus) ? flattenStatus($builderStatus) : [];
$builderMtime = is_file(BUILDER_STATUS_FILE) ? @filemtime(BUILDER_STATUS_FILE) : false;

$title = (string)($project['title'] ?? 'Black Cat');
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

$candidate = is_array($project['candidate'] ?? null) ? $project['candidate'] : [];
$candidateStatus = strtolower(trim((string)($candidate['status'] ?? 'none')));
$candidateFilename = trim((string)($candidate['filename'] ?? ''));
$candidateBytes = max(0, (int)($candidate['size_bytes'] ?? 0));
$candidateHash = strtolower(trim((string)($candidate['sha256'] ?? '')));
$candidateSourceCommit = trim((string)($candidate['source_commit'] ?? ''));
$candidateBuildRun = trim((string)($candidate['build_run'] ?? ''));
$candidateArtifactId = trim((string)($candidate['artifact_id'] ?? ''));
$candidateArtifactName = trim((string)($candidate['artifact_name'] ?? ''));
$candidateSmokeTest = strtolower(trim((string)($candidate['smoke_test'] ?? 'pending')));
$candidateDownload = safeHttpsUrl((string)($candidate['download_url'] ?? ''));
$candidateNote = trim((string)($candidate['note'] ?? ''));

$candidateEnabled =
    $project !== null &&
    $candidateStatus === 'test' &&
    $candidateFilename !== '' &&
    $candidateBytes > 0 &&
    preg_match('/^[a-f0-9]{64}$/', $candidateHash) === 1 &&
    preg_match('/^[a-f0-9]{40}$/i', $candidateSourceCommit) === 1 &&
    preg_match('/^[0-9]+$/', $candidateBuildRun) === 1 &&
    preg_match('/^[0-9]+$/', $candidateArtifactId) === 1 &&
    $candidateArtifactName !== '' &&
    $candidateSmokeTest === 'passed' &&
    $candidateDownload !== '';

$release = is_array($project['release'] ?? null) ? $project['release'] : [];
$releaseStatus = strtolower(trim((string)($release['status'] ?? 'none')));
$releaseDownload = safeHttpsUrl((string)($release['download_url'] ?? ''));
$releasePage = safeHttpsUrl((string)($release['release_page_url'] ?? ''));
$releaseFilename = trim((string)($release['filename'] ?? ''));
$releaseBytes = max(0, (int)($release['size_bytes'] ?? 0));
$releaseHash = strtolower(trim((string)($release['sha256'] ?? '')));
$releaseSourceCommit = trim((string)($release['source_commit'] ?? ''));
$releaseBuildRun = trim((string)($release['build_run'] ?? ''));
$releaseSmokeTest = strtolower(trim((string)($release['smoke_test'] ?? 'pending')));
$releaseNote = trim((string)($release['note'] ?? ''));

$downloadEnabled =
    $project !== null &&
    $releaseStatus === 'approved' &&
    $releaseDownload !== '' &&
    $releaseFilename !== '' &&
    $releaseBytes > 0 &&
    preg_match('/^[a-f0-9]{64}$/', $releaseHash) === 1 &&
    preg_match('/^[a-f0-9]{40}$/i', $releaseSourceCommit) === 1 &&
    $releaseBuildRun !== '' &&
    $releaseSmokeTest === 'passed';
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= h($title) ?></title>
<style>
:root{--bg:#050706;--panel:#0d120f;--panel2:#111713;--text:#f4f7f5;--muted:#a7b3ab;--muted2:#758078;--green:#67f58e;--greenB:rgba(103,245,142,.22);--border:rgba(255,255,255,.075);--yellow:#f0cc70;--red:#ff8080;--max:1180px}*{box-sizing:border-box}html{scroll-behavior:smooth}body{margin:0;font-family:Inter,ui-sans-serif,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;background:radial-gradient(circle at 50% -10%,rgba(70,255,125,.09),transparent 35%),linear-gradient(180deg,#070a08,#040504 45%,#070907);color:var(--text);line-height:1.65}.wrap{width:min(var(--max),calc(100% - 40px));margin:auto}nav{position:sticky;top:0;z-index:30;background:rgba(5,7,6,.92);border-bottom:1px solid var(--border)}.nav{min-height:68px;display:flex;align-items:center;justify-content:space-between;gap:20px}.brand{font-weight:850;display:flex;align-items:center;gap:11px}.logo{width:34px;height:34px;border:1px solid var(--greenB);border-radius:10px;display:grid;place-items:center;color:var(--green);font-size:12px}.navlinks{display:flex;gap:15px;align-items:center}.navlinks a,.navlinks button{color:var(--muted);font:inherit;font-size:12px;text-decoration:none;background:none;border:0;cursor:pointer}.hero{padding:82px 0 58px}.kicker{color:var(--green);font-size:11px;font-weight:900;letter-spacing:.15em;text-transform:uppercase}.hero h1,h2{letter-spacing:-.045em;line-height:1.05}.hero h1{font-size:clamp(48px,7vw,86px);margin:12px 0 0}.summary{font-size:19px;color:#bec8c1;max-width:850px;margin-top:26px}.cards,.sourcegrid,.statusgrid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px}.cards{margin-top:36px}.card,.releasebox,.doc,.mile,.sourcebox,.statuscard,.errorbox{border:1px solid var(--border);background:var(--panel);border-radius:14px}.card{padding:18px}.label,.sourceitem strong,.statuscard strong{color:var(--muted2);font-size:10px;text-transform:uppercase;letter-spacing:.1em;font-weight:900}.value{font-weight:800;font-size:18px;margin-top:5px}.bar{height:8px;border:1px solid var(--border);background:#111713;border-radius:99px;overflow:hidden;margin-top:17px}.bar>div{height:100%;background:linear-gradient(90deg,#2ec75e,var(--green));width:<?= $progress ?>%}.release,.docs,.section{padding:62px 0;border-top:1px solid var(--border)}h2{font-size:clamp(31px,4vw,50px);margin:7px 0 20px}.releasebox{padding:25px;border-color:var(--greenB);margin-bottom:18px}.candidatebox{border-color:rgba(240,204,112,.32)}.releasehead,.statushead,.sourcehead{display:flex;justify-content:space-between;gap:20px;align-items:flex-start}.release-status,.pill{font-size:10px;font-weight:900;letter-spacing:.1em;text-transform:uppercase;padding:7px 10px;border-radius:99px;height:max-content}.approved,.livepill{color:var(--green);background:rgba(103,245,142,.09);border:1px solid rgba(103,245,142,.18)}.pending{color:var(--yellow);background:rgba(240,204,112,.09)}.releasegrid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:9px 22px}.releasefield{padding:9px 0;border-bottom:1px solid rgba(255,255,255,.05)}.releasefield strong{display:block;color:var(--muted2);font-size:10px;text-transform:uppercase;letter-spacing:.09em}.releasefield span,.releasefield code{display:block;margin-top:4px;overflow-wrap:anywhere}.releaseactions{display:flex;gap:12px;flex-wrap:wrap;margin-top:20px}.downloadbtn,.secondarybtn{display:inline-flex;padding:12px 16px;border-radius:11px;font-weight:850;text-decoration:none}.downloadbtn{background:var(--green);color:#041006}.candidatebtn{background:var(--yellow);color:#171005}.downloadbtn.disabled{background:#182019;color:#657068;pointer-events:none}.secondarybtn{border:1px solid var(--border);color:var(--muted)}.release-note,.mutednote{color:var(--muted);font-size:13px;margin-top:15px}.warning{color:var(--yellow);font-weight:850}.sourcebox{padding:25px;border-color:var(--greenB)}.sourcegrid{grid-template-columns:repeat(3,minmax(0,1fr));margin-top:20px}.sourceitem,.statuscard{padding:17px;background:var(--panel2);border:1px solid var(--border);border-radius:12px;min-width:0}.sourceitem span,.sourceitem code,.statuscard span{display:block;margin-top:6px;overflow-wrap:anywhere;font-size:14px}.statusgrid{grid-template-columns:repeat(3,minmax(0,1fr));margin-top:20px}.statuscard span{font-size:15px;font-weight:700}.docgrid{display:grid;grid-template-columns:repeat(3,1fr);gap:14px}.doc{display:flex;flex-direction:column;padding:21px;min-height:210px;text-decoration:none;color:inherit}.doctype,.date{color:var(--muted2);font-size:10px;text-transform:uppercase;letter-spacing:.1em}.doctype{color:var(--green);font-weight:900}.doc h3{margin:8px 0}.doc p,.prose,.event p{color:var(--muted)}.doclink{margin-top:auto;color:var(--green);font-weight:800}.sectiongrid{display:grid;grid-template-columns:210px 1fr;gap:45px}.index{color:var(--green);font-size:10px;text-transform:uppercase;letter-spacing:.12em;font-weight:900}.prose{font-size:17px;max-width:900px}.milestones{display:grid;gap:9px}.mile{display:grid;grid-template-columns:65px 1fr auto;gap:15px;align-items:center;padding:14px 17px}.status{padding:5px 8px;border-radius:99px;font-size:9px;text-transform:uppercase;font-weight:900}.complete{color:var(--green)}.current{color:var(--yellow)}.planned{color:var(--muted)}.timeline{border-left:1px solid var(--greenB);padding-left:24px}.event{margin-bottom:28px}.event h3{margin:3px 0}.errorbox{max-width:760px;margin:90px auto;padding:34px;border-color:rgba(255,128,128,.25)}.fail{color:var(--red);font-weight:850}footer{padding:38px 0 55px;border-top:1px solid var(--border);color:var(--muted2);font-size:12px}@media(max-width:900px){.cards,.sourcegrid,.statusgrid,.docgrid,.releasegrid{grid-template-columns:1fr 1fr}.sectiongrid{grid-template-columns:1fr}}@media(max-width:620px){.wrap{width:calc(100% - 26px)}.cards,.sourcegrid,.statusgrid,.docgrid,.releasegrid{grid-template-columns:1fr}.navlinks a{display:none}.mile{grid-template-columns:52px 1fr}.mile .status{grid-column:2}.releasehead,.statushead,.sourcehead{display:block}.pill,.release-status{display:inline-block;margin-top:8px}}@media print{nav,.downloadbtn,.secondarybtn{display:none}body{background:white;color:#111}}
</style>
</head>
<body>
<nav><div class="wrap nav"><div class="brand"><span class="logo">BC</span>Black Cat <span style="color:var(--muted2)">/ PTL</span></div><div class="navlinks"><a href="#candidate">Test ISO</a><a href="#status">Status</a><a href="#source">Data source</a><a href="#documents">Documents</a><a href="#progress">Progress</a><button type="button" onclick="window.print()">Print / PDF</button></div></div></nav>

<?php if ($project === null): ?>
<main class="wrap"><div class="errorbox"><div class="kicker">Fail-closed project connection</div><h1>Black Cat project data unavailable.</h1><p class="fail">No stale project data is being served as authority.</p><p><?= h($error) ?></p><p>The credential value is never displayed by this page.</p></div></main>
<?php else: ?>
<header class="hero"><div class="wrap"><div class="kicker">Portable Trusted Linux engineering project</div><h1><?= h($title) ?></h1><div class="summary"><?= nl2br(h($summary)) ?></div><div class="cards"><div class="card"><div class="label">Version</div><div class="value"><?= h($version) ?></div></div><div class="card"><div class="label">Phase</div><div class="value"><?= h($phase) ?></div></div><div class="card"><div class="label">Progress</div><div class="value"><?= $progress ?>%</div></div><div class="card"><div class="label">Updated</div><div class="value"><?= h($updated) ?></div></div></div><div class="bar"><div></div></div></div></header>

<section class="release" id="candidate"><div class="wrap"><div class="releasebox candidatebox"><div class="releasehead"><div><div class="kicker">Development distribution</div><h2>Current Test Candidate</h2></div><div class="release-status pending"><?= $candidateEnabled ? 'Ready for physical testing' : 'No test candidate' ?></div></div><?php if ($candidateEnabled): ?><p class="warning">TEST CANDIDATE — NOT AN APPROVED RELEASE</p><div class="releasegrid"><div class="releasefield"><strong>Filename</strong><span><?= h($candidateFilename) ?></span></div><div class="releasefield"><strong>Exact size</strong><span><?= h(formatBytes($candidateBytes)) ?></span></div><div class="releasefield"><strong>SHA-256</strong><code><?= h($candidateHash) ?></code></div><div class="releasefield"><strong>Source commit</strong><code><?= h($candidateSourceCommit) ?></code></div><div class="releasefield"><strong>Build run</strong><span><?= h($candidateBuildRun) ?></span></div><div class="releasefield"><strong>Artifact</strong><span><?= h($candidateArtifactName . ' (#' . $candidateArtifactId . ')') ?></span></div><div class="releasefield"><strong>Automated boot smoke test</strong><span><?= h($candidateSmokeTest) ?></span></div><div class="releasefield"><strong>Physical hardware validation</strong><span>pending</span></div></div><div class="releaseactions"><a class="downloadbtn candidatebtn" href="<?= h($candidateDownload) ?>" target="_blank" rel="noopener noreferrer">Download current test candidate ↓</a></div><div class="release-note"><?= h($candidateNote !== '' ? $candidateNote : 'This link targets one frozen GitHub Actions artifact. GitHub sign-in and repository read access may be required.') ?></div><?php else: ?><div class="releaseactions"><span class="downloadbtn disabled" aria-disabled="true">Test candidate unavailable</span></div><div class="release-note">A candidate appears only when one exact build satisfies all identity and automated-smoke gates.</div><?php endif; ?></div></div></section>

<section class="release" id="release"><div class="wrap"><div class="releasebox"><div class="releasehead"><div><div class="kicker">Controlled release distribution</div><h2>Approved Black Cat ISO</h2></div><div class="release-status <?= $downloadEnabled ? 'approved' : 'pending' ?>"><?= $downloadEnabled ? 'Approved download' : 'No approved ISO' ?></div></div><div class="releasegrid"><div class="releasefield"><strong>Filename</strong><span><?= h($releaseFilename !== '' ? $releaseFilename : 'Not published') ?></span></div><div class="releasefield"><strong>Exact size</strong><span><?= h(formatBytes($releaseBytes)) ?></span></div><div class="releasefield"><strong>SHA-256</strong><code><?= h($releaseHash !== '' ? $releaseHash : 'Not published') ?></code></div><div class="releasefield"><strong>Source commit</strong><code><?= h($releaseSourceCommit !== '' ? $releaseSourceCommit : 'Not published') ?></code></div><div class="releasefield"><strong>Build run</strong><span><?= h($releaseBuildRun !== '' ? $releaseBuildRun : 'Not published') ?></span></div><div class="releasefield"><strong>Automated boot smoke test</strong><span><?= h($releaseSmokeTest !== '' ? $releaseSmokeTest : 'pending') ?></span></div></div><div class="releaseactions"><?php if ($downloadEnabled): ?><a class="downloadbtn" href="<?= h($releaseDownload) ?>">Download approved ISO ↓</a><?php else: ?><span class="downloadbtn disabled" aria-disabled="true">Download unavailable</span><?php endif; ?><?php if ($releasePage !== ''): ?><a class="secondarybtn" href="<?= h($releasePage) ?>" target="_blank" rel="noopener noreferrer">View GitHub release →</a><?php endif; ?></div><div class="release-note"><?= h($releaseNote !== '' ? $releaseNote : 'An approved ISO appears only after explicit promotion. A development candidate does not satisfy this gate.') ?></div></div></div></section>

<section class="section" id="status"><div class="wrap"><div class="statushead"><div><div class="kicker">Operational observation</div><h2>Builder / Server Status</h2></div><div class="pill <?= $builderRows ? 'livepill' : 'pending' ?>"><?= $builderRows ? 'Status file available' : 'Status unavailable' ?></div></div><?php if ($builderRows): ?><div class="statusgrid"><?php foreach ($builderRows as $row): ?><div class="statuscard"><strong><?= h(str_replace(['_', '-'], ' ', $row['label'])) ?></strong><span><?= h($row['value']) ?></span></div><?php endforeach; ?></div><?php else: ?><div class="statuscard"><strong>Runtime status</strong><span>No readable builder status data is currently available.</span></div><?php endif; ?><div class="mutednote">Source: <code>project_cache/builder_status.json</code><?php if ($builderMtime !== false): ?> · file updated <?= h(date(DATE_ATOM, (int)$builderMtime)) ?><?php endif; ?>. This is display-only operational telemetry. It cannot enable, replace, supersede or approve project/candidate/release data.</div></div></section>

<section class="section" id="source"><div class="wrap"><div class="sourcebox"><div class="sourcehead"><div><div class="kicker">Project Data Source</div><h2>Where this page gets authoritative project data</h2></div><div class="pill livepill">✓ LIVE / SYNCHRONISED</div></div><div class="sourcegrid"><div class="sourceitem"><strong>Repository</strong><span><?= h(GITHUB_OWNER . '/' . GITHUB_REPO) ?></span></div><div class="sourceitem"><strong>Repository branch</strong><code><?= h(GITHUB_BRANCH) ?></code></div><div class="sourceitem"><strong>Project data</strong><span><?= h(CONTENT_FILE) ?></span></div><div class="sourceitem"><strong>Git blob</strong><code><?= h($githubSha !== '' ? $githubSha : 'unknown') ?></code></div><div class="sourceitem"><strong>Credential</strong><span>Protected server file — value hidden</span></div><div class="sourceitem"><strong>Authority</strong><span>Live GitHub API only</span></div></div><div class="mutednote">Project, test-candidate and approved-release metadata comes directly from the private GitHub repository. Local builder/server telemetry is shown separately above and is never used as authority for downloads or release state.</div></div></div></section>

<section class="docs" id="documents"><div class="wrap"><div class="kicker">Project library</div><h2>Documents &amp; project files</h2><div class="docgrid"><?php foreach ($documents as $doc): $url = safeHttpsUrl((string)($doc['url'] ?? '')); if ($url === '') continue; ?><a class="doc" href="<?= h($url) ?>" target="_blank" rel="noopener noreferrer"><div class="doctype"><?= h((string)($doc['type'] ?? 'Document')) ?></div><h3><?= h((string)($doc['title'] ?? 'Untitled')) ?></h3><p><?= h((string)($doc['description'] ?? '')) ?></p><div class="doclink">Open document →</div></a><?php endforeach; ?></div></div></section>

<?php foreach ($sections as $index => $section): ?><section class="section"><div class="wrap sectiongrid"><aside class="index">Section <?= str_pad((string)($index + 1), 2, '0', STR_PAD_LEFT) ?></aside><div><h2><?= h((string)($section['title'] ?? '')) ?></h2><div class="prose"><?php $paragraphs = preg_split('/\R{2,}/', trim((string)($section['content'] ?? ''))); if (is_array($paragraphs)) foreach ($paragraphs as $paragraph) if (trim($paragraph) !== '') echo '<p>' . nl2br(h(trim($paragraph))) . '</p>'; ?></div></div></div></section><?php endforeach; ?>

<section class="section" id="progress"><div class="wrap"><div class="kicker">Project progress</div><h2>Milestones</h2><div class="milestones"><?php foreach ($milestones as $milestone): $status = strtolower((string)($milestone['status'] ?? 'planned')); if (!in_array($status, ['complete','current','planned'], true)) $status = 'planned'; ?><div class="mile"><div class="date"><?= h((string)($milestone['id'] ?? '')) ?></div><div><?= h((string)($milestone['title'] ?? '')) ?></div><div class="status <?= h($status) ?>"><?= h($status) ?></div></div><?php endforeach; ?></div></div></section>

<section class="section" id="decisions"><div class="wrap"><div class="kicker">Architecture record</div><h2>Decisions</h2><div class="timeline"><?php foreach (array_reverse($decisions) as $decision): ?><div class="event"><div class="date"><?= h((string)($decision['date'] ?? '')) ?></div><h3><?= h((string)($decision['title'] ?? '')) ?></h3><p><?= h((string)($decision['text'] ?? '')) ?></p></div><?php endforeach; ?></div></div></section>

<section class="section"><div class="wrap"><div class="kicker">Historical record</div><h2>Version history</h2><div class="timeline"><?php foreach (array_reverse($changelog) as $change): ?><div class="event"><div class="date"><?= h((string)($change['date'] ?? '')) ?></div><h3>Version <?= h((string)($change['version'] ?? '')) ?></h3><p><?= h((string)($change['summary'] ?? '')) ?></p></div><?php endforeach; ?></div></div></section>
<?php endif; ?>

<footer><div class="wrap"><strong style="color:#fff">Black Cat</strong> · Portable Trusted Linux<br>Development candidate identity separated from approved release distribution</div></footer>
</body>
</html>
