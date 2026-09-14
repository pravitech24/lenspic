<?php

namespace Tests\Feature;

use App\Models\{Group, UploadBatch, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\{Hash, Queue, Storage};
use Tests\TestCase;

class PhotoUploadLimitsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['media.disk' => 'private_local']);
        Storage::fake('private_local');
        Queue::fake();
    }

    private function ownerAndGroup(): array
    {
        $owner = User::create(['name' => 'Owner', 'email' => 'limits@example.com', 'password' => Hash::make('x'), 'status' => 'active', 'account_type' => 'photographer']);
        $group = Group::create(['name' => 'Test Group', 'creator_id' => $owner->id]);
        $group->members()->attach($owner->id, ['role' => 'admin', 'membership_status' => 'active', 'access_type' => 'full_access']);

        return [$owner, $group];
    }

    public function test_upload_limits_have_the_required_central_defaults(): void
    {
        $this->assertSame(30, config('media.photo_upload.max_file_mb'));
        $this->assertSame(50, config('media.photo_upload.max_batch_files'));
    }

    public function test_fifty_one_photos_are_rejected_before_ingestion(): void
    {
        [$owner, $group] = $this->ownerAndGroup();
        $photos = array_map(fn ($number) => UploadedFile::fake()->image("photo-{$number}.jpg"), range(1, 51));

        $this->actingAs($owner)->postJson(route('photos.store', $group), ['photos' => $photos])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'You can upload up to 50 photos at once.');

        $this->assertDatabaseCount('upload_batches', 0);
    }

    public function test_gallery_uses_dynamic_limits_and_required_copy(): void
    {
        [$owner, $group] = $this->ownerAndGroup();

        $this->actingAs($owner)->get(route('groups.gallery', $group))
            ->assertInertia(fn ($page) => $page
                ->where('photoUploadLimits.maxFileMb', 30)
                ->where('photoUploadLimits.maxBatchFiles', 50));

        $source = file_get_contents(resource_path('js/Pages/Groups/Show.vue'));
        $this->assertStringContainsString('JPG, JPEG, PNG or WebP · maximum {{maxUploadFileMb}} MB each · up to {{maxUploadBatchFiles}} photos', $source);
        $this->assertStringContainsString('.jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp', $source);
    }

    public function test_unsupported_content_is_rejected_even_with_a_jpg_extension(): void
    {
        [$owner, $group] = $this->ownerAndGroup();

        $this->actingAs($owner)->postJson(route('photos.store', $group), [
            'photos' => [UploadedFile::fake()->createWithContent('malicious.jpg', '<?php echo "not an image";')],
        ])->assertUnprocessable();

        $this->assertDatabaseHas('upload_batch_files', ['client_filename' => 'malicious.jpg', 'state' => 'failed']);
        $this->assertDatabaseCount('media_assets', 0);
    }

    public function test_stalled_processing_is_reported_without_faking_failure_or_completion(): void
    {
        [$owner, $group] = $this->ownerAndGroup();
        $batch = UploadBatch::create(['uuid' => (string) \Illuminate\Support\Str::uuid(), 'group_id' => $group->id, 'user_id' => $owner->id, 'state' => 'queued', 'total_files' => 1, 'pending_files' => 1]);
        $file = $batch->files()->create(['client_filename' => 'waiting.jpg', 'state' => 'queued']);
        \Illuminate\Support\Facades\DB::table('upload_batch_files')->where('id', $file->id)->update(['updated_at' => now()->subMinutes(10)]);

        $this->actingAs($owner)->getJson(route('processing.show', $batch))
            ->assertOk()
            ->assertJsonPath('status', 'queued')
            ->assertJsonPath('progress_percentage', 0)
            ->assertJsonPath('stalled', true);
    }
}
