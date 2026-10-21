<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('users')->insert([
            [
                'username' => 'admin',
                'email' => 'admin@admin.com',
                'password' => Hash::make('password123'),
                'phone_number' => null,
                'role' => 'admin',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'username' => 'volttech',
                'email' => 'contact@volttech.com',
                'password' => Hash::make('password123'),
                'phone_number' => '+15550101',
                'role' => 'vendor',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'username' => 'audiopulse',
                'email' => 'support@audiopulse.com',
                'password' => Hash::make('password123'),
                'phone_number' => '+15550102',
                'role' => 'vendor',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'username' => 'cybergear',
                'email' => 'sales@cybergeargaming.com',
                'password' => Hash::make('password123'),
                'phone_number' => '+15550103',
                'role' => 'vendor',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'username' => 'nextgen',
                'email' => 'info@nextgencomponents.com',
                'password' => Hash::make('password123'),
                'phone_number' => '+15550104',
                'role' => 'vendor',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'username' => 'smarthome',
                'email' => 'hello@smarthomeinnovations.com',
                'password' => Hash::make('password123'),
                'phone_number' => '+15550105',
                'role' => 'vendor',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'username' => 'demo_customer',
                'email' => 'customer@example.test',
                'password' => Hash::make('password123'),
                'phone_number' => null,
                'role' => 'customer',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'username' => 'demo_vendor',
                'email' => 'vendor@example.test',
                'password' => Hash::make('password123'),
                'phone_number' => '0900000000',
                'role' => 'vendor',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'username' => 'admin',
                'email' => 'imadmin@mail.com',
                'password' => Hash::make('cj*djX8P@A'),
                'role' => 'admin',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);
    }
}
