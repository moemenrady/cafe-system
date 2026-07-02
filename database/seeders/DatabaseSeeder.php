<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            CafeSystemSeeder::class, // 🌟 تأكد أن هذا الاسم هو المكتوب لتجنب خطأ السطر 15 السابق
        ]);
    }
}