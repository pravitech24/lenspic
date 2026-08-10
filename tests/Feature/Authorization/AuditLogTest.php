<?php

namespace Tests\Feature\Authorization;

use App\Models\AuditLog;
use App\Models\Group;
use App\Models\GroupAccessInvite;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_sensitive_invitation_change_is_written_to_general_audit_log(): void
    {
        $owner = User::create(['name'=>'Owner','email'=>'owner@example.com','password'=>bcrypt('password'),'status'=>'active','account_type'=>'photographer']);
        $group = Group::create(['name'=>'Event','creator_id'=>$owner->id]);
        $invite = GroupAccessInvite::makeFor($group, GroupAccessInvite::FULL, $owner->id);

        $this->actingAs($owner)->post(route('groups.access-invites.revoke', [$group, $invite]))->assertRedirect();

        $log = AuditLog::where('action', 'invitation.revoked')->firstOrFail();
        $this->assertSame($owner->id, $log->actor_id);
        $this->assertSame($group->id, $log->group_id);
        $this->assertSame($invite->id, $log->subject_id);
    }
}
