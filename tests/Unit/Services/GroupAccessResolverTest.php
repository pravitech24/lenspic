<?php

namespace Tests\Unit\Services;

use App\Models\Group;
use App\Models\User;
use App\Services\GroupAccessResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GroupAccessResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_removed_member_is_denied_and_anonymous_modes_are_explicit(): void
    {
        $owner = User::create(['name'=>'Owner','email'=>'o@example.com','password'=>bcrypt('password'),'status'=>'active']);
        $member = User::create(['name'=>'Member','email'=>'m@example.com','password'=>bcrypt('password'),'status'=>'active']);
        $group = Group::create(['name'=>'Event','creator_id'=>$owner->id,'anonymous_access_mode'=>'disabled']);
        $group->members()->attach($member->id, ['role'=>'member','membership_status'=>'removed','access_type'=>'full_access']);
        $resolver = app(GroupAccessResolver::class);
        $this->assertSame('denied', $resolver->role($group, $member));
        $this->assertSame('denied', $resolver->role($group, null));
        $group->update(['anonymous_access_mode'=>'face_only']);
        $this->assertSame('anonymous_face_only', $resolver->role($group->fresh(), null));
    }
}
