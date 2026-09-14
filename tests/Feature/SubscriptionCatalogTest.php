<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Billing\PlanCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{Hash, Http};
use Tests\TestCase;

class SubscriptionCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_contains_the_approved_prices_quotas_and_gst_totals(): void
    {
        $plans = collect(app(PlanCatalog::class)->all())->keyBy('code');
        $this->assertSame(['basic','standard','essential','premium'], $plans->keys()->all());

        $expected = [
            'basic'=>[99900,349000,20000,0,20000,411820],
            'standard'=>[179900,749000,100000,5000,200000,883820],
            'essential'=>[359900,1499000,200000,10000,400000,1768820],
            'premium'=>[689900,2999000,500000,25000,1000000,3538820],
        ];
        foreach ($expected as $code => [$quarterly,$yearly,$photos,$video,$reuse,$annualTotal]) {
            $plan = $plans[$code];
            $this->assertSame($photos, $plan['entitlements']['photo_limit']);
            $this->assertSame($video, $plan['entitlements']['video_storage_limit_mb']);
            $this->assertSame($reuse, $plan['entitlements']['photo_delete_reupload_limit']);
            $this->assertSame($quarterly, collect($plan['cycles'])->firstWhere('cycle','quarterly')['base_amount']);
            $annual = collect($plan['cycles'])->firstWhere('cycle','yearly');
            $this->assertSame($yearly, $annual['base_amount']);
            $this->assertSame($annualTotal, $annual['total_amount']);
            $this->assertSame(12, $annual['validity_months']);
        }
    }

    public function test_checkout_ignores_browser_amount_for_every_approved_plan(): void
    {
        config(['services.razorpay'=>['key_id'=>'key','key_secret'=>'secret','webhook_secret'=>'hook','gst_percent'=>18]]);
        Http::fake(['api.razorpay.com/v1/orders'=>Http::response(['id'=>'order_secure','currency'=>'INR'])]);
        $user=User::create(['name'=>'Owner','email'=>'catalog@example.com','password'=>Hash::make('x'),'status'=>'active']);
        $response=$this->actingAs($user)->postJson(route('billing.razorpay.order'),['plan'=>'premium','cycle'=>'yearly','amount'=>1]);
        $response->assertOk()->assertJsonPath('amount',3538820)->assertJsonPath('base_amount',2999000)->assertJsonPath('gst_amount',539820);
    }

    public function test_plan_cards_use_equal_height_footers_and_contain_no_placeholder_copy(): void
    {
        $sources = [resource_path('js/Pages/Settings/Subscription.vue'),resource_path('views/marketing/pricing.blade.php'),config_path('billing.php')];
        foreach ($sources as $file) {
            $source=file_get_contents($file);
            $this->assertStringNotContainsString('awaiting'.' business approval', strtolower($source));
            $this->assertStringNotContainsString('weighted'.' photo quota', strtolower($source));
        }
        $component=file_get_contents($sources[0]);
        foreach (['md:grid-cols-2 xl:grid-cols-4','flex h-full','mt-auto pt-6','Store up to {{x.entitlements.photo_limit.toLocaleString()}} photos','Current Plan','Switch at Renewal'] as $text) {
            $this->assertStringContainsString($text, $component);
        }
    }

    public function test_every_catalog_feature_has_a_consistent_supported_icon(): void
    {
        $plans = app(PlanCatalog::class)->all();
        $iconsByLabel = [];
        $supported = ['palette','download','lock','share','scan-face','heart','globe','shield-users','sliders','chart-users','users','stamp'];

        foreach ($plans as $plan) {
            foreach ($plan['features'] as $feature) {
                $this->assertContains($feature['icon'], $supported);
                $iconsByLabel[$feature['label']] ??= $feature['icon'];
                $this->assertSame($iconsByLabel[$feature['label']], $feature['icon']);
            }
        }

        $component = file_get_contents(resource_path('js/Pages/Settings/Subscription.vue'));
        $this->assertStringNotContainsString('aria-hidden="true">✓</span>', $component);
        foreach (['name="images"','name="video"','name="refresh"','name="plus-circle"','name="check-circle"',':name="f.icon"','mt-auto pt-6'] as $text) {
            $this->assertStringContainsString($text, $component);
        }
    }

    public function test_free_change_is_scheduled_without_ending_current_access(): void
    {
        $user=User::create(['name'=>'Paid','email'=>'paid-free@example.com','password'=>Hash::make('x'),'status'=>'active','plan'=>'standard','plan_expires_at'=>now()->addMonths(2)]);
        $subscription=$user->subscriptions()->create(['plan'=>'standard','status'=>'active','billing_cycle'=>'quarterly','starts_at'=>now(),'expires_at'=>now()->addMonths(2),'amount'=>2122.82,'currency'=>'INR']);
        $this->actingAs($user)->postJson(route('billing.subscription.schedule-free'))->assertOk();
        $this->assertSame('free',$subscription->fresh()->scheduled_plan);
        $this->assertSame('standard',$user->fresh()->plan);
    }
}
