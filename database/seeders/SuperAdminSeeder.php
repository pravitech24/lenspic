<?php
namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'superadmin@pravitech.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('SuperAdmin@123'),
                'email_verified_at' => now(),
                'phone_verified_at' => now(),
                'role' => 'super_admin',
                'is_admin' => true,
                'plan' => 'enterprise',
                'plan_expires_at' => now()->addYears(1),
            ]
        );
    }
}
