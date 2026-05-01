<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaystackWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $secret = (string) config('services.paystack.webhook_secret');
        $signature = (string) $request->header('x-paystack-signature', '');
        $payload = (string) $request->getContent();

        if ($secret === '' || $signature === '') {
            abort(401);
        }

        $expected = hash_hmac('sha512', $payload, $secret);
        if (!hash_equals($expected, $signature)) {
            abort(401);
        }

        $event = $request->json()->all();

        // Phase 1 boundary: accept and authenticate webhooks at Peldarg only.
        // Credit fulfillment logic will be implemented in later phases.
        Log::info('paystack webhook received', [
            'event' => $event['event'] ?? null,
            'reference' => $event['data']['reference'] ?? null,
            'status' => $event['data']['status'] ?? null,
        ]);

        return response()->json(['ok' => true], 200);
    }
}
