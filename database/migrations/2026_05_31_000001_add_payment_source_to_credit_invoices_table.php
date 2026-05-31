<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Separate the manual invoice (admin-approval) flow from the Paystack
     * automatic-payment flow.
     *
     * payment_source = 'paystack' → created by PaystackPaymentService, auto-fulfilled
     *                                on Paystack success, never shown in admin queue
     * payment_source = 'manual'   → created by user via bank-transfer form, requires
     *                                admin approval
     *
     * Existing rows have no payment_provider='paystack', so they are manual by default.
     */
    public function up(): void
    {
        Schema::table('credit_invoices', function (Blueprint $table) {
            $table->enum('payment_source', ['manual', 'paystack'])->default('manual')->after('payment_provider');
        });

        // Back-fill: any row that already has a payment_provider is a Paystack invoice.
        \Illuminate\Support\Facades\DB::table('credit_invoices')
            ->whereNotNull('payment_provider')
            ->update(['payment_source' => 'paystack']);
    }

    public function down(): void
    {
        Schema::table('credit_invoices', function (Blueprint $table) {
            $table->dropColumn('payment_source');
        });
    }
};
