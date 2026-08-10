<?php

namespace Tests\Feature\Authorization;

use App\Models\Group;
use App\Models\Photo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class PhaseOneAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private function user(array $overrides = []): User
    {
        return User::create(array_merge([
            'name' => 'Test User', 'email' => uniqid().'@example.com',
            'password' => bcrypt('password'), 'status' => 'active',
            'onboarding_step' => 'completed', 'onboarding_completed_at' => now(),
        ], $overrides));
    }

    private function group(User $owner, array $overrides = []): Group
    {
        return Group::create(array_merge([
            'name' => 'Private Event', 'creator_id' => $owner->id,
            'privacy' => 'link_only', 'anonymous_access_mode' => 'disabled',
        ], $overrides));
    }

    public function test_owner_admin_full_and_partial_permissions_are_centralized(): void
    {
        $owner = $this->user(['account_type' => 'photographer']);
        $admin = $this->user(); $full = $this->user(); $partial = $this->user();
        $group = $this->group($owner);
        $group->members()->attach($admin->id, ['role'=>'admin','membership_status'=>'active','access_type'=>'full_access']);
        $group->members()->attach($full->id, ['role'=>'member','membership_status'=>'active','access_type'=>'full_access']);
        $group->members()->attach($partial->id, ['role'=>'member','membership_status'=>'active','access_type'=>'partial_access']);

        $this->assertTrue(Gate::forUser($owner)->allows('update', $group));
        $this->assertTrue(Gate::forUser($admin)->allows('manageMembers', $group));
        $this->assertTrue(Gate::forUser($full)->allows('viewFullGallery', $group));
        $this->assertFalse(Gate::forUser($full)->allows('update', $group));
        $this->assertFalse(Gate::forUser($partial)->allows('viewFullGallery', $group));
    }

    public function test_suspended_user_is_denied_even_with_admin_membership(): void
    {
        $owner = $this->user(['account_type'=>'photographer']);
        $suspended = $this->user(['status'=>'suspended']);
        $group = $this->group($owner);
        $group->members()->attach($suspended->id, ['role'=>'admin','membership_status'=>'active','access_type'=>'full_access']);
        $this->assertFalse(Gate::forUser($suspended)->allows('update', $group));
    }

    public function test_guest_share_link_requires_explicit_anonymous_full_access(): void
    {
        $group = $this->group($this->user());
        $this->get(route('guest.group', $group->share_token))->assertNotFound();
        $group->update(['anonymous_access_mode'=>'full']);
        $this->get(route('guest.group', $group->share_token))->assertOk();
        $group->update(['is_active'=>false]);
        $this->get(route('guest.group', $group->share_token))->assertNotFound();
    }

    public function test_photo_from_another_group_cannot_be_accessed_through_route_substitution(): void
    {
        $user = $this->user(); $owner = $this->user(['account_type'=>'photographer']);
        $first = $this->group($owner); $second = $this->group($owner);
        $first->members()->attach($user->id, ['role'=>'member','membership_status'=>'active','access_type'=>'full_access']);
        $photo = Photo::create(['group_id'=>$second->id,'filename'=>'x.jpg','original_filename'=>'x.jpg','path'=>'photos/x.jpg']);
        $this->actingAs($user)->get(route('photos.show', [$first, $photo]))->assertNotFound();
    }

    public function test_non_platform_admin_cannot_open_admin_surfaces(): void
    {
        $this->actingAs($this->user())->get(route('admin.dashboard'))->assertForbidden();
        $this->actingAs($this->user())->get(route('super-admin.dashboard'))->assertForbidden();
    }
}
