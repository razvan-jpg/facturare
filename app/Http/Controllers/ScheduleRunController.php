<?php

namespace App\Http\Controllers;

use App\Services\DailyOpsReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Throwable;

class ScheduleRunController extends Controller
{
    public function __invoke(Request $request): SymfonyResponse
    {
        $token = (string) config('dateconta.schedule_token', '');
        $provided = (string) $request->query('token', '');

        if ($token === '' || ! hash_equals($token, $provided)) {
            abort(404);
        }

        Artisan::call('schedule:run');
        $output = trim(Artisan::output());

        $catchup = $this->ensureDailyOpsReportSent();
        if ($catchup !== null) {
            $output = ($output !== '' ? $output."\n" : '').$catchup;
        }

        return response($output !== '' ? $output : "schedule:run ok\n", 200, [
            'Content-Type' => 'text/plain; charset=utf-8',
            'Cache-Control' => 'no-store',
        ]);
    }

    /**
     * Plasă de siguranță: dacă după 23:51 (Europe/Bucharest) raportul zilei lipsește,
     * sau dimineața (până la 12:00) lipsește raportul de ieri — trimite acum.
     * Idempotent (DailyOpsReportService cache). Acoperă cron la fiecare 17 minute / minute ratate.
     */
    private function ensureDailyOpsReportSent(): ?string
    {
        $now = Carbon::now('Europe/Bucharest');
        $date = null;

        if ($now->hour === 23 && $now->minute >= 51) {
            $date = $now->copy()->startOfDay();
        } elseif ($now->hour < 12) {
            $date = $now->copy()->subDay()->startOfDay();
        }

        if ($date === null) {
            return null;
        }

        $service = app(DailyOpsReportService::class);
        if ($service->wasSent($date)) {
            return null;
        }

        try {
            $ok = $service->send($date);
            $msg = sprintf(
                'ops-catchup %s → %s',
                $date->toDateString(),
                $ok ? 'trimis' : 'EȘEC'
            );
            Log::error($msg); // LOG_LEVEL=error pe prod — trebuie vizibil

            return $msg;
        } catch (Throwable $e) {
            Log::error('ops-catchup failed', [
                'date' => $date->toDateString(),
                'error' => $e->getMessage(),
            ]);

            return 'ops-catchup ERR '.$e->getMessage();
        }
    }
}
