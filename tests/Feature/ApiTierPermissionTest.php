<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ApiTierPermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_upload_rejects_any_tier_other_than_paid_1(): void
    {
        $user = User::factory()->create([
            'status' => 'active',
            'is_admin' => true,
            'credit_balance' => 20,
            'allowed_api_tiers' => ['paid_1'],
        ]);

        foreach (['paid_2', 'paid_3'] as $invalidTier) {
            $response = $this->withSession([
                'authenticated' => true,
                'user_id' => $user->id,
                'is_admin' => true,
            ])->postJson('/api/upload', $this->uploadPayload($invalidTier));

            $response->assertStatus(422);
            $response->assertJsonValidationErrors(['api_tier']);
        }
    }

    public function test_upload_accepts_paid_1_for_tier_validation(): void
    {
        $user = User::factory()->create([
            'status' => 'active',
            'is_admin' => false,
            'credit_balance' => 20,
            'allowed_api_tiers' => ['paid_1'],
        ]);

        $response = $this->withSession([
            'authenticated' => true,
            'user_id' => $user->id,
            'is_admin' => false,
        ])->postJson('/api/upload', $this->uploadPayload('paid_1'));

        // Tier is accepted; any failure at this point should be unrelated to api_tier.
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['file']);
        $response->assertJsonMissingValidationErrors(['api_tier']);
    }

    private function uploadPayload(string $tier): array
    {
        return [
            'file' => UploadedFile::fake()->create('booklet.pdf', 16, 'application/pdf'),
            'api_tier' => $tier,
            'session' => '2025/2026',
        ];
    }
}
