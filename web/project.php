<?php
declare(strict_types=1);

/* Black Cat living viewer. Metadata checks are not host or runtime attestation.
 * This release keeps the operator-supplied deployed feed (docs/21), not a branch migration.
 * One protected token file, no credential fallback, no stale-data success on upstream failure.
 */
const VIEWER_VERSION = '2026-11-10.1';
const GITHUB_OWNER = 'efrgdgr0024345';
const GITHUB_REPO = 'portable-trusted-linux';
const GITHUB_BRANCH = 'codex/remove-chroot-systemd-boot';
const CANONICAL_BRANCH = 'main';
const CONTENT_FILE = 'project_content.json';
const TOKEN_FILE = '/home/learning/.blackcat/github_token';
const BUILDER_STATUS_FILE = '/home/learning/.blackcat/builder_status.json';
const MAX_API_BYTES = 2097152;
const MAX_PROJECT_BYTES = 1048576;
const TELEMETRY_MAX_AGE = 900;

ini_set('display_errors', '0');
date_default_timezone_set('UTC');
$scriptNonce = bin2hex(random_bytes(18));
header('Content-Type: text/html; charset=UTF-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: no-referrer');
header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header("Content-Security-Policy: default-src 'self'; script-src 'nonce-{$scriptNonce}'; script-src-attr 'none'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; connect-src 'none'; object-src 'none'; frame-ancestors 'self'; base-uri 'none'; form-action 'none'");
if (PHP_VERSION_ID < 80100) {
    http_response_code(503);
    echo '<h1>Project viewer unavailable</h1><p>PHP 8.1 or newer is required. Downloads are disabled.</p>';
    exit;
}

