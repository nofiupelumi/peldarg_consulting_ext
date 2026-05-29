<?php

namespace Tests\Feature;

use App\Models\PartnerExtractionAuthorization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AdminReconciliationControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_reconciliation_returns_expected_shape_and_variance_values(): void
    {
        config()->set('services.partner.token', 'shared-token');
        config()->set('services.partner.reconciliation_url', 'http://partner.test/api/partner/reconciliation-summary');

        Http::fake([
            'http://partner.test/api/partner/reconciliation-summary' => Http::response([
                'date_from' => '2026-05-01',
                'date_to' => '2026-05-01',
                'processed_pages_total' => 12,
                'booklet_pages_total' => 8,
                'certificate_pages_total' => 4,
                'completed_documents_total' => 3,
                'completed_booklets_total' => 2,
                'completed_certificates_total' => 1,
            ], 200),
        ]);

        $admin = User::factory()->create([
            'status' => 'active',
            'is_admin' => true,
            'credit_balance' => 100,
        ]);

        $this->makeAuthorization([
            'user_id' => $admin->id,
            'partner_request_id' => 'req-1',
            'pages_requested' => 10,
            'pages_processed' => 10,
            'credits_reserved' => 10,
            'credits_consumed' => 9,
            'credits_refunded' => 1,
            'status' => 'finalized',
            'created_at' => Carbon::parse('2026-05-01 09:00:00'),
        ]);

        $this->makeAuthorization([
            'user_id' => $admin->id,
            'partner_request_id' => 'req-2',
            'pages_requested' => 5,
            'pages_processed' => 5,
            'credits_reserved' => 5,
            'credits_consumed' => 5,
            'credits_refunded' => 0,
            'status' => 'failed',
            'created_at' => Carbon::parse('2026-05-01 12:00:00'),
        ]);

        // Outside requested range; should not affect totals.
        $this->makeAuthorization([
            'user_id' => $admin->id,
            'partner_request_id' => 'req-3',
            'pages_requested' => 99,
            'pages_processed' => 99,
            'credits_reserved' => 99,
            'credits_consumed' => 99,
            'credits_refunded' => 0,
            'status' => 'finalized',
            'created_at' => Carbon::parse('2026-04-30 23:59:00'),
        ]);

        $response = $this->withSession([
            'authenticated' => true,
            'user_id' => $admin->id,
            'is_admin' => true,
        ])->getJson('/api/admin/reconciliation?date_from=2026-05-01&date_to=2026-05-01');

        $response->assertOk();
        $response->assertJsonStructure([
            'date_from',
            'date_to',
            'peldarg' => [
                'authorization_count',
                'pages_requested_total',
                'pages_processed_total',
                'reserved_credits_total',
                'consumed_credits_total',
                'refunded_credits_total',
                'success_count',
                'failed_count',
            ],
            'partner',
            'variance' => [
                'processed_pages_delta',
                'consumed_vs_processed_delta',
            ],
        ]);

        $response->assertJsonPath('peldarg.authorization_count', 2);
        $response->assertJsonPath('peldarg.pages_requested_total', 15);
        $response->assertJsonPath('peldarg.pages_processed_total', 15);
        $response->assertJsonPath('peldarg.reserved_credits_total', 15);
        $response->assertJsonPath('peldarg.consumed_credits_total', 14);
        $response->assertJsonPath('peldarg.refunded_credits_total', 1);
        $response->assertJsonPath('variance.processed_pages_delta', 3);
        $response->assertJsonPath('variance.consumed_vs_processed_delta', 2);
        $response->assertJsonPath('partner.available', true);
        $response->assertJsonPath('partner.processed_pages_total', 12);
    }

    private function makeAuthorization(array $attributes): void
    {
        $record = new PartnerExtractionAuthorization([
            'user_id' => $attributes['user_id'],
            'partner_name' => 'riskcontrol',
            'partner_domain' => 'http://127.0.0.1:9020',
            'partner_user_reference' => 'admin@rcsn.com',
            'partner_request_id' => $attributes['partner_request_id'],
            'extraction_type' => 'convocation',
            'pages_requested' => $attributes['pages_requested'],
            'pages_processed' => $attributes['pages_processed'],
            'pages_with_results' => 0,
            'credits_reserved' => $attributes['credits_reserved'],
            'credits_consumed' => $attributes['credits_consumed'],
            'credits_refunded' => $attributes['credits_refunded'],
            'api_tier' => 'paid_1',
            'status' => $attributes['status'],
            'failed_reason' => null,
            'expires_at' => now()->addHour(),
            'finalized_at' => null,
            'meta' => [],
        ]);

        $record->created_at = $attributes['created_at'];
        $record->updated_at = $attributes['created_at'];
        $record->save();
    }
}
