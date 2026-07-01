<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserTableSeeder extends Seeder
{
  /**
   * Run the database seeds.
   */
  public function run(): void
  {
    /*
        |--------------------------------------------------------------------------
        | Admin
        |--------------------------------------------------------------------------
        */

    User::create([
      'name' => 'Admin',
      'email' => 'admin@gmail.com',
      'password' => 'Qqwwee332211',
      'role' => 'admin',

    ]);

    /*
        |--------------------------------------------------------------------------
        | Supervisor
        |--------------------------------------------------------------------------
        */

    User::create([
      'name' => 'Supervisor',
      'email' => 'supervisor@gmail.com',
      'password' => 'Qqwwee332211',
      'role' => 'supervisor',

    ]);
  }
}
