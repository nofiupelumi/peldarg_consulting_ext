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

    public function initializeForUser(User $user, int $requestedCredits): array
    {
        $settings = AppSetting::current();
        $requestedCredits = max(1, $requestedCredits);
        $unitPriceUsd = (float) $settings->unit_price_usd;
        $amountUsd = round($requestedCredits * $unitPriceUsd, 4);
        $amountKobo = max(100, (int) round($amountUsd * (float) $settings->fx_rate_ngn * 100));

        $invoice = DB::transaction(function () use ($user, $requestedCredits, $unitPriceUsd, $amountUsd, $amountKobo) {
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

            $existing = CreditInvoice::query()
                ->lockForUpdate()
                ->where('user_id', $lockedUser->id)
                ->where('payment_provider', 'paystack')
                ->whereNull('fulfilled_at')
                ->whereIn('gateway_status', self::ACTIVE_GATEWAY_STATUSES)
                ->latest('id')
                ->first();

            if ($existing) {
                return $existing;
            }

            return CreditInvoice::create([
                'user_id' => $lockedUser->id,
                'invoice_number' => 'INV-' . now()->format('YmdHis') . '-' . $lockedUser->id . '-' . Str::upper(Str::random(4)),
                'requested_credits' => $requestedCredits,
                'unit_price_usd' => $unitPriceUsd,
                'requested_amount_usd' => $amountUsd,
                'payment_provider' => 'paystack',
                'gateway_status' => 'initializing',
                'amount_ngn_kobo' => $amountKobo,
                'status' => 'pending',
            ]);
        });

        if ($invoice->gateway_authorization_url && $invoice->gateway_access_code && in_array((string) $invoice->gateway_status, self::ACTIVE_GATEWAY_STATUSES, true)) {
            return ['invoice' => $invoice->fresh(), 'reused' => true];
        }

        $secret = (string) config('services.paystack.secret_key');
        if ($secret === '') {
            throw ValidationException::withMessages(['paystack' => 'Paystack secret key is not configured.']);
        }

        $reference = 'PSK-' . str_replace('INV-', '', (string) $invoice->invoice_number) . '-' . Str::upper(Str::random(6));
        $callbackUrl = (string) (config('services.paystack.callback_url') ?: url('/top-up'));

        $response = Http::withToken($secret)
            ->acceptJson()
            ->post(rtrim((string) config('services.paystack.base_url', 'https://api.paystack.co'), '/') . '/transaction/initialize', [
                'email' => $user->email,
                'amount' => (int) $invoice->amount_ngn_kobo,
                'reference' => $reference,
                'callback_url' => $callbackUrl,
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

        return DB::transaction(function () use ($data, $reference, $expectedUserId) {
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

            if ($amount !== (int) $invoice->amount_ngn_kobo) {
                throw ValidationException::withMessages(['amount' => 'Verified amount does not match invoice amount.']);
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
                'credit_balance' => $after,
            ];
        });
    }
}