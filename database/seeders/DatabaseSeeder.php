<?php

namespace Database\Seeders;

use App\Models\Group;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(SubscriptionCatalogSeeder::class);
        $admin = User::create([
            'name'              => 'Super Admin',
            'email'             => 'admin@lenspic.in',
            'password'          => Hash::make('password'),
            'email_verified_at' => now(),
            'is_admin'          => true,
            'role'              => 'super_admin',
        ]);

        $demo = User::create([
            'name'              => 'Demo User',
            'email'             => 'demo@lenspic.in',
            'password'          => Hash::make('password'),
            'email_verified_at' => now(),
            'is_admin'          => false,
            'role'              => 'photographer',
            'account_type'      => 'photographer',
        ]);

        $groups = [
            ['name' => "Priya & Rahul's Wedding",  'event_type' => 'wedding',  'privacy' => 'link_only', 'face_recognition_enabled' => true],
            ['name' => "Aarav's Birthday Party",   'event_type' => 'birthday', 'privacy' => 'link_only', 'allow_guest_upload' => true],
            ['name' => 'Goa Trip 2024',             'event_type' => 'travel',   'privacy' => 'public',    'allow_guest_upload' => true],
        ];

        foreach ($groups as $data) {
            $group = Group::create(array_merge($data, [
                'creator_id'  => $demo->id,
                'event_date'  => now()->addDays(rand(-30, 60)),
                'description' => 'Share your beautiful memories!',
            ]));
            $group->members()->attach($demo->id, ['role' => 'admin', 'joined_at' => now()]);
        }
    }
}
