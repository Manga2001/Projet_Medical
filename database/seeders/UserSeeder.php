<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
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
                'first_name' => 'Admin',
                'last_name' => 'Principal',
                'email' => 'admin@medical-app.com',
                'password' => Hash::make('password123'),
                'phone' => '77 123 45 67',
                'date_of_birth' => '1985-01-15',
                'gender' => 'male',
                'address' => 'Dakar, Sénégal',
                'role' => 'admin',
                'is_active' => true,
                'email_verified_at' => now()
            ],
            [
                'first_name' => 'Fatou',
                'last_name' => 'Diop',
                'email' => 'fatou.admin@medical-app.com',
                'password' => Hash::make('password123'),
                'phone' => '77 987 65 43',
                'date_of_birth' => '1990-05-20',
                'gender' => 'female',
                'address' => 'Thiès, Sénégal',
                'role' => 'admin',
                'is_active' => true,
                'email_verified_at' => now()
            ]
        ];

        foreach ($users as $user) {
            User::create($user);
        }
    }
}