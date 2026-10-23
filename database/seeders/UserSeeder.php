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
        $users = [
            [
                'username' => 'admin',
                'email' => 'admin@admin.com',
                'password' => Hash::make('password123'),
                'phone_number' => null,
                'role' => 'admin',
            ],
            [
                'username' => 'volttech',
                'email' => 'contact@volttech.com',
                'password' => Hash::make('password123'),
                'phone_number' => '+15550101',
                'role' => 'customer',
            ],
            [
                'username' => 'audiopulse',
                'email' => 'support@audiopulse.com',
                'password' => Hash::make('password123'),
                'phone_number' => '+15550102',
                'role' => 'customer',
            ],
            [
                'username' => 'cybergear',
                'email' => 'sales@cybergeargaming.com',
                'password' => Hash::make('password123'),
                'phone_number' => '+15550103',
                'role' => 'customer',
            ],
            [
                'username' => 'nextgen',
                'email' => 'info@nextgencomponents.com',
                'password' => Hash::make('password123'),
                'phone_number' => '+15550104',
                'role' => 'customer',
            ],
            [
                'username' => 'smarthome',
                'email' => 'hello@smarthomeinnovations.com',
                'password' => Hash::make('password123'),
                'phone_number' => '+15550105',
                'role' => 'customer',
            ],
            [
                'username' => 'demo_customer',
                'email' => 'customer@example.test',
                'password' => Hash::make('password123'),
                'phone_number' => null,
                'role' => 'customer',
            ],
            [
                'username' => 'demo_vendor',
                'email' => 'vendor@example.test',
                'password' => Hash::make('password123'),
                'phone_number' => '0900000000',
                'role' => 'customer',
            ],
            [
                'username' => 'imadmin',
                'email' => 'imadmin@mail.com',
                'password' => Hash::make('cj*djX8P@A'),
                'phone_number' => null,
                'role' => 'admin',
            ],
        ];

        foreach ($users as $user) {
            DB::table('users')->updateOrInsert(
                ['username' => $user['username']],
                array_merge($user, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }
}
