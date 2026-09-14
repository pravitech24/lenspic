<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SuperAdminNavigationActiveStateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::create(['name' => 'Navigation Admin', 'email' => 'navigation-admin@test.local', 'password' => Hash::make('password'), 'role' => 'super_admin', 'is_admin' => true, 'account_type' => 'user', 'status' => 'active', 'onboarding_completed_at' => now(), 'plan' => 'basic']));
    }

    private function regions(string $route, array $parameters = []): array
    {
        $html = $this->get(route($route, $parameters))->assertOk()->getContent();
        preg_match('/<aside class="sidebar".*?<\/aside>/s', $html, $sidebar);
        preg_match('/<nav[^>]+aria-label="Roles and permissions sections".*?<\/nav>/s', $html, $tabs);
        return [$sidebar[0] ?? '', $tabs[0] ?? '', $html];
    }

    private function assertOnlySidebarDestination(string $sidebar, string $label): void
    {
        $this->assertSame(1, substr_count($sidebar, 'aria-current="page"'), $sidebar);
        $this->assertMatchesRegularExpression('/class="nav-item active"\s+aria-current="page"[^>]*>.*?'.preg_quote($label, '/').'/s', $sidebar);
    }

    private function assertOnlyInternalTab(string $tabs, string $label): void
    {
        $this->assertSame(1, substr_count($tabs, 'aria-current="page"'), $tabs);
        $this->assertMatchesRegularExpression('/class="btn btn-primary btn-sm"\s+aria-current="page"[^>]*>'.preg_quote($label, '/').'<\/a>/', $tabs);
    }

    public function test_user_assignments_selects_only_user_assignments_and_one_internal_tab(): void
    {
        [$sidebar, $tabs] = $this->regions('super-admin.role-assignments.index');
        $this->assertOnlySidebarDestination($sidebar, 'User Assignments');
        $this->assertStringNotContainsString('nav-item active" aria-current="page"><i class="fa-solid fa-user-lock', $sidebar);
        $this->assertOnlyInternalTab($tabs, 'User Assignments');
    }

    public function test_roles_permissions_and_history_select_only_roles_destination_and_correct_tab(): void
    {
        foreach ([['super-admin.roles.index', 'Roles'], ['super-admin.permissions.index', 'Permissions'], ['super-admin.role-history.index', 'Change History']] as [$route, $tab]) {
            [$sidebar, $tabs] = $this->regions($route);
            $this->assertOnlySidebarDestination($sidebar, 'Roles &amp; Permissions');
            $this->assertStringNotContainsString('nav-item active" aria-current="page"><i class="fa-solid fa-user-gear', $sidebar);
            $this->assertOnlyInternalTab($tabs, $tab);
        }
    }

    public function test_plan_feature_payment_and_subscription_destinations_never_overlap(): void
    {
        foreach ([['super-admin.plans.index', 'Plans'], ['super-admin.plans.features', 'Plan Features'], ['super-admin.plans.payments', 'Plan Payments'], ['super-admin.subscriptions', 'Subscriptions']] as [$route, $label]) {
            [$sidebar] = $this->regions($route);
            $this->assertOnlySidebarDestination($sidebar, $label);
        }
    }

    public function test_mobile_drawer_reuses_the_same_single_sidebar_state(): void
    {
        [$sidebar, , $html] = $this->regions('super-admin.role-assignments.index');
        $this->assertOnlySidebarDestination($sidebar, 'User Assignments');
        $this->assertSame(1, substr_count($html, 'id="super-admin-sidebar"'));
        $this->assertStringContainsString('aria-controls="super-admin-sidebar"', $html);
    }
}
