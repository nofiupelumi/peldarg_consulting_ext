<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credit_ledgers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('document_id')->nullable()->constrained('documents')->nullOnDelete();
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->enum('action_type', [
                'reserve',
                'consume',
                'refund',
                'admin_add',
                'admin_deduct',
                'invoice_approved',
                'invoice_rejected',
                'manual_refund',
                'password_reset',
            ]);
            $table->bigInteger('credits');
            $table->unsignedBigInteger('balance_before');
            $table->unsignedBigInteger('balance_after');
            $table->decimal('unit_price_usd', 10, 4)->nullable();
            $table->decimal('amount_usd', 12, 4)->nullable();
            $table->json('meta')->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_ledgers');
    }
};
