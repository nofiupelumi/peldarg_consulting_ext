<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('credit_invoices', function (Blueprint $table) {
            $table->string('payment_provider')->nullable()->after('payment_reference');
            $table->string('gateway_reference')->nullable()->after('payment_provider');
            $table->string('gateway_access_code')->nullable()->after('gateway_reference');
            $table->text('gateway_authorization_url')->nullable()->after('gateway_access_code');
            $table->string('gateway_status')->nullable()->after('gateway_authorization_url');
            $table->unsignedBigInteger('amount_ngn_kobo')->nullable()->after('gateway_status');
            $table->json('payment_payload')->nullable()->after('amount_ngn_kobo');
            $table->timestamp('initialized_at')->nullable()->after('reviewed_at');
            $table->timestamp('paid_at')->nullable()->after('initialized_at');
            $table->timestamp('fulfilled_at')->nullable()->after('paid_at');

            $table->unique('gateway_reference');
        });
    }

    public function down(): void
    {
        Schema::table('credit_invoices', function (Blueprint $table) {
            $table->dropUnique(['gateway_reference']);
            $table->dropColumn([
                'payment_provider',
                'gateway_reference',
                'gateway_access_code',
                'gateway_authorization_url',
                'gateway_status',
                'amount_ngn_kobo',
                'payment_payload',
                'initialized_at',
                'paid_at',
                'fulfilled_at',
            ]);
        });
    }
};