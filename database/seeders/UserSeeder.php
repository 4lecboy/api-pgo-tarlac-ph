<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Enums\UserRole;
use App\Enums\Department;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $password = Hash::make('password'); // Default password for all seeded users

        foreach (Department::cases() as $dept) {
            $deptName = $dept->value;
            $slug = Str::slug($deptName);

            // 1. Super Admin (IT Department only)
            if ($dept === Department::IT) {
                User::firstOrCreate(
                    ['email' => 'tech@pgo.gov'], // Consistent email for IT
                    [
                        'first_name' => 'IT',
                        'last_name' => 'Support',
                        'role' => UserRole::SUPER_ADMIN,
                        'department' => $deptName,
                        'password' => $password,
                        'status' => 'active',
                        'position' => 'System Administrator'
                    ]
                );
                continue; 
            }

            // 2. Department Admin
            User::firstOrCreate(
                ['email' => "admin.{$slug}@pgo.gov"],
                [
                    'first_name' => 'Admin',
                    'last_name' => $deptName,
                    'role' => UserRole::ADMIN,
                    'department' => $deptName,
                    'password' => $password,
                    'status' => 'active',
                    'position' => 'Department Head'
                ]
            );

            // 3. Department User
            User::firstOrCreate(
                ['email' => "user.{$slug}@pgo.gov"],
                [
                    'first_name' => 'Staff',
                    'last_name' => $deptName,
                    'role' => UserRole::USER,
                    'department' => $deptName,
                    'password' => $password,
                    'status' => 'active',
                    'position' => 'Staff'
                ]
            );
        }
    }
}
