<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('extraction_activity_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stream_id')->constrained('extraction_activity_streams')->cascadeOnDelete();
            $table->uuid('partner_request_id');
            $table->string('event_key');
            $table->unsignedBigInteger('sequence');
            $table->string('status')->nullable();
            $table->string('phase')->nullable();
            $table->string('run_id')->nullable();
            $table->unsignedBigInteger('doc_id')->nullable();
            $table->timestamp('event_at')->nullable();
            $table->string('dedupe_key')->unique();
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->unique(['stream_id', 'sequence']);
            $table->index(['partner_request_id']);
            $table->index(['event_key']);
            $table->index(['status']);
            $table->index(['phase']);
            $table->index(['event_at']);
            $table->index(['created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('extraction_activity_events');
    }
};
