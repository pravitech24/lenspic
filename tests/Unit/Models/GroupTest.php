<?php

namespace Tests\Unit\Models;

use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class GroupTest extends TestCase
{
    use RefreshDatabase;

    public function test_group_generates_share_token_and_labels_event_type(): void
    {
        $creator = User::create([
            'name' => 'Creator User',
            'email' => 'creator@example.com',
            'phone' => '5554445555',
            'password' => Hash::make('password'),
        ]);

        $group = Group::create([
            'name' => 'Summer Trip',
            'description' => 'A weekend away',
            'event_type' => 'travel',
            'creator_id' => $creator->id,
            'privacy' => 'public',
        ]);

        $this->assertNotEmpty($group->share_token);
        $this->assertSame(route('guest.group', $group->share_token), $group->share_url);
        $this->assertSame('✈️ Travel', $group->getEventTypeLabel());
        $this->assertTrue($group->isAdmin($creator));
        $this->assertFalse($group->isMember($creator));
    }

    public function test_group_member_helpers_and_token_regeneration_work(): void
    {
        $creator = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'phone' => '5554446666',
            'password' => Hash::make('password'),
        ]);

        $member = User::create([
            'name' => 'Member User',
            'email' => 'member@example.com',
            'phone' => '5554447777',
            'password' => Hash::make('password'),
        ]);

        $group = Group::create([
            'name' => 'Conference',
            'event_type' => 'corporate',
            'creator_id' => $creator->id,
            'privacy' => 'link_only',
        ]);

        $group->members()->attach($member->id, [
            'role' => 'member',
            'joined_at' => now(),
        ]);

        $originalToken = $group->share_token;

        $this->assertTrue($group->isMember($member));
        $this->assertFalse($group->isAdmin($member));

        $group->regenerateToken();

        $this->assertNotSame($originalToken, $group->fresh()->share_token);
    }
}