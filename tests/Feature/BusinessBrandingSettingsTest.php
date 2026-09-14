<?php

namespace Tests\Feature;

use App\Models\{BusinessBranding, Group, User};
use App\Services\Branding\BusinessBrandingPresenter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\{Hash, Storage};
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class BusinessBrandingSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['media.disk' => 'private_local']);
        Storage::fake('private_local');
    }

    private function user(string $email = 'studio@test.local', string $type = 'photographer'): User
    {
        return User::create(['name' => 'Studio Owner', 'email' => $email, 'password' => Hash::make('password'), 'status' => 'active', 'account_type' => $type, 'plan' => $type === 'user' ? 'free' : 'basic']);
    }

    private function payload(array $overrides = []): array
    {
        return array_replace([
            'business_name' => 'Moonlight Studio', 'business_phone_country_code' => '+91', 'business_phone_number' => '9876543210', 'show_business_phone_in_gallery' => true,
            'business_email' => 'hello@moonlight.test', 'show_business_email_in_gallery' => true,
            'website' => 'https://moonlight.test/', 'show_website_in_gallery' => true,
            'instagram_url' => 'https://instagram.com/moonlight/', 'show_instagram_in_gallery' => true,
            'facebook_url' => 'https://facebook.com/moonlight', 'show_facebook_in_gallery' => true,
            'whatsapp_country_code' => '+91', 'whatsapp_phone_number' => '9988776655', 'show_whatsapp_in_portfolio' => true,
            'youtube_url' => 'https://youtube.com/@moonlight/', 'show_youtube_in_portfolio' => true,
            'vimeo_url' => 'https://vimeo.com/moonlight', 'show_vimeo_in_portfolio' => true,
            'remove_logo' => false,
        ], $overrides);
    }

    public function test_page_loads_all_saved_values_without_private_storage_details(): void
    {
        $user = $this->user();
        $user->businessBranding()->create(array_merge($this->payload(), ['logo_disk' => 'private_local', 'logo_object_key' => 'branding/users/1/secret.png', 'logo_mime_type' => 'image/png', 'logo_size_bytes' => 10]));
        $this->actingAs($user)->get(route('settings.business-branding'))->assertInertia(fn (Assert $page) => $page
            ->component('Settings/Index')->where('branding.business_name', 'Moonlight Studio')->where('branding.instagram_url', 'https://instagram.com/moonlight/')
            ->where('branding.has_logo', true)->where('branding.logo_url', route('settings.business-branding.logo'))->missing('branding.logo_object_key')->missing('branding.logo_disk'));
    }

    public function test_owner_can_update_every_field_and_toggle_without_erasing_hidden_values(): void
    {
        $user = $this->user();
        $this->actingAs($user)->post(route('settings.business-branding.update'), $this->payload(['show_business_email_in_gallery' => false, 'show_youtube_in_portfolio' => false]))->assertRedirect()->assertSessionHas('success');
        $this->assertDatabaseHas('business_brandings', ['user_id' => $user->id, 'business_email' => 'hello@moonlight.test', 'show_business_email_in_gallery' => false, 'youtube_url' => 'https://youtube.com/@moonlight/', 'show_youtube_in_portfolio' => false]);
    }

    public function test_validation_is_field_specific_and_accepts_supported_urls_with_trailing_slashes(): void
    {
        $user = $this->user();
        $this->actingAs($user)->from(route('settings.business-branding'))->post(route('settings.business-branding.update'), $this->payload())->assertRedirect(route('settings.business-branding'))->assertSessionHasNoErrors();
        $this->actingAs($user)->from(route('settings.business-branding'))->post(route('settings.business-branding.update'), $this->payload([
            'business_email' => 'bad-email', 'business_phone_number' => '12', 'instagram_url' => 'https://example.com/instagram',
            'facebook_url' => 'ftp://facebook.com/a', 'youtube_url' => 'https://example.com/video', 'vimeo_url' => 'vimeo',
        ]))->assertSessionHasErrors(['business_email', 'business_phone_number', 'instagram_url', 'facebook_url', 'youtube_url', 'vimeo_url']);
        $this->assertSame('Moonlight Studio', session()->getOldInput('business_name'));
    }

    public function test_logo_upload_replace_remove_and_private_delivery(): void
    {
        $user = $this->user();
        $this->actingAs($user)->post(route('settings.business-branding.update'), $this->payload(['logo' => UploadedFile::fake()->image('logo.png', 240, 120)]))->assertSessionHasNoErrors();
        $branding = $user->businessBranding()->firstOrFail(); $first = $branding->logo_object_key;
        Storage::disk('private_local')->assertExists($first); Storage::disk('public')->assertMissing($first);
        $this->actingAs($user)->get(route('settings.business-branding.logo'))->assertOk()->assertHeader('Content-Type', 'image/png');
        $this->actingAs($user)->post(route('settings.business-branding.update'), $this->payload(['logo' => UploadedFile::fake()->image('replacement.jpg', 240, 120)]))->assertSessionHasNoErrors();
        $branding->refresh(); Storage::disk('private_local')->assertMissing($first); Storage::disk('private_local')->assertExists($branding->logo_object_key);
        $last = $branding->logo_object_key;
        $this->actingAs($user)->post(route('settings.business-branding.update'), $this->payload(['remove_logo' => true]))->assertSessionHasNoErrors();
        Storage::disk('private_local')->assertMissing($last); $this->assertNull($branding->fresh()->logo_object_key);
    }

    public function test_logo_rejects_invalid_mime_and_files_above_five_megabytes(): void
    {
        $user = $this->user();
        $this->actingAs($user)->post(route('settings.business-branding.update'), $this->payload(['logo' => UploadedFile::fake()->create('logo.webp', 10, 'image/webp')]))->assertSessionHasErrors('logo');
        $this->actingAs($user)->post(route('settings.business-branding.update'), $this->payload(['logo' => UploadedFile::fake()->create('logo.png', 5121, 'image/png')]))->assertSessionHasErrors('logo');
    }

    public function test_visibility_is_independent_and_portfolio_fields_never_leak_to_gallery(): void
    {
        $user = $this->user(); $branding = $user->businessBranding()->create($this->payload(['show_business_phone_in_gallery' => false, 'show_instagram_in_gallery' => false, 'show_youtube_in_portfolio' => false]));
        $group = Group::create(['name' => 'Wedding', 'creator_id' => $user->id, 'is_active' => true]);
        $presenter = app(BusinessBrandingPresenter::class); $gallery = $presenter->gallery($group->load('creator.businessBranding')); $portfolio = $presenter->portfolio($user->load('businessBranding'));
        $this->assertArrayNotHasKey('phone', $gallery); $this->assertArrayNotHasKey('instagram_url', $gallery); $this->assertSame('hello@moonlight.test', $gallery['email']);
        $this->assertArrayNotHasKey('whatsapp', $gallery); $this->assertArrayNotHasKey('youtube_url', $gallery); $this->assertArrayNotHasKey('vimeo_url', $gallery);
        $this->assertSame('+91 9988776655', $portfolio['whatsapp']); $this->assertArrayNotHasKey('youtube_url', $portfolio); $this->assertSame('https://vimeo.com/moonlight', $portfolio['vimeo_url']);
    }

    public function test_participants_and_cross_accounts_cannot_update_an_owners_branding(): void
    {
        $owner = $this->user(); $other = $this->user('other@test.local'); $participant = $this->user('participant@test.local', 'user');
        $owner->businessBranding()->create($this->payload(['business_name' => 'Owner Brand']));
        $this->actingAs($participant)->post(route('settings.business-branding.update'), $this->payload())->assertForbidden();
        $this->actingAs($other)->post(route('settings.business-branding.update'), $this->payload(['business_name' => 'Other Brand']))->assertRedirect();
        $this->assertSame('Owner Brand', $owner->businessBranding()->first()->business_name);
        $this->assertSame('Other Brand', $other->businessBranding()->first()->business_name);
    }
}
