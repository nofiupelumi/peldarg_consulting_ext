<?php

namespace Tests\Feature;

use App\Models\CreditInvoice;
use App\Models\CreditLedger;
use App\Models\PartnerAllowlist;
use App\Models\User;
use App\Services\SignatureService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

class PaymentHistoryEndpointsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-06-20 12:00:00'));

        config()->set('services.partner.token', 'shared-token');

        PartnerAllowlist::create([
            'partner_name' => 'riskcontrol',
            'partner_domain' => 'http://127.0.0.1:9020',
            'allowed_ips' => '127.0.0.1,::1',
            'current_secret_key' => 'test_secret_key_64_chars_long_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',
            'current_secret_key_id' => 'key_v1',
            'secret_rotated_at' => now(),
            'secret_expires_at' => now()->addDays(90),
            'active' => true,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_partner_payment_history_applies_month_year_summary_filters(): void
    {
        $user = User::factory()->create([
            'email' => 'client@example.com',
            'status' => 'active',
        ]);

        $invoiceMay = CreditInvoice::create([
            'user_id' => $user->id,
            'invoice_number' => 'INV-MAY',
            'requested_credits' => 10,
            'unit_price_usd' => 0.06,
            'requested_amount_usd' => 0.6,
            'amount_ngn_kobo' => 100000,
            'payment_reference' => 'PAY-MAY',
            'status' => 'approved',
            'paid_at' => Carbon::parse('2026-05-10 09:00:00'),
            'fulfilled_at' => Carbon::parse('2026-05-10 09:10:00'),
            'created_at' => Carbon::parse('2026-05-10 08:30:00'),
        ]);

        $invoiceJune = CreditInvoice::create([
            'user_id' => $user->id,
            'invoice_number' => 'INV-JUN',
            'requested_credits' => 20,
            'unit_price_usd' => 0.06,
            'requested_amount_usd' => 1.2,
            'amount_ngn_kobo' => 200000,
            'payment_reference' => 'PAY-JUN',
            'status' => 'approved',
            'paid_at' => Carbon::parse('2026-06-12 10:00:00'),
            'fulfilled_at' => Carbon::parse('2026-06-12 10:20:00'),
            'created_at' => Carbon::parse('2026-06-12 09:00:00'),
        ]);

        CreditInvoice::create([
            'user_id' => $user->id,
            'invoice_number' => 'INV-JUN-PENDING',
            'requested_credits' => 5,
            'unit_price_usd' => 0.06,
            'requested_amount_usd' => 0.3,
            'amount_ngn_kobo' => 50000,
            'payment_reference' => 'PAY-JUN-PENDING',
            'status' => 'pending',
            'created_at' => Carbon::parse('2026-06-13 10:00:00'),
        ]);

        CreditLedger::create([
            'user_id' => $user->id,
            'invoice_id' => $invoiceJune->id,
            'action_type' => 'invoice_approved',
            'credits' => 20,
            'balance_before' => 10,
            'balance_after' => 30,
            'amount_usd' => 1.2,
            'created_at' => Carbon::parse('2026-06-12 10:30:00'),
        ]);

        CreditLedger::create([
            'user_id' => $user->id,
            'invoice_id' => $invoiceMay->id,
            'action_type' => 'invoice_approved',
            'credits' => 10,
            'balance_before' => 0,
            'balance_after' => 10,
            'amount_usd' => 0.6,
            'created_at' => Carbon::parse('2026-05-10 09:20:00'),
        ]);

        $payload = [
            'user_email' => $user->email,
            'year' => 2026,
            'month' => 6,
        ];

        $response = $this->postJson(
            '/api/partner/payment-history',
            $payload,
            $this->signedPartnerHeaders('/api/partner/payment-history', $payload)
        );

        $response->assertOk();
        $response->assertJsonPath('filters.year', 2026);
        $response->assertJsonPath('filters.month', 6);
        $response->assertJsonPath('summary.selected_period.invoice_count', 1);
        $response->assertJsonPath('summary.selected_period.requested_credits', 20);
        $response->assertJsonPath('summary.selected_period.amount_ngn_kobo', 200000);
        $response->assertJsonPath('summary.current_month.requested_credits', 20);
        $response->assertJsonPath('summary.current_year.requested_credits', 30);

        $items = collect($response->json('items'));
        $juneApproved = $items->firstWhere('invoice_number', 'INV-JUN');

        $this->assertNotNull($juneApproved);
        $this->assertSame(1, count($juneApproved['ledger_entries'] ?? []));
        $this->assertSame('invoice_approved', $juneApproved['ledger_entries'][0]['action_type'] ?? null);
    }

    public function test_admin_payment_history_endpoint_returns_user_scoped_payload(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin@peldarg.test',
            'is_admin' => true,
            'status' => 'active',
        ]);

        $user = User::factory()->create([
            'email' => 'tenant@example.com',
            'company_name' => 'Tenant Co',
            'status' => 'active',
        ]);

        CreditInvoice::create([
            'user_id' => $user->id,
            'invoice_number' => 'INV-ADMIN-JUN',
            'requested_credits' => 12,
            'unit_price_usd' => 0.06,
            'requested_amount_usd' => 0.72,
            'amount_ngn_kobo' => 120000,
            'payment_reference' => 'PAY-ADMIN',
            'status' => 'approved',
            'paid_at' => Carbon::parse('2026-06-11 08:00:00'),
            'fulfilled_at' => Carbon::parse('2026-06-11 08:10:00'),
            'created_at' => Carbon::parse('2026-06-11 07:30:00'),
        ]);

        $response = $this->withSession([
            'authenticated' => true,
            'user_id' => $admin->id,
        ])->getJson("/api/admin/payment-history?user_id={$user->id}&year=2026&month=6");

        $response->assertOk();
        $response->assertJsonPath('filters.user_id', $user->id);
        $response->assertJsonPath('filters.year', 2026);
        $response->assertJsonPath('filters.month', 6);
        $response->assertJsonCount(1, 'users');
        $response->assertJsonPath('users.0.user_email', 'tenant@example.com');
        $response->assertJsonPath('users.0.payment_history.summary.selected_period.requested_credits', 12);
    }

    private function signedPartnerHeaders(string $path, array $payload): array
    {
        $secret = 'test_secret_key_64_chars_long_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx';
        $body = json_encode($payload) ?: '{}';
        $sig = SignatureService::generateSignature($secret, 'POST', $path, $body);

        return [
            'X-Partner-Name' => 'riskcontrol',
            'X-Partner-Token' => 'shared-token',
            'X-Partner-Signature' => $sig['signature'],
            'X-Partner-Timestamp' => $sig['timestamp'],
            'X-Partner-Nonce' => $sig['nonce'],
            'X-Signature-Algorithm' => $sig['algorithm'],
            'Idempotency-Key' => (string) Str::uuid(),
        ];
    }
}
