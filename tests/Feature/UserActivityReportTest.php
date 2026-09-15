<?php

namespace Tests\Feature;

use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserActivityReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_counts_only_users_seen_in_the_last_thirty_days(): void
    {
        $this->travelTo(now()->startOfSecond());

        foreach ([null, now()->subDays(31), now()->subDays(30), now()] as $seenAt) {
            $user = User::create(['name' => 'Report user', 'password' => 'unused']);
            $user->forceFill(['last_seen_at' => $seenAt])->save();
        }

        $report = Report::generateUserActivityReport();
        $this->assertSame(4, $report['total_users']);
        $this->assertSame(2, $report['active_users']);
    }

    public function test_reports_page_tracks_activity_and_throttles_updates(): void
    {
        $this->travelTo(now()->startOfSecond());
        $user = User::create(['name' => 'Admin', 'password' => 'unused', 'role' => 'super_admin']);
        $updatedAt = $user->updated_at;

        $this->actingAs($user)->get(route('super-admin.reports'))->assertOk();
        $seenAt = $user->fresh()->last_seen_at;
        $this->assertTrue($seenAt->equalTo(now()));

        $this->travel(1)->minutes();
        $this->get(route('super-admin.reports'))->assertOk();
        $this->assertTrue($user->fresh()->last_seen_at->equalTo($seenAt));

        $this->travel(5)->minutes();
        $this->get(route('super-admin.reports'))->assertOk();
        $this->assertTrue($user->fresh()->last_seen_at->equalTo(now()));
        $this->assertTrue($user->fresh()->updated_at->equalTo($updatedAt));
    }

    public function test_guests_can_visit_the_login_page(): void
    {
        $this->get(route('login'))->assertOk();
        $this->assertSame(0, User::count());
    }
}
