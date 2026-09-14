<?php

namespace Tests\Feature;

use App\Models\{Group, MediaExport, Subscription, UploadBatch, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\{Hash, Queue, Storage};
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class FullUiAuditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['media.disk' => 'private_local', 'queue.default' => 'sync']);
        Storage::fake('private_local');
        Queue::fake();
    }

    private function owner(string $email = 'ui-audit@test.local'): array
    {
        $user = User::create(['name' => 'Lens Pic', 'email' => $email, 'password' => Hash::make('password'), 'status' => 'active', 'account_type' => 'photographer', 'plan' => 'premium']);
        $group = Group::create(['name' => 'Connected Event', 'creator_id' => $user->id, 'face_recognition_enabled' => true]);
        $group->members()->attach($user->id, ['role' => 'admin', 'membership_status' => 'active', 'access_type' => 'full_access']);

        return [$user, $group];
    }

    public function test_settings_navigation_uses_the_connected_inertia_page(): void
    {
        [$user] = $this->owner();

        $this->actingAs($user)->get(route('settings'))->assertRedirect(route('settings.profile'));

        foreach (['settings.profile', 'settings.business-branding'] as $route) {
            $this->actingAs($user)->get(route($route))->assertOk()->assertInertia(fn (Assert $page) => $page->component('Settings/Index'));
        }
        $this->actingAs($user)->get(route('settings.team'))->assertRedirect(route('settings.team-login'));

        $this->actingAs($user)->get(route('settings.flipbook'))->assertOk()->assertInertia(fn (Assert $page) => $page->component('Settings/Flipbook'));
        $this->actingAs($user)->get(route('settings.watermark'))->assertOk()->assertInertia(fn (Assert $page) => $page->component('Settings/Watermark'));
        $this->actingAs($user)->get(route('settings.portfolio'))->assertOk()->assertInertia(fn (Assert $page) => $page->component('Settings/Portfolio'));

        $this->actingAs($user)->get(route('settings.wallet'))->assertOk()->assertInertia(fn (Assert $page) => $page->component('Settings/Wallet'));

        $this->actingAs($user)->get(route('settings.transactions'))->assertOk()->assertInertia(fn (Assert $page) => $page->component('Settings/Transactions'));

        foreach (['settings.privacy'] as $route) {
            $this->actingAs($user)->get(route($route))->assertOk()->assertInertia(fn (Assert $page) => $page->component('Settings/Utility'));
        }
    }

    public function test_subscription_is_a_complete_authorized_account_scoped_inertia_page(): void
    {
        [$owner] = $this->owner();
        [$other] = $this->owner('other-billing@test.local');
        Subscription::create(['user_id'=>$owner->id,'plan'=>'essential','status'=>'active','billing_cycle'=>'yearly','amount'=>11999,'currency'=>'INR','starts_at'=>now(),'expires_at'=>now()->addYear()]);
        Subscription::create(['user_id'=>$other->id,'plan'=>'premium','status'=>'active','billing_cycle'=>'yearly','amount'=>22999,'currency'=>'INR','starts_at'=>now(),'expires_at'=>now()->addYear()]);

        $this->get(route('settings.subscription'))->assertRedirect(route('login'));
        $this->actingAs($owner)->get(route('settings.subscription'))->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Settings/Subscription')
            ->where('user.plan', $owner->plan)
            ->has('subscriptions', 1)
            ->where('subscriptions.0.plan', 'essential')
            ->has('availablePlans', 4));

        $participant = User::create(['name'=>'Participant','email'=>'participant-billing@test.local','password'=>Hash::make('password'),'status'=>'active','account_type'=>'user']);
        $this->actingAs($participant)->get(route('settings.subscription'))->assertForbidden();
    }

    public function test_main_modules_use_full_page_renderers_and_only_destructive_group_actions_use_modals(): void
    {
        [$owner, $group] = $this->owner();
        $this->actingAs($owner)->get(route('groups.create'))->assertInertia(fn(Assert $page)=>$page->component('Groups/Create'));
        $this->get(route('events.create'))->assertRedirect(route('groups.create'));
        $this->get(route('groups.settings',$group))->assertInertia(fn(Assert $page)=>$page->component('Groups/Settings'));
        $this->get(route('events.settings',$group))->assertRedirect(route('groups.settings',$group));
        $this->get(route('analytics'))->assertInertia(fn(Assert $page)=>$page->component('Analytics'));
        $this->get(route('notifications'))->assertInertia(fn(Assert $page)=>$page->component('Notifications'));

        foreach (['resources/js/Layouts/AppShell.vue','resources/js/Components/SettingsNav.vue'] as $path) {
            $source=file_get_contents(base_path($path));
            foreach (['data-modal','data-popup','data-dialog','data-remote','data-ajax','iframe','@click.prevent'] as $attribute) $this->assertStringNotContainsString($attribute,$source);
        }
        $this->assertStringNotContainsString('UiModal',file_get_contents(resource_path('js/Pages/Groups/Create.vue')));
        $this->assertStringContainsString('UiModal',file_get_contents(resource_path('js/Pages/Groups/Members.vue')));
    }

    public function test_inertia_upload_returns_to_the_group_gallery(): void
    {
        [$user, $group] = $this->owner();

        $this->actingAs($user)->withHeader('X-Inertia', 'true')->post(route('photos.store', $group), [
            'photos' => [UploadedFile::fake()->image('private.jpg')],
        ])->assertStatus(303)->assertRedirect(route('groups.gallery', $group));

        $this->assertDatabaseHas('upload_batches', ['group_id' => $group->id, 'user_id' => $user->id]);
        $this->assertDatabaseCount('upload_batch_files', 1);
    }

    public function test_inertia_processing_and_export_controls_return_to_the_authorized_screen(): void
    {
        [$user, $group] = $this->owner();
        $batch = UploadBatch::create(['uuid' => fake()->uuid(), 'group_id' => $group->id, 'user_id' => $user->id, 'state' => 'queued', 'total_files' => 1, 'pending_files' => 1]);
        $export = MediaExport::create(['uuid' => fake()->uuid(), 'group_id' => $group->id, 'user_id' => $user->id, 'state' => 'queued', 'photo_ids' => [10]]);

        $this->actingAs($user)->from(route('groups.operations', $group))->withHeader('X-Inertia', 'true')->post(route('processing.cancel', $batch))->assertStatus(303)->assertRedirect(route('groups.operations', $group));
        $this->from(route('groups.operations', $group))->withHeader('X-Inertia', 'true')->post(route('exports.cancel', $export))->assertStatus(303)->assertRedirect(route('groups.operations', $group));
        $this->from(route('groups.operations', $group))->withHeader('X-Inertia', 'true')->post(route('biometric.index-start', $group))->assertStatus(303)->assertRedirect(route('groups.operations', $group));

        $this->assertSame('cancelled', $batch->fresh()->state);
        $this->assertSame('cancelled', $export->fresh()->state);
        $this->assertDatabaseHas('event_face_index_runs', ['group_id' => $group->id, 'state' => 'queued']);
    }

    public function test_cross_event_user_cannot_operate_batches_or_exports(): void
    {
        [$owner, $group] = $this->owner();
        [$other] = $this->owner('other-ui-audit@test.local');
        $batch = UploadBatch::create(['uuid' => fake()->uuid(), 'group_id' => $group->id, 'user_id' => $owner->id, 'state' => 'queued', 'total_files' => 1]);
        $export = MediaExport::create(['uuid' => fake()->uuid(), 'group_id' => $group->id, 'user_id' => $owner->id, 'state' => 'queued', 'photo_ids' => [1]]);

        $this->actingAs($other)->post(route('processing.cancel', $batch))->assertForbidden();
        $this->post(route('exports.cancel', $export))->assertForbidden();
        $this->post(route('biometric.index-start', $group))->assertForbidden();
    }
}
