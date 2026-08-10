<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GroupSettingsTest extends TestCase
{
    use RefreshDatabase;

    private function owner(): User
    {
        return User::create([
            'name' => 'Owner',
            'email' => 'owner@example.com',
            'password' => bcrypt('password'),
        ]);
    }

    public function test_a_single_settings_section_can_be_updated_without_other_required_fields(): void
    {
        $owner = $this->owner();
        $group = Group::create([
            'name' => 'Original',
            'creator_id' => $owner->id,
            'privacy' => 'link_only',
            'allow_guest_upload' => true,
            'face_recognition_enabled' => true,
        ]);

        $this->actingAs($owner)
            ->put(route('groups.update', $group), ['privacy' => 'private', 'face_recognition_enabled' => '0'])
            ->assertSessionHasNoErrors();

        $group->refresh();
        $this->assertSame('private', $group->privacy);
        $this->assertFalse($group->face_recognition_enabled);
        $this->assertTrue($group->allow_guest_upload);
        $this->assertSame('Original', $group->name);
    }

    public function test_general_settings_can_turn_a_checkbox_off(): void
    {
        $owner = $this->owner();
        $group = Group::create([
            'name' => 'Original',
            'creator_id' => $owner->id,
            'privacy' => 'link_only',
            'allow_guest_upload' => true,
            'watermark_enabled' => true,
        ]);

        $this->actingAs($owner)
            ->put(route('groups.update', $group), [
                'name' => 'Renamed',
                'event_type' => 'event',
                'allow_guest_upload' => '0',
            ])
            ->assertSessionHasNoErrors();

        $group->refresh();
        $this->assertSame('Renamed', $group->name);
        $this->assertFalse($group->allow_guest_upload);
        $this->assertTrue($group->watermark_enabled);
    }
}
