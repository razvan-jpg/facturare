<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NormalizeRomanianDates
{
    /** @var list<string> */
    private array $fields = [
        'issue_date',
        'due_date',
        'paid_at',
        'start_date',
        'next_run_date',
        'end_date',
        'from',
        'to',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $payload = $request->all();
        $changed = false;

        foreach ($this->fields as $field) {
            if (! array_key_exists($field, $payload)) {
                continue;
            }
            $raw = $payload[$field];
            if (! is_string($raw) || $raw === '') {
                continue;
            }
            $normalized = dc_parse_date($raw);
            if ($normalized !== null && $normalized !== $raw) {
                $payload[$field] = $normalized;
                $changed = true;
            }
        }

        if ($changed) {
            $request->merge($payload);
        }

        return $next($request);
    }
}
