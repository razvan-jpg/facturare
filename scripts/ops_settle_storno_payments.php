<?php

/**
 * One-shot ops: settle payment status for all storno + original pairs.
 *
 * CLI:  php scripts/ops_settle_storno_payments.php
 * Web:  upload as public/dc_ops_settle_storno.php then GET ?run=1 (self-deletes).
 */

declare(strict_types=1);

use App\Models\Document;
use App\Services\DocumentService;
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

try {
    $svc = app(DocumentService::class);
    $stornos = Document::query()
        ->where('status', 'storno')
        ->whereNotNull('related_document_id')
        ->orderBy('id')
        ->get();

    echo 'storno_count='.$stornos->count()."\n";
    @ob_flush();
    @flush();

    $updated = 0;
    $ok = 0;
    $missing = 0;

    foreach ($stornos as $storno) {
        $original = Document::query()->find($storno->related_document_id);
        if (! $original) {
            echo "MISSING original for storno #{$storno->id}\n";
            $missing++;

            continue;
        }

        $beforePaid = $original->payment_status === 'paid' && $storno->payment_status === 'paid';
        $beforeAmtOk = (float) $original->paid_amount + 0.009 >= abs((float) $original->total)
            && (float) $storno->paid_amount + 0.009 >= abs((float) $storno->total);

        $svc->settleStornoPair($original, $storno);
        $original->refresh();
        $storno->refresh();

        $afterPaid = $original->payment_status === 'paid' && $storno->payment_status === 'paid';
        if ((! $beforePaid || ! $beforeAmtOk) && $afterPaid) {
            $updated++;
            echo "UPDATED original #{$original->id} + storno #{$storno->id} {$storno->number_full}\n";
        } else {
            $ok++;
            echo "OK original #{$original->id} ({$original->payment_status}) + storno #{$storno->id} ({$storno->payment_status})\n";
        }
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

    echo "summary updated={$updated} already_ok={$ok} missing={$missing}\n";
} catch (Throwable $e) {
    echo 'ERR '.$e->getMessage()."\n".$e->getFile().':'.$e->getLine()."\n";
}

if (PHP_SAPI !== 'cli') {
    @unlink(__FILE__);
    echo "self_deleted\n";
}
