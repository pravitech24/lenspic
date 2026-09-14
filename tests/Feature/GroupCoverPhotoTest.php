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

class GroupCoverPhotoTest extends TestCase
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

    public function test_owner_can_upload_a_private_cover_and_processing_is_queued(): void
    {
        Queue::fake();
        $owner = $this->user('owner@example.com');
        $group = $this->group($owner);

        $this->actingAs($owner)->post(route('groups.cover.store', $group), [
            'cover_photo' => UploadedFile::fake()->image('celebration.jpg', 1200, 700),
        ])->assertRedirect();

        $asset = MediaAsset::with('variants')->where('kind', 'group_cover')->firstOrFail();
        $this->assertSame($asset->id, $group->fresh()->pending_cover_media_asset_id);
        $this->assertNull($group->fresh()->cover_media_asset_id);
        $this->assertSame('private_local', $asset->storage_disk);
        $this->assertStringStartsWith('media/covers/groups/'.$group->id.'/originals/', $asset->original_object_key);
        $this->assertStringNotContainsString('celebration', $asset->original_object_key);
        $this->assertStringNotContainsString('/storage/', $asset->original_object_key);
        Storage::disk('private_local')->assertExists($asset->original_object_key);
        Storage::disk('public')->assertMissing($asset->original_object_key);
        Queue::assertPushed(ProcessGroupCover::class, fn ($job) => $job->assetId === $asset->id);
    }

    public function test_unauthorized_and_cross_studio_users_cannot_manage_or_guess_a_cover(): void
    {
        Queue::fake();
        $owner = $this->user('owner@example.com');
        $outsider = $this->user('outsider@example.com');
        $group = $this->group($owner);
        $this->actingAs($owner)->post(route('groups.cover.store', $group), [
            'cover_photo' => UploadedFile::fake()->image('cover.jpg', 1000, 600),
        ])->assertRedirect();
        $asset = MediaAsset::firstOrFail();

        $this->actingAs($outsider)->post(route('groups.cover.store', $group), [
            'cover_photo' => UploadedFile::fake()->image('other.jpg', 1000, 600),
        ])->assertForbidden();
        $this->actingAs($outsider)->delete(route('groups.cover.destroy', $group))->assertForbidden();
        $this->actingAs($outsider)->get(route('media.show', [$asset->uuid, 'cover_grid']))->assertForbidden();
        $this->actingAs($owner)->get(route('media.show', [$asset->uuid, 'original']))->assertNotFound();
    }

    public function test_cover_validation_accepts_supported_images_and_rejects_unsafe_uploads(): void
    {
        Queue::fake();
        $owner = $this->user('owner@example.com');

        foreach (['jpg', 'png'] as $extension) {
            $group = $this->group($owner, 'Valid '.strtoupper($extension));
            $this->actingAs($owner)->post(route('groups.cover.store', $group), [
                'cover_photo' => UploadedFile::fake()->image('cover.'.$extension, 1000, 600),
            ])->assertSessionHasNoErrors();
        }

        if (function_exists('imagewebp')) {
            $group = $this->group($owner, 'Valid WebP');
            $this->actingAs($owner)->post(route('groups.cover.store', $group), [
                'cover_photo' => $this->webp('cover.webp', 1000, 600),
            ])->assertSessionHasNoErrors();
        }

        $group = $this->group($owner, 'Invalid files');
        $this->actingAs($owner)->from(route('groups.settings', $group))->post(route('groups.cover.store', $group), [
            'cover_photo' => UploadedFile::fake()->createWithContent('renamed.jpg', 'not an image'),
        ])->assertRedirect(route('groups.settings', $group))->assertSessionHasErrors('cover_photo');
        $this->actingAs($owner)->post(route('groups.cover.store', $group), [
            'cover_photo' => UploadedFile::fake()->createWithContent('vector.svg', '<svg xmlns="http://www.w3.org/2000/svg"/>'),
        ])->assertSessionHasErrors('cover_photo');
        $this->actingAs($owner)->post(route('groups.cover.store', $group), [
            'cover_photo' => UploadedFile::fake()->create('huge.jpg', 10241, 'image/jpeg'),
        ])->assertSessionHasErrors('cover_photo');
        $this->actingAs($owner)->post(route('groups.cover.store', $group), [
            'cover_photo' => UploadedFile::fake()->image('small.jpg', 799, 399),
        ])->assertSessionHasErrors('cover_photo');
        $this->assertNull($group->fresh()->pending_cover_media_asset_id);
    }

    public function test_processing_generates_private_cropped_variants_and_atomically_replaces_cover(): void
    {
        Queue::fake();
        $owner = $this->user('owner@example.com');
        $group = $this->group($owner);

        $first = $this->upload($owner, $group, 'first.jpg');
        app(GroupCoverProcessor::class)->process($first);
        $this->assertSame($first->id, $group->fresh()->cover_media_asset_id);

        $second = $this->upload($owner, $group->fresh(), 'second.jpg');
        $this->assertSame($first->id, $group->fresh()->cover_media_asset_id, 'Old cover must remain until replacement processing succeeds.');
        app(GroupCoverProcessor::class)->process($second);

        $group->refresh();
        $this->assertSame($second->id, $group->cover_media_asset_id);
        $this->assertNull($group->pending_cover_media_asset_id);
        $second->refresh()->load('variants');
        $this->assertSame('completed', $second->state);
        $this->assertEqualsCanonicalizing(
            ['original', 'cover_thumbnail', 'cover_grid', 'cover_large'],
            $second->variants->pluck('variant_type')->all()
        );
        foreach ([
            'cover_thumbnail' => [400, 225],
            'cover_grid' => [800, 450],
            'cover_large' => [1600, 900],
        ] as $type => [$width, $height]) {
            $variant = $second->variants->firstWhere('variant_type', $type);
            $this->assertSame($width, $variant->width);
            $this->assertSame($height, $variant->height);
            $this->assertSame('private_local', $variant->storage_disk);
            Storage::disk('private_local')->assertExists($variant->object_key);
        }
        $this->assertSoftDeleted('media_assets', ['id' => $first->id]);
        Queue::assertPushed(DeleteMediaAsset::class, fn ($job) => $job->assetId === $first->id);
    }

    public function test_cover_removal_and_group_deletion_schedule_safe_cleanup(): void
    {
        Queue::fake();
        $owner = $this->user('owner@example.com');
        $group = $this->group($owner);
        $asset = $this->upload($owner, $group, 'remove.jpg');
        app(GroupCoverProcessor::class)->process($asset);

        $this->actingAs($owner)->delete(route('groups.cover.destroy', $group))->assertRedirect();
        $this->assertNull($group->fresh()->cover_media_asset_id);
        $this->assertSoftDeleted('media_assets', ['id' => $asset->id]);
        Queue::assertPushed(DeleteMediaAsset::class, fn ($job) => $job->assetId === $asset->id);

        Queue::fake();
        $secondGroup = $this->group($owner, 'Delete group');
        $second = $this->upload($owner, $secondGroup, 'group-delete.jpg');
        app(GroupCoverProcessor::class)->process($second);
        $this->actingAs($owner)->delete(route('groups.destroy', $secondGroup))->assertRedirect(route('groups.index'));
        Queue::assertPushed(DeleteMediaAsset::class, fn ($job) => $job->assetId === $second->id);
    }

    public function test_index_and_overview_receive_authorized_variant_routes_and_fallback_data(): void
    {
        Queue::fake();
        $owner = $this->user('owner@example.com');
        $covered = $this->group($owner, 'Covered Event');
        $fallback = $this->group($owner, 'Fallback Event');
        $asset = $this->upload($owner, $covered, 'grid.jpg');
        app(GroupCoverProcessor::class)->process($asset);

        $this->actingAs($owner)->get(route('groups.index'))->assertInertia(fn (Assert $page) => $page
            ->component('Groups/Index')
            ->where('groups.0.cover.url', route('media.show', [$asset->uuid, 'cover_grid']))
            ->where('groups.1.cover.url', null)
            ->where('groups.1.cover.state', 'empty')
        );
        $this->actingAs($owner)->get(route('groups.show', $covered))->assertInertia(fn (Assert $page) => $page
            ->component('Groups/Show')
            ->where('group.cover.url', route('media.show', [$asset->uuid, 'cover_large']))
        );
        $this->assertStringNotContainsString($asset->original_object_key, route('media.show', [$asset->uuid, 'cover_grid']));
    }

    public function test_processing_failure_is_recorded_without_replacing_the_current_cover(): void
    {
        Queue::fake();
        $owner = $this->user('owner@example.com');
        $group = $this->group($owner);
        $asset = $this->upload($owner, $group, 'broken-later.jpg');
        Storage::disk('private_local')->put($asset->original_object_key, 'corrupt image bytes');

        try {
            (new ProcessGroupCover($asset->id))->handle(app(GroupCoverProcessor::class));
            $this->fail('The corrupt cover should fail processing.');
        } catch (\Throwable) {
            // Expected: the job records a safe failure state before retrying.
        }

        $this->assertSame('failed', $asset->fresh()->state);
        $this->assertSame('Cover processing failed.', $asset->fresh()->processing_error);
        $this->assertNull($group->fresh()->cover_media_asset_id);
        $this->actingAs($owner)->get(route('groups.index'))->assertOk();
    }

    public function test_group_creation_remains_valid_without_a_cover(): void
    {
        $owner = $this->user('owner@example.com');

        $this->actingAs($owner)->post(route('groups.store'), [
            'name' => 'No Cover Event',
            'event_type' => 'wedding',
            'privacy' => 'private',
            'access_options' => ['full_access'],
        ])->assertRedirect();

        $group = Group::where('name', 'No Cover Event')->firstOrFail();
        $this->assertNull($group->cover_media_asset_id);
        $this->assertNull($group->pending_cover_media_asset_id);
    }

    private function upload(User $owner, Group $group, string $name): MediaAsset
    {
        $this->actingAs($owner)->post(route('groups.cover.store', $group), [
            'cover_photo' => UploadedFile::fake()->image($name, 1200, 800),
        ])->assertSessionHasNoErrors();

        return $group->fresh()->pendingCoverMediaAsset()->firstOrFail();
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

    private function group(User $owner, string $name = 'Wedding Group'): Group
    {
        $group = Group::create(['name' => $name, 'creator_id' => $owner->id]);
        $group->members()->attach($owner->id, [
            'role' => 'admin',
            'membership_status' => 'active',
            'access_type' => 'full_access',
        ]);

        return $group;
    }

    private function webp(string $name, int $width, int $height): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'lenspic-webp-');
        $image = imagecreatetruecolor($width, $height);
        imagewebp($image, $path, 80);
        imagedestroy($image);

        return new UploadedFile($path, $name, 'image/webp', null, true);
    }
}
