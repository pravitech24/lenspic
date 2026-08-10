<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\Photo;
use App\Models\User;
use App\Services\FaceRecognitionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FaceRecognitionTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_face_search_uses_fastapi_matches(): void
    {
        Storage::fake('public');
        config(['services.face_recognition.url' => 'http://fastapi.test']);

        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'phone' => '9999999999',
            'password' => Hash::make('password'),
        ]);

        $group = Group::create([
            'name' => 'Test Group',
            'creator_id' => $user->id,
            'privacy' => 'link_only',
            'face_recognition_enabled' => true,
        ]);

        $selfiePath = 'selfies/' . $group->id . '/' . $user->id . '.jpg';
        Storage::disk('public')->put($selfiePath, 'selfie-bytes');
        $group->members()->attach($user->id, [
            'role' => 'member',
            'joined_at' => now(),
            'selfie_path' => $selfiePath,
        ]);

        $photoPath = 'photos/' . $group->id . '/match.jpg';
        Storage::disk('public')->put($photoPath, 'photo-bytes');

        $photo = Photo::create([
            'group_id' => $group->id,
            'filename' => 'match.jpg',
            'original_filename' => 'match.jpg',
            'path' => $photoPath,
            'thumbnail_path' => $photoPath,
        ]);

        Http::fake([
            'http://fastapi.test/recognize' => Http::response([
                'matches' => [
                    ['photo_id' => $photo->id, 'score' => 0.97],
                ],
            ], 200),
        ]);

        $this->actingAs($user)
            ->postJson(route('face.recognize', $group))
            ->assertOk()
            ->assertJsonPath('photos.0.id', $photo->id)
            ->assertJsonPath('photos.0.url', asset('storage/' . $photoPath));

        Http::assertSent(function ($request) {
            $body = (string) $request->body();
            return str_contains($body, 'name="photo_ids[]"') && str_contains($body, 'name="photos[]"');
        });
    }

    public function test_weak_matches_are_filtered_from_results(): void
    {
        config(['services.face_recognition.min_score' => 0.0]);

        $service = new FaceRecognitionService();
        $method = new \ReflectionMethod($service, 'normalizeMatches');
        $method->setAccessible(true);

        $matches = $method->invoke($service, [
            'matches' => [
                ['photo_id' => 101, 'score' => 0.95],
                ['photo_id' => 102, 'score' => 0.72],
                ['photo_id' => 103, 'score' => 0.8],
                ['photo_id' => 101, 'score' => 0.97],
            ],
        ]);

        $this->assertSame([101, 103, 102], $matches->pluck('photo_id')->all());
    }

    public function test_prepare_photo_for_recognition_falls_back_to_original_file_when_gd_cannot_process_it(): void
    {
        $service = new FaceRecognitionService();
        $method = new \ReflectionMethod($service, 'preparePhotoForRecognition');
        $method->setAccessible(true);

        $path = tempnam(sys_get_temp_dir(), 'kwikpic');
        file_put_contents($path, 'not-a-valid-image');

        $result = $method->invoke($service, $path);

        $this->assertSame($path, $result);

        @unlink($path);
    }

    public function test_guest_selfie_match_uses_fastapi_matches(): void
    {
        Storage::fake('public');
        config(['services.face_recognition.url' => 'http://fastapi.test']);

        $creator = User::create([
            'name' => 'Creator',
            'email' => 'creator@example.com',
            'phone' => '8888888888',
            'password' => Hash::make('password'),
        ]);

        $group = Group::create([
            'name' => 'Public Event',
            'creator_id' => $creator->id,
            'privacy' => 'link_only',
            'face_recognition_enabled' => true,
        ]);

        $photoPath = 'photos/' . $group->id . '/guest-match.jpg';
        Storage::disk('public')->put($photoPath, 'photo-bytes');

        $photo = Photo::create([
            'group_id' => $group->id,
            'filename' => 'guest-match.jpg',
            'original_filename' => 'guest-match.jpg',
            'path' => $photoPath,
            'thumbnail_path' => $photoPath,
        ]);

        Http::fake([
            'http://fastapi.test/recognize' => Http::response([
                'matches' => [
                    ['photo_id' => $photo->id, 'score' => 0.94],
                ],
            ], 200),
        ]);

        $response = $this->post(route('guest.selfie', $group), [
            'name' => 'Priya',
            'phone' => '9999999999',
            'selfie' => UploadedFile::fake()->image('selfie.jpg'),
        ]);

        $response->assertOk();
        $response->assertSee('Found 1 Photos of You!');
    }
}