<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$destination = $argv[1] ?? '';
$allowed = realpath(getenv('LOCALAPPDATA').'/BankDPTaspenBackups');
$resolved = realpath($destination);
if (! $allowed || ! $resolved || ! str_starts_with(strtolower($resolved), strtolower($allowed.DIRECTORY_SEPARATOR))) {
    throw new RuntimeException('Use a pre-created protected backup directory outside the project.');
}
$config = config('database.connections.'.config('database.default'));
if (! in_array($config['driver'], ['mysql', 'mariadb'], true)) {
    throw new RuntimeException('This backup command requires the application MySQL connection.');
}
$dump = 'D:/xampp/mysql/bin/mysqldump.exe';
if (! is_file($dump)) {
    throw new RuntimeException('mysqldump is unavailable.');
}
$defaults = $resolved.'/connection.cnf';
$escape = fn ($value) => '"'.str_replace(['\\', '"', "\n", "\r"], ['\\\\', '\\"', '\\n', '\\r'], (string) $value).'"';
file_put_contents($defaults, "[client]\n".implode("\n", array_map(fn ($key, $value) => $key.'='.$escape($value),
    ['host', 'port', 'user', 'password'], [$config['host'], $config['port'], $config['username'], $config['password']])));
try {
    $process = proc_open([$dump, '--defaults-extra-file='.$defaults, '--single-transaction', '--routines', '--events', '--triggers', '--hex-blob', '--result-file='.$resolved.'/database.sql', $config['database']],
        [0 => ['pipe', 'r'], 1 => ['file', $resolved.'/dump-output.log', 'w'], 2 => ['file', $resolved.'/dump-error.log', 'w']], $pipes);
    if (! is_resource($process)) {
        throw new RuntimeException('Backup process could not start.');
    }
    fclose($pipes[0]);
    if (proc_close($process) !== 0 || filesize($resolved.'/database.sql') < 1000) {
        throw new RuntimeException('Database backup failed. Inspect the protected backup log.');
    }
} finally {
    @unlink($defaults);
}
$zip = new ZipArchive;
if ($zip->open($resolved.'/source-before.zip', ZipArchive::CREATE | ZipArchive::EXCL) !== true) {
    throw new RuntimeException('Source backup could not be created.');
}
foreach (['app', 'bootstrap', 'config', 'database/migrations', 'database/seeders', 'docs', 'resources', 'routes', 'scripts', 'tests', 'agent', 'android-print-agent'] as $directory) {
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(base_path($directory), FilesystemIterator::SKIP_DOTS));
    foreach ($iterator as $file) {
        $relative = str_replace('\\', '/', substr($file->getPathname(), strlen(base_path()) + 1));
        if ($file->isFile() && ! preg_match('~/(?:\.sdk|\.build|\.gradle|cache)/~', $relative)) {
            $zip->addFile($file->getPathname(), $relative);
        }
    }
}
foreach (['.env', '.gitignore', 'composer.json', 'composer.lock', 'package.json', 'package-lock.json', 'phpunit.xml', 'README.md', 'kiosk.config.json', 'buka-kiosk.cmd'] as $file) {
    if (is_file(base_path($file))) {
        $zip->addFile(base_path($file), $file);
    }
}
$zip->close();
$counts = [];
foreach (['branches', 'queues', 'queue_logs', 'print_jobs', 'print_agents', 'kiosk_devices', 'kiosk_print_bindings'] as $table) {
    $counts[$table] = DB::table($table)->count();
}
$metadata = ['created_at' => now()->toIso8601String(), 'counts' => $counts, 'database_sha256' => hash_file('sha256', $resolved.'/database.sql'), 'source_sha256' => hash_file('sha256', $resolved.'/source-before.zip')];
file_put_contents($resolved.'/before.json', json_encode($metadata, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
echo json_encode(['backup' => $resolved, 'database_bytes' => filesize($resolved.'/database.sql'), 'counts' => $counts], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR).PHP_EOL;
