<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('extraction_activity_streams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('authorization_id')->nullable()->constrained('partner_extraction_authorizations')->nullOnDelete();
            $table->uuid('partner_request_id')->unique();
            $table->string('partner_name')->nullable();
            $table->string('partner_domain')->nullable();
            $table->string('user_email')->nullable();
            $table->string('extraction_type')->nullable();
            $table->string('status')->default('processing');
            $table->string('phase')->default('processing');
            $table->string('last_event_key')->nullable();
            $table->unsignedBigInteger('latest_sequence')->default(0);
            $table->unsignedInteger('pages_requested')->default(0);
            $table->unsignedInteger('pages_processed')->default(0);
            $table->unsignedInteger('pages_with_results')->default(0);
            $table->unsignedBigInteger('credits_reserved')->default(0);
            $table->unsignedBigInteger('credits_consumed')->default(0);
            $table->unsignedBigInteger('credits_refunded')->default(0);
            $table->string('credit_outcome')->nullable();
            $table->text('failed_reason')->nullable();
            $table->string('run_id')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('last_event_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->json('last_payload')->nullable();
            $table->timestamps();

            $table->index(['created_at']);
            $table->index(['partner_name']);
            $table->index(['user_email']);
            $table->index(['extraction_type']);
            $table->index(['status']);
            $table->index(['phase']);
            $table->index(['credit_outcome']);
            $table->index(['last_event_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('extraction_activity_streams');
    }
};
