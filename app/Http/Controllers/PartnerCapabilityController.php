<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PartnerCapabilityController extends Controller
{
    public function show(Request $request)
    {
        $expectedToken = (string) config('services.partner.token');
        $providedToken = (string) $request->header('X-Partner-Token', '');

        if ($expectedToken === '' || !hash_equals($expectedToken, $providedToken)) {
            abort(401);
        }

        return response()->json([
            'billing_authority' => [
                'app' => (string) config('app.name', 'PeldargExtractor'),
                'domain' => (string) config('services.partner.authority_domain'),
                'centralized_billing_enabled' => (bool) config('services.partner.centralized_billing_enabled', true),
                'manual_topup_allowed' => true,
                'payment_provider' => 'paystack',
            ],
            'policy' => [
                'credits_source_of_truth' => 'peldarg',
                'allow_direct_partner_payment' => false,
                'allow_raw_provider_key_at_partner' => false,
            ],
            'integrations' => [
                'paystack' => [
                    'public_key_set' => !empty(config('services.paystack.public_key')),
                    'secret_key_set' => !empty(config('services.paystack.secret_key')),
                    'webhook_secret_set' => !empty(config('services.paystack.webhook_secret')),
                ],
                'extractor' => [
                    'callback_secret_set' => !empty(config('services.extractor.secret')),
                    'result_token_set' => !empty(config('services.extractor.token')),
                ],
            ],
            'partner' => [
                'allowed_origins' => (array) config('services.partner.allowed_origins', []),
            ],
        ]);
    }
}
