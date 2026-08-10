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
        $amount = 117882;
        Http::fake(function (Request $request) use ($amount) {
            return match (true) {
                $request->method() === 'POST' => Http::response(['id' => 'order_123', 'amount' => $amount, 'currency' => 'INR']),
                str_contains($request->url(), '/payments/') => Http::response(['id' => 'pay_123', 'order_id' => 'order_123', 'amount' => $amount, 'currency' => 'INR', 'status' => 'captured', 'method' => 'upi']),
                default => Http::response(['id' => 'order_123', 'amount' => $amount, 'currency' => 'INR', 'status' => 'paid']),
            };
        });

        $this->actingAs($user)->postJson('/billing/razorpay/order', ['plan' => 'basic', 'cycle' => 'quarterly'])->assertOk()->assertJsonPath('amount', $amount);
        $signature = hash_hmac('sha256', 'order_123|pay_123', 'secret');
        $payload = ['razorpay_payment_id' => 'pay_123', 'razorpay_order_id' => 'order_123', 'razorpay_signature' => $signature, 'plan' => 'basic', 'cycle' => 'quarterly'];

        $this->actingAs($user)->post('/billing/razorpay/verify', $payload)->assertRedirect('/settings/subscription');
        $firstExpiry = $user->refresh()->plan_expires_at;
        $this->assertDatabaseHas('subscriptions', ['payment_id' => 'pay_123', 'plan' => 'basic', 'status' => 'active']);

        $this->actingAs($user)->post('/billing/razorpay/verify', $payload)->assertRedirect('/settings/subscription');
        $this->assertSame(1, $user->subscriptions()->where('payment_id', 'pay_123')->count());
        $this->assertTrue($firstExpiry->equalTo($user->refresh()->plan_expires_at));
    }
}
