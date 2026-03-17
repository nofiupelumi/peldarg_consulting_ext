<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ApiTierPermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_select_any_paid_tier_without_tier_validation_block(): void
    {
        $admin = User::factory()->create([
            'status' => 'active',
            'is_admin' => true,
            'credit_balance' => 20,
            'allowed_api_tiers' => ['paid_1'],
        ]);

        $response = $this->withSession([
            'authenticated' => true,
            'user_id' => $admin->id,
            'is_admin' => true,
        ])->postJson('/api/upload', $this->uploadPayload('paid_3'));

        // The request should pass tier authorization and fail only at PDF page parsing.
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['file']);
        $response->assertJsonMissingValidationErrors(['api_tier']);
    }

    public function test_non_admin_is_blocked_when_selecting_unassigned_tier(): void
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
        ])->postJson('/api/upload', $this->uploadPayload('paid_3'));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['api_tier']);
    }

    public function test_non_admin_allowed_tier_is_accepted_by_permission_check(): void
    {
        $user = User::factory()->create([
            'status' => 'active',
            'is_admin' => false,
            'credit_balance' => 20,
            'allowed_api_tiers' => ['paid_2'],
        ]);

        $response = $this->withSession([
            'authenticated' => true,
            'user_id' => $user->id,
            'is_admin' => false,
        ])->postJson('/api/upload', $this->uploadPayload('paid_2'));

        // Tier is allowed; parser failure should surface under file, not api_tier.
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
