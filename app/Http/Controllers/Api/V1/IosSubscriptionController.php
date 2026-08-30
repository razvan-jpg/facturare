<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\IosSubscriptionGate;
use App\Services\IosSubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Throwable;

class IosSubscriptionController extends Controller
{
    public function status(Request $request, IosSubscriptionGate $gate): JsonResponse
    {
        return response()->json($gate->statusPayload($request->user()));
    }

    public function verify(Request $request, IosSubscriptionService $service): JsonResponse
    {
        $data = $request->validate([
            'signed_transaction' => ['required', 'string', 'max:200000'],
        ]);

        try {
            $status = $service->verifyAndAttach($request->user(), $data['signed_transaction']);
        } catch (Throwable $e) {
            return response()->json([
                'message' => $e->getMessage() ?: 'Verificare abonament eșuată.',
                'code' => 'ios_subscription_invalid',
            ], 422);
        }

        return response()->json($status);
    }

    public function notifications(Request $request, IosSubscriptionService $service): Response
    {
        $signed = $request->input('signedPayload')
            ?? $request->json('signedPayload')
            ?? $request->getContent();

        if (is_string($signed) && str_starts_with(ltrim($signed), '{')) {
            $json = json_decode($signed, true);
            $signed = is_array($json) ? ($json['signedPayload'] ?? null) : null;
        }

        if (! is_string($signed) || $signed === '') {
            return response('Bad Request', 400);
        }

        try {
            $service->handleServerNotification($signed);
        } catch (Throwable $e) {
            report($e);

            return response('Invalid', 400);
        }

        return response('OK', 200);
    }
}
