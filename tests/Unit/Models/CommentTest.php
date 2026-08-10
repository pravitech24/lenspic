<?php

namespace Tests\Unit\Models;

use App\Models\Comment;
use App\Models\Group;
use App\Models\Photo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CommentTest extends TestCase
{
    use RefreshDatabase;

    public function test_comment_relations_point_to_photo_and_user(): void
    {
        $user = User::create([
            'name' => 'Comment User',
            'email' => 'comment@example.com',
            'phone' => '5559990000',
            'password' => Hash::make('password'),
        ]);

        $group = Group::create([
            'name' => 'Comments Group',
            'event_type' => 'event',
            'creator_id' => $user->id,
            'privacy' => 'public',
        ]);

        $photo = Photo::create([
            'group_id' => $group->id,
            'uploader_id' => $user->id,
            'filename' => 'comment.jpg',
            'original_filename' => 'comment.jpg',
            'path' => 'photos/1/comment.jpg',
            'thumbnail_path' => null,
            'file_size' => 1024,
            'mime_type' => 'image/jpeg',
        ]);

        $comment = Comment::create([
            'photo_id' => $photo->id,
            'user_id' => $user->id,
            'body' => 'Nice shot!',
        ]);

        $this->assertSame($photo->id, $comment->photo->id);
        $this->assertSame($user->id, $comment->user->id);
    }
}