<?php

namespace Tests\Feature;

use App\Models\MediaAsset;
use App\Models\Group;
use App\Models\StorageLedgerEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PhotographerSettingsAuditTest extends TestCase
{
    use RefreshDatabase;

    private function user(array $attributes = []): User
    {
        return User::create(array_merge([
            'name' => 'Asha Rao', 'email' => 'asha@example.test', 'phone' => '+919876543210',
            'password' => Hash::make('Existing!Password123'), 'email_verified_at' => now(),
            'account_type' => 'photographer', 'status' => 'active', 'plan' => 'basic',
        ], $attributes));
    }

    public function test_profile_update_is_scoped_normalized_and_email_change_requires_verification(): void
    {
        $user = $this->user();
        $other = $this->user(['email' => 'other@example.test', 'phone' => '+919876543211']);

        $this->actingAs($user)->post(route('settings.profile.update'), [
            'first_name' => '  Asha ', 'last_name' => ' Rao ',
            'email' => 'NEW@EXAMPLE.TEST', 'phone' => '+919876543212',
        ])->assertRedirect();

        $user->refresh();
        $this->assertSame('Asha Rao', $user->name);
        $this->assertSame('new@example.test', $user->email);
        $this->assertNull($user->email_verified_at);
        $this->assertSame('other@example.test', $other->fresh()->email);
    }

    public function test_password_update_requires_current_password_and_is_rate_limited_route(): void
    {
        $user = $this->user();
        $payload = ['current_password' => 'wrong', 'password' => 'New!SecurePassword456', 'password_confirmation' => 'New!SecurePassword456'];
        $this->actingAs($user)->put(route('settings.password.update'), $payload)->assertSessionHasErrors('current_password');
        $this->assertTrue(Hash::check('Existing!Password123', $user->fresh()->password));

        $payload['current_password'] = 'Existing!Password123';
        $this->put(route('settings.password.update'), $payload)->assertRedirect();
        $this->assertTrue(Hash::check('New!SecurePassword456', $user->fresh()->password));
        $this->assertStringContainsString('throttle:5,1', implode(',', app('router')->getRoutes()->getByName('settings.password.update')->gatherMiddleware()));
    }

    public function test_storage_summary_uses_owned_media_and_ledger_data(): void
    {
        $user = $this->user();
        $group = Group::create(['name' => 'Studio gallery', 'creator_id' => $user->id]);
        $photo = \App\Models\Photo::create(['group_id'=>$group->id,'uploader_id'=>$user->id,'filename'=>'a.jpg','original_filename'=>'a.jpg','path'=>'media/a.jpg','file_size'=>2000]);
        $asset = MediaAsset::create(['uuid' => fake()->uuid(), 'photo_id'=>$photo->id, 'owner_id' => $user->id, 'group_id' => $group->id, 'kind' => 'photo', 'storage_disk' => 'private_local', 'original_object_key' => 'media/a.jpg', 'original_filename' => 'a.jpg', 'mime_type' => 'image/jpeg', 'size_bytes' => 2000, 'checksum_sha256' => hash('sha256', 'fixture'), 'state' => 'completed']);
        StorageLedgerEntry::create(['owner_id' => $user->id, 'group_id' => $group->id, 'media_asset_id' => $asset->id, 'event_type' => 'media_ingested', 'byte_delta' => 2000, 'idempotency_key' => 'settings-a']);

        $this->actingAs($user)->get(route('settings.profile'))->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Settings/Index')->where('section', 'profile')->where('storage.counted_photos', 1)->where('storage.physical_storage_bytes', 2000)
        );
    }

    public function test_supported_preferences_persist_and_page_is_full_page_component(): void
    {
        $user = $this->user();
        $this->actingAs($user)->put(route('settings.account-preferences.update'), ['upload_quality_preference' => 'high_resolution', 'post_transfer_action' => 'leave_group'])->assertRedirect();
        $this->assertSame('high_resolution', $user->fresh()->meta['preferences']['upload_quality_preference']);
        $this->assertSame('leave_group', $user->fresh()->meta['preferences']['post_transfer_action']);
        $this->get(route('settings.account-preferences'))->assertOk()->assertInertia(fn (Assert $page) => $page->component('Settings/Index')->where('section', 'preferences'));
        $source = file_get_contents(resource_path('js/Pages/Settings/Index.vue'));
        $this->assertStringContainsString('<AppShell>', $source);
        $this->assertStringNotContainsString('UiModal', $source);
        $this->assertStringContainsString('1 High Resolution upload = 2.5 photos', $source);
    }
}
