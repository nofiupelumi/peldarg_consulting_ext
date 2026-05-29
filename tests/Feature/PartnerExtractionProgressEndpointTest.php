<?php

namespace Tests\Feature;

use App\Models\PartnerAllowlist;
use App\Models\PartnerExtractionAuthorization;
use App\Models\User;
use App\Services\SignatureService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

class PartnerExtractionProgressEndpointTest extends TestCase
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

    public function test_partner_progress_returns_processing_phase_with_percent(): void
    {
        $user = User::factory()->create([
            'email' => 'client@example.com',
            'status' => 'active',
        ]);

        $requestId = (string) Str::uuid();

        PartnerExtractionAuthorization::create([
            'user_id' => $user->id,
            'partner_name' => 'riskcontrol',
            'partner_domain' => 'https://riskcontrol.example',
            'partner_request_id' => $requestId,
            'extraction_type' => 'convocation',
            'pages_requested' => 10,
            'pages_processed' => 4,
            'pages_with_results' => 3,
            'credits_reserved' => 10,
            'credits_consumed' => 4,
            'credits_refunded' => 0,
            'status' => 'authorized',
            'expires_at' => Carbon::parse('2026-06-30 12:00:00'),
        ]);

        $payload = [
            'partner_request_id' => $requestId,
            'user_email' => $user->email,
        ];

        $response = $this->postJson(
            '/api/partner/extraction-progress',
            $payload,
            $this->signedHeaders('/api/partner/extraction-progress', $payload)
        );

        $response->assertOk();
        $response->assertJsonPath('partner_request_id', $requestId);
        $response->assertJsonPath('status', 'authorized');
        $response->assertJsonPath('phase', 'processing');
        $response->assertJsonPath('pages_requested', 10);
        $response->assertJsonPath('pages_processed', 4);
        $response->assertJsonPath('progress_percent', 40);
    }

    public function test_partner_progress_rejects_mismatched_user_email(): void
    {
        $owner = User::factory()->create(['email' => 'owner@example.com']);

        $requestId = (string) Str::uuid();

        PartnerExtractionAuthorization::create([
            'user_id' => $owner->id,
            'partner_name' => 'riskcontrol',
            'partner_domain' => 'https://riskcontrol.example',
            'partner_request_id' => $requestId,
            'extraction_type' => 'convocation',
            'pages_requested' => 10,
            'pages_processed' => 0,
            'status' => 'authorized',
        ]);

        $payload = [
            'partner_request_id' => $requestId,
            'user_email' => 'intruder@example.com',
        ];

        $response = $this->postJson(
            '/api/partner/extraction-progress',
            $payload,
            $this->signedHeaders('/api/partner/extraction-progress', $payload)
        );

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['partner_request_id']);
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
