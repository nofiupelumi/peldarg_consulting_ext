<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\CreditInvoice;
use App\Models\User;
use App\Services\PaymentHistoryService;
use App\Services\PaystackPaymentService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PartnerCapabilityController extends Controller
{
    private function assertPartnerToken(Request $request): void
    {
        $expectedToken = (string) config('services.partner.token');
        $providedToken = (string) $request->header('X-Partner-Token', '');

        if ($expectedToken === '' || !hash_equals($expectedToken, $providedToken)) {
            abort(401);
        }
    }

    public function show(Request $request)
    {
        $this->assertPartnerToken($request);

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

    public function creditSummary(Request $request)
    {
        $this->assertPartnerToken($request);

        $data = $request->validate([
            'user_email' => 'required|email',
        ]);

        $user = User::query()->where('email', $data['user_email'])->first();
        if (!$user) {
            throw ValidationException::withMessages(['user_email' => 'No peldarg account found for this email.']);
        }

        $settings = AppSetting::current();

        return response()->json([
            'user_email' => (string) $user->email,
            'credit_balance' => (int) ($user->credit_balance ?? 0),
            'credit_cap' => (int) ($user->credit_cap ?? 0),
            'status' => (string) ($user->status ?? 'active'),
            'unit_price_usd' => (string) $settings->unit_price_usd,
            'fx_rate_ngn' => (string) $settings->fx_rate_ngn,
            'authority_domain' => (string) config('services.partner.authority_domain'),
        ]);
    }

    public function paymentHistory(Request $request, PaymentHistoryService $paymentHistoryService)
    {
        $this->assertPartnerToken($request);

        $data = $request->validate([
            'user_email' => 'required|email',
            'year' => 'nullable|integer|min:2000|max:2100',
            'month' => 'nullable|integer|min:1|max:12',
        ]);

        return response()->json(
            $paymentHistoryService->forUserEmail(
                (string) $data['user_email'],
                isset($data['year']) ? (int) $data['year'] : null,
                isset($data['month']) ? (int) $data['month'] : null,
            )
        );
    }

    public function updateUserEmail(Request $request)
    {
        $this->assertPartnerToken($request);

        $data = $request->validate([
            'current_email' => 'required|email',
            'new_email'     => 'required|email|max:180',
        ]);

        $user = User::query()->where('email', $data['current_email'])->first();
        if (!$user) {
            throw ValidationException::withMessages(['current_email' => 'No peldarg account found for this email.']);
        }

        if (strtolower($data['new_email']) !== strtolower($data['current_email'])) {
            $taken = User::query()
                ->where('email', $data['new_email'])
                ->where('id', '!=', $user->id)
                ->exists();
            if ($taken) {
                throw ValidationException::withMessages(['new_email' => 'That email is already registered on this system.']);
            }
        }

        $user->email = $data['new_email'];
        $user->save();

        return response()->json([
            'success' => true,
            'email'   => $user->email,
        ]);
    }

    public function paystackInitialize(Request $request, PaystackPaymentService $paystack)
    {
        $this->assertPartnerToken($request);

        $data = $request->validate([
            'user_email'        => 'required|email',
            'requested_credits' => 'required|integer|min:1',
            'callback_url'      => 'nullable|url|max:500',
        ]);

        $user = User::query()->where('email', $data['user_email'])->first();
        if (!$user) {
            throw ValidationException::withMessages(['user_email' => 'No peldarg account found for this email.']);
        }

        // Partner API calls always force a fresh Paystack access_code so the inline popup
        // never receives a stale/consumed code. The invoice row is reused if one exists.
        $result = $paystack->initializeForUser(
            $user,
            (int) $data['requested_credits'],
            isset($data['callback_url']) ? (string) $data['callback_url'] : null,
            forceRefresh: true,
        );
        /** @var CreditInvoice $invoice */
        $invoice = $result['invoice'];

        return response()->json([
            'invoice_id'        => $invoice->id,
            'invoice_number'    => $invoice->invoice_number,
            'reference'         => $invoice->gateway_reference,
            'access_code'       => $invoice->gateway_access_code,
            'authorization_url' => $invoice->gateway_authorization_url,
            'amount_ngn_kobo'   => (int) $invoice->amount_ngn_kobo,
            'requested_credits' => (int) $invoice->requested_credits,
            'public_key'        => (string) config('services.paystack.public_key', ''),
            'user_email'        => (string) $user->email,
            'reused'            => (bool) ($result['reused'] ?? false),
        ]);
    }

    public function paystackVerify(Request $request, PaystackPaymentService $paystack)
    {
        $this->assertPartnerToken($request);

        $data = $request->validate([
            'user_email' => 'required|email',
            'reference' => 'required|string|max:255',
        ]);

        $user = User::query()->where('email', $data['user_email'])->first();
        if (!$user) {
            throw ValidationException::withMessages(['user_email' => 'No peldarg account found for this email.']);
        }

        $result = $paystack->verifyAndFulfill((string) $data['reference'], (int) $user->id);
        /** @var CreditInvoice $invoice */
        $invoice = $result['invoice'];

        return response()->json([
            'handled' => (bool) ($result['handled'] ?? false),
            'already_fulfilled' => (bool) ($result['already_fulfilled'] ?? false),
            'invoice_id' => $invoice->id,
            'status' => $invoice->status,
            'gateway_status' => $invoice->gateway_status,
            'credit_balance' => $result['credit_balance'] ?? null,
        ]);
    }
}