function h(string $value): string { return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function objectArray($value): bool { return is_array($value) && $value !== [] && !array_is_list($value); }
function textValue($value, int $limit = 2000): bool {
    return is_string($value) && strlen($value) <= $limit && preg_match('//u', $value) === 1;
}
function nonemptyText($value, int $limit = 2000): bool { return textValue($value, $limit) && trim($value) !== ''; }
function identifier($value): bool { return (is_string($value) || is_int($value)) && preg_match('/^[1-9][0-9]{0,18}$/D', (string)$value) === 1; }
function digest64($value): bool { return is_string($value) && preg_match('/^[a-f0-9]{64}$/Di', $value) === 1; }
function commit40($value): bool { return is_string($value) && preg_match('/^[a-f0-9]{40}$/Di', $value) === 1; }
function timestamp($value): ?int {
    if (!is_string($value) || preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d{1,6})?(?:Z|[+-]\d{2}:\d{2})$/D', $value) !== 1) return null;
    try { $date = new DateTimeImmutable($value); $errors = DateTimeImmutable::getLastErrors(); }
    catch (Exception $e) { return null; }
    return is_array($errors) && ($errors['warning_count'] || $errors['error_count']) ? null : $date->getTimestamp();
}
function privateModeIsSafe(string $path): bool {
    $mode = @fileperms($path);
    return !is_link($path) && $mode !== false && (($mode & 0077) === 0);
}
function outsideWebRoot(string $path): bool {
    $real = realpath($path);
    $root = realpath((string)($_SERVER['DOCUMENT_ROOT'] ?? ''));
    if ($root === false || $root === '') $root = realpath(__DIR__);
    return $real !== false && $root !== false && $real !== $root && !str_starts_with($real, rtrim($root, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR);
}
function githubToken(): string {
    $directory = dirname(TOKEN_FILE);
    if (!is_dir($directory) || !privateModeIsSafe($directory) || !is_file(TOKEN_FILE) || !is_readable(TOKEN_FILE)
        || !privateModeIsSafe(TOKEN_FILE) || !outsideWebRoot(TOKEN_FILE) || @filesize(TOKEN_FILE) > 4096) return '';
    if (@fileowner($directory) !== @fileowner(TOKEN_FILE)) return '';
    if (function_exists('posix_geteuid') && @fileowner(TOKEN_FILE) !== posix_geteuid()) return '';
    $raw = @file_get_contents(TOKEN_FILE, false, null, 0, 4097);
    if (!is_string($raw)) return '';
    $token = trim($raw);
    return $token !== '' && strlen($token) <= 4096 && preg_match('/^[A-Za-z0-9_.-]+$/D', $token) === 1 ? $token : '';
}
function safeHttpsUrl(string $url): string {
    if ($url === '' || strlen($url) > 8192 || preg_match('/[\x00-\x20\x7f]/', $url) || filter_var($url, FILTER_VALIDATE_URL) === false) return '';
    $parts = parse_url($url);
    if (!is_array($parts) || strtolower((string)($parts['scheme'] ?? '')) !== 'https' || empty($parts['host'])
        || isset($parts['user']) || isset($parts['pass']) || (isset($parts['port']) && $parts['port'] !== 443)) return '';
    return $url;
}
function formatBytes(int $bytes): string {
    if ($bytes <= 0) return 'UNKNOWN';
    $units = ['B', 'KiB', 'MiB', 'GiB', 'TiB']; $value = (float)$bytes; $unit = 0;
    while ($value >= 1024 && $unit < count($units) - 1) { $value /= 1024; $unit++; }
    return number_format($value, $unit === 0 ? 0 : 2) . ' ' . $units[$unit] . ' (' . number_format($bytes) . ' bytes)';
}
function cacheDirectory(): ?string {
    $parent = dirname(TOKEN_FILE);
    if (!is_dir($parent) || !privateModeIsSafe($parent) || !outsideWebRoot($parent)) return null;
    $directory = $parent . '/viewer_http_cache';
    if (!is_dir($directory) && !@mkdir($directory, 0700)) return null;
    return privateModeIsSafe($directory) && outsideWebRoot($directory) ? $directory : null;
}
function githubRequest(string $path, string $token): array {
    // Only fixed-repository GitHub API paths reach the network. JSON cannot supply a fetch URL.
    if ($token === '' || !function_exists('curl_init') || $path === '' || $path[0] !== '/' || str_contains($path, '://') || preg_match('/[\r\n]/', $path))
        return ['ok' => false, 'error' => 'GitHub transport prerequisite unavailable.'];
    $url = 'https://api.github.com/repos/' . GITHUB_OWNER . '/' . GITHUB_REPO . $path;
    $cacheDir = cacheDirectory(); $cachePath = $cacheDir === null ? null : $cacheDir . '/' . hash('sha256', $url) . '.json';
    $cached = null;
    if ($cachePath !== null && is_file($cachePath) && privateModeIsSafe($cachePath) && @filesize($cachePath) <= MAX_API_BYTES * 2) {
        $raw = @file_get_contents($cachePath);
        $record = is_string($raw) ? json_decode($raw, true) : null;
        if (is_array($record) && ($record['url'] ?? '') === $url && is_string($record['body'] ?? null)
            && strlen($record['body']) <= MAX_API_BYTES && is_string($record['etag'] ?? null)
            && preg_match('/^(?:W\/)?"[A-Za-z0-9._:-]{1,160}"$/D', $record['etag']) === 1) $cached = $record;
    }
    $headers = ['Accept: application/vnd.github+json', 'Authorization: Bearer ' . $token,
        'X-GitHub-Api-Version: 2022-11-28', 'User-Agent: Black-Cat-PTL-Viewer/' . VIEWER_VERSION];
    if ($cached !== null) $headers[] = 'If-None-Match: ' . $cached['etag'];
    $body = ''; $etag = ''; $tooLarge = false;
    $curl = curl_init($url);
    if ($curl === false) return ['ok' => false, 'error' => 'GitHub transport could not initialize.'];
    curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_CONNECTTIMEOUT => 5, CURLOPT_TIMEOUT => 12, CURLOPT_SSL_VERIFYPEER => true, CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_WRITEFUNCTION => static function ($handle, string $chunk) use (&$body, &$tooLarge): int {
            if (strlen($body) + strlen($chunk) > MAX_API_BYTES) { $tooLarge = true; return 0; }
            $body .= $chunk; return strlen($chunk);
        },
        CURLOPT_HEADERFUNCTION => static function ($handle, string $line) use (&$etag): int {
            if (stripos($line, 'ETag:') === 0) $etag = trim(substr($line, 5));
            return strlen($line);
        }]);
    $response = curl_exec($curl); $status = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE); curl_close($curl);
    if ($response === false || $tooLarge) return ['ok' => false, 'error' => 'GitHub connection failed or response exceeded the size limit.'];
    if ($status === 304) {
        if ($cached === null) return ['ok' => false, 'error' => 'GitHub returned 304 without a usable private cache.'];
        $body = $cached['body'];
    } elseif ($status !== 200) {
        // Never substitute cached success for a timeout, denied request, 404 or rate limit.
        return ['ok' => false, 'error' => 'GitHub returned HTTP ' . $status . '; no stale success is substituted.'];
    }
    try { $data = json_decode($body, true, 64, JSON_THROW_ON_ERROR); }
    catch (JsonException $e) { return ['ok' => false, 'error' => 'GitHub response is not valid bounded JSON.']; }
    if (!objectArray($data)) return ['ok' => false, 'error' => 'GitHub response is not an object.'];
    if ($status === 200 && $cachePath !== null && preg_match('/^(?:W\/)?"[A-Za-z0-9._:-]{1,160}"$/D', $etag) === 1) {
        $tmp = @tempnam($cacheDir, 'pending-');
        if (is_string($tmp)) {
            @chmod($tmp, 0600);
            if (@file_put_contents($tmp, json_encode(['url' => $url, 'etag' => $etag, 'body' => $body], JSON_INVALID_UTF8_SUBSTITUTE), LOCK_EX) !== false) @rename($tmp, $cachePath);
            if (is_file($tmp)) @unlink($tmp);
        }
    }
    return ['ok' => true, 'data' => $data, 'revalidated' => $status === 304];
}
function projectSchemaErrors(array $project): array {
    $errors = [];
    foreach (['title' => 300, 'version' => 100, 'phase' => 300, 'updated' => 40, 'summary' => 12000] as $key => $limit)
        if (!nonemptyText($project[$key] ?? null, $limit)) $errors[] = 'Missing or invalid project field: ' . $key;
    if (isset($project['viewer_schema_version']) && $project['viewer_schema_version'] !== 'blackcat-project-index-v1') $errors[] = 'Unsupported viewer schema version.';
    $shapes = ['sections' => ['title', 'content'], 'documents' => ['title', 'url'], 'milestones' => ['id', 'title', 'status'],
        'capabilities' => ['title', 'stage', 'scope'], 'decisions' => ['title'], 'changelog' => ['version', 'date', 'summary']];
    foreach ($shapes as $key => $required) {
        if (!is_array($project[$key] ?? null) || !array_is_list($project[$key]) || count($project[$key]) > 500) { $errors[] = 'Invalid project list: ' . $key; continue; }
        foreach ($project[$key] as $item) {
            if (!objectArray($item)) { $errors[] = 'Invalid object in ' . $key; break; }
            foreach ($required as $field) if (!nonemptyText($item[$field] ?? null, 50000)) { $errors[] = 'Invalid ' . $key . ' field: ' . $field; break 2; }
            // Every field rendered by PHP must have its expected scalar type.
            foreach (['id','title','content','url','type','description','status','stage','scope','date','text','version','summary','evidence_url'] as $field)
                if (isset($item[$field]) && !textValue($item[$field], 50000)) { $errors[] = 'Invalid rendered value in ' . $key; break 2; }
        }
    }
    foreach (['candidate', 'release'] as $key) if (!objectArray($project[$key] ?? null) || !nonemptyText($project[$key]['status'] ?? null, 100)) $errors[] = 'Invalid ' . $key . ' state object.';
    foreach (['candidate','release'] as $key) if (objectArray($project[$key] ?? null)) {
        foreach (['status','filename','sha256','source_commit','artifact_name','smoke_test','download_url','note','release_page_url','tag','artifact_sha256'] as $field)
            if (isset($project[$key][$field]) && !textValue($project[$key][$field], 8192)) $errors[] = 'Invalid download text field: ' . $field;
        foreach (['size_bytes'] as $field) if (isset($project[$key][$field]) && (!is_int($project[$key][$field]) || $project[$key][$field] < 0)) $errors[] = 'Invalid download byte count.';
        foreach (['build_run','artifact_id','release_id','asset_id'] as $field)
            if (isset($project[$key][$field]) && !(is_string($project[$key][$field]) || is_int($project[$key][$field]))) $errors[] = 'Invalid download identifier type.';
    }
    return $errors;
}
function readProjectRef(string $ref, string $token): array {
    $path = '/contents/' . implode('/', array_map('rawurlencode', explode('/', CONTENT_FILE))) . '?ref=' . rawurlencode($ref);
    $response = githubRequest($path, $token);
    if (!$response['ok']) return $response;
    $api = $response['data'];
    if (($api['encoding'] ?? '') !== 'base64' || !is_string($api['content'] ?? null) || !commit40($api['sha'] ?? null))
        return ['ok' => false, 'error' => 'GitHub content identity/encoding is missing.'];
    $decoded = base64_decode(str_replace(["\r", "\n"], '', $api['content']), true);
    if (!is_string($decoded) || strlen($decoded) > MAX_PROJECT_BYTES) return ['ok' => false, 'error' => 'Project payload is invalid or too large.'];
    // Standard Git blob object identifier, not a cryptographic approval or independent trust root.
    if (!hash_equals(strtolower($api['sha']), sha1('blob ' . strlen($decoded) . "\0" . $decoded))) return ['ok' => false, 'error' => 'Project payload does not match its Git blob identifier.'];
    try { $object = json_decode($decoded, false, 64, JSON_THROW_ON_ERROR); $project = json_decode($decoded, true, 64, JSON_THROW_ON_ERROR); }
    catch (JsonException $e) { return ['ok' => false, 'error' => 'Project JSON cannot be decoded.']; }
    if (!($object instanceof stdClass) || !objectArray($project)) return ['ok' => false, 'error' => 'Project JSON must be a nonempty object, not an array.'];
    $errors = projectSchemaErrors($project);
    if ($errors !== []) return ['ok' => false, 'error' => 'Project schema rejected: ' . implode(' ', array_slice($errors, 0, 4))];
    return ['ok' => true, 'project' => $project, 'sha' => strtolower($api['sha']), 'revalidated' => $response['revalidated']];
}
function consistencyWarnings(array $project): array {
    $values = []; $warnings = [];
    if (isset($project['m15']) && !objectArray($project['m15'])) $warnings[] = 'M15 record has an invalid type.';
    if (is_array($project['m15'] ?? null) && array_key_exists('merged_to_main', $project['m15'])) $values[] = $project['m15']['merged_to_main'];
    foreach ($project['milestones'] as $milestone) if ($milestone['id'] === 'M15' && array_key_exists('merged_to_main', $milestone)) $values[] = $milestone['merged_to_main'];
    foreach ($values as $value) if (!is_bool($value)) $warnings[] = 'M15 merge status must be a boolean.';
    if (count(array_unique(array_map(static fn($v): string => json_encode($v), $values))) > 1) $warnings[] = 'M15 merge records disagree; downloads are blocked.';
    return array_values(array_unique($warnings));
}
function fetchProject(): array {
    $token = githubToken();
    if ($token === '') return ['ok' => false, 'error' => 'Protected viewer credential is unavailable or failed file checks. Downloads are disabled.'];
    $feed = readProjectRef(GITHUB_BRANCH, $token);
    if (!$feed['ok']) return $feed;
    $main = GITHUB_BRANCH === CANONICAL_BRANCH ? $feed : readProjectRef(CANONICAL_BRANCH, $token);
    $feed['main_sha'] = $main['ok'] ? $main['sha'] : '';
    $feed['main_match'] = $main['ok'] && hash_equals($feed['sha'], $main['sha']);
    $feed['warnings'] = consistencyWarnings($feed['project']);
    if (!$main['ok']) $feed['warnings'][] = 'Canonical main could not be validated; feed/main match is UNKNOWN and downloads are blocked.';
    elseif (!$feed['main_match']) $feed['warnings'][] = 'Feed JSON differs from main; downloads are blocked.';
    $feed['publication_ok'] = $feed['main_match'] && $feed['warnings'] === [];
    $feed['checked_at'] = gmdate(DATE_ATOM);
    return $feed;
}
function completedRun($runId, string $source, string $token): array {
    $r = githubRequest('/actions/runs/' . (string)$runId, $token);
    if (!$r['ok']) return $r;
    $d = $r['data'];
    if ((string)($d['id'] ?? '') !== (string)$runId || ($d['status'] ?? '') !== 'completed' || ($d['conclusion'] ?? '') !== 'success'
        || strtolower((string)($d['head_sha'] ?? '')) !== strtolower($source) || ($d['repository']['full_name'] ?? '') !== GITHUB_OWNER . '/' . GITHUB_REPO)
        return ['ok' => false, 'error' => 'Build run/repository/source metadata does not match a completed successful run.'];
    return ['ok' => true];
}
function candidateGate(array $candidate, bool $publicationOkay): array {
    $state = $candidate['status'];
    if ($state !== 'test') return ['enabled' => false, 'reason' => in_array($state, ['none','superseded','unavailable'], true) ? 'No active test candidate; status: ' . $state . '.' : 'UNKNOWN candidate state; download disabled.'];
    if (!$publicationOkay) return ['enabled' => false, 'reason' => 'Publication consistency checks have not passed.'];
    $valid = nonemptyText($candidate['filename'] ?? null, 200) && preg_match('/^[A-Za-z0-9][A-Za-z0-9._+-]*\.iso$/D', $candidate['filename']) === 1
        && is_int($candidate['size_bytes'] ?? null) && $candidate['size_bytes'] > 0 && digest64($candidate['sha256'] ?? null)
        && commit40($candidate['source_commit'] ?? null) && identifier($candidate['build_run'] ?? null) && identifier($candidate['artifact_id'] ?? null)
        && nonemptyText($candidate['artifact_name'] ?? null, 200) && ($candidate['smoke_test'] ?? '') === 'passed' && digest64($candidate['artifact_sha256'] ?? null);
    if (!$valid) return ['enabled' => false, 'reason' => 'Candidate identity, recorded test result or separate artifact ZIP digest is incomplete.'];
    $expected = 'https://github.com/' . GITHUB_OWNER . '/' . GITHUB_REPO . '/actions/runs/' . $candidate['build_run'] . '/artifacts/' . $candidate['artifact_id'];
    if (($candidate['download_url'] ?? '') !== $expected) return ['enabled' => false, 'reason' => 'Candidate download URL does not match the exact repository/run/artifact.'];
    $token = githubToken(); $run = completedRun($candidate['build_run'], $candidate['source_commit'], $token);
    if (!$run['ok']) return ['enabled' => false, 'reason' => $run['error']];
    $r = githubRequest('/actions/artifacts/' . $candidate['artifact_id'], $token);
    if (!$r['ok']) return ['enabled' => false, 'reason' => $r['error']];
    $d = $r['data']; $expires = timestamp($d['expires_at'] ?? null);
    if ((string)($d['id'] ?? '') !== (string)$candidate['artifact_id'] || ($d['name'] ?? '') !== $candidate['artifact_name']
        || ($d['expired'] ?? null) !== false || $expires === null || $expires <= time()
        || (string)($d['workflow_run']['id'] ?? '') !== (string)$candidate['build_run']
        || strtolower((string)($d['workflow_run']['head_sha'] ?? '')) !== strtolower($candidate['source_commit'])
        || strtolower((string)($d['digest'] ?? '')) !== 'sha256:' . strtolower($candidate['artifact_sha256']))
        return ['enabled' => false, 'reason' => 'Artifact identity, ZIP digest, workflow binding or expiry check failed.'];
    return ['enabled' => true, 'reason' => 'GitHub publication/run/artifact metadata checked. ISO hash and test outcome are repository records, not independent binary verification.', 'expires_at' => $d['expires_at']];
}
function releaseGate(array $release, bool $publicationOkay): array {
    if ($release['status'] !== 'approved') return ['enabled' => false, 'reason' => $release['status'] === 'none' ? 'No approved release is recorded.' : 'UNKNOWN or unapproved release state; download disabled.'];
    if (!$publicationOkay) return ['enabled' => false, 'reason' => 'Publication consistency checks have not passed.'];
    if (!identifier($release['release_id'] ?? null) || !identifier($release['asset_id'] ?? null) || !identifier($release['build_run'] ?? null)
        || !commit40($release['source_commit'] ?? null) || !digest64($release['sha256'] ?? null) || !nonemptyText($release['tag'] ?? null, 150)
        || !nonemptyText($release['filename'] ?? null, 200) || preg_match('/^[A-Za-z0-9][A-Za-z0-9._+-]*\.iso$/D', $release['filename']) !== 1
        || !is_int($release['size_bytes'] ?? null) || $release['size_bytes'] <= 0 || ($release['smoke_test'] ?? '') !== 'passed'
        || ($release['authentication']['status'] ?? '') !== 'VERIFIED' || !digest64($release['authentication']['manifest_sha256'] ?? null)
        || !digest64($release['authentication']['public_key_spki_sha256'] ?? null))
        return ['enabled' => false, 'reason' => 'Approved release identity or publisher-recorded authenticated-manifest evidence is incomplete.'];
    $prefix = 'https://github.com/' . GITHUB_OWNER . '/' . GITHUB_REPO . '/releases/download/';
    $url = safeHttpsUrl($release['download_url'] ?? '');
    if ($url === '' || !str_starts_with($url, $prefix)) return ['enabled' => false, 'reason' => 'Release download is not a bound repository asset URL.'];
    $token = githubToken(); $run = completedRun($release['build_run'], $release['source_commit'], $token);
    if (!$run['ok']) return ['enabled' => false, 'reason' => $run['error']];
    $r = githubRequest('/releases/' . $release['release_id'], $token);
    if (!$r['ok']) return ['enabled' => false, 'reason' => $r['error']];
    $d = $r['data'];
    if ((string)($d['id'] ?? '') !== (string)$release['release_id'] || ($d['tag_name'] ?? '') !== $release['tag'] || ($d['draft'] ?? true) !== false || ($d['prerelease'] ?? true) !== false || !is_array($d['assets'] ?? null))
        return ['enabled' => false, 'reason' => 'Release metadata does not match the approved published release.'];
    $commit = githubRequest('/commits/' . rawurlencode($release['tag']), $token);
    if (!$commit['ok'] || strtolower((string)($commit['data']['sha'] ?? '')) !== strtolower($release['source_commit'])) return ['enabled' => false, 'reason' => 'Release tag does not resolve to the recorded source commit.'];
    foreach ($d['assets'] as $asset) if (is_array($asset) && (string)($asset['id'] ?? '') === (string)$release['asset_id']
        && ($asset['name'] ?? '') === $release['filename'] && ($asset['state'] ?? '') === 'uploaded' && ($asset['size'] ?? null) === $release['size_bytes']
        && ($asset['browser_download_url'] ?? '') === $url && strtolower((string)($asset['digest'] ?? '')) === 'sha256:' . strtolower($release['sha256']))
        return ['enabled' => true, 'reason' => 'Approval/authentication are publisher records; GitHub release asset and source metadata match. The viewer does not independently verify an ISO signature.'];
    return ['enabled' => false, 'reason' => 'Release asset identity, ISO byte count or SHA-256 does not match GitHub metadata.'];
}
function readTelemetry(): array {
    $empty = ['status' => 'UNKNOWN', 'rows' => [], 'reason' => 'No validated private public-telemetry snapshot is available.'];
    $path = BUILDER_STATUS_FILE;
    if (!is_file($path) || !is_readable($path) || !privateModeIsSafe($path) || !outsideWebRoot($path) || @filesize($path) > 16384) return $empty;
    $raw = @file_get_contents($path, false, null, 0, 16385); $d = is_string($raw) ? json_decode($raw, true) : null;
    if (!objectArray($d) || ($d['schema'] ?? '') !== 'blackcat-public-telemetry-v1' || ($observed = timestamp($d['observed_at'] ?? null)) === null || $observed > time() + 60) return $empty;
    $rows = [['label' => 'Observed at (UTC/offset)', 'value' => $d['observed_at']]];
    $enums = ['runner_status' => ['online','offline','busy','idle','unknown'], 'service_status' => ['active','inactive','failed','unknown'],
        'last_build_conclusion' => ['success','failure','cancelled','in_progress','unknown']];
    foreach ($enums as $key => $allowed) if (is_string($d[$key] ?? null) && in_array($d[$key], $allowed, true)) $rows[] = ['label' => $key, 'value' => $d[$key]];
    if (is_int($d['disk_free_bytes'] ?? null) && $d['disk_free_bytes'] >= 0) $rows[] = ['label' => 'disk_free_bytes', 'value' => formatBytes($d['disk_free_bytes'])];
    if (identifier($d['last_build_run'] ?? null)) $rows[] = ['label' => 'last_build_run', 'value' => (string)$d['last_build_run']];
    $stale = time() - $observed > TELEMETRY_MAX_AGE;
    return ['status' => $stale ? 'WARNING' : 'OBSERVED', 'rows' => $rows, 'reason' => $stale ? 'STALE snapshot: older than 15 minutes; current runtime state is UNKNOWN.' : 'Recent allowlisted observation, not host or hardware attestation.'];
}

