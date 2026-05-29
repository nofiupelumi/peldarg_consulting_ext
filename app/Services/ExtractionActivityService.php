<?php

namespace App\Services;

use App\Models\ExtractionActivityEvent;
use App\Models\ExtractionActivityStream;
use App\Models\PartnerExtractionAuthorization;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class ExtractionActivityService
{
    public function ingest(array $payload): array
    {
        return DB::transaction(function () use ($payload) {
            $requestId = (string) $payload['partner_request_id'];

            $stream = ExtractionActivityStream::query()
                ->where('partner_request_id', $requestId)
                ->lockForUpdate()
                ->first();

            if (!$stream) {
                $user = null;
                if (!empty($payload['user_email'])) {
                    $user = User::query()->where('email', (string) $payload['user_email'])->first();
                }

                $authorization = PartnerExtractionAuthorization::query()
                    ->where('partner_request_id', $requestId)
                    ->first();

                $stream = ExtractionActivityStream::create([
                    'partner_request_id' => $requestId,
                    'user_id' => $user?->id ?? $authorization?->user_id,
                    'authorization_id' => $authorization?->id,
                    'partner_name' => $payload['partner_name'] ?? $authorization?->partner_name,
                    'partner_domain' => $payload['partner_domain'] ?? $authorization?->partner_domain,
                    'user_email' => $payload['user_email'] ?? $user?->email,
                    'extraction_type' => $payload['extraction_type'] ?? $authorization?->extraction_type,
                    'status' => $payload['status'] ?? 'processing',
                    'phase' => $payload['phase'] ?? 'processing',
                    'pages_requested' => (int) ($payload['pages_requested'] ?? $authorization?->pages_requested ?? 0),
                    'credits_reserved' => (int) ($payload['credits_reserved'] ?? $authorization?->credits_reserved ?? 0),
                    'started_at' => isset($payload['event_at']) ? CarbonImmutable::parse((string) $payload['event_at']) : now(),
                ]);
            }

            $requestedSequence = isset($payload['sequence']) ? (int) $payload['sequence'] : 0;
            $normalizedSequence = $requestedSequence > $stream->latest_sequence
                ? $requestedSequence
                : ((int) $stream->latest_sequence + 1);

            $dedupeKey = (string) ($payload['dedupe_key'] ?? hash('sha256', json_encode([
                'partner_request_id' => $requestId,
                'event_key' => (string) ($payload['event_key'] ?? 'event'),
                'sequence' => $requestedSequence,
                'run_id' => (string) ($payload['run_id'] ?? ''),
                'event_at' => (string) ($payload['event_at'] ?? ''),
            ])));

            $existing = ExtractionActivityEvent::query()->where('dedupe_key', $dedupeKey)->first();
            if ($existing) {
                return [
                    'stream' => $stream,
                    'event' => $existing,
                    'reused' => true,
                    'normalized_sequence' => (int) $existing->sequence,
                ];
            }

            $eventAt = isset($payload['event_at'])
                ? CarbonImmutable::parse((string) $payload['event_at'])
                : now();

            $event = ExtractionActivityEvent::create([
                'stream_id' => (int) $stream->id,
                'partner_request_id' => $requestId,
                'event_key' => (string) $payload['event_key'],
                'sequence' => $normalizedSequence,
                'status' => $payload['status'] ?? null,
                'phase' => $payload['phase'] ?? null,
                'run_id' => $payload['run_id'] ?? null,
                'doc_id' => isset($payload['doc_id']) ? (int) $payload['doc_id'] : null,
                'event_at' => $eventAt,
                'dedupe_key' => $dedupeKey,
                'payload' => $payload,
            ]);

            $nextStatus = (string) ($payload['status'] ?? $stream->status ?? 'processing');
            $nextPhase = (string) ($payload['phase'] ?? $stream->phase ?? 'processing');
            $failedReason = $payload['failed_reason'] ?? $stream->failed_reason;

            $completedAt = $stream->completed_at;
            if (in_array($nextStatus, ['finalized', 'failed'], true) && $completedAt === null) {
                $completedAt = $eventAt;
            }

            $stream->fill([
                'partner_name' => $payload['partner_name'] ?? $stream->partner_name,
                'partner_domain' => $payload['partner_domain'] ?? $stream->partner_domain,
                'user_email' => $payload['user_email'] ?? $stream->user_email,
                'extraction_type' => $payload['extraction_type'] ?? $stream->extraction_type,
                'status' => $nextStatus,
                'phase' => $nextPhase,
                'last_event_key' => (string) $payload['event_key'],
                'latest_sequence' => $normalizedSequence,
                'pages_requested' => (int) ($payload['pages_requested'] ?? $stream->pages_requested ?? 0),
                'pages_processed' => (int) ($payload['pages_processed'] ?? $stream->pages_processed ?? 0),
                'pages_with_results' => (int) ($payload['pages_with_results'] ?? $stream->pages_with_results ?? 0),
                'credits_reserved' => (int) ($payload['credits_reserved'] ?? $stream->credits_reserved ?? 0),
                'credits_consumed' => (int) ($payload['credits_consumed'] ?? $stream->credits_consumed ?? 0),
                'credits_refunded' => (int) ($payload['credits_refunded'] ?? $stream->credits_refunded ?? 0),
                'credit_outcome' => $payload['credit_outcome'] ?? $stream->credit_outcome,
                'failed_reason' => $failedReason,
                'run_id' => $payload['run_id'] ?? $stream->run_id,
                'last_event_at' => $eventAt,
                'started_at' => $stream->started_at ?: $eventAt,
                'completed_at' => $completedAt,
                'last_payload' => $payload,
            ]);
            $stream->save();

            return [
                'stream' => $stream,
                'event' => $event,
                'reused' => false,
                'normalized_sequence' => $normalizedSequence,
            ];
        });
    }

    public function emitSystemEvent(string $eventKey, PartnerExtractionAuthorization $authorization, array $overrides = []): array
    {
        $status = (string) ($authorization->status ?? 'processing');
        $phase = match ($status) {
            'authorized' => 'processing',
            'finalized' => 'completed',
            'failed' => 'failed',
            default => 'processing',
        };

        return $this->ingest(array_merge([
            'partner_request_id' => (string) $authorization->partner_request_id,
            'event_key' => $eventKey,
            'status' => $status,
            'phase' => $phase,
            'partner_name' => (string) ($authorization->partner_name ?? 'riskcontrol'),
            'partner_domain' => $authorization->partner_domain,
            'user_email' => optional($authorization->user)->email,
            'extraction_type' => $authorization->extraction_type,
            'pages_requested' => (int) ($authorization->pages_requested ?? 0),
            'pages_processed' => (int) ($authorization->pages_processed ?? 0),
            'pages_with_results' => (int) ($authorization->pages_with_results ?? 0),
            'credits_reserved' => (int) ($authorization->credits_reserved ?? 0),
            'credits_consumed' => (int) ($authorization->credits_consumed ?? 0),
            'credits_refunded' => (int) ($authorization->credits_refunded ?? 0),
            'failed_reason' => $authorization->failed_reason,
            'event_at' => now()->toIso8601String(),
        ], $overrides));
    }
}
