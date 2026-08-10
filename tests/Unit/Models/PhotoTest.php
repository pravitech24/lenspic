<?php

namespace Tests\Unit\Models;

use App\Models\Group;
use App\Models\Photo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PhotoTest extends TestCase
{
    use RefreshDatabase;

    public function test_photo_accessors_and_like_checks_work(): void
    {
        $user = User::create([
            'name' => 'Photo User',
            'email' => 'photo@example.com',
            'phone' => '5558889999',
            'password' => Hash::make('password'),
        ]);

        $group = Group::create([
            'name' => 'Album',
            'event_type' => 'event',
            'creator_id' => $user->id,
            'privacy' => 'public',
        ]);

        $photo = Photo::create([
            'group_id' => $group->id,
            'uploader_id' => $user->id,
            'filename' => 'photo.jpg',
            'original_filename' => 'photo.jpg',
            'path' => 'photos/1/photo.jpg',
            'thumbnail_path' => null,
            'file_size' => 1536,
            'mime_type' => 'image/jpeg',
            'width' => 1200,
            'height' => 800,
        ]);

        $photo->likes()->attach($user->id);

        $this->assertSame($photo->url, $photo->thumbnail_url);
        $this->assertSame('1.5 KB', $photo->file_size_human);
        $this->assertTrue($photo->isLikedBy($user));
        $this->assertFalse($photo->isLikedBy(null));

        $photo->incrementViews();
        $photo->incrementDownloads();

        $fresh = $photo->fresh();

        $this->assertSame(1, $fresh->views_count);
        $this->assertSame(1, $fresh->downloads_count);
    }
}