<?php

namespace Database\Seeders;

use App\Enums\UserType;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Initial Super Admin
        User::firstOrCreate(
            ['email' => 'admin@company.com'],
            [
                'name'        => 'System Admin',
                'password'    => Hash::make('password123'),
                'employee_id' => 'EMP-0001',
                'designation' => 'Super Administrator',
                'role'        => UserType::SUPER_ADMIN,
            ]
        );
    }
}
