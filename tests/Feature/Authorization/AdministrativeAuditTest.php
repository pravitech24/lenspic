<?php

namespace Tests\Feature\Authorization;

use App\Models\AuditLog;
use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdministrativeAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_participant_removal_is_audited(): void
    {
        $owner = User::create(['name'=>'Owner','email'=>'owner@example.com','password'=>bcrypt('password'),'account_type'=>'photographer']);
        $member = User::create(['name'=>'Member','email'=>'member@example.com','password'=>bcrypt('password')]);
        $group = Group::create(['name'=>'Event','creator_id'=>$owner->id]);
        $group->members()->attach($member->id, ['role'=>'member','membership_status'=>'active','access_type'=>'full_access']);

        $this->actingAs($owner)->delete(route('groups.members.remove', [$group, $member]))->assertRedirect();

        $this->assertDatabaseHas('audit_logs', [
            'action'=>'group.member_removed', 'actor_id'=>$owner->id,
            'group_id'=>$group->id, 'subject_id'=>$member->id,
        ]);
    }

    public function test_successful_platform_admin_mutation_is_audited(): void
    {
        $admin = User::create(['name'=>'Admin','email'=>'admin@example.com','password'=>bcrypt('password'),'role'=>'super_admin']);
        $target = User::create(['name'=>'Target','email'=>'target@example.com','password'=>bcrypt('password'),'role'=>'user']);

        $this->actingAs($admin)->post(route('super-admin.users.suspend', $target))->assertRedirect();

        $this->assertTrue(AuditLog::where('action', 'admin.super-admin.users.suspend')->where('actor_id', $admin->id)->exists());
    }
}
