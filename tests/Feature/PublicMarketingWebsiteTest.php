<?php

namespace Tests\Feature;

use App\Mail\ContactEnquiryAcknowledgement;
use App\Models\ContactEnquiry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PublicMarketingWebsiteTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_canonical_public_pages_render_with_public_navigation(): void
    {
        $routes = [
            'home','marketing.features','marketing.how-it-works','marketing.photographers','marketing.guests',
            'marketing.discovery','marketing.biometric','marketing.galleries','marketing.solutions','marketing.about',
            'marketing.contact','pricing','marketing.blog','marketing.faqs','marketing.help','marketing.join',
            'marketing.privacy','marketing.terms','marketing.refunds','marketing.cookies','marketing.security',
            'marketing.biometric-consent','marketing.retention','marketing.deletion','marketing.acceptable-use','marketing.copyright',
        ];
        foreach ($routes as $route) {
            $this->get(route($route))->assertOk()->assertSee('LensPic')->assertSee('Skip to main content');
        }
        foreach (config('marketing.solutions') as $solution) {
            $this->get(route($solution['route']))->assertOk()->assertSee($solution['title'])->assertDontSee('data-hero-carousel', false);
        }
        foreach (array_keys(config('marketing.posts')) as $slug) $this->get(route('marketing.blog.article', $slug))->assertOk();
    }

    public function test_legal_pages_use_central_dates_without_development_status_language(): void
    {
        foreach (['marketing.privacy','marketing.terms','marketing.refunds','marketing.cookies','marketing.security','marketing.biometric-consent','marketing.retention','marketing.deletion','marketing.acceptable-use','marketing.copyright'] as $route) {
            $this->get(route($route))->assertOk()->assertSee('Effective date')->assertSee('26 August 2026')->assertSee('Last updated')->assertDontSee('dra'.'ft', false)->assertDontSee('Pending legal'.' approval');
        }
        $this->get(route('marketing.biometric-consent'))->assertSee(route('marketing.privacy'))->assertSee(route('marketing.deletion'));
    }

    public function test_old_landing_urls_redirect_to_canonical_pages(): void
    {
        $this->get('/landing/home')->assertRedirect('/');
        $this->get('/landing/aboutus')->assertRedirect('/about');
        $this->get('/landing/contactus')->assertRedirect('/contact');
        $this->get('/landing/pricing')->assertRedirect('/pricing');
        $this->get('/landing/join')->assertRedirect('/join');
    }

    public function test_authenticated_header_uses_workspace_cta(): void
    {
        $user = User::create(['name' => 'Owner', 'email' => 'owner@test.local', 'password' => Hash::make('password'), 'plan' => 'standard']);
        $this->actingAs($user)->get(route('home'))->assertOk()->assertSee('Go to workspace')->assertDontSee('href="'.route('login').'"', false);
    }

    public function test_contact_enquiry_is_validated_persisted_and_acknowledged(): void
    {
        Mail::fake();
        $payload = [
            'full_name' => 'Studio Owner', 'business_name' => 'Northlight Studio',
            'email' => 'owner@example.test', 'phone_country_code' => '+91', 'phone_number' => '9876543210',
            'country' => 'India', 'enquiry_type' => 'book_demo',
            'message' => 'Please arrange a demonstration for our photography team.', 'privacy_consent' => '1', 'website' => '',
        ];
        $this->post(route('marketing.contact.store'), $payload)->assertRedirect(route('marketing.contact'))->assertSessionHas('success');
        $this->assertDatabaseHas('contact_enquiries', ['email' => 'owner@example.test', 'enquiry_type' => 'book_demo', 'status' => 'new']);
        Mail::assertQueued(ContactEnquiryAcknowledgement::class, fn ($mail) => $mail->hasTo('owner@example.test'));
        $this->post(route('marketing.contact.store'), $payload)->assertSessionHasErrors('message');
    }

    public function test_contact_honeypot_and_invalid_fields_are_rejected(): void
    {
        $this->post(route('marketing.contact.store'), [
            'full_name' => 'A', 'email' => 'invalid', 'phone_country_code' => '91', 'phone_number' => 'abc',
            'country' => '', 'enquiry_type' => 'unknown', 'message' => 'short', 'website' => 'spam.example',
        ])->assertSessionHasErrors(['email','phone_country_code','phone_number','country','enquiry_type','message','privacy_consent','website']);
        $this->assertSame(0, ContactEnquiry::count());
    }

    public function test_sitemap_feed_and_join_handoff_are_public(): void
    {
        $this->get(route('marketing.sitemap'))->assertOk()->assertHeader('Content-Type', 'application/xml')->assertSee('/solutions/weddings');
        $this->get(route('marketing.feed'))->assertOk()->assertHeader('Content-Type', 'application/rss+xml')->assertSee('LensPic Journal');
        $this->get(route('marketing.join'))->assertOk()->assertSee(route('groups.join-code'));
    }

    public function test_hero_carousels_render_accessible_static_fallback_and_real_assets(): void
    {
        $home = $this->get(route('home'))->assertOk()
            ->assertSee('aria-roledescription="carousel"', false)
            ->assertSee('data-home-hero-slider', false)
            ->assertSee('Your Group Photos, Delivered Beautifully')
            ->assertSee('data-carousel-prev', false)
            ->assertSee('data-carousel-toggle', false)
            ->assertSee('aria-label="Previous slide"', false)
            ->assertSee('aria-label="Next slide"', false)
            ->assertSee('aria-label="Pause slideshow"', false)
            ->assertSee('aria-label="Go to slide 1"', false)
            ->assertSee('aria-pressed="false"', false)
            ->assertSee('hero-content-width-wide', false)
            ->assertSee('hero-overlay-left-strong', false)
            ->assertSee('fetchpriority="high"', false)
            ->assertSee('loading="lazy"', false)
            ->assertDontSee('Event');
        $this->assertSame(1, substr_count($home->getContent(), 'data-home-hero-slider'));

        foreach (['marketing.features','marketing.how-it-works','marketing.photographers','marketing.guests','marketing.discovery','marketing.galleries'] as $route) {
            $this->get(route($route))->assertOk()
                ->assertSee('data-inner-page-header', false)
                ->assertDontSee('data-home-hero-slider', false)
                ->assertDontSee('data-hero-carousel', false)
                ->assertDontSee('data-carousel-next', false)
                ->assertDontSee('data-carousel-toggle', false);
        }
        $this->get(route('marketing.features'))->assertSee('lenspic-features-multi-device.png')->assertSee('lenspic-features-multi-device-mobile.png');
        $this->get(route('marketing.how-it-works'))->assertSee('lenspic-photographer-workflow.png')->assertSee('lenspic-group-member-workflow.png');

        foreach (['group-gallery','photographers','find-my-photos','private-sharing','group-occasions'] as $asset) {
            $this->assertFileExists(public_path("images/marketing/lenspic-slider-{$asset}-desktop.webp"));
            $this->assertFileExists(public_path("images/marketing/lenspic-slider-{$asset}-tablet.webp"));
            $this->assertFileExists(public_path("images/marketing/lenspic-slider-{$asset}-mobile.webp"));
        }
        foreach (['features-multi-device','photographer-workflow','group-member-workflow'] as $asset) {
            $this->assertFileExists(public_path("images/marketing/lenspic-{$asset}.png"));
            $this->assertFileExists(public_path("images/marketing/lenspic-{$asset}-mobile.png"));
        }
        foreach (['photographers','weddings','celebrations','corporate','institutions','conferences'] as $asset) {
            $this->assertFileExists(public_path("images/marketing/lenspic-solution-{$asset}.png"));
            $this->assertFileExists(public_path("images/marketing/lenspic-solution-{$asset}-mobile.png"));
        }
        foreach (config('marketing.solutions') as $solution) {
            $this->get(route($solution['route']))->assertOk()
                ->assertSee('class="solution-photo"', false)
                ->assertDontSee('Demonstration'.' content only')
                ->assertDontSee('visual-tile', false);
        }
        $this->get('/event-galleries')->assertRedirect('/group-galleries');
        $this->get('/solutions/corporate-events')->assertRedirect('/solutions/corporate-gatherings');
    }

    public function test_every_public_inner_page_excludes_the_home_slider(): void
    {
        $routes = [
            'marketing.features','marketing.how-it-works','marketing.photographers','marketing.guests',
            'marketing.discovery','marketing.galleries','marketing.solutions','marketing.about','marketing.contact',
            'pricing','marketing.blog','marketing.faqs','marketing.help','marketing.join','marketing.privacy',
            'marketing.terms','marketing.refunds','marketing.cookies','marketing.security','marketing.biometric-consent',
            'marketing.retention','marketing.deletion','marketing.acceptable-use','marketing.copyright',
        ];

        foreach ($routes as $route) {
            $this->get(route($route))->assertOk()->assertDontSee('data-home-hero-slider', false);
        }
        foreach (config('marketing.solutions') as $solution) {
            $this->get(route($solution['route']))->assertOk()->assertDontSee('data-home-hero-slider', false);
        }
    }

    public function test_solutions_navigation_and_named_audience_pages_are_complete(): void
    {
        $response = $this->get(route('home'))->assertOk()
            ->assertSee('Solutions &amp; Use Cases', false)
            ->assertSee('data-solutions-trigger', false)
            ->assertSee('aria-controls="solutions-navigation"', false)
            ->assertSee('aria-expanded="false"', false);

        foreach ([
            'solutions.photographers' => '/solutions/photographers',
            'solutions.weddings' => '/solutions/weddings',
            'solutions.celebrations' => '/solutions/parties-and-celebrations',
            'solutions.corporate' => '/solutions/corporate-groups',
            'solutions.institutions' => '/solutions/colleges-and-institutions',
            'solutions.conferences' => '/solutions/conferences-and-communities',
        ] as $name => $path) {
            $this->assertSame(url($path), route($name));
            $page = $this->get(route($name))->assertOk()
                ->assertSee('Privacy and permissions')
                ->assertSee('Simple workflow')
                ->assertSee('data-solutions-menu', false)
                ->assertDontSee('data-hero-carousel', false)
                ->assertDontSee('Create an Event');
            $this->assertSame(1, substr_count($page->getContent(), '<h1'));
        }

        $this->get(route('marketing.sitemap'))->assertSee('/solutions/photographers')->assertSee('/solutions/conferences-and-communities');
        $this->get('/solutions/corporate-gatherings')->assertRedirect('/solutions/corporate-groups');
        $this->get('/solutions/private-gatherings')->assertRedirect('/solutions/parties-and-celebrations');
    }
}
