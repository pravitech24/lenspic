<?php

namespace Tests\Feature;

use App\Models\{RazorpayOrder, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\{Hash, Http};
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PaymentSuccessFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.razorpay.key_id'=>'key','services.razorpay.key_secret'=>'secret','services.razorpay.webhook_secret'=>'hook','services.razorpay.display_name'=>'PraviTech','services.razorpay.display_description'=>'LensPic Subscription']);
    }

    private function user(string $email='payment-success@test.local'): User
    {
        return User::create(['name'=>'LensPic Buyer','email'=>$email,'password'=>Hash::make('password'),'status'=>'active','account_type'=>'photographer','plan'=>'free']);
    }

    private function order(User $user, string $cycle='quarterly'): RazorpayOrder
    {
        Http::fake(['api.razorpay.com/v1/orders'=>Http::response(['id'=>'order_'.$cycle.'_'.str_replace('@','_',$user->email),'status'=>'created','currency'=>'INR'])]);
        $this->actingAs($user)->postJson(route('billing.razorpay.order'),['plan'=>'essential','cycle'=>$cycle])->assertOk();
        return RazorpayOrder::where('user_id',$user->id)->latest()->firstOrFail();
    }

    private function gateway(RazorpayOrder $order, ?int $paymentAmount=null): void
    {
        Http::fake(function(Request $request)use($order,$paymentAmount){
            if(str_contains($request->url(),'/payments/'))return Http::response(['id'=>'pay_success','order_id'=>$order->provider_order_id,'amount'=>$paymentAmount??$order->amount,'currency'=>$order->currency,'status'=>'captured','method'=>'upi']);
            return Http::response(['id'=>$order->provider_order_id,'amount'=>$order->amount,'currency'=>$order->currency,'status'=>'paid']);
        });
    }

    private function callbackPayload(RazorpayOrder $order, string $signature=null): array
    {
        return ['razorpay_payment_id'=>'pay_success','razorpay_order_id'=>$order->provider_order_id,'razorpay_signature'=>$signature??hash_hmac('sha256',$order->provider_order_id.'|pay_success','secret')];
    }

    public function test_verified_payment_redirects_to_owned_success_page_and_refresh_is_idempotent(): void
    {
        $user=$this->user();$order=$this->order($user);$this->gateway($order);
        $this->post(route('billing.razorpay.verify'),$this->callbackPayload($order))->assertRedirect(route('billing.payment.success',$order->uuid));
        $this->get(route('billing.payment.success',$order->uuid))->assertOk()->assertInertia(fn(Assert$page)=>$page->component('Billing/PaymentStatus')->where('payment.status','paid')->where('payment.plan','Essential')->where('payment.total_amount','₹4,246.82')->where('payment.base_amount','₹3,599.00')->where('payment.gst_amount','₹647.82')->where('payment.payment_id','pay_success'));
        $this->get(route('billing.payment.success',$order->uuid))->assertOk();
        $this->post(route('billing.razorpay.verify'),$this->callbackPayload($order))->assertRedirect(route('billing.payment.success',$order->uuid));
        $this->assertSame(1,$user->subscriptions()->count());
        $this->assertDatabaseHas('audit_logs',['action'=>'subscription.payment_activated','subject_id'=>$order->id]);
    }

    public function test_invalid_signature_never_activates(): void
    {
        $user=$this->user('invalid-payment@test.local');$order=$this->order($user);$this->gateway($order);
        $this->post(route('billing.razorpay.verify'),$this->callbackPayload($order,str_repeat('0',64)))->assertRedirect(route('pricing'));
        $this->assertSame(0,$user->subscriptions()->count());
    }

    public function test_amount_mismatch_never_activates(): void
    {
        $user=$this->user('amount-mismatch@test.local');$order=$this->order($user);$this->gateway($order,$order->amount+1);
        $this->post(route('billing.razorpay.verify'),$this->callbackPayload($order))->assertRedirect(route('billing.payment.status',$order->uuid));
        $this->assertSame('failed',$order->fresh()->status);$this->assertSame(0,$user->subscriptions()->count());
    }

    public function test_order_id_mismatch_is_rejected(): void
    {
        $user=$this->user('order-mismatch@test.local');$order=$this->order($user);
        $payload=$this->callbackPayload($order);$payload['razorpay_order_id']='order_not_owned';
        $this->post(route('billing.razorpay.verify'),$payload)->assertRedirect(route('pricing'));
        $this->assertSame(0,$user->subscriptions()->count());
    }

    public function test_success_status_and_receipt_are_private_and_unpaid_order_is_not_successful(): void
    {
        $owner=$this->user('owner-payment@test.local');$order=$this->order($owner);$other=$this->user('other-payment@test.local');
        $this->actingAs($other)->get(route('billing.payment.success',$order->uuid))->assertNotFound();
        auth()->logout();$this->get(route('billing.payment.success',$order->uuid))->assertRedirect(route('login'));
        $this->actingAs($owner)->get(route('billing.payment.success',$order->uuid))->assertInertia(fn(Assert$page)=>$page->where('payment.status','created')->where('payment.payment_id',null)->where('payment.receipt_url',null));
        $this->get(route('billing.payment.receipt',$order->uuid))->assertNotFound();
        $this->post(route('billing.razorpay.cancel',$order->uuid))->assertNoContent();
        $this->get(route('billing.payment.status',$order->uuid))->assertInertia(fn(Assert$page)=>$page->where('payment.status','cancelled'));
    }

    public function test_failed_webhook_is_signed_and_idempotent(): void
    {
        $user=$this->user('failed-webhook@test.local');$order=$this->order($user);$payload=['event'=>'payment.failed','payload'=>['payment'=>['entity'=>['id'=>'pay_failed','order_id'=>$order->provider_order_id,'error_description'=>'Bank rejected payment']]]];$raw=json_encode($payload);$signature=hash_hmac('sha256',$raw,'hook');
        for($i=0;$i<2;$i++)$this->call('POST',route('billing.razorpay.webhook'),[],[],[],['CONTENT_TYPE'=>'application/json','HTTP_X_RAZORPAY_SIGNATURE'=>$signature],$raw)->assertOk();
        $this->assertSame('failed',$order->fresh()->status);$this->assertSame(0,$user->subscriptions()->count());
    }

    public function test_duplicate_captured_webhooks_activate_exactly_once(): void
    {
        $user=$this->user('captured-webhook@test.local');$order=$this->order($user);$this->gateway($order);$payment=['id'=>'pay_success','order_id'=>$order->provider_order_id,'amount'=>$order->amount,'currency'=>$order->currency,'status'=>'captured','method'=>'upi'];$payload=['event'=>'payment.captured','payload'=>['payment'=>['entity'=>$payment]]];$raw=json_encode($payload);$signature=hash_hmac('sha256',$raw,'hook');
        for($i=0;$i<2;$i++)$this->call('POST',route('billing.razorpay.webhook'),[],[],[],['CONTENT_TYPE'=>'application/json','HTTP_X_RAZORPAY_SIGNATURE'=>$signature],$raw)->assertOk();
        $this->assertSame(1,$user->subscriptions()->count());$this->assertSame('paid',$order->fresh()->status);
    }

    public function test_quarterly_validity_and_entitlements_come_from_database_catalog(): void{$this->assertCatalogActivation('quarterly',3);}
    public function test_yearly_validity_and_entitlements_come_from_database_catalog(): void{$this->assertCatalogActivation('yearly',12);}
    private function assertCatalogActivation(string$cycle,int$months):void{$user=$this->user($cycle.'@validity.test');$order=$this->order($user,$cycle);$this->gateway($order);$this->post(route('billing.razorpay.verify'),$this->callbackPayload($order));$subscription=$user->subscriptions()->firstOrFail();$this->assertTrue($subscription->starts_at->copy()->addMonthsNoOverflow($months)->equalTo($subscription->expires_at));$this->assertSame($order->entitlement_snapshot['features'],$subscription->entitlementSnapshot->features);}
}
