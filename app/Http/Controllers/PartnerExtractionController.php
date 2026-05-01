<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\CreditLedger;
use App\Models\PartnerExtractionAuthorization;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PartnerExtractionController extends Controller
{
    private const API_TIERS = ['paid_1', 'paid_2', 'paid_3'];

    private function assertPartnerToken(Request $request): void
    {
        $expectedToken = (string) config('services.partner.token');
        $providedToken = (string) $request->header('X-Partner-Token', '');

        if ($expectedToken === '' || !hash_equals($expectedToken, $providedToken)) {
            abort(401);
        }
    }

    public function authorizeExtraction(Request $request)
    {
        $this->assertPartnerToken($request);

        $data = $request->validate([
            'partner_request_id' => 'required|uuid',
            'user_email' => 'required|email',
            'pages_requested' => 'required|integer|min:1',
            'extraction_type' => 'required|string|max:50',
            'partner_name' => 'nullable|string|max:100',
            'partner_domain' => 'nullable|string|max:255',
            'partner_user_reference' => 'nullable|string|max:255',
            'requested_api_tier' => 'nullable|string|in:paid_1,paid_2,paid_3',
        ]);

        $result = DB::transaction(function () use ($data) {
            $existing = PartnerExtractionAuthorization::query()
                ->lockForUpdate()
                ->where('partner_request_id', $data['partner_request_id'])
                ->first();

            if ($existing) {
                $balance = (int) $existing->user()->lockForUpdate()->firstOrFail()->credit_balance;
                return ['authorization' => $existing, 'credit_balance' => $balance, 'reused' => true];
            }

            $user = User::query()->lockForUpdate()->where('email', $data['user_email'])->first();
            if (!$user) {
                throw ValidationException::withMessages(['user_email' => 'No peldarg account found for this email.']);
            }
            if ($user->status !== 'active') {
                throw ValidationException::withMessages(['user' => 'User account is suspended.']);
            }

            $allowedApiTiers = (bool) $user->is_admin
                ? self::API_TIERS
                : array_values(array_intersect(self::API_TIERS, (array) ($user->allowed_api_tiers ?? [])));
            if ($allowedApiTiers === []) {
                $allowedApiTiers = ['paid_1'];
            }

            $requestedApiTier = strtolower(trim((string) ($data['requested_api_tier'] ?? '')));
            $selectedApiTier = in_array($requestedApiTier, $allowedApiTiers, true)
                ? $requestedApiTier
                : $allowedApiTiers[0];

            $requiredCredits = max(1, (int) $data['pages_requested']);
            $before = (int) $user->credit_balance;
            if ($before < $requiredCredits) {
                throw ValidationException::withMessages(['credit_balance' => 'Insufficient credits for this extraction.']);
            }

            $settings = AppSetting::current();
            $after = $before - $requiredCredits;
            CreditLedger::create([
                'user_id' => $user->id,
                'document_id' => null,
                'invoice_id' => null,
                'action_type' => 'reserve',
                'credits' => -$requiredCredits,
                'balance_before' => $before,
                'balance_after' => $after,
                'unit_price_usd' => $settings->unit_price_usd,
                'amount_usd' => round($requiredCredits * (float) $settings->unit_price_usd, 4),
                'meta' => [
                    'source' => 'partner_authorize',
                    'partner_request_id' => $data['partner_request_id'],
                    'partner_name' => $data['partner_name'] ?? null,
                    'partner_domain' => $data['partner_domain'] ?? null,
                    'extraction_type' => $data['extraction_type'],
                ],
                'created_by_user_id' => $user->id,
            ]);

            $user->credit_balance = $after;
            $user->save();

            $authorization = PartnerExtractionAuthorization::create([
                'user_id' => $user->id,
                'partner_name' => $data['partner_name'] ?? null,
                'partner_domain' => $data['partner_domain'] ?? null,
                'partner_user_reference' => $data['partner_user_reference'] ?? null,
                'partner_request_id' => $data['partner_request_id'],
                'extraction_type' => $data['extraction_type'],
                'pages_requested' => $requiredCredits,
                'credits_reserved' => $requiredCredits,
                'api_tier' => $selectedApiTier,
                'status' => 'authorized',
                'expires_at' => now()->addDay(),
                'meta' => [
                    'allowed_api_tiers' => $allowedApiTiers,
                ],
            ]);

            return ['authorization' => $authorization, 'credit_balance' => $after, 'reused' => false];
        });

        /** @var PartnerExtractionAuthorization $authorization */
        $authorization = $result['authorization'];

        return response()->json([
            'partner_request_id' => $authorization->partner_request_id,
            'authorization_id' => $authorization->id,
            'api_tier' => $authorization->api_tier,
            'credits_reserved' => (int) $authorization->credits_reserved,
            'credit_balance' => (int) $result['credit_balance'],
            'expires_at' => optional($authorization->expires_at)->toIso8601String(),
            'reused' => (bool) $result['reused'],
        ]);
    }

    public function finalizeExtraction(Request $request)
    {
        $this->assertPartnerToken($request);

        $data = $request->validate([
            'partner_request_id' => 'required|uuid',
            'status' => 'required|string|in:success,failed',
            'pages_processed' => 'required|integer|min:0',
            'pages_with_results' => 'nullable|integer|min:0',
            'failed_reason' => 'nullable|string|max:1000',
        ]);

        $result = DB::transaction(function () use ($data) {
            $authorization = PartnerExtractionAuthorization::query()
                ->lockForUpdate()
                ->where('partner_request_id', $data['partner_request_id'])
                ->firstOrFail();

            if (in_array($authorization->status, ['finalized', 'failed'], true)) {
                $user = User::query()->lockForUpdate()->findOrFail($authorization->user_id);
                return ['authorization' => $authorization, 'credit_balance' => (int) $user->credit_balance, 'reused' => true];
            }

            $user = User::query()->lockForUpdate()->findOrFail($authorization->user_id);
            $settings = AppSetting::current();
            $rate = (float) $settings->unit_price_usd;
            $reserved = (int) $authorization->credits_reserved;
            $processed = max(0, (int) $data['pages_processed']);
            $pagesWithResults = max(0, (int) ($data['pages_with_results'] ?? 0));

            if ($data['status'] !== 'success') {
                if ($reserved > 0) {
                    $before = (int) $user->credit_balance;
                    $after = $before + $reserved;
                    CreditLedger::create([
                        'user_id' => $user->id,
                        'document_id' => null,
                        'invoice_id' => null,
                        'action_type' => 'refund',
                        'credits' => $reserved,
                        'balance_before' => $before,
                        'balance_after' => $after,
                        'unit_price_usd' => $rate,
                        'amount_usd' => $reserved * $rate,
                        'meta' => ['source' => 'partner_finalize_failed', 'partner_request_id' => $authorization->partner_request_id],
                        'created_by_user_id' => $user->id,
                    ]);
                    $user->credit_balance = $after;
                    $user->save();
                }

                $authorization->status = 'failed';
                $authorization->pages_processed = $processed;
                $authorization->pages_with_results = $pagesWithResults;
                $authorization->credits_refunded = $reserved;
                $authorization->failed_reason = $data['failed_reason'] ?? 'Processing failed';
                $authorization->finalized_at = now();
                $authorization->save();

                return ['authorization' => $authorization, 'credit_balance' => (int) $user->credit_balance, 'reused' => false];
            }

            $extraNeeded = max(0, $processed - $reserved);
            $refund = max(0, $reserved - $processed);

            if ($extraNeeded > 0) {
                $before = (int) $user->credit_balance;
                if ($before < $extraNeeded) {
                    throw ValidationException::withMessages(['credit_balance' => 'Not enough credits to finalize partner extraction.']);
                }
                $after = $before - $extraNeeded;
                CreditLedger::create([
                    'user_id' => $user->id,
                    'document_id' => null,
                    'invoice_id' => null,
                    'action_type' => 'consume',
                    'credits' => -$extraNeeded,
                    'balance_before' => $before,
                    'balance_after' => $after,
                    'unit_price_usd' => $rate,
                    'amount_usd' => $extraNeeded * $rate,
                    'meta' => ['source' => 'partner_finalize_extra', 'partner_request_id' => $authorization->partner_request_id],
                    'created_by_user_id' => $user->id,
                ]);
                $user->credit_balance = $after;
                $user->save();
            }

            if ($refund > 0) {
                $before = (int) $user->credit_balance;
                $after = $before + $refund;
                CreditLedger::create([
                    'user_id' => $user->id,
                    'document_id' => null,
                    'invoice_id' => null,
                    'action_type' => 'refund',
                    'credits' => $refund,
                    'balance_before' => $before,
                    'balance_after' => $after,
                    'unit_price_usd' => $rate,
                    'amount_usd' => $refund * $rate,
                    'meta' => ['source' => 'partner_finalize_refund', 'partner_request_id' => $authorization->partner_request_id],
                    'created_by_user_id' => $user->id,
                ]);
                $user->credit_balance = $after;
                $user->save();
            }

            $authorization->status = 'finalized';
            $authorization->pages_processed = $processed;
            $authorization->pages_with_results = $pagesWithResults;
            $authorization->credits_consumed = $processed;
            $authorization->credits_refunded = $refund;
            $authorization->failed_reason = null;
            $authorization->finalized_at = now();
            $authorization->save();

            return ['authorization' => $authorization, 'credit_balance' => (int) $user->credit_balance, 'reused' => false];
        });

        /** @var PartnerExtractionAuthorization $authorization */
        $authorization = $result['authorization'];

        return response()->json([
            'partner_request_id' => $authorization->partner_request_id,
            'status' => $authorization->status,
            'credits_consumed' => (int) $authorization->credits_consumed,
            'credits_refunded' => (int) $authorization->credits_refunded,
            'credit_balance' => (int) $result['credit_balance'],
            'reused' => (bool) $result['reused'],
        ]);
    }
}