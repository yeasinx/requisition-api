<?php

namespace Database\Seeders;

use App\Enums\UserType;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {

        $email = config('seeding.admin_email');
        $password = config('seeding.admin_password');

        if (! $email || ! $password) {
            throw new RuntimeException('SEED_ADMIN_EMAIL and SEED_ADMIN_PASSWORD environment variables must be set.');
        }

        // 1. Initial Super Admin
        User::firstOrCreate(
            ['email' => $email],
            [
                'name' => 'System Admin',
                'password' => Hash::make($password),
                'employee_id' => 'EMP-0001',
                'designation' => 'Super Administrator',
                'role' => UserType::SUPER_ADMIN,
            ]
        );
    }
}
