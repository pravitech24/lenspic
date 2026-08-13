<?php

namespace Tests\Feature;

use App\Models\{Group, MediaExport, UploadBatch, User};
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
        $user = User::create(['name' => 'Lens Pic', 'email' => $email, 'password' => Hash::make('password'), 'status' => 'active', 'account_type' => 'photographer']);
        $group = Group::create(['name' => 'Connected Event', 'creator_id' => $user->id, 'face_recognition_enabled' => true]);
        $group->members()->attach($user->id, ['role' => 'admin', 'membership_status' => 'active', 'access_type' => 'full_access']);

        return [$user, $group];
    }

    public function test_settings_navigation_uses_the_connected_inertia_page(): void
    {
        [$user] = $this->owner();

        foreach (['settings', 'settings.profile', 'settings.branding', 'settings.watermark', 'settings.team', 'settings.subscription'] as $route) {
            $this->actingAs($user)->get(route($route))->assertOk()->assertInertia(fn (Assert $page) => $page->component('Settings/Index'));
        }
    }

    public function test_inertia_upload_redirects_to_the_durable_batch_screen(): void
    {
        [$user, $group] = $this->owner();

        $this->actingAs($user)->withHeader('X-Inertia', 'true')->post(route('photos.store', $group), [
            'photos' => [UploadedFile::fake()->image('private.jpg')],
        ])->assertStatus(303)->assertRedirect(route('groups.operations', $group));

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
