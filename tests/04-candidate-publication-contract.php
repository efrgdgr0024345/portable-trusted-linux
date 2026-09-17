<?php
declare(strict_types=1);

function safeHttps(string $url): string {
    $url = trim($url);
    return filter_var($url, FILTER_VALIDATE_URL) !== false
        && strtolower((string)parse_url($url, PHP_URL_SCHEME)) === 'https' ? $url : '';
}
function viewerEnables(array $c): bool {
    return strtolower(trim((string)($c['status'] ?? 'none'))) === 'test'
        && trim((string)($c['filename'] ?? '')) !== ''
        && (int)($c['size_bytes'] ?? 0) > 0
        && preg_match('/^[a-f0-9]{64}$/', strtolower(trim((string)($c['sha256'] ?? '')))) === 1
        && preg_match('/^[a-f0-9]{40}$/i', trim((string)($c['source_commit'] ?? ''))) === 1
        && preg_match('/^[0-9]+$/', (string)($c['build_run'] ?? '')) === 1
        && preg_match('/^[0-9]+$/', (string)($c['artifact_id'] ?? '')) === 1
        && trim((string)($c['artifact_name'] ?? '')) !== ''
        && strtolower(trim((string)($c['smoke_test'] ?? 'pending'))) === 'passed'
        && safeHttps((string)($c['download_url'] ?? '')) !== '';
}
function publicationMatches(array $c, array $e): bool {
    $expectedUrl = 'https://github.com/efrgdgr0024345/portable-trusted-linux/actions/runs/'
        . $e['build_run'] . '/artifacts/' . $e['artifact']['id'];
    return viewerEnables($c)
        && $c['filename'] === $e['filename']
        && (int)$c['size_bytes'] === (int)$e['size_bytes']
        && strtolower($c['sha256']) === strtolower($e['sha256'])
        && strtolower($c['source_commit']) === strtolower($e['source_commit'])
        && (string)$c['build_run'] === (string)$e['build_run']
        && (string)$c['artifact_id'] === (string)$e['artifact']['id']
        && $c['artifact_name'] === $e['artifact']['name']
        && $c['download_url'] === $expectedUrl;
}
function check(bool $condition, string $label): void {
    if (!$condition) { fwrite(STDERR, "[FAILED] $label\n"); exit(1); }
    fwrite(STDOUT, "[OK]      $label\n");
}
$root = dirname(__DIR__);
$viewer = file_get_contents($root . '/web/project.php');
$evidence = json_decode((string)file_get_contents($root . '/evidence/2026-09-17-artwork-candidate/report.json'), true, 512, JSON_THROW_ON_ERROR);
check(str_contains((string)$viewer, "\$candidateStatus === 'test'"), 'fixture matches deployed viewer status contract');
$valid = [
 'status'=>'test','channel'=>'graphical-physical-test','filename'=>$evidence['filename'],
 'size_bytes'=>$evidence['size_bytes'],'sha256'=>$evidence['sha256'],'source_commit'=>$evidence['source_commit'],
 'build_run'=>(string)$evidence['build_run'],'artifact_id'=>(string)$evidence['artifact']['id'],
 'artifact_name'=>$evidence['artifact']['name'],'smoke_test'=>'passed',
 'download_url'=>'https://github.com/efrgdgr0024345/portable-trusted-linux/actions/runs/'.$evidence['build_run'].'/artifacts/'.$evidence['artifact']['id'],
 'physical_hardware_validation'=>'pending'
];
check(publicationMatches($valid,$evidence), 'correct candidate renders intended artifact link');
$case=$valid; unset($case['sha256']); check(!viewerEnables($case), 'missing hash disables handoff');
$case=$valid; $case['status']='ready-for-physical-testing'; check(!viewerEnables($case), 'wrong status disables handoff');
$case=$valid; $case['download_url']='https://github.com/efrgdgr0024345/portable-trusted-linux/actions/runs/1/artifacts/2';
check(viewerEnables($case) && !publicationMatches($case,$evidence), 'mismatched artifact URL fails publication check');
$case=$valid; unset($case['artifact_name']); check(!viewerEnables($case), 'incomplete identity disables handoff');
check(($evidence['release_approval'] ?? '') === 'none', 'evidence does not claim release approval');
