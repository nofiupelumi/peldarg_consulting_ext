<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('company_name')->nullable()->after('name');
            $table->boolean('is_admin')->default(false)->after('password');
            $table->enum('status', ['active', 'suspended'])->default('active')->after('is_admin');
            $table->unsignedBigInteger('credit_balance')->default(0)->after('status');
            $table->unsignedBigInteger('credit_cap')->default(0)->after('credit_balance');
            $table->boolean('must_change_password')->default(false)->after('credit_cap');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'company_name',
                'is_admin',
                'status',
                'credit_balance',
                'credit_cap',
                'must_change_password',
            ]);
        });
    }
};
