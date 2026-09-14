<?php

namespace Tests\Feature;

use App\Jobs\{DeleteMediaAsset, ProcessGroupCover};
use App\Models\{Group, MediaAsset, User};
use App\Services\Media\GroupCoverProcessor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\{Hash, Queue, Storage};
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class GroupSettingsCoverFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['media.disk' => 'private_local', 'media.delivery_driver' => 'local']);
        Storage::fake('private_local');
        Storage::fake('public');
        $this->withoutMiddleware(ThrottleRequests::class);
    }

    public function test_ui_shaped_settings_payload_persists_and_keeps_existing_cover(): void
    {
        Queue::fake();
        $owner = $this->user('owner@example.com');
        $group = $this->group($owner);
        $cover = $this->uploadAndProcess($owner, $group, 'current.jpg');

        $this->actingAs($owner)->post(route('groups.update', $group), [
            '_method' => 'PUT',
            'name' => 'Updated Wedding',
            'description' => 'A persisted settings description.',
            'event_type' => 'wedding',
            'event_date' => null,
            'location' => 'Mumbai',
            'membership_status' => 'open',
            'membership_limit' => null,
            'is_active' => '1',
        ])->assertRedirect()->assertSessionHas('success', 'Group settings updated successfully.');

        $group->refresh();
        $this->assertSame('Updated Wedding', $group->name);
        $this->assertSame('A persisted settings description.', $group->description);
        $this->assertSame($cover->id, $group->cover_media_asset_id);
        $this->assertDatabaseHas('media_assets', ['id' => $cover->id, 'deleted_at' => null]);
    }

    public function test_settings_update_accepts_multipart_cover_and_preserves_old_cover_until_success(): void
    {
        Queue::fake();
        $owner = $this->user('owner@example.com');
        $group = $this->group($owner);
        $old = $this->uploadAndProcess($owner, $group, 'old.jpg');

        $this->actingAs($owner)->post(route('groups.update', $group), [
            '_method' => 'PUT',
            'name' => 'Cover Replacement',
            'cover_photo' => UploadedFile::fake()->image('replacement.png', 1200, 700),
        ])->assertRedirect()->assertSessionHasNoErrors();

        $group->refresh();
        $new = $group->pendingCoverMediaAsset()->firstOrFail();
        $this->assertSame($old->id, $group->cover_media_asset_id);
        $this->assertNotSame($old->id, $new->id);
        $this->assertStringStartsWith('media/covers/groups/'.$group->id.'/originals/', $new->original_object_key);
        $this->assertStringNotContainsString('replacement', $new->original_object_key);
        Queue::assertPushed(ProcessGroupCover::class, fn ($job) => $job->assetId === $new->id);

        app(GroupCoverProcessor::class)->process($new);
        $group->refresh();
        $this->assertSame($new->id, $group->cover_media_asset_id);
        $this->assertNull($group->pending_cover_media_asset_id);
        Queue::assertPushed(DeleteMediaAsset::class, fn ($job) => $job->assetId === $old->id);

        foreach ([route('groups.settings', $group), route('groups.show', $group), route('groups.index')] as $url) {
            $this->actingAs($owner)->get($url)->assertOk();
        }
        $this->actingAs($owner)->get(route('groups.settings', $group))->assertInertia(fn (Assert $page) => $page
            ->component('Groups/Settings')
            ->where('cover.asset_uuid', $new->uuid)
            ->where('cover.url', route('media.show', [$new->uuid, 'cover_large']))
        );
    }

    public function test_failed_replacement_keeps_current_cover_and_reports_safe_state(): void
    {
        Queue::fake();
        $owner = $this->user('owner@example.com');
        $group = $this->group($owner);
        $old = $this->uploadAndProcess($owner, $group, 'old.jpg');
        $this->actingAs($owner)->post(route('groups.cover.store', $group), [
            'cover_photo' => UploadedFile::fake()->image('bad-after-upload.jpg', 1200, 700),
        ])->assertSessionHasNoErrors();
        $replacement = $group->fresh()->pendingCoverMediaAsset;
        Storage::disk('private_local')->put($replacement->original_object_key, 'broken bytes');

        try {
            (new ProcessGroupCover($replacement->id))->handle(app(GroupCoverProcessor::class));
        } catch (\Throwable) {
            // The queue will retry; the previous completed cover must remain linked.
        }

        $group->refresh();
        $this->assertSame($old->id, $group->cover_media_asset_id);
        $this->assertSame($replacement->id, $group->pending_cover_media_asset_id);
        $this->assertSame('failed', $replacement->fresh()->state);
        $this->actingAs($owner)->get(route('groups.settings', $group))->assertInertia(fn (Assert $page) => $page
            ->where('cover.asset_uuid', $old->uuid)
            ->where('cover.state', 'failed')
            ->where('cover.error', 'Cover processing failed. Retry or choose another image.')
        );
    }

    public function test_settings_authorization_validation_and_full_page_cover_heights(): void
    {
        $owner = $this->user('owner@example.com');
        $outsider = $this->user('outsider@example.com');
        $group = $this->group($owner);

        $this->actingAs($outsider)->put(route('groups.update', $group), ['name' => 'Stolen'])->assertForbidden();
        $this->actingAs($owner)->from(route('groups.settings', $group))->put(route('groups.update', $group), [
            'name' => '',
            'description' => str_repeat('x', 2001),
        ])->assertRedirect(route('groups.settings', $group))->assertSessionHasErrors(['name', 'description']);
        $this->get(route('groups.settings', $group))->assertInertia(fn (Assert $page) => $page
            ->component('Groups/Settings')
            ->where('group.id', $group->id)
        );

        $settings = file_get_contents(resource_path('js/Pages/Groups/Settings.vue'));
        $index = file_get_contents(resource_path('js/Pages/Groups/Index.vue'));
        $show = file_get_contents(resource_path('js/Pages/Groups/Show.vue'));
        $this->assertStringContainsString('<AppShell>', $settings);
        $this->assertStringNotContainsString('<iframe', $settings);
        $this->assertStringContainsString('h-[140px]', $settings);
        $this->assertStringContainsString('md:h-[180px]', $settings);
        $this->assertStringContainsString('h-[130px]', $index);
        $this->assertStringContainsString('md:h-[140px]', $index);
        $this->assertStringContainsString('xl:h-[160px]', $index);
        $this->assertStringContainsString('Album by', $show);
        $this->assertStringContainsString('columns-1', $show);
        $this->assertStringContainsString('xl:columns-5', $show);
        $this->assertStringContainsString('aspectRatio', $show);
        $this->assertStringContainsString('forceFormData:true', $settings);
        $this->assertStringContainsString("['_method','PUT']", $settings);
        $this->assertStringContainsString('function chooseCover(e){const file=e.target.files?.[0]', $settings);
        $this->assertStringNotContainsString('function chooseCover(e){clearCover()', $settings);
    }

    private function uploadAndProcess(User $owner, Group $group, string $filename): MediaAsset
    {
        $this->actingAs($owner)->post(route('groups.cover.store', $group), [
            'cover_photo' => UploadedFile::fake()->image($filename, 1200, 700),
        ])->assertSessionHasNoErrors();
        $asset = $group->fresh()->pendingCoverMediaAsset()->firstOrFail();
        app(GroupCoverProcessor::class)->process($asset);

        return $asset->fresh();
    }

    private function user(string $email): User
    {
        return User::create([
            'name' => 'Studio Owner',
            'email' => $email,
            'password' => Hash::make('password'),
            'status' => 'active',
            'account_type' => 'photographer',
        ]);
    }

    private function group(User $owner): Group
    {
        $group = Group::create(['name' => 'Wedding Group', 'creator_id' => $owner->id, 'privacy' => 'link_only']);
        $group->members()->attach($owner->id, [
            'role' => 'admin',
            'membership_status' => 'active',
            'access_type' => 'full_access',
        ]);

        return $group;
    }
}
