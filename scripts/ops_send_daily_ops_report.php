<?php

/**
 * One-shot ops: trimite raportul recapitular zilnic (ops:daily-report).
 *
 * CLI:  php scripts/ops_send_daily_ops_report.php
 * Web:  upload as public/dc_ops_daily_report.php then GET ?run=1&date=YYYY-MM-DD (self-deletes).
 */
declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;

$base = dirname(__DIR__);
if (! is_file($base.'/vendor/autoload.php')) {
    fwrite(STDERR, "autoload missing at {$base}\n");
    exit(1);
}

require $base.'/vendor/autoload.php';
$app = require $base.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

if (PHP_SAPI !== 'cli') {
    header('Content-Type: text/plain; charset=utf-8');
    if (($_GET['run'] ?? '') !== '1') {
        echo "Add ?run=1&date=YYYY-MM-DD to execute.\n";
        exit;
    }
}

$date = PHP_SAPI === 'cli'
    ? ($argv[1] ?? date('Y-m-d'))
    : (string) ($_GET['date'] ?? date('Y-m-d'));

try {
    echo "=== ops:daily-report --date={$date} --force ===\n";
    @ob_flush();
    @flush();

    $exit = Artisan::call('ops:daily-report', [
        '--date' => $date,
        '--force' => true,
    ]);
    echo Artisan::output();
    echo "exit={$exit}\n";
} catch (Throwable $e) {
    echo 'ERR '.$e->getMessage()."\n".$e->getFile().':'.$e->getLine()."\n";
    $exit = 1;
}

if (PHP_SAPI !== 'cli') {
    @unlink(__FILE__);
    echo "self_deleted\n";
}

exit($exit ?? 0);
