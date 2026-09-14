<?php

namespace Tests\Feature;

use App\Models\{AuditLog, Group, StudioTeamMembership, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SuperAdminRoleAssignmentTest extends TestCase
{
    use RefreshDatabase;
    private function user(string $email, array $extra = []): User { return User::create(array_merge(['name' => 'Assignment User', 'email' => $email, 'password' => Hash::make('password'), 'role' => 'user', 'account_type' => 'user', 'status' => 'active', 'onboarding_completed_at' => now(), 'plan' => 'basic'], $extra)); }
    private function payload(string $role, array $extra = []): array { return array_merge(['assignment_role' => $role, 'group_access_type' => 'full_access', 'status' => 'active', 'reason' => 'Approved operational role change', 'confirm_role_change' => '1'], $extra); }

    public function test_super_admin_can_search_filter_and_open_assignments_but_other_roles_are_forbidden(): void
    {
        $admin = $this->user('role-admin@test.local', ['role' => 'super_admin', 'is_admin' => true]);
        $target = $this->user('needle-role@test.local', ['name' => 'Needle Person', 'account_type' => 'photographer']);
        $this->actingAs($admin)->get(route('super-admin.role-assignments.index', ['search' => 'Needle']))->assertOk()->assertSee($target->email)->assertDontSee('role-admin@test.local');
        $this->get(route('super-admin.role-assignments.index', ['role' => 'photographer']))->assertOk()->assertSee($target->email);
        foreach (['photographer', 'team', 'group'] as $type) $this->actingAs($this->user("{$type}-role-denied@test.local", ['account_type' => $type === 'photographer' ? 'photographer' : 'user']))->get(route('super-admin.role-assignments.index'))->assertForbidden();
    }

    public function test_photographer_team_and_group_assignments_use_existing_scoped_architecture(): void
    {
        $admin = $this->user('scope-admin@test.local', ['role' => 'super_admin', 'is_admin' => true]);
        $target = $this->user('scope-target@test.local');
        $this->actingAs($admin)->put(route('super-admin.role-assignments.update', $target), $this->payload('photographer'))->assertSessionHasNoErrors();
        $this->assertSame('photographer', $target->fresh()->account_type);
        $owner = $this->user('scope-owner@test.local', ['account_type' => 'photographer']);
        $this->put(route('super-admin.role-assignments.update', $target), $this->payload('team_member', ['studio_owner_id' => $owner->id, 'permissions' => ['view_groups', 'roles.assign']]))->assertSessionHasErrors('permissions.1');
        $this->put(route('super-admin.role-assignments.update', $target), $this->payload('team_member', ['studio_owner_id' => $owner->id, 'permissions' => ['view_groups', 'upload_photos']]))->assertSessionHasNoErrors();
        $membership = StudioTeamMembership::where('user_id', $target->id)->firstOrFail();
        $this->assertSame($owner->id, $membership->studio_owner_id); $this->assertSame(['view_groups', 'upload_photos'], $membership->permissions);
        $this->put(route('super-admin.role-assignments.update', $target), $this->payload('team_member'))->assertSessionHasErrors('studio_owner_id');
        $group = Group::create(['name' => 'Scoped Gallery', 'creator_id' => $owner->id]);
        $this->put(route('super-admin.role-assignments.update', $target), $this->payload('group_member', ['group_ids' => [$group->id], 'group_access_type' => 'partial_access']))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('group_members', ['user_id' => $target->id, 'group_id' => $group->id, 'role' => 'member', 'access_type' => 'partial_access']);
        $this->assertSame('suspended', $membership->fresh()->status);
    }

    public function test_super_admin_assignment_requires_warning_password_reason_and_cannot_be_self_assigned(): void
    {
        $admin = $this->user('privileged-admin@test.local', ['role' => 'super_admin', 'is_admin' => true]);
        $target = $this->user('privileged-target@test.local');
        $this->actingAs($admin)->put(route('super-admin.role-assignments.update', $target), $this->payload('super_admin'))->assertSessionHasErrors('confirm_super_admin');
        $this->put(route('super-admin.role-assignments.update', $target), $this->payload('super_admin', ['confirm_super_admin' => 1, 'password' => 'wrong']))->assertSessionHasErrors('password');
        $this->put(route('super-admin.role-assignments.update', $target), $this->payload('super_admin', ['confirm_super_admin' => 1, 'password' => 'password']))->assertSessionHasNoErrors();
        $this->assertTrue($target->fresh()->isSuperAdmin());
        $this->assertDatabaseHas('audit_logs', ['action' => 'roles.assignment.changed', 'subject_id' => $target->id, 'actor_id' => $admin->id]);
        $otherAdmin = $target->fresh();
        $candidate = $this->user('self-candidate@test.local');
        $this->actingAs($candidate)->put(route('super-admin.role-assignments.update', $candidate), $this->payload('super_admin', ['confirm_super_admin' => 1, 'password' => 'password']))->assertForbidden();
    }

    public function test_last_active_super_admin_cannot_be_reassigned(): void
    {
        $admin = $this->user('last-admin@test.local', ['role' => 'super_admin', 'is_admin' => true]);
        $this->actingAs($admin)->put(route('super-admin.role-assignments.update', $admin), $this->payload('photographer'))->assertSessionHasErrors('assignment_role');
        $this->assertTrue($admin->fresh()->isSuperAdmin());
    }

    public function test_initial_super_admin_command_requires_verified_existing_user_and_is_idempotent(): void
    {
        $user = $this->user('bootstrap-admin@test.local', ['email_verified_at' => now()]);
        $this->artisan('lenspic:assign-super-admin', ['email' => $user->email, '--force' => true])->assertSuccessful();
        $this->assertTrue($user->fresh()->isSuperAdmin());
        $this->assertDatabaseHas('audit_logs', ['action' => 'roles.initial_super_admin_assigned', 'subject_id' => $user->id]);
        $count = AuditLog::where('action', 'roles.initial_super_admin_assigned')->count();
        $this->artisan('lenspic:assign-super-admin', ['email' => $user->email, '--force' => true])->assertSuccessful();
        $this->assertSame($count, AuditLog::where('action', 'roles.initial_super_admin_assigned')->count());
        $unverified = $this->user('unverified-admin@test.local');
        $this->artisan('lenspic:assign-super-admin', ['email' => $unverified->email, '--force' => true])->assertFailed();
    }

    public function test_super_admin_can_create_user_with_scoped_initial_role_without_default_password(): void
    {
        $admin = $this->user('create-role-admin@test.local', ['role' => 'super_admin', 'is_admin' => true]);
        $owner = $this->user('create-role-owner@test.local', ['account_type' => 'photographer']);
        $this->actingAs($admin)->post(route('super-admin.role-assignments.store'), $this->payload('team_member', ['name' => 'Created Team User', 'email' => 'created-team@test.local', 'phone' => '+919876543210', 'studio_owner_id' => $owner->id, 'permissions' => ['view_groups']]))->assertSessionHasNoErrors();
        $created = User::where('email', 'created-team@test.local')->firstOrFail();
        $this->assertFalse(Hash::check('password', $created->password));
        $this->assertDatabaseHas('studio_team_memberships', ['user_id' => $created->id, 'studio_owner_id' => $owner->id, 'status' => 'active']);
    }
}
