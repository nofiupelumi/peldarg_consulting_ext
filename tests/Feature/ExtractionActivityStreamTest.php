<?php

namespace Tests\Feature;

use App\Models\ExtractionActivityEvent;
use App\Models\ExtractionActivityStream;
use App\Models\PartnerAllowlist;
use App\Models\User;
use App\Services\SignatureService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ExtractionActivityStreamTest extends TestCase
{
    use RefreshDatabase;

    private string $secret = 'test_secret_key_64_chars_long_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx';

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.partner.token', 'shared-token');

        PartnerAllowlist::create([
            'partner_name' => 'riskcontrol',
            'partner_domain' => 'http://127.0.0.1:9020',
            'allowed_ips' => '127.0.0.1,::1',
            'current_secret_key' => $this->secret,
            'current_secret_key_id' => 'key_v1',
            'secret_rotated_at' => now(),
            'secret_expires_at' => now()->addDays(90),
            'active' => true,
        ]);
    }

    public function test_activity_ingest_persists_stream_and_normalizes_sequence_with_deduplication(): void
    {
        $requestId = (string) Str::uuid();

        $payload1 = [
            'partner_request_id' => $requestId,
            'event_key' => 'dispatched',
            'sequence' => 1,
            'status' => 'processing',
            'phase' => 'processing',
            'partner_name' => 'riskcontrol',
            'partner_domain' => 'https://riskcontrol.example',
            'user_email' => 'client@example.com',
            'extraction_type' => 'convocation',
            'pages_requested' => 10,
            'pages_processed' => 2,
            'credits_reserved' => 10,
            'dedupe_key' => 'dup-1',
        ];

        $res1 = $this->postJson('/api/partner/activity-events', $payload1, $this->signedHeaders('/api/partner/activity-events', $payload1));
        $res1->assertOk();
        $res1->assertJsonPath('reused', false);
        $res1->assertJsonPath('normalized_sequence', 1);

        $res2 = $this->postJson('/api/partner/activity-events', $payload1, $this->signedHeaders('/api/partner/activity-events', $payload1));
        $res2->assertOk();
        $res2->assertJsonPath('reused', true);
        $res2->assertJsonPath('normalized_sequence', 1);

        $payload3 = [
            'partner_request_id' => $requestId,
            'event_key' => 'extract_completed',
            'sequence' => 1,
            'status' => 'processing',
            'phase' => 'processing',
            'pages_requested' => 10,
            'pages_processed' => 7,
            'credits_reserved' => 10,
            'dedupe_key' => 'dup-2',
        ];

        $res3 = $this->postJson('/api/partner/activity-events', $payload3, $this->signedHeaders('/api/partner/activity-events', $payload3));
        $res3->assertOk();
        $res3->assertJsonPath('reused', false);
        $res3->assertJsonPath('normalized_sequence', 2);

        $this->assertDatabaseCount('extraction_activity_streams', 1);
        $this->assertDatabaseCount('extraction_activity_events', 2);

        $stream = ExtractionActivityStream::query()->where('partner_request_id', $requestId)->firstOrFail();
        $this->assertSame(2, (int) $stream->latest_sequence);
        $this->assertSame('extract_completed', (string) $stream->last_event_key);

        $sequences = ExtractionActivityEvent::query()
            ->where('partner_request_id', $requestId)
            ->orderBy('sequence')
            ->pluck('sequence')
            ->all();

        $this->assertSame([1, 2], array_map('intval', $sequences));
    }

    public function test_admin_activity_filters_return_matching_streams(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin@peldarg.test',
            'is_admin' => true,
            'status' => 'active',
        ]);

        $failed = ExtractionActivityStream::create([
            'partner_request_id' => (string) Str::uuid(),
            'partner_name' => 'riskcontrol',
            'partner_domain' => 'https://riskcontrol.example',
            'user_email' => 'failed@example.com',
            'extraction_type' => 'convocation',
            'status' => 'failed',
            'phase' => 'failed',
            'last_event_key' => 'failed',
            'latest_sequence' => 3,
            'pages_requested' => 10,
            'pages_processed' => 4,
            'credits_reserved' => 10,
            'credits_consumed' => 0,
            'credits_refunded' => 10,
            'credit_outcome' => 'refunded',
            'last_event_at' => now(),
        ]);

        ExtractionActivityEvent::create([
            'stream_id' => $failed->id,
            'partner_request_id' => $failed->partner_request_id,
            'event_key' => 'failed',
            'sequence' => 3,
            'status' => 'failed',
            'phase' => 'failed',
            'dedupe_key' => 'failed-event',
            'event_at' => now(),
            'payload' => ['event_key' => 'failed'],
        ]);

        $finalized = ExtractionActivityStream::create([
            'partner_request_id' => (string) Str::uuid(),
            'partner_name' => 'riskcontrol',
            'partner_domain' => 'https://riskcontrol.example',
            'user_email' => 'done@example.com',
            'extraction_type' => 'convocation',
            'status' => 'finalized',
            'phase' => 'completed',
            'last_event_key' => 'finalized',
            'latest_sequence' => 5,
            'pages_requested' => 10,
            'pages_processed' => 10,
            'credits_reserved' => 10,
            'credits_consumed' => 10,
            'credits_refunded' => 0,
            'credit_outcome' => 'settled',
            'last_event_at' => now(),
        ]);

        ExtractionActivityEvent::create([
            'stream_id' => $finalized->id,
            'partner_request_id' => $finalized->partner_request_id,
            'event_key' => 'finalized',
            'sequence' => 5,
            'status' => 'finalized',
            'phase' => 'completed',
            'dedupe_key' => 'finalized-event',
            'event_at' => now(),
            'payload' => ['event_key' => 'finalized'],
        ]);

        $response = $this->withSession([
            'authenticated' => true,
            'user_id' => $admin->id,
        ])->getJson('/api/admin/activity-streams?partner=riskcontrol&status=failed&credit_outcome=refunded');

        $response->assertOk();
        $response->assertJsonPath('pagination.total', 1);
        $response->assertJsonCount(1, 'streams');
        $response->assertJsonPath('streams.0.status', 'failed');
        $response->assertJsonPath('streams.0.credit_outcome', 'refunded');
    }

    private function signedHeaders(string $path, array $payload): array
    {
        $body = json_encode($payload) ?: '{}';
        $sig = SignatureService::generateSignature($this->secret, 'POST', $path, $body);

        return [
            'X-Partner-Name' => 'riskcontrol',
            'X-Partner-Token' => 'shared-token',
            'X-Partner-Signature' => $sig['signature'],
            'X-Partner-Timestamp' => $sig['timestamp'],
            'X-Partner-Nonce' => $sig['nonce'],
            'X-Signature-Algorithm' => $sig['algorithm'],
            'Idempotency-Key' => (string) Str::uuid(),
            'X-Forwarded-For' => '127.0.0.1',
        ];
    }
}
