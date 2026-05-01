<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partner_extraction_authorizations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('partner_name')->nullable();
            $table->string('partner_domain')->nullable();
            $table->string('partner_user_reference')->nullable();
            $table->uuid('partner_request_id')->unique();
            $table->string('extraction_type');
            $table->unsignedInteger('pages_requested');
            $table->unsignedInteger('pages_processed')->default(0);
            $table->unsignedInteger('pages_with_results')->default(0);
            $table->unsignedBigInteger('credits_reserved')->default(0);
            $table->unsignedBigInteger('credits_consumed')->default(0);
            $table->unsignedBigInteger('credits_refunded')->default(0);
            $table->string('api_tier')->nullable();
            $table->string('status')->default('authorized');
            $table->text('failed_reason')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('finalized_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partner_extraction_authorizations');
    }
};