$result = fetchProject();
$project = (($result['ok'] ?? false) === true && is_array($result['project'] ?? null)) ? $result['project'] : null;
$error = $project === null ? (string)($result['error'] ?? 'Project data unavailable. Downloads are disabled.') : '';
$githubSha = $project !== null ? $result['sha'] : '';
if ($project === null) http_response_code(503);
$publicationOkay = $project !== null && ($result['publication_ok'] ?? false) === true;
$builderStatus = readTelemetry(); $builderRows = $builderStatus['rows'];
$title = (string)($project['title'] ?? 'Black Cat'); $version = (string)($project['version'] ?? 'UNKNOWN');
$phase = (string)($project['phase'] ?? 'UNKNOWN'); $updated = (string)($project['updated'] ?? 'UNKNOWN'); $summary = (string)($project['summary'] ?? '');
$capabilities = $project['capabilities'] ?? []; $sections = $project['sections'] ?? []; $documents = $project['documents'] ?? [];
$milestones = $project['milestones'] ?? []; $decisions = $project['decisions'] ?? []; $changelog = $project['changelog'] ?? [];
$candidate = $project['candidate'] ?? ['status' => 'none']; $candidateStatus = $candidate['status'];
$candidateFilename = $candidate['filename'] ?? ''; $candidateBytes = $candidate['size_bytes'] ?? 0; $candidateHash = $candidate['sha256'] ?? '';
$candidateSourceCommit = $candidate['source_commit'] ?? ''; $candidateBuildRun = (string)($candidate['build_run'] ?? '');
$candidateArtifactId = (string)($candidate['artifact_id'] ?? ''); $candidateArtifactName = $candidate['artifact_name'] ?? '';
$candidateSmokeTest = $candidate['smoke_test'] ?? 'UNKNOWN'; $candidateDownload = safeHttpsUrl($candidate['download_url'] ?? ''); $candidateNote = $candidate['note'] ?? '';
$candidateCheck = candidateGate($candidate, $publicationOkay); $candidateEnabled = $candidateCheck['enabled'];
$release = $project['release'] ?? ['status' => 'none']; $releaseStatus = $release['status']; $releaseDownload = safeHttpsUrl($release['download_url'] ?? '');
$releasePageExpected = 'https://github.com/' . GITHUB_OWNER . '/' . GITHUB_REPO . '/releases/tag/' . rawurlencode($release['tag'] ?? '');
$releasePage = ($release['release_page_url'] ?? '') === $releasePageExpected ? safeHttpsUrl($releasePageExpected) : ''; $releaseFilename = $release['filename'] ?? ''; $releaseBytes = $release['size_bytes'] ?? 0;
$releaseHash = $release['sha256'] ?? ''; $releaseSourceCommit = $release['source_commit'] ?? ''; $releaseBuildRun = (string)($release['build_run'] ?? '');
$releaseSmokeTest = $release['smoke_test'] ?? 'UNKNOWN'; $releaseNote = $release['note'] ?? '';
$releaseCheck = releaseGate($release, $publicationOkay); $downloadEnabled = $releaseCheck['enabled'];
$checks = [
    ['label' => 'Protected credential file prerequisites', 'status' => githubToken() !== '' ? 'OBSERVED' : 'FAILED', 'detail' => 'Checked file location, restrictive permissions and available ownership checks; token value is never rendered. Token scope and host security are not attested.'],
    ['label' => 'Project JSON structure and Git blob identity', 'status' => $project !== null ? 'VERIFIED' : 'FAILED', 'detail' => 'Bounded typed contract and standard Git object identifier; not release or runtime trust.'],
    ['label' => 'Feed JSON matches canonical main snapshot', 'status' => $project === null ? 'UNKNOWN' : (($result['main_match'] ?? false) ? 'VERIFIED' : 'WARNING'), 'detail' => 'Compares this feed with main JSON fetched/revalidated for this request; not all repository files or deployment synchronization.'],
    ['label' => 'Repeated status consistency', 'status' => $publicationOkay ? 'VERIFIED' : 'WARNING', 'detail' => implode(' ', $result['warnings'] ?? []) ?: 'Known repeated merge-status fields agree.'],
    ['label' => 'Firmware, target runtime and independent attestation', 'status' => 'UNKNOWN', 'detail' => 'This webpage does not inspect or attest the target system.']
];
$viewerSha = @hash_file('sha256', __FILE__) ?: 'UNKNOWN';
?>

