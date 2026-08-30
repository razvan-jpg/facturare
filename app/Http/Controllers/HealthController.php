<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $checks = [
            'app' => true,
            'database' => false,
            'sessions_table' => false,
            'jobs_table' => false,
        ];

        try {
            DB::select('select 1 as ok');
            $checks['database'] = true;
            $checks['sessions_table'] = Schema::hasTable('sessions');
            $checks['jobs_table'] = Schema::hasTable('jobs');
        } catch (Throwable) {
            // keep false
        }

        $ok = $checks['app'] && $checks['database'];

        return response()->json([
            'status' => $ok ? 'ok' : 'degraded',
            'version' => config('dateconta.version'),
            'checks' => $checks,
            'time' => now()->toIso8601String(),
        ], $ok ? 200 : 503);
    }
}
