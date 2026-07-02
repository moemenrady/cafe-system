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
        User::create([
            'name' => 'المدير العام',
            'email' => 'admin@cafe.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'is_active' => true,
            'email_verified_at' => now(), // 🌟 أضف هذا السطر هنا
        ]);

        // 2. حساب المشرف (Supervisor)
        User::create([
            'name' => 'المشرف أحمد',
            'email' => 'supervisor@cafe.com',
            'password' => Hash::make('password'),
            'role' => 'supervisor',
            'is_active' => true,
            'email_verified_at' => now(), // 🌟 وهذا السطر هنا
        ]);

        // 3. حساب الكاشير (Cashier)
        User::create([
            'name' => 'كاشير الويتر محمد',
            'email' => 'cashier@cafe.com',
            'password' => Hash::make('password'),
            'role' => 'cashier',
            'is_active' => true,
            'email_verified_at' => now(), // 🌟 وهذا السطر هنا
        ]);

        // 4. حساب الباريستا (Barista)
        User::create([
            'name' => 'باريستا صانع القهوة',
            'email' => 'barista@cafe.com',
            'password' => Hash::make('password'),
            'role' => 'barista',
            'is_active' => true,
            'email_verified_at' => now(), // 🌟 وهذا السطر هنا
        ]);
    }
}