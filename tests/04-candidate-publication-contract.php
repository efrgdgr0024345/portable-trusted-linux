<?php
declare(strict_types=1);
// Compatibility entry point. The actual production PHP, not a duplicate
// predicate, is exercised with synthetic transport/credential fixtures.
putenv('PTL_PHP_BINARY=' . PHP_BINARY);
$process = proc_open(['python3', __DIR__ . '/06-project-viewer-check.py'], [0 => STDIN, 1 => STDOUT, 2 => STDERR], $pipes, dirname(__DIR__));
if (!is_resource($process)) { fwrite(STDERR, "Actual-renderer fixture process could not start.\n"); exit(1); }
exit(proc_close($process));
