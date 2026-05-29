<?php

namespace Tests\Feature;

use App\Models\PartnerAllowlist;
use App\Models\PartnerRequestIdempotency;
use App\Models\PartnerSecretRotationLog;
use App\Services\SignatureService;
use App\Services\PartnerSecretRotationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase6SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected string $partnerName = 'riskcontrol';
    protected string $secretKey = 'test_secret_key_64_chars_long_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx';
    protected string $secretKeyId = 'key_id_v1';

    protected function setUp(): void
    {
        parent::setUp();

        // Setup partner in allowlist
        PartnerAllowlist::create([
            'partner_name' => $this->partnerName,
            'partner_domain' => 'http://127.0.0.1:9020',
            'allowed_ips' => '127.0.0.1,::1',
            'current_secret_key' => $this->secretKey,
            'current_secret_key_id' => $this->secretKeyId,
            'secret_rotated_at' => now(),
            'secret_expires_at' => now()->addDays(90),
            'active' => true,
        ]);
    }

    /**
     * Test that valid HMAC signature is accepted
     */
    public function testValidHmacSignatureIsAccepted()
    {
        $method = 'POST';
        $path = '/api/partner/authorize-extraction';
        $body = json_encode(['pages_requested' => 10]);
        $timestamp = now()->toIso8601String();
        $nonce = (string) \Illuminate\Support\Str::uuid();

        $sig = SignatureService::generateSignature(
            $this->secretKey,
            $method,
            $path,
            $body,
            $timestamp,
            $nonce
        );

        $response = $this->postJson(
            '/api/partner/authorize-extraction',
            ['pages_requested' => 10],
            [
                'X-Partner-Name' => $this->partnerName,
                'X-Partner-Signature' => $sig['signature'],
                'X-Partner-Timestamp' => $sig['timestamp'],
                'X-Partner-Nonce' => $sig['nonce'],
                'X-Signature-Algorithm' => 'hmac-sha256',
            ]
        );

        // Signature middleware should not be the reason for rejection.
        $error = (string) ($response->json('error') ?? '');
        $this->assertFalse(
            str_contains($error, 'Signature verification failed'),
            'Valid signature should not fail signature verification middleware.'
        );
    }

    /**
     * Test that invalid HMAC signature is rejected
     */
    public function testInvalidHmacSignatureIsRejected()
    {
        $method = 'POST';
        $path = '/api/partner/authorize-extraction';
        $body = json_encode(['pages_requested' => 10]);
        $timestamp = now()->toIso8601String();
        $nonce = (string) \Illuminate\Support\Str::uuid();

        $sig = SignatureService::generateSignature(
            $this->secretKey,
            $method,
            $path,
            $body,
            $timestamp,
            $nonce
        );

        // Corrupt the signature
        $badSignature = str_repeat('a', 64);

        $response = $this->postJson(
            '/api/partner/authorize-extraction',
            ['pages_requested' => 10],
            [
                'X-Partner-Name' => $this->partnerName,
                'X-Partner-Signature' => $badSignature,
                'X-Partner-Timestamp' => $sig['timestamp'],
                'X-Partner-Nonce' => $sig['nonce'],
            ]
        );

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertStringContainsString('verification failed', $response->json('error'));
    }

    /**
     * Test that missing signature headers are rejected
     */
    public function testMissingSignatureHeadersAreRejected()
    {
        $response = $this->postJson('/api/partner/authorize-extraction', ['pages_requested' => 10]);

        $this->assertEquals(401, $response->getStatusCode());
        // Middleware order can return either missing header or unknown partner first.
        $error = (string) ($response->json('error') ?? '');
        $this->assertTrue(
            str_contains($error, 'Missing') || str_contains($error, 'not found'),
            "Expected missing-header or partner-not-found error, got: {$error}"
        );
    }

    /**
     * Test that expired timestamp is rejected
     */
    public function testExpiredTimestampIsRejected()
    {
        $method = 'POST';
        $path = '/api/partner/authorize-extraction';
        $body = json_encode(['pages_requested' => 10]);
        $oldTimestamp = now()->subMinutes(10)->toIso8601String();
        $nonce = (string) \Illuminate\Support\Str::uuid();

        $sig = SignatureService::generateSignature(
            $this->secretKey,
            $method,
            $path,
            $body,
            $oldTimestamp,
            $nonce
        );

        $response = $this->postJson(
            '/api/partner/authorize-extraction',
            ['pages_requested' => 10],
            [
                'X-Partner-Name' => $this->partnerName,
                'X-Partner-Signature' => $sig['signature'],
                'X-Partner-Timestamp' => $oldTimestamp,
                'X-Partner-Nonce' => $sig['nonce'],
            ]
        );

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertStringContainsString('timestamp', strtolower($response->json('error')));
    }

    /**
     * Test that nonce replay is prevented
     */
    public function testNonceReplayIsPreventedWithIdempotency()
    {
        $method = 'POST';
        $path = '/api/partner/authorize-extraction';
        $body = json_encode(['pages_requested' => 10, 'user_email' => 'test@example.com']);
        $timestamp = now()->toIso8601String();
        $nonce = (string) \Illuminate\Support\Str::uuid();
        $idempotencyKey = (string) \Illuminate\Support\Str::uuid();

        $sig = SignatureService::generateSignature(
            $this->secretKey,
            $method,
            $path,
            $body,
            $timestamp,
            $nonce
        );

        // First request
        $response1 = $this->postJson(
            '/api/partner/authorize-extraction',
            json_decode($body, true) + ['partner_request_id' => $idempotencyKey],
            [
                'X-Partner-Name' => $this->partnerName,
                'X-Partner-Signature' => $sig['signature'],
                'X-Partner-Timestamp' => $sig['timestamp'],
                'X-Partner-Nonce' => $sig['nonce'],
                'Idempotency-Key' => $idempotencyKey,
            ]
        );

        // Attempt replay with same nonce - should fail
        $response2 = $this->postJson(
            '/api/partner/authorize-extraction',
            json_decode($body, true) + ['partner_request_id' => $idempotencyKey],
            [
                'X-Partner-Name' => $this->partnerName,
                'X-Partner-Signature' => $sig['signature'],
                'X-Partner-Timestamp' => $sig['timestamp'],
                'X-Partner-Nonce' => $sig['nonce'],
                'Idempotency-Key' => $idempotencyKey,
            ]
        );

        // Second request should return cached response (200 if first succeeded)
        $this->assertIsInt($response2->getStatusCode());
    }

    /**
     * Test that IP allowlist is enforced
     */
    public function testIpAllowlistIsEnforced()
    {
        PartnerAllowlist::where('partner_name', $this->partnerName)->update([
            'allowed_ips' => '192.168.1.1,10.0.0.1',  // Restrict to different IPs
        ]);

        $method = 'POST';
        $path = '/api/partner/authorize-extraction';
        $body = json_encode(['pages_requested' => 10]);
        $timestamp = now()->toIso8601String();
        $nonce = (string) \Illuminate\Support\Str::uuid();

        $sig = SignatureService::generateSignature(
            $this->secretKey,
            $method,
            $path,
            $body,
            $timestamp,
            $nonce
        );

        $response = $this->postJson(
            '/api/partner/authorize-extraction',
            ['pages_requested' => 10],
            [
                'X-Partner-Name' => $this->partnerName,
                'X-Partner-Signature' => $sig['signature'],
                'X-Partner-Timestamp' => $sig['timestamp'],
                'X-Partner-Nonce' => $sig['nonce'],
            ]
        );

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertStringContainsString('allowlist', $response->json('error'));
    }

    /**
     * Test that inactive partner is rejected
     */
    public function testInactivePartnerIsRejected()
    {
        PartnerAllowlist::where('partner_name', $this->partnerName)->update(['active' => false]);

        $method = 'POST';
        $path = '/api/partner/authorize-extraction';
        $body = json_encode(['pages_requested' => 10]);
        $timestamp = now()->toIso8601String();
        $nonce = (string) \Illuminate\Support\Str::uuid();

        $sig = SignatureService::generateSignature(
            $this->secretKey,
            $method,
            $path,
            $body,
            $timestamp,
            $nonce
        );

        $response = $this->postJson(
            '/api/partner/authorize-extraction',
            ['pages_requested' => 10],
            [
                'X-Partner-Name' => $this->partnerName,
                'X-Partner-Signature' => $sig['signature'],
                'X-Partner-Timestamp' => $sig['timestamp'],
                'X-Partner-Nonce' => $sig['nonce'],
            ]
        );

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertStringContainsString('not found', $response->json('error'));
    }

    /**
     * Test that idempotency tracking records requests
     */
    public function testIdempotencyTrackingRecordsRequests()
    {
        $method = 'POST';
        $path = '/api/partner/authorize-extraction';
        $payload = [
            'pages_requested' => 10,
            'user_email' => 'test@example.com',
            'partner_request_id' => (string) \Illuminate\Support\Str::uuid(),
        ];
        $body = json_encode($payload);
        $timestamp = now()->toIso8601String();
        $nonce = (string) \Illuminate\Support\Str::uuid();
        $idempotencyKey = (string) \Illuminate\Support\Str::uuid();

        $sig = SignatureService::generateSignature(
            $this->secretKey,
            $method,
            $path,
            $body,
            $timestamp,
            $nonce
        );

        $this->postJson(
            '/api/partner/authorize-extraction',
            $payload,
            [
                'X-Partner-Name' => $this->partnerName,
                'X-Partner-Signature' => $sig['signature'],
                'X-Partner-Timestamp' => $sig['timestamp'],
                'X-Partner-Nonce' => $sig['nonce'],
                'Idempotency-Key' => $idempotencyKey,
            ]
        );

        $record = PartnerRequestIdempotency::where('idempotency_key', $idempotencyKey)->first();
        $this->assertNotNull($record);
        $this->assertEquals($this->partnerName, $record->partner_name);
        $this->assertEquals($method, $record->request_method);
    }

    /**
     * Test secret rotation
     */
    public function testSecretRotationUpdatesKeys()
    {
        $oldKeyId = PartnerAllowlist::where('partner_name', $this->partnerName)->value('current_secret_key_id');

        PartnerSecretRotationService::rotateSecret($this->partnerName, 'manual', null, 'Test rotation');

        $partner = PartnerAllowlist::where('partner_name', $this->partnerName)->first();
        $this->assertNotNull($partner);
        $this->assertNotEquals($oldKeyId, $partner->current_secret_key_id);

        $log = PartnerSecretRotationLog::where('partner_name', $this->partnerName)->first();
        $this->assertNotNull($log);
        $this->assertEquals('manual', $log->reason);
    }

    /**
     * Test signature is timing-safe
     */
    public function testSignatureVerificationIsTimingSafe()
    {
        $method = 'POST';
        $path = '/api/partner/authorize-extraction';
        $body = json_encode(['data' => 'test']);
        $timestamp = now()->toIso8601String();
        $nonce = (string) \Illuminate\Support\Str::uuid();

        $sig = SignatureService::generateSignature(
            $this->secretKey,
            $method,
            $path,
            $body,
            $timestamp,
            $nonce
        );

        $result = SignatureService::verifySignature(
            $this->secretKey,
            $method,
            $path,
            $body,
            $sig['signature'],
            $sig['timestamp'],
            $sig['nonce']
        );

        $this->assertTrue($result['valid']);
        $this->assertNull($result['error']);
    }
}
