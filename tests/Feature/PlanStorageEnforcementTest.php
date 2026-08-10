<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PlanStorageEnforcementTest extends TestCase
{
    use RefreshDatabase;

    public function test_free_plan_users_cannot_create_more_than_three_groups(): void
    {
        Storage::fake('public');

        $user = User::create([
            'name' => 'Plan User',
            'email' => 'plan@example.com',
            'phone' => '9999999999',
            'password' => Hash::make('password'),
        ]);

        for ($i = 0; $i < 3; $i++) {
            Group::create([
                'name' => 'Group ' . $i,
                'creator_id' => $user->id,
                'privacy' => 'public',
                'event_type' => 'birthday',
            ]);
        }

        $response = $this->actingAs($user)->post(route('groups.store'), [
            'name' => 'Fourth Group',
            'event_type' => 'birthday',
            'privacy' => 'public',
            'access_options' => ['partial_access', 'full_access'],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertCount(3, Group::where('creator_id', $user->id)->get());
    }

    public function test_trial_users_are_limited_to_upload_and_share_only(): void
    {
        $user = User::create([
            'name' => 'Trial User',
            'email' => 'trial@example.com',
            'phone' => '5555555555',
            'password' => Hash::make('password'),
            'plan' => 'trial',
        ]);

        $this->assertTrue($user->isTrial());
        $this->assertSame(500, $user->plan_limits['photos_per_group']);
        $this->assertTrue($user->canAccessFeature('upload'));
        $this->assertTrue($user->canAccessFeature('share'));
        $this->assertFalse($user->canAccessFeature('create_group'));
        $this->assertFalse($user->canAccessFeature('settings'));
    }

    public function test_multiple_uploaded_files_are_stored_when_the_request_uses_photos_array_field(): void
    {
        Storage::fake('public');

        $user = User::create([
            'name' => 'Upload User',
            'email' => 'upload@example.com',
            'phone' => '7777777777',
            'password' => Hash::make('password'),
        ]);

        $group = Group::create([
            'name' => 'Upload Group',
            'creator_id' => $user->id,
            'privacy' => 'public',
            'event_type' => 'birthday',
        ]);

        $group->members()->attach($user->id, [
            'role' => 'admin',
            'joined_at' => now(),
        ]);

        $response = $this->actingAs($user)->call('POST', route('photos.store', $group), [], [], [
            'photos[]' => [
                UploadedFile::fake()->image('one.jpg'),
                UploadedFile::fake()->image('two.jpg'),
            ],
        ], [
            'HTTP_ACCEPT' => 'application/json',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['uploaded' => 2]);
        $this->assertCount(2, $group->photos()->get());
    }

    public function test_users_cannot_upload_when_storage_limit_is_exceeded(): void
    {
        Storage::fake('public');

        $user = User::create([
            'name' => 'Storage User',
            'email' => 'storage@example.com',
            'phone' => '8888888888',
            'password' => Hash::make('password'),
            'storage_used' => 1_073_741_824 + 1,
        ]);

        $group = Group::create([
            'name' => 'Storage Group',
            'creator_id' => $user->id,
            'privacy' => 'public',
            'event_type' => 'birthday',
        ]);

        $group->members()->attach($user->id, [
            'role' => 'admin',
            'joined_at' => now(),
        ]);

        $response = $this->actingAs($user)->post(route('photos.store', $group), [
            'photos' => [UploadedFile::fake()->image('photo.jpg')],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertCount(0, $group->photos()->get());
    }
}
