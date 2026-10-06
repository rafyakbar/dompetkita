<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::factory()->count(1)->create([
            'name' => 'Shipu Ahamed',
            'email' => 'demo@dinero.app',
            'password' => Hash::make('password'),
        ]);
    }
}
