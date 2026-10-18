<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // 1. حساب المدير العام (Admin)
        User::firstOrCreate(
            ['email' => 'admin@cafe.com'],
            [
                'name'              => 'المدير العام',
                'password'          => Hash::make('password'),
                'role'              => 'admin',
                'is_active'         => true,
                'email_verified_at' => now(),
            ]
        );

        // 2. حساب المشرف (Supervisor)
        User::firstOrCreate(
            ['email' => 'supervisor@cafe.com'],
            [
                'name'              => 'المشرف أحمد',
                'password'          => Hash::make('password'),
                'role'              => 'supervisor',
                'is_active'         => true,
                'email_verified_at' => now(),
            ]
        );

        // 3. حساب الكاشير (Cashier)
        User::firstOrCreate(
            ['email' => 'cashier@cafe.com'],
            [
                'name'              => 'كاشير الويتر محمد',
                'password'          => Hash::make('password'),
                'role'              => 'cashier',
                'is_active'         => true,
                'email_verified_at' => now(),
            ]
        );

        // 4. حساب الباريستا (Barista)
        User::firstOrCreate(
            ['email' => 'barista@cafe.com'],
            [
                'name'              => 'باريستا صانع القهوة',
                'password'          => Hash::make('password'),
                'role'              => 'barista',
                'is_active'         => true,
                'email_verified_at' => now(),
            ]
        );
    }
}