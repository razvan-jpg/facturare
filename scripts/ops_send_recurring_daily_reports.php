<?php

/**
 * One-shot ops: trimite raportul PDF zilnic pentru emiterea recurentelor
 * (toate firmele) pentru datele lipsă / catch-up.
 *
 * CLI:  php scripts/ops_send_recurring_daily_reports.php
 * Web:  upload as public/dc_ops_recurring_daily_reports.php then GET ?run=1 (self-deletes).
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
        echo "Add ?run=1 to execute.\n";
        exit;
    }
}

$dates = ['2026-08-15', '2026-08-16'];

try {
    foreach ($dates as $date) {
        echo "=== recurring:finalize-day --date={$date} ===\n";
        @ob_flush();
        @flush();

        $exit = Artisan::call('recurring:finalize-day', [
            '--date' => $date,
        ]);
        echo Artisan::output();
        echo "exit={$exit}\n\n";
        @ob_flush();
        @flush();
    }

    try {
        Artisan::call('config:clear');
        Artisan::call('view:clear');
        echo "cache_cleared\n";
    } catch (Throwable $e) {
        echo 'cache_clear_skip '.$e->getMessage()."\n";
    }

    echo "done\n";
} catch (Throwable $e) {
    echo 'ERR '.$e->getMessage()."\n".$e->getFile().':'.$e->getLine()."\n";
}

if (PHP_SAPI !== 'cli') {
    @unlink(__FILE__);
    echo "self_deleted\n";
}
