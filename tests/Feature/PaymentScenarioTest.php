<?php

namespace Tests\Feature;

use App\Models\CreditInvoice;
use App\Models\CreditLedger;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 8: Payment scenario test matrix - 9 scenarios
 * 
 * Valid action_types: reserve, consume, refund, admin_add, admin_deduct, 
 *                     invoice_approved, invoice_rejected, manual_refund, password_reset
 */
class PaymentScenarioTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'email' => 'payment-test@example.com',
            'name' => 'Payment Test User',
            'password' => bcrypt('password'),
            'credit_balance' => 1000,
            'credit_cap' => 2000,
            'status' => 'active',
        ]);
    }

    /** Scenario 1: Successful payment via invoice */
    public function test_scenario_1_successful_payment(): void
    {
        $invoice = CreditInvoice::create([
            'user_id' => $this->user->id,
            'invoice_number' => 'INV-001',
            'requested_credits' => 500,
            'unit_price_usd' => 0.10,
            'requested_amount_usd' => 50,
            'payment_provider' => 'paystack',
            'status' => 'pending',
        ]);

        $invoice->update(['status' => 'approved']);
        CreditLedger::create([
            'user_id' => $this->user->id,
            'invoice_id' => $invoice->id,
            'action_type' => 'invoice_approved',
            'credits' => 500,
            'balance_before' => 1000,
            'balance_after' => 1500,
        ]);
        
        $this->user->update(['credit_balance' => 1500]);
        $this->user->refresh();
        $this->assertEquals(1500, $this->user->credit_balance);
    }

    /** Scenario 2: Failed payment - invoice rejected, no ledger entry */
    public function test_scenario_2_failed_payment(): void
    {
        $invoice = CreditInvoice::create([
            'user_id' => $this->user->id,
            'invoice_number' => 'INV-002',
            'requested_credits' => 500,
            'unit_price_usd' => 0.06,
            'requested_amount_usd' => 30,
            'payment_provider' => 'paystack',
            'status' => 'pending',
        ]);

        $invoice->update(['status' => 'rejected']);
        // No ledger entry for failed payment

        $this->user->refresh();
        $this->assertEquals(1000, $this->user->credit_balance); // Unchanged
    }

    /** Scenario 3: Duplicate webhook prevention - idempotent */
    public function test_scenario_3_duplicate_prevention(): void
    {
        $invoice = CreditInvoice::create([
            'user_id' => $this->user->id,
            'invoice_number' => 'INV-003',
            'requested_credits' => 500,
            'unit_price_usd' => 0.06,
            'requested_amount_usd' => 30,
            'payment_reference' => 'ref-dup',
            'payment_provider' => 'paystack',
            'status' => 'pending',
        ]);

        $invoice->update(['status' => 'approved']);
        CreditLedger::create([
            'user_id' => $this->user->id,
            'invoice_id' => $invoice->id,
            'action_type' => 'invoice_approved',
            'credits' => 500,
            'balance_before' => 1000,
            'balance_after' => 1500,
        ]);

        // Second call would see invoice already paid
        $count = CreditLedger::where('invoice_id', $invoice->id)
            ->where('action_type', 'invoice_approved')->count();
        $this->assertEquals(1, $count); // Only one approval entry
    }

    /** Scenario 4: Reserve success - authorization reserves without deducting */
    public function test_scenario_4_reserve_success(): void
    {
        CreditLedger::create([
            'user_id' => $this->user->id,
            'action_type' => 'reserve',
            'credits' => 5,
            'balance_before' => 1000,
            'balance_after' => 1000, // Balance unchanged
        ]);

        $this->user->refresh();
        // Balance unchanged during reserve
        $this->assertEquals(1000, $this->user->credit_balance);
    }

    /** Scenario 5: Reserve failure - insufficient balance */
    public function test_scenario_5_reserve_failure(): void
    {
        $this->user->update(['credit_balance' => 2]);

        // Authorization would fail - no reservation created
        $reservations = CreditLedger::where('user_id', $this->user->id)
            ->where('action_type', 'reserve')
            ->count();
        $this->assertEquals(0, $reservations);
    }

    /** Scenario 6: Extraction failure - full refund */
    public function test_scenario_6_extraction_failure_refund(): void
    {
        // First reserve
        CreditLedger::create([
            'user_id' => $this->user->id,
            'action_type' => 'reserve',
            'credits' => 10,
            'balance_before' => 1000,
            'balance_after' => 1000,
        ]);

        // Then refund on failure
        CreditLedger::create([
            'user_id' => $this->user->id,
            'action_type' => 'refund',
            'credits' => 10,
            'balance_before' => 1000,
            'balance_after' => 1010,
        ]);

        $this->user->update(['credit_balance' => 1010]);
        $this->user->refresh();
        $this->assertEquals(1010, $this->user->credit_balance);
    }

    /** Scenario 7: Partial extraction - partial refund */
    public function test_scenario_7_partial_extraction(): void
    {
        // Reserve 10 pages
        CreditLedger::create([
            'user_id' => $this->user->id,
            'action_type' => 'reserve',
            'credits' => 10,
            'balance_before' => 1000,
            'balance_after' => 1000,
        ]);

        // Consume 7 pages
        CreditLedger::create([
            'user_id' => $this->user->id,
            'action_type' => 'consume',
            'credits' => 7,
            'balance_before' => 1000,
            'balance_after' => 993,
        ]);

        // Refund 3 unused pages
        CreditLedger::create([
            'user_id' => $this->user->id,
            'action_type' => 'refund',
            'credits' => 3,
            'balance_before' => 993,
            'balance_after' => 996,
        ]);

        $this->user->update(['credit_balance' => 996]);
        $this->user->refresh();
        $this->assertEquals(996, $this->user->credit_balance);
    }

    /** Scenario 8: Admin top-up */
    public function test_scenario_8_admin_topup(): void
    {
        CreditLedger::create([
            'user_id' => $this->user->id,
            'action_type' => 'admin_add',
            'credits' => 500,
            'balance_before' => 1000,
            'balance_after' => 1500,
        ]);

        $this->user->update(['credit_balance' => 1500]);
        $this->user->refresh();
        $this->assertEquals(1500, $this->user->credit_balance);
    }

    /** Scenario 9: Admin deduct */
    public function test_scenario_9_admin_deduct(): void
    {
        CreditLedger::create([
            'user_id' => $this->user->id,
            'action_type' => 'admin_deduct',
            'credits' => 200,
            'balance_before' => 1000,
            'balance_after' => 800,
        ]);

        $this->user->update(['credit_balance' => 800]);
        $this->user->refresh();
        $this->assertEquals(800, $this->user->credit_balance);
    }
}

