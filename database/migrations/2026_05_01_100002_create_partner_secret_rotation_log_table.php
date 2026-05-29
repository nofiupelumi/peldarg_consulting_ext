<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partner_secret_rotation_log', function (Blueprint $table) {
            $table->id();
            $table->string('partner_name')->index();
            $table->string('old_key_id');
            $table->string('new_key_id');
            $table->enum('reason', ['scheduled', 'incident', 'manual'])->default('manual');
            $table->integer('rotated_by_user_id')->nullable();
            $table->text('reason_note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partner_secret_rotation_log');
    }
};
