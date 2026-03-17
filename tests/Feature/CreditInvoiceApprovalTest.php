<?php

namespace Tests\Feature;

use App\Models\CreditInvoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreditInvoiceApprovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_approve_pending_invoice_and_credit_user(): void
    {
        /** @var User $admin */
        $admin = User::factory()->create([
            'company_name' => 'Peldarg Admin',
            'status' => 'active',
            'credit_balance' => 0,
            'credit_cap' => 0,
            'must_change_password' => false,
            'is_admin' => true,
        ]);

        /** @var User $target */
        $target = User::factory()->create([
            'company_name' => 'Acme Ltd',
            'status' => 'active',
            'credit_balance' => 5,
            'credit_cap' => 0,
            'must_change_password' => false,
            'is_admin' => false,
        ]);

        $invoice = CreditInvoice::create([
            'user_id' => $target->id,
            'invoice_number' => 'INV-TEST-1',
            'requested_credits' => 10,
            'unit_price_usd' => 0.06,
            'requested_amount_usd' => 0.6,
            'payment_reference' => 'REF-123',
            'proof_path' => null,
            'status' => 'pending',
        ]);

        $res = $this->withSession([
            'authenticated' => true,
            'user_id' => $admin->id,
        ])->postJson("/api/admin/credit-invoices/{$invoice->id}/approve", []);

        $res->assertOk()->assertJson(['ok' => true]);

        $invoice->refresh();
        $target->refresh();

        $this->assertSame('approved', $invoice->status);
        $this->assertSame(15, (int) $target->credit_balance);
    }
}
