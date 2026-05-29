<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partner_allowlist', function (Blueprint $table) {
            $table->id();
            $table->string('partner_name')->unique();
            $table->string('partner_domain');
            $table->text('allowed_ips')->nullable();
            $table->string('current_secret_key');
            $table->string('current_secret_key_id');
            $table->timestamp('secret_rotated_at');
            $table->timestamp('secret_expires_at')->nullable();
            $table->boolean('active')->default(true);
            $table->text('audit_note')->nullable();
            $table->timestamps();
            $table->index('partner_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partner_allowlist');
    }
};
