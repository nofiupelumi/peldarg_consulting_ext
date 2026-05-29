<?php

namespace App\Services;

use App\Jobs\SendPartnerCreditSyncEventJob;
use App\Models\User;

class PartnerCreditSyncService
{
    public function notifyCreditUpdated(User $user, string $eventType, array $meta = []): void
    {
        $url = trim((string) config('services.partner.credit_sync_url', ''));
        $token = (string) config('services.partner.token', '');

        if ($url === '' || $token === '') {
            return;
        }

        SendPartnerCreditSyncEventJob::dispatch([
            'event_type' => $eventType,
            'user_email' => (string) $user->email,
            'credit_balance' => (int) ($user->credit_balance ?? 0),
            'credit_cap' => (int) ($user->credit_cap ?? 0),
            'status' => (string) ($user->status ?? 'active'),
            'meta' => $meta,
            'occurred_at' => now()->toIso8601String(),
        ]);
    }
}
