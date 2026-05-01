<?php

namespace App\Http\Controllers;

use App\Services\PaystackPaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaystackWebhookController extends Controller
{
    public function __construct(private PaystackPaymentService $paystack)
    {
    }

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

        try {
            $this->paystack->fulfillFromWebhookEvent($event);
        } catch (\Throwable $e) {
            Log::warning('paystack webhook processing failed', [
                'message' => $e->getMessage(),
                'reference' => $event['data']['reference'] ?? null,
            ]);
        }

        return response()->json(['ok' => true], 200);
    }
}
