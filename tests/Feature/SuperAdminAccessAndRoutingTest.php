<?php

namespace Tests\Feature;

use App\Models\{Group, StudioTeamMembership, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class SuperAdminAccessAndRoutingTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $email, array $attributes = []): User
    {
        return User::create(array_merge([
            'name' => 'Role Test', 'email' => $email, 'password' => Hash::make('password'),
            'role' => 'user', 'account_type' => 'photographer', 'status' => 'active',
            'onboarding_completed_at' => now(), 'plan' => 'basic',
        ], $attributes));
    }

    public function test_password_login_uses_role_aware_landing_routes(): void
    {
        $super = $this->user('super-routing@test.local', ['role' => 'super_admin']);
        $this->post(route('login'), ['email' => $super->email, 'password' => 'password'])->assertRedirect(route('super-admin.dashboard'));
        auth()->logout();
        $photographer = $this->user('photographer-routing@test.local');
        $this->post(route('login'), ['email' => $photographer->email, 'password' => 'password'])->assertRedirect(route('dashboard'));
    }

    public function test_inertia_password_login_navigates_the_browser_for_each_account_type(): void
    {
        $owner = $this->user('navigation-owner@test.local');
        $group = Group::create(['name' => 'Navigation Gallery', 'creator_id' => $owner->id]);
        foreach (['super_admin', 'photographer', 'team', 'member', 'user', 'incomplete'] as $kind) {
            $user = $this->user($kind.'-navigation@test.local', [
                'role' => $kind === 'super_admin' ? 'super_admin' : 'user',
                'account_type' => in_array($kind, ['photographer', 'incomplete']) ? 'photographer' : 'user',
                'onboarding_completed_at' => $kind === 'incomplete' ? null : now(),
            ]);
            if ($kind === 'team') {
                StudioTeamMembership::create(['uuid' => (string) Str::uuid(), 'studio_owner_id' => $owner->id, 'user_id' => $user->id, 'role' => 'viewer', 'status' => 'active']);
            }
            if ($kind === 'member') {
                $group->members()->attach($user->id, ['role' => 'member', 'membership_status' => 'active', 'access_type' => 'full_access']);
            }
            $destination = route(match ($kind) {
                'super_admin' => 'super-admin.dashboard',
                'team', 'member' => 'groups.index',
                'incomplete' => 'onboarding.resume',
                default => 'dashboard',
            });
            $this->withHeader('X-Inertia', 'true')->post(route('login'), ['email' => $user->email, 'password' => 'password'])
                ->assertStatus(409)->assertHeader('X-Inertia-Location', $destination)->assertContent('');
            $this->assertAuthenticatedAs($user);
            $this->flushHeaders();
            $response = $this->get($destination);
            if ($kind === 'incomplete') $response->assertRedirect(route('onboarding.profile'));
            else $response->assertOk();
            auth()->logout();
        }
    }

    public function test_invalid_inertia_login_keeps_validation_errors_without_navigating(): void
    {
        $user = $this->user('invalid-navigation@test.local');
        $this->withHeader('X-Inertia', 'true')->from(route('login'))
            ->post(route('login'), ['email' => $user->email, 'password' => 'incorrect'])
            ->assertRedirect(route('login'))->assertSessionHasErrors('email')->assertHeaderMissing('X-Inertia-Location');
        $this->assertGuest();
    }

    public function test_team_and_group_members_land_in_the_authorized_groups_workspace(): void
    {
        $owner = $this->user('owner-routing@test.local');
        $team = $this->user('team-routing@test.local', ['account_type' => 'user']);
        StudioTeamMembership::create(['uuid' => (string) Str::uuid(), 'studio_owner_id' => $owner->id, 'user_id' => $team->id, 'role' => 'viewer', 'status' => 'active']);
        $this->post(route('login'), ['email' => $team->email, 'password' => 'password'])->assertRedirect(route('groups.index'));
        auth()->logout();
        $member = $this->user('member-routing@test.local', ['account_type' => 'user']);
        $group = Group::create(['name' => 'Member Gallery', 'creator_id' => $owner->id]);
        $group->members()->attach($member->id, ['role' => 'member', 'membership_status' => 'active', 'access_type' => 'full_access']);
        $this->post(route('login'), ['email' => $member->email, 'password' => 'password'])->assertRedirect(route('groups.index'));
    }

    public function test_super_admin_dashboard_and_plan_shell_are_separate_and_protected(): void
    {
        $super = $this->user('super-shell@test.local', ['role' => 'super_admin']);
        $this->actingAs($super)->get('/dashboard')->assertRedirect(route('super-admin.dashboard'));
        $this->get(route('super-admin.dashboard'))->assertOk()->assertSee('LensPic Super Admin')->assertSee('Administrative Overview')->assertDontSee('Create Group');
        $this->get(route('super-admin.plans.index'))->assertOk()->assertSee('Plans')->assertSee('Super Admin / Plans');
        $this->assertSame('/super-admin/plans', parse_url(route('super-admin.plans.index'), PHP_URL_PATH));
        $this->assertStringContainsString('platform.admin:super', implode(',', app('router')->getRoutes()->getByName('super-admin.plans.index')->gatherMiddleware()));
    }

    public function test_non_admin_roles_receive_403_for_plan_management_get_and_write(): void
    {
        foreach (['photographer', 'team', 'group'] as $kind) {
            $user = $this->user($kind.'-denied@test.local', ['account_type' => $kind === 'photographer' ? 'photographer' : 'user']);
            $this->actingAs($user)->get(route('super-admin.plans.index'))->assertForbidden();
            $plan = \App\Models\SubscriptionPlan::where('code', 'basic')->firstOrFail();
            $this->put(route('super-admin.plans.update', $plan), [])->assertForbidden();
        }
    }

    public function test_super_admin_navigation_has_no_placeholder_links_and_mobile_controls_exist(): void
    {
        $super = $this->user('super-nav@test.local', ['role' => 'super_admin']);
        $response = $this->actingAs($super)->get(route('super-admin.dashboard'))->assertOk();
        $response->assertSee('Subscriptions &amp; Billing', false)->assertSee('data-admin-nav-open', false)->assertSee('data-admin-nav-close', false)->assertDontSee('href="#"', false)->assertDontSee('Upload Photos');
    }
}
