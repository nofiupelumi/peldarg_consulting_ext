<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained('users')->nullOnDelete();
            $table->uuid('request_id')->nullable()->after('user_id');
            $table->unsignedInteger('page_start')->nullable()->after('request_id');
            $table->unsignedInteger('page_end')->nullable()->after('page_start');
            $table->unsignedInteger('pages_requested')->nullable()->after('page_end');
            $table->integer('pages_processed')->nullable()->after('pages_requested');
            $table->integer('pages_with_results')->nullable()->after('pages_processed');
            $table->unsignedBigInteger('credits_reserved')->default(0)->after('pages_with_results');
            $table->unsignedBigInteger('credits_consumed')->default(0)->after('credits_reserved');
            $table->unsignedBigInteger('credits_refunded')->default(0)->after('credits_consumed');
            $table->enum('credit_status', ['none', 'reserved', 'finalized', 'failed'])->default('none')->after('credits_refunded');
            $table->text('failed_reason')->nullable()->after('credit_status');
        });

        Schema::table('documents', function (Blueprint $table) {
            $table->unique('request_id');
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
            $table->dropColumn([
                'request_id',
                'page_start',
                'page_end',
                'pages_requested',
                'pages_processed',
                'pages_with_results',
                'credits_reserved',
                'credits_consumed',
                'credits_refunded',
                'credit_status',
                'failed_reason',
            ]);
        });
    }
};
