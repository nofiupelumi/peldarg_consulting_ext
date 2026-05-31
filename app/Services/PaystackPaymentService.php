<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Models\CreditInvoice;
use App\Models\CreditLedger;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PaystackPaymentService
{
    private const ACTIVE_GATEWAY_STATUSES = ['initializing', 'initialized', 'pending', 'processing', 'ongoing'];

    public function __construct(private readonly PartnerCreditSyncService $partnerCreditSyncService)
    {
    }

    public function initializeForUser(User $user, int $requestedCredits, ?string $callbackUrl = null, bool $forceRefresh = false): array
    {
        $settings = AppSetting::current();
        $requestedCredits = max(1, $requestedCredits);
        $unitPriceUsd = (float) $settings->unit_price_usd;
        $amountUsd = round($requestedCredits * $unitPriceUsd, 4);
        $amountKobo = max(100, (int) round($amountUsd * (float) $settings->fx_rate_ngn * 100));

        $invoice = DB::transaction(function () use ($user, $requestedCredits, $unitPriceUsd, $amountUsd, $amountKobo, $forceRefresh) {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->id);

            if ($lockedUser->status !== 'active') {
                throw ValidationException::withMessages(['user' => 'User account is suspended.']);
            }

            $cap = (int) $lockedUser->credit_cap;
            if ($cap > 0 && ((int) $lockedUser->credit_balance + $requestedCredits) > $cap) {
                throw ValidationException::withMessages([
                    'requested_credits' => 'Requested credits would exceed your credit cap.',
                ]);
            }

            if ($forceRefresh) {
                // Cancel any existing active Paystack invoices so we always start with a
                // fresh invoice reflecting the current requested_credits and live pricing.
                // Only touches payment_source='paystack' rows; manual invoices are unaffected.
                CreditInvoice::query()
                    ->where('user_id', $lockedUser->id)
                    ->where('payment_source', 'paystack')
                    ->whereNull('fulfilled_at')
                    ->whereIn('gateway_status', self::ACTIVE_GATEWAY_STATUSES)
                    ->whereNotIn('status', ['approved'])
                    ->update(['gateway_status' => 'cancelled', 'status' => 'cancelled']);
            } else {
                $existing = CreditInvoice::query()
                    ->lockForUpdate()
                    ->where('user_id', $lockedUser->id)
                    ->where('payment_source', 'paystack')
                    ->whereNull('fulfilled_at')
                    ->whereIn('gateway_status', self::ACTIVE_GATEWAY_STATUSES)
                    ->whereNotIn('status', ['approved', 'rejected', 'cancelled'])
                    ->latest('id')
                    ->first();

                if ($existing) {
                    return $existing;
                }
            }

            return CreditInvoice::create([
                'user_id' => $lockedUser->id,
                'invoice_number' => 'INV-' . now()->format('YmdHis') . '-' . $lockedUser->id . '-' . Str::upper(Str::random(4)),
                'requested_credits' => $requestedCredits,
                'unit_price_usd' => $unitPriceUsd,
                'requested_amount_usd' => $amountUsd,
                'payment_provider' => 'paystack',
                'payment_source' => 'paystack',
                'gateway_status' => 'initializing',
                'amount_ngn_kobo' => $amountKobo,
                'status' => 'pending',
            ]);
        });

        // Paystack access codes expire after ~10 minutes.
        // When $forceRefresh is true (partner API calls always set this) or when the
        // access_code is stale (> 8 min old), we fall through and call the Paystack API
        // to generate a fresh reference + access_code on the same invoice row.
        $accessCodeFresh = !$forceRefresh
            && $invoice->initialized_at
            && $invoice->initialized_at->gt(now()->subMinutes(8));

        if ($invoice->gateway_authorization_url && $invoice->gateway_access_code
            && in_array((string) $invoice->gateway_status, self::ACTIVE_GATEWAY_STATUSES, true)
            && $accessCodeFresh) {
            return ['invoice' => $invoice->fresh(), 'reused' => true];
        }

        $secret = (string) config('services.paystack.secret_key');
        if ($secret === '') {
            throw ValidationException::withMessages(['paystack' => 'Paystack secret key is not configured.']);
        }

        // Build a unique reference for this Paystack transaction.
        // forceRefresh always creates a brand-new invoice so gateway_reference is null;
        // the plain suffix path is taken. The -R path handles the edge case where the
        // same invoice row is re-initialized (non-forceRefresh stale path).
        $baseRef = 'PSK-' . str_replace('INV-', '', (string) $invoice->invoice_number);
        $reference = $invoice->gateway_reference
            ? $baseRef . '-R' . now()->format('His') . Str::upper(Str::random(4))
            : $baseRef . '-' . Str::upper(Str::random(6));

        $resolvedCallbackUrl = $callbackUrl
            ?: (string) (config('services.paystack.callback_url') ?: url('/top-up'));

        $response = Http::withToken($secret)
            ->acceptJson()
            ->post(rtrim((string) config('services.paystack.base_url', 'https://api.paystack.co'), '/') . '/transaction/initialize', [
                'email' => $user->email,
                'amount' => (int) $invoice->amount_ngn_kobo,
                'reference' => $reference,
                'callback_url' => $resolvedCallbackUrl,
                'metadata' => [
                    'invoice_id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'requested_credits' => (int) $invoice->requested_credits,
                    'user_id' => $user->id,
                ],
            ]);

        if (!$response->successful()) {
            $invoice->gateway_status = 'init_failed';
            $invoice->payment_payload = ['error' => mb_substr((string) $response->body(), 0, 1000)];
            $invoice->status = 'cancelled';
            $invoice->save();

            throw ValidationException::withMessages(['paystack' => 'Unable to initialize Paystack transaction.']);
        }

        $data = (array) $response->json('data', []);
        $invoice->gateway_reference = $reference;
        $invoice->payment_reference = $reference;
        $invoice->gateway_access_code = (string) ($data['access_code'] ?? '');
        $invoice->gateway_authorization_url = (string) ($data['authorization_url'] ?? '');
        $invoice->gateway_status = 'initialized';
        $invoice->initialized_at = now();
        $invoice->payment_payload = $data;
        $invoice->save();

        return ['invoice' => $invoice->fresh(), 'reused' => false];
    }

    public function verifyAndFulfill(string $reference, ?int $expectedUserId = null): array
    {
        $secret = (string) config('services.paystack.secret_key');
        if ($secret === '') {
            throw ValidationException::withMessages(['paystack' => 'Paystack secret key is not configured.']);
        }

        $response = Http::withToken($secret)
            ->acceptJson()
            ->get(rtrim((string) config('services.paystack.base_url', 'https://api.paystack.co'), '/') . '/transaction/verify/' . rawurlencode($reference));

        if (!$response->successful()) {
            throw ValidationException::withMessages(['reference' => 'Unable to verify Paystack transaction.']);
        }

        return $this->fulfillFromVerifiedPayload((array) $response->json('data', []), $expectedUserId);
    }

    public function fulfillFromWebhookEvent(array $event): array
    {
        if (($event['event'] ?? '') !== 'charge.success') {
            return ['handled' => false, 'reason' => 'ignored_event'];
        }

        return $this->fulfillFromVerifiedPayload((array) ($event['data'] ?? []), null);
    }

    private function fulfillFromVerifiedPayload(array $data, ?int $expectedUserId): array
    {
        $reference = (string) ($data['reference'] ?? '');
        if ($reference === '') {
            throw ValidationException::withMessages(['reference' => 'Transaction reference is missing.']);
        }

        $result = DB::transaction(function () use ($data, $reference, $expectedUserId) {
            $invoice = CreditInvoice::query()
                ->lockForUpdate()
                ->where('gateway_reference', $reference)
                ->firstOrFail();

            if ($expectedUserId !== null && (int) $invoice->user_id !== $expectedUserId) {
                throw ValidationException::withMessages(['reference' => 'Transaction does not belong to the current user.']);
            }

            if ($invoice->fulfilled_at) {
                return [
                    'handled' => true,
                    'already_fulfilled' => true,
                    'invoice' => $invoice,
                ];
            }

            $status = (string) ($data['status'] ?? '');
            $amount = (int) ($data['amount'] ?? 0);
            $invoice->gateway_status = $status;
            $invoice->payment_payload = $data;
            if ($status === 'success' && $invoice->paid_at === null) {
                $invoice->paid_at = now();
            }

            if ($status !== 'success') {
                $invoice->save();
                return ['handled' => false, 'invoice' => $invoice, 'reason' => 'payment_not_successful'];
            }

            // Trust Paystack's status check. The kobo amount returned by Paystack
            // must be >= what the invoice expects (we won't fulfil underpayments).
            // We allow a 1-kobo tolerance for rounding and never fail on overpayment
            // (rounding up is fine). Strict equality caused false failures in test mode.
            if ($amount < ((int) $invoice->amount_ngn_kobo - 1)) {
                $invoice->admin_note = trim((string) $invoice->admin_note . "\nPaystack amount {$amount} kobo less than invoice amount {$invoice->amount_ngn_kobo} kobo. Manual review required.");
                $invoice->save();
                throw ValidationException::withMessages(['amount' => 'Payment amount is less than the invoice amount. Please contact support.']);
            }

            $user = User::query()->lockForUpdate()->findOrFail($invoice->user_id);
            $credits = (int) $invoice->requested_credits;
            $before = (int) $user->credit_balance;
            $cap = (int) $user->credit_cap;
            $after = $before + $credits;

            if ($cap > 0 && $after > $cap) {
                $invoice->admin_note = trim((string) $invoice->admin_note . "\nPaid via Paystack but exceeds current credit cap; manual review required.");
                $invoice->save();
                throw ValidationException::withMessages([
                    'credit_cap' => 'Payment verified but credit application exceeds current cap. Manual review required.',
                ]);
            }

            CreditLedger::create([
                'user_id' => $user->id,
                'document_id' => null,
                'invoice_id' => $invoice->id,
                'action_type' => 'invoice_approved',
                'credits' => $credits,
                'balance_before' => $before,
                'balance_after' => $after,
                'unit_price_usd' => $invoice->unit_price_usd,
                'amount_usd' => $invoice->requested_amount_usd,
                'meta' => [
                    'provider' => 'paystack',
                    'reference' => $reference,
                    'channel' => $data['channel'] ?? null,
                ],
                'created_by_user_id' => null,
            ]);

            $user->credit_balance = $after;
            $user->save();

            $invoice->status = 'approved';
            $invoice->fulfilled_at = now();
            $invoice->reviewed_at = $invoice->reviewed_at ?: now();
            $invoice->admin_note = trim((string) $invoice->admin_note . "\nAuto-approved via Paystack.");
            $invoice->save();

            return [
                'handled' => true,
                'already_fulfilled' => false,
                'invoice' => $invoice,
                'user' => $user,
                'credit_balance' => $after,
            ];
        });

        if (($result['handled'] ?? false) === true && ($result['already_fulfilled'] ?? false) === false && ($result['user'] ?? null) instanceof User) {
            /** @var User $syncedUser */
            $syncedUser = $result['user'];
            $this->partnerCreditSyncService->notifyCreditUpdated($syncedUser, 'paystack_payment_verified', [
                'reference' => $reference,
            ]);
        }

        return $result;
    }
}