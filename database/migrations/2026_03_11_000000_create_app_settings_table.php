<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_settings', function (Blueprint $table) {
            $table->id();
            $table->decimal('unit_price_usd', 10, 4)->default(0.0600);
            $table->decimal('fx_rate_ngn', 12, 2)->default(1500.00);
            $table->unsignedInteger('max_upload_mb')->default(100);
            $table->boolean('admin_2fa_required')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_settings');
    }
};
