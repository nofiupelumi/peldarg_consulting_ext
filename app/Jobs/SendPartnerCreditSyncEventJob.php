<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class SendPartnerCreditSyncEventJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    public int $maxExceptions = 3;

    public function __construct(public array $payload)
    {
        $this->onQueue('partner-sync');
    }

    public function backoff(): array
    {
        return [10, 30, 90, 180, 300];
    }

    public function handle(): void
    {
        $url = trim((string) config('services.partner.credit_sync_url', ''));
        $token = (string) config('services.partner.token', '');

        if ($url === '' || $token === '') {
            Log::warning('partner sync skipped due to missing configuration', [
                'has_url' => $url !== '',
                'has_token' => $token !== '',
                'event_type' => (string) ($this->payload['event_type'] ?? ''),
            ]);
            return;
        }

        $response = Http::withHeaders([
                'X-Partner-Token' => $token,
                'Accept' => 'application/json',
            ])
            ->connectTimeout(5)
            ->timeout((int) config('services.partner.credit_sync_timeout', 10))
            ->post($url, $this->payload);

        if (!$response->successful()) {
            throw new RuntimeException('Partner credit sync failed with HTTP ' . $response->status());
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('partner credit sync exhausted retries', [
            'event_type' => (string) ($this->payload['event_type'] ?? ''),
            'user_email' => (string) ($this->payload['user_email'] ?? ''),
            'message' => $exception->getMessage(),
        ]);
    }
}
