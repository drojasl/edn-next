<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Entrepreneur;

class EntrepreneurSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create test entrepreneurs
        Entrepreneur::create([
            'codigo_amway' => '12345',
            'is_account_holder' => true,
            'name' => 'Juan Pérez (Account Holder)',
            'password' => 'password123', // Will be hashed automatically
        ]);

        Entrepreneur::create([
            'codigo_amway' => '12345',
            'is_account_holder' => false,
            'name' => 'María Pérez (Co-Holder)',
            'password' => 'password123',
        ]);

        Entrepreneur::create([
            'codigo_amway' => '67890',
            'is_account_holder' => true,
            'name' => 'Carlos González (Account Holder)',
            'password' => 'password123',
        ]);
    }
}
