<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create admin user for Peldarg Consulting Limited
        User::updateOrCreate(
            ['email' => 'admin@peldargconsulting.com'],
            [
                'name' => 'Peldarg Admin',
                'company_name' => 'Peldarg Consulting Limited',
                'email' => 'admin@peldargconsulting.com',
                'password' => Hash::make('Admin@12345'),
                'is_admin' => true,
                'status' => 'active',
                'credit_cap' => 1000000,
                'credit_balance' => 0,
                'email_verified_at' => now(),
            ]
        );

        $this->command->info('✅ Admin user created successfully!');
        $this->command->info('   Email: admin@peldargconsulting.com');
        $this->command->info('   Password: Admin@12345');
    }
}