<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= h($title) ?></title>
<style>
:root{--bg:#050706;--panel:#0d120f;--panel2:#111713;--text:#f4f7f5;--muted:#a7b3ab;--muted2:#758078;--green:#67f58e;--greenB:rgba(103,245,142,.22);--border:rgba(255,255,255,.075);--yellow:#f0cc70;--red:#ff8080;--max:1180px}*{box-sizing:border-box}html{scroll-behavior:smooth}body{margin:0;font-family:Inter,ui-sans-serif,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;background:radial-gradient(circle at 50% -10%,rgba(70,255,125,.09),transparent 35%),linear-gradient(180deg,#070a08,#040504 45%,#070907);color:var(--text);line-height:1.65}.wrap{width:min(var(--max),calc(100% - 40px));margin:auto}nav{position:sticky;top:0;z-index:30;background:rgba(5,7,6,.92);border-bottom:1px solid var(--border)}.nav{min-height:68px;display:flex;align-items:center;justify-content:space-between;gap:20px}.brand{font-weight:850;display:flex;align-items:center;gap:11px}.logo{width:34px;height:34px;border:1px solid var(--greenB);border-radius:10px;display:grid;place-items:center;color:var(--green);font-size:12px}.navlinks{display:flex;gap:15px;align-items:center}.navlinks a,.navlinks button{color:var(--muted);font:inherit;font-size:12px;text-decoration:none;background:none;border:0;cursor:pointer}.hero{padding:82px 0 58px}.kicker{color:var(--green);font-size:11px;font-weight:900;letter-spacing:.15em;text-transform:uppercase}.hero h1,h2{letter-spacing:-.045em;line-height:1.05}.hero h1{font-size:clamp(48px,7vw,86px);margin:12px 0 0}.summary{font-size:19px;color:#bec8c1;max-width:850px;margin-top:26px}.cards,.sourcegrid,.statusgrid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px}.cards{margin-top:36px}.card,.releasebox,.doc,.mile,.sourcebox,.statuscard,.errorbox{border:1px solid var(--border);background:var(--panel);border-radius:14px}.card{padding:18px}.label,.sourceitem strong,.statuscard strong{color:var(--muted2);font-size:10px;text-transform:uppercase;letter-spacing:.1em;font-weight:900}.value{font-weight:800;font-size:18px;margin-top:5px}.bar{height:8px;border:1px solid var(--border);background:#111713;border-radius:99px;overflow:hidden;margin-top:17px}.bar>div{height:100%;background:linear-gradient(90deg,#2ec75e,var(--green));width:0}.release,.docs,.section{padding:62px 0;border-top:1px solid var(--border)}h2{font-size:clamp(31px,4vw,50px);margin:7px 0 20px}.releasebox{padding:25px;border-color:var(--greenB);margin-bottom:18px}.candidatebox{border-color:rgba(240,204,112,.32)}.releasehead,.statushead,.sourcehead{display:flex;justify-content:space-between;gap:20px;align-items:flex-start}.release-status,.pill{font-size:10px;font-weight:900;letter-spacing:.1em;text-transform:uppercase;padding:7px 10px;border-radius:99px;height:max-content}.approved,.livepill{color:var(--green);background:rgba(103,245,142,.09);border:1px solid rgba(103,245,142,.18)}.pending{color:var(--yellow);background:rgba(240,204,112,.09)}.releasegrid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:9px 22px}.releasefield{padding:9px 0;border-bottom:1px solid rgba(255,255,255,.05)}.releasefield strong{display:block;color:var(--muted2);font-size:10px;text-transform:uppercase;letter-spacing:.09em}.releasefield span,.releasefield code{display:block;margin-top:4px;overflow-wrap:anywhere}.releaseactions{display:flex;gap:12px;flex-wrap:wrap;margin-top:20px}.downloadbtn,.secondarybtn{display:inline-flex;padding:12px 16px;border-radius:11px;font-weight:850;text-decoration:none}.downloadbtn{background:var(--green);color:#041006}.candidatebtn{background:var(--yellow);color:#171005}.downloadbtn.disabled{background:#182019;color:#657068;pointer-events:none}.secondarybtn{border:1px solid var(--border);color:var(--muted)}.release-note,.mutednote{color:var(--muted);font-size:13px;margin-top:15px}.warning{color:var(--yellow);font-weight:850}.sourcebox{padding:25px;border-color:var(--greenB)}.sourcegrid{grid-template-columns:repeat(3,minmax(0,1fr));margin-top:20px}.sourceitem,.statuscard{padding:17px;background:var(--panel2);border:1px solid var(--border);border-radius:12px;min-width:0}.sourceitem span,.sourceitem code,.statuscard span{display:block;margin-top:6px;overflow-wrap:anywhere;font-size:14px}.statusgrid{grid-template-columns:repeat(3,minmax(0,1fr));margin-top:20px}.statuscard span{font-size:15px;font-weight:700}.docgrid{display:grid;grid-template-columns:repeat(3,1fr);gap:14px}.doc{display:flex;flex-direction:column;padding:21px;min-height:210px;text-decoration:none;color:inherit}.doctype,.date{color:var(--muted2);font-size:10px;text-transform:uppercase;letter-spacing:.1em}.doctype{color:var(--green);font-weight:900}.doc h3{margin:8px 0}.doc p,.prose,.event p{color:var(--muted)}.doclink{margin-top:auto;color:var(--green);font-weight:800}.sectiongrid{display:grid;grid-template-columns:210px 1fr;gap:45px}.index{color:var(--green);font-size:10px;text-transform:uppercase;letter-spacing:.12em;font-weight:900}.prose{font-size:17px;max-width:900px}.milestones{display:grid;gap:9px}.mile{display:grid;grid-template-columns:65px 1fr auto;gap:15px;align-items:center;padding:14px 17px}.status{padding:5px 8px;border-radius:99px;font-size:9px;text-transform:uppercase;font-weight:900}.complete{color:var(--green)}.current{color:var(--yellow)}.planned{color:var(--muted)}.timeline{border-left:1px solid var(--greenB);padding-left:24px}.event{margin-bottom:28px}.event h3{margin:3px 0}.errorbox{max-width:760px;margin:90px auto;padding:34px;border-color:rgba(255,128,128,.25)}.fail{color:var(--red);font-weight:850}footer{padding:38px 0 55px;border-top:1px solid var(--border);color:var(--muted2);font-size:12px}@media(max-width:900px){.cards,.sourcegrid,.statusgrid,.docgrid,.releasegrid{grid-template-columns:1fr 1fr}.sectiongrid{grid-template-columns:1fr}}@media(max-width:620px){.wrap{width:calc(100% - 26px)}.cards,.sourcegrid,.statusgrid,.docgrid,.releasegrid{grid-template-columns:1fr}.navlinks a{display:none}.mile{grid-template-columns:52px 1fr}.mile .status{grid-column:2}.releasehead,.statushead,.sourcehead{display:block}.pill,.release-status{display:inline-block;margin-top:8px}}@media print{nav,.downloadbtn,.secondarybtn{display:none}body{background:white;color:#111}}
.prose,footer,.mutednote{overflow-wrap:anywhere}.mile>div{min-width:0;overflow-wrap:anywhere}
</style>
</head>
<body>
<nav><div class="wrap nav"><div class="brand"><span class="logo">BC</span>Black Cat <span style="color:var(--muted2)">/ PTL</span></div><div class="navlinks"><a href="#candidate">Test ISO</a><a href="#status">Status</a><a href="#source">Data source</a><a href="#documents">Documents</a><a href="#progress">Progress</a><button type="button" id="viewer-print">Print / PDF</button></div></div></nav>

<?php if ($project === null): ?>
<main class="wrap"><div class="errorbox"><div class="kicker">Fail-closed project connection</div><h1>Black Cat project data unavailable.</h1><p class="fail">No stale project data is being served as authority.</p><p><?= h($error) ?></p><p>The credential value is never displayed by this page.</p></div></main>
<?php else: ?>
<header class="hero"><div class="wrap"><div class="kicker">Portable Trusted Linux engineering project</div><h1><?= h($title) ?></h1><div class="summary"><?= nl2br(h($summary)) ?></div><div class="cards"><div class="card"><div class="label">Version</div><div class="value"><?= h($version) ?></div></div><div class="card"><div class="label">Phase</div><div class="value"><?= h($phase) ?></div></div><div class="card"><div class="label">Evidence</div><div class="value">Capability by capability</div></div><div class="card"><div class="label">Updated</div><div class="value"><?= h($updated) ?></div></div></div></div></header>

<section class="section" id="checks"><div class="wrap"><div class="kicker">Checks with explicit limits</div><h2>What this page checked</h2><div class="statusgrid"><?php foreach ($checks as $check): ?><div class="statuscard"><strong><?= h($check['label']) ?></strong><span><?= h($check['status']) ?></span><p class="mutednote"><?= h($check['detail']) ?></p></div><?php endforeach; ?></div><p class="mutednote">These results cover this viewer's observed configuration and publication metadata. They do not establish that a host or target machine is fully secured. Candidate and release gates are separate.</p></div></section>

<section class="release" id="candidate"><div class="wrap"><div class="releasebox candidatebox"><div class="releasehead"><div><div class="kicker">Development distribution</div><h2>Current Test Candidate</h2></div><div class="release-status pending"><?= $candidateEnabled ? 'Metadata checks VERIFIED' : ($candidateStatus === 'test' ? 'Candidate download blocked' : 'No test candidate') ?></div></div><?php if ($candidateEnabled): ?><p class="warning">TEST CANDIDATE — NOT AN APPROVED RELEASE</p><div class="releasegrid"><div class="releasefield"><strong>Filename</strong><span><?= h($candidateFilename) ?></span></div><div class="releasefield"><strong>Exact size</strong><span><?= h(formatBytes($candidateBytes)) ?></span></div><div class="releasefield"><strong>SHA-256</strong><code><?= h($candidateHash) ?></code></div><div class="releasefield"><strong>Source commit</strong><code><?= h($candidateSourceCommit) ?></code></div><div class="releasefield"><strong>Build run</strong><span><?= h($candidateBuildRun) ?></span></div><div class="releasefield"><strong>Artifact</strong><span><?= h($candidateArtifactName . ' (#' . $candidateArtifactId . ')') ?></span></div><div class="releasefield"><strong>Recorded automated boot smoke test</strong><span><?= h($candidateSmokeTest) ?></span></div><div class="releasefield"><strong>Physical hardware validation</strong><span>pending</span></div></div><div class="releaseactions"><a class="downloadbtn candidatebtn" href="<?= h($candidateDownload) ?>" target="_blank" rel="noopener noreferrer">Download current test candidate ↓</a></div><div class="release-note"><?= h($candidateCheck['reason']) ?><br><?= h($candidateNote) ?><br>Artifact expiry: <?= h((string)($candidateCheck['expires_at'] ?? 'UNKNOWN')) ?>. Physical media actions require separate operator authorization.</div><?php else: ?><div class="releaseactions"><span class="downloadbtn disabled" aria-disabled="true">Test candidate unavailable</span></div><div class="release-note"><?= h($candidateCheck['reason']) ?></div><?php endif; ?></div></div></section>

<section class="release" id="release"><div class="wrap"><div class="releasebox"><div class="releasehead"><div><div class="kicker">Controlled release distribution</div><h2>Approved Black Cat ISO</h2></div><div class="release-status <?= $downloadEnabled ? 'approved' : 'pending' ?>"><?= $downloadEnabled ? 'Approval recorded; asset checked' : ($releaseStatus === 'approved' ? 'Approved download blocked' : 'No approved ISO') ?></div></div><div class="releasegrid"><div class="releasefield"><strong>Filename</strong><span><?= h($releaseFilename !== '' ? $releaseFilename : 'Not published') ?></span></div><div class="releasefield"><strong>Exact size</strong><span><?= h(formatBytes($releaseBytes)) ?></span></div><div class="releasefield"><strong>SHA-256</strong><code><?= h($releaseHash !== '' ? $releaseHash : 'Not published') ?></code></div><div class="releasefield"><strong>Source commit</strong><code><?= h($releaseSourceCommit !== '' ? $releaseSourceCommit : 'Not published') ?></code></div><div class="releasefield"><strong>Build run</strong><span><?= h($releaseBuildRun !== '' ? $releaseBuildRun : 'Not published') ?></span></div><div class="releasefield"><strong>Recorded automated boot smoke test</strong><span><?= h($releaseSmokeTest !== '' ? $releaseSmokeTest : 'pending') ?></span></div></div><div class="releaseactions"><?php if ($downloadEnabled): ?><a class="downloadbtn" href="<?= h($releaseDownload) ?>">Download approved ISO ↓</a><?php else: ?><span class="downloadbtn disabled" aria-disabled="true">Download unavailable</span><?php endif; ?><?php if ($releasePage !== ''): ?><a class="secondarybtn" href="<?= h($releasePage) ?>" target="_blank" rel="noopener noreferrer">View GitHub release →</a><?php endif; ?></div><div class="release-note"><?= h($releaseCheck['reason']) ?><br><?= h($releaseNote) ?></div></div></div></section>

<section class="section" id="status"><div class="wrap"><div class="kicker">Operational observation</div><h2>Builder / Server Status</h2><div class="pill pending"><?= h($builderStatus['status']) ?></div><p><?= h($builderStatus['reason']) ?></p><div class="statusgrid"><?php foreach ($builderRows as $row): ?><div class="statuscard"><strong><?= h(str_replace('_', ' ', $row['label'])) ?></strong><span><?= h($row['value']) ?></span></div><?php endforeach; ?></div><div class="mutednote">Only allowlisted enum/numeric fields from a private snapshot are displayed. This is display-only operational telemetry. It cannot enable, replace, supersede or approve project/candidate/release data. Old public project_cache/builder_status.json files need removal or explicit web-server access denial; this PHP cannot protect a separate static URL.</div></div></section>

<section class="section" id="source"><div class="wrap"><div class="sourcebox"><div class="kicker">Project Data Source</div><h2>Data identity checked for this request</h2><div class="pill pending">FETCHED DATA — SCOPE SHOWN BELOW</div><div class="sourcegrid"><div class="sourceitem"><strong>Repository</strong><span><?= h(GITHUB_OWNER . '/' . GITHUB_REPO) ?></span></div><div class="sourceitem"><strong>Feed branch</strong><code><?= h(GITHUB_BRANCH) ?></code></div><div class="sourceitem"><strong>Project data</strong><span><?= h(CONTENT_FILE) ?></span></div><div class="sourceitem"><strong>Feed Git blob</strong><code><?= h($githubSha) ?></code></div><div class="sourceitem"><strong>Main Git blob</strong><code><?= h((string)($result['main_sha'] ?? 'UNKNOWN')) ?></code></div><div class="sourceitem"><strong>Checked at UTC</strong><span><?= h((string)($result['checked_at'] ?? 'UNKNOWN')) ?></span></div></div><p class="mutednote">GitHub HTTPS API data, with conditional revalidation when a private cache is available. An upstream failure never substitutes a cached success. Matching JSON is not proof of deployed PHP identity, ISO signatures or runtime security.</p></div></div></section>

<section class="docs" id="documents"><div class="wrap"><div class="kicker">Project library</div><h2>Documents &amp; project files</h2><div class="docgrid"><?php foreach ($documents as $doc): $url = safeHttpsUrl((string)($doc['url'] ?? '')); if ($url === '') continue; ?><a class="doc" href="<?= h($url) ?>" target="_blank" rel="noopener noreferrer"><div class="doctype"><?= h((string)($doc['type'] ?? 'Document')) ?></div><h3><?= h((string)($doc['title'] ?? 'Untitled')) ?></h3><p><?= h((string)($doc['description'] ?? '')) ?></p><div class="doclink">Open document →</div></a><?php endforeach; ?></div></div></section>

<?php foreach ($sections as $index => $section): ?><section class="section"><div class="wrap sectiongrid"><aside class="index">Section <?= str_pad((string)($index + 1), 2, '0', STR_PAD_LEFT) ?></aside><div><h2><?= h((string)($section['title'] ?? '')) ?></h2><div class="prose"><?php $paragraphs = preg_split('/\R{2,}/', trim((string)($section['content'] ?? ''))); if (is_array($paragraphs)) foreach ($paragraphs as $paragraph) if (trim($paragraph) !== '') echo '<p>' . nl2br(h(trim($paragraph))) . '</p>'; ?></div></div></div></section><?php endforeach; ?>

<section class="section" id="capabilities"><div class="wrap"><div class="kicker">Evidence, not a completion percentage</div><h2>What has been demonstrated</h2><div class="milestones"><?php foreach ($capabilities as $capability): ?><div class="mile"><div class="date"><?= h((string)($capability['id'] ?? '')) ?></div><div><strong><?= h((string)($capability['title'] ?? '')) ?></strong><br><span class="mutednote"><?= h((string)($capability['scope'] ?? '')) ?></span></div><div class="status"><?= h((string)($capability['stage'] ?? 'unknown')) ?></div></div><?php endforeach; ?></div></div></section>

<section class="section" id="progress"><div class="wrap"><div class="kicker">Project progress</div><h2>Milestones</h2><div class="milestones"><?php foreach ($milestones as $milestone): $status = (string)($milestone['status'] ?? 'UNKNOWN'); $statusKey = strtolower($status); $statusClass = in_array($statusKey, ['complete','current','planned'], true) ? $statusKey : 'planned'; ?><div class="mile"><div class="date"><?= h((string)($milestone['id'] ?? '')) ?></div><div><?= h((string)($milestone['title'] ?? '')) ?></div><div class="status <?= h($statusClass) ?>"><?= h($status) ?></div></div><?php endforeach; ?></div></div></section>

<section class="section" id="decisions"><div class="wrap"><div class="kicker">Architecture record</div><h2>Decisions</h2><div class="timeline"><?php foreach (array_reverse($decisions) as $decision): ?><div class="event"><div class="date"><?= h((string)($decision['date'] ?? '')) ?></div><h3><?= h((string)($decision['title'] ?? '')) ?></h3><p><?= h((string)($decision['text'] ?? '')) ?></p></div><?php endforeach; ?></div></div></section>

<section class="section"><div class="wrap"><div class="kicker">Historical record</div><h2>Version history</h2><div class="timeline"><?php foreach (array_reverse($changelog) as $change): ?><div class="event"><div class="date"><?= h((string)($change['date'] ?? '')) ?></div><h3>Version <?= h((string)($change['version'] ?? '')) ?></h3><p><?= h((string)($change['summary'] ?? '')) ?></p></div><?php endforeach; ?></div></div></section>
<?php endif; ?>

<footer><div class="wrap"><strong style="color:#fff">Black Cat</strong> · Portable Trusted Linux<br>Development candidate identity separated from approved release distribution</div><p class="wrap mutednote">Viewer <?= h(VIEWER_VERSION) ?> · source SHA-256 <code><?= h($viewerSha) ?></code>. Compare this source digest with the reviewed deployment artifact; this page cannot independently attest itself.</p></footer>
<script nonce="<?= h($scriptNonce) ?>">document.getElementById('viewer-print')?.addEventListener('click', () => window.print());</script>
</body>
</html>

