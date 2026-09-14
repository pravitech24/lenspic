<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RazorpayBillingTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_captured_payment_is_recorded_once(): void
    {
        config()->set('services.razorpay', ['key_id' => 'key', 'key_secret' => 'secret', 'webhook_secret' => 'hook', 'gst_percent' => 18]);
        $user = User::create(['name' => 'Buyer', 'email' => 'buyer@example.com', 'phone' => '+919876543210', 'password' => bcrypt('password')]);
        $amount = 212282;
        Http::fake(function (Request $request) use ($amount) {
            return match (true) {
                $request->method() === 'POST' => Http::response(['id' => 'order_123', 'amount' => $amount, 'currency' => 'INR']),
                str_contains($request->url(), '/payments/') => Http::response(['id' => 'pay_123', 'order_id' => 'order_123', 'amount' => $amount, 'currency' => 'INR', 'status' => 'captured', 'method' => 'upi']),
                default => Http::response(['id' => 'order_123', 'amount' => $amount, 'currency' => 'INR', 'status' => 'paid']),
            };
        });

        $this->actingAs($user)->postJson('/billing/razorpay/order', ['plan' => 'standard', 'cycle' => 'quarterly', 'amount' => 1])->assertOk()->assertJsonPath('amount', $amount)->assertJsonPath('display_name', 'PraviTech')->assertJsonPath('description', 'LensPic Subscription');
        $signature = hash_hmac('sha256', 'order_123|pay_123', 'secret');
        $payload = ['razorpay_payment_id' => 'pay_123', 'razorpay_order_id' => 'order_123', 'razorpay_signature' => $signature, 'plan' => 'premium', 'cycle' => 'yearly'];

        $order = \App\Models\RazorpayOrder::where('provider_order_id', 'order_123')->firstOrFail();
        $this->actingAs($user)->post('/billing/razorpay/verify', $payload)->assertRedirect(route('billing.payment.success', $order->uuid));
        $firstExpiry = $user->refresh()->plan_expires_at;
        $this->assertDatabaseHas('subscriptions', ['payment_id' => 'pay_123', 'plan' => 'standard', 'status' => 'active']);

        $this->actingAs($user)->post('/billing/razorpay/verify', $payload)->assertRedirect(route('billing.payment.success', $order->uuid));
        $this->assertSame(1, $user->subscriptions()->where('payment_id', 'pay_123')->count());
        $this->assertTrue($firstExpiry->equalTo($user->refresh()->plan_expires_at));
    }
}
