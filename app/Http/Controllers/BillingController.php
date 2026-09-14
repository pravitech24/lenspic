<?php

namespace App\Http\Controllers;

use App\Models\RazorpayOrder;
use App\Models\Subscription;
use App\Models\User;
use App\Models\{SubscriptionAddon,SubscriptionFeature,SubscriptionPlan};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;
use App\Services\Billing\PlanCatalog;

class BillingController extends Controller
{
    public static function planCatalog(): array
    {
        return app(PlanCatalog::class)->all();
    }

    public function createOrder(Request $request)
    {
        $validated = $request->validate(['plan' => ['required','string','max:40','regex:/^[a-z0-9_-]+$/'], 'cycle' => ['required', 'in:quarterly,yearly']]);
        if (!$this->credentialsConfigured()) return response()->json(['message' => 'Razorpay credentials are not configured.'], 422);

        if (!$this->paymentsAllowedHere()) return response()->json(['message' => 'Live payments are disabled in this environment.'], 422);
        $plan = app(PlanCatalog::class)->find($validated['plan'], $validated['cycle']);
        $price = $plan['selected_price'];
        $amount = $price['total_amount'];
        $entitlementSnapshot = app(PlanCatalog::class)->purchaseSnapshot($plan);
        try {
            $response = $this->razorpay()->post('https://api.razorpay.com/v1/orders', [
                'amount' => $amount,
                'currency' => 'INR',
                'receipt' => 'plan_'.Str::uuid(),
                'notes' => ['user_id' => (string) Auth::id(), 'plan' => $validated['plan'], 'cycle' => $validated['cycle']],
            ]);
        } catch (Throwable $exception) {
            report($exception);
            return response()->json(['message' => 'Unable to connect to Razorpay right now.'], 422);
        }

        if ($response->failed()) {
            Log::error('Razorpay order creation failed', ['status' => $response->status(), 'user_id' => Auth::id()]);
            return response()->json(['message' => 'Unable to start checkout right now.'], 422);
        }

        $order = $response->json();
        $localOrder = RazorpayOrder::create([
            'uuid' => (string) Str::uuid(), 'user_id' => Auth::id(), 'subscription_plan_id'=>$plan['id'], 'subscription_plan_price_id'=>$price['id'], 'provider_order_id' => $order['id'], 'plan' => $validated['plan'],
            'billing_cycle' => $validated['cycle'], 'amount' => $amount, 'base_amount_paise'=>$price['base_amount'], 'gst_amount_paise'=>$price['gst_amount'], 'currency' => 'INR', 'status' => 'created', 'entitlement_snapshot'=>$entitlementSnapshot, 'expires_at'=>now()->addMinutes(30),
        ]);

        return response()->json([
            'key' => config('services.razorpay.key_id'), 'order_id' => $order['id'], 'amount' => $amount,
            'currency' => 'INR', 'plan' => $validated['plan'], 'cycle' => $validated['cycle'],
            'display_name' => config('services.razorpay.display_name', 'PraviTech'), 'description' => config('services.razorpay.display_description', 'LensPic Subscription'), 'logo_url' => config('services.razorpay.logo_url'),
            'base_amount' => $price['base_amount'], 'gst_amount' => $price['gst_amount'], 'gst_basis_points' => $price['gst_basis_points'],
            'payment_reference' => $localOrder->uuid, 'cancel_url' => route('billing.razorpay.cancel', $localOrder->uuid),
            'prefill' => ['name' => Auth::user()->name, 'email' => Auth::user()->email, 'contact' => Auth::user()->phone],
        ]);
    }

    public function verify(Request $request)
    {
        $validated = $request->validate([
            'razorpay_payment_id' => ['required', 'string', 'max:100'], 'razorpay_order_id' => ['required', 'string', 'max:100'],
            'razorpay_signature' => ['required', 'string', 'size:64'],
        ]);
        if (!$this->credentialsConfigured()) return redirect()->route('pricing')->withErrors('Razorpay credentials are not configured.');

        $localOrder = RazorpayOrder::where('provider_order_id', $validated['razorpay_order_id'])->where('user_id', Auth::id())->first();
        if (!$localOrder) {
            return redirect()->route('pricing')->withErrors('The payment order does not match this account or plan.');
        }
        if($localOrder->expires_at?->isPast()) return redirect()->route('pricing')->withErrors('This payment order has expired. Please start checkout again.');
        $signature = hash_hmac('sha256', $localOrder->provider_order_id.'|'.$validated['razorpay_payment_id'], config('services.razorpay.key_secret'));
        if (!hash_equals($signature, $validated['razorpay_signature'])) return redirect()->route('pricing')->withErrors('Payment verification failed.');

        try {
            $paymentResponse = $this->razorpay()->get('https://api.razorpay.com/v1/payments/'.$validated['razorpay_payment_id']);
            $orderResponse = $this->razorpay()->get('https://api.razorpay.com/v1/orders/'.$localOrder->provider_order_id);
        } catch (Throwable $exception) {
            report($exception);
            return redirect()->route('pricing')->withErrors('Unable to confirm the payment right now.');
        }
        if ($paymentResponse->failed() || $orderResponse->failed()) return redirect()->route('billing.payment.status', $localOrder->uuid)->with('error', 'Payment verification is still processing.');

        $result = $this->activate($localOrder, $paymentResponse->json(), $orderResponse->json());
        if (!$result['ok']) return redirect()->route('billing.payment.status', $localOrder->uuid)->with('error', $result['message']);
        return redirect()->route('billing.payment.success', $localOrder->uuid);
    }

    public function createAddonOrder(Request $request)
    {
        $data=$request->validate(['feature'=>['required','string','max:80','regex:/^[a-z0-9_]+$/']]);
        if(!$this->credentialsConfigured())return response()->json(['message'=>'Razorpay credentials are not configured.'],422);
        $user=$request->user();$subscription=$user->subscriptions()->where('status','active')->where('expires_at','>',now())->latest()->firstOrFail();
        $plan=SubscriptionPlan::whereKey($subscription->subscription_plan_id)->first()?:SubscriptionPlan::where('code',$subscription->plan)->firstOrFail();
        $feature=SubscriptionFeature::where('code',$data['feature'])->where('is_active',true)->firstOrFail();$assignment=$plan->features()->whereKey($feature->id)->first()?->pivot;
        abort_unless($assignment?->is_addon&&$assignment->addon_price_paise,422,'This add-on is not available for the active plan.');
        abort_if(app(\App\Services\Billing\AccountEntitlements::class)->allows($user,$feature->code),422,'This feature is already active.');
        $base=(int)$assignment->addon_price_paise;$rate=$plan->currentPrice($subscription->billing_cycle)?->gst_rate_basis_points??1800;$gst=intdiv($base*$rate+5000,10000);$total=$base+$gst;
        $addon=$user->subscriptionAddons()->create(['subscription_id'=>$subscription->id,'subscription_feature_id'=>$feature->id,'status'=>'pending','base_amount_paise'=>$base,'gst_amount_paise'=>$gst,'total_amount_paise'=>$total]);
        try{$response=$this->razorpay()->post('https://api.razorpay.com/v1/orders',['amount'=>$total,'currency'=>'INR','receipt'=>'addon_'.Str::uuid(),'notes'=>['user_id'=>(string)$user->id,'purchase_type'=>'addon','feature'=>$feature->code]]);}catch(Throwable$e){$addon->update(['status'=>'failed']);report($e);return response()->json(['message'=>'Unable to connect to Razorpay right now.'],422);}if($response->failed()){$addon->update(['status'=>'failed']);return response()->json(['message'=>'Unable to start add-on checkout.'],422);}$order=$response->json();RazorpayOrder::create(['user_id'=>$user->id,'purchase_type'=>'addon','subscription_plan_id'=>$plan->id,'subscription_addon_id'=>$addon->id,'provider_order_id'=>$order['id'],'plan'=>$plan->code,'billing_cycle'=>$subscription->billing_cycle,'amount'=>$total,'base_amount_paise'=>$base,'gst_amount_paise'=>$gst,'currency'=>'INR','status'=>'created','entitlement_snapshot'=>['purchase_type'=>'addon','addon_id'=>$addon->id,'feature_code'=>$feature->code,'total_amount_paise'=>$total,'expires_at'=>$subscription->expires_at->toIso8601String()],'expires_at'=>now()->addMinutes(30)]);return response()->json(['key'=>config('services.razorpay.key_id'),'order_id'=>$order['id'],'amount'=>$total,'currency'=>'INR','display_name'=>config('services.razorpay.display_name','PraviTech'),'description'=>config('services.razorpay.display_description','LensPic Subscription'),'logo_url'=>config('services.razorpay.logo_url'),'base_amount'=>$base,'gst_amount'=>$gst,'gst_basis_points'=>$rate]);
    }

    public function webhook(Request $request)
    {
        $secret = config('services.razorpay.webhook_secret');
        $received = (string) $request->header('X-Razorpay-Signature');
        if (!$secret || !$received || !hash_equals(hash_hmac('sha256', $request->getContent(), $secret), $received)) {
            return response()->json(['message' => 'Invalid webhook signature.'], 401);
        }
        $payload = $request->json()->all();
        $event = $payload['event'] ?? null;
        if (!in_array($event, ['payment.captured', 'payment.failed', 'order.paid'], true)) return response()->json(['message' => 'Event ignored.']);
        $payment = data_get($payload, 'payload.payment.entity');
        $providerOrderId = $payment['order_id'] ?? data_get($payload, 'payload.order.entity.id');
        $localOrder = RazorpayOrder::where('provider_order_id', $providerOrderId)->first();
        if (!$localOrder) return response()->json(['message' => 'Order not found.'], 404);

        if ($event === 'payment.failed') {
            if ($localOrder->status !== 'paid') $localOrder->update(['status' => 'failed', 'failed_at' => now(), 'failure_reason' => Str::limit((string) data_get($payment, 'error_description', 'Payment failed.'), 1000)]);
            return response()->json(['message' => 'Failure recorded.']);
        }
        if (!$payment) return response()->json(['message' => 'Payment reconciliation is pending.'], 202);

        try {
            $orderResponse = $this->razorpay()->get('https://api.razorpay.com/v1/orders/'.$localOrder->provider_order_id);
        } catch (Throwable $exception) {
            report($exception);
            return response()->json(['message' => 'Temporary gateway error.'], 503);
        }
        if ($orderResponse->failed()) return response()->json(['message' => 'Could not verify order.'], 503);
        $result = $this->activate($localOrder, $payment, $orderResponse->json());
        return response()->json(['message' => $result['message']], $result['ok'] ? 200 : 422);
    }

    public function scheduleFree(Request $request)
    {
        $subscription = $request->user()->subscriptions()->where('status', 'active')->where('expires_at', '>', now())->latest()->firstOrFail();
        $subscription->update(['scheduled_plan'=>'free','scheduled_change_at'=>$subscription->expires_at]);
        return response()->json(['message'=>'Switch to Free is scheduled for '.$subscription->expires_at->toDateString().'. Your current access remains active until then.']);
    }

    private function activate(RazorpayOrder $localOrder, array $payment, array $order): array
    {
        if (($payment['id'] ?? null) === null || ($payment['status'] ?? null) !== 'captured') return ['ok' => false, 'message' => 'Payment has not been captured.'];
        if (($payment['order_id'] ?? null) !== $localOrder->provider_order_id || (int) ($payment['amount'] ?? 0) !== $localOrder->amount || ($payment['currency'] ?? null) !== $localOrder->currency) {$localOrder->update(['status'=>'failed','failed_at'=>now(),'failure_reason'=>'Payment details did not match the locally created order.']);return ['ok' => false, 'message' => 'Payment details do not match the order.'];}
        if (($order['id'] ?? null) !== $localOrder->provider_order_id || (int) ($order['amount'] ?? 0) !== $localOrder->amount || ($order['currency'] ?? null) !== $localOrder->currency) {$localOrder->update(['status'=>'failed','failed_at'=>now(),'failure_reason'=>'Razorpay order details did not match the local order.']);return ['ok' => false, 'message' => 'The Razorpay order does not match.'];}
        if (($order['status'] ?? null) !== 'paid') return ['ok' => false, 'message' => 'The Razorpay order is not paid.'];

        return DB::transaction(function () use ($localOrder, $payment) {
            $lockedOrder = RazorpayOrder::lockForUpdate()->findOrFail($localOrder->id);
            if($lockedOrder->purchase_type==='addon'){$addon=SubscriptionAddon::lockForUpdate()->whereKey($lockedOrder->subscription_addon_id)->where('user_id',$lockedOrder->user_id)->firstOrFail();if($addon->status==='active')return['ok'=>true,'message'=>'This add-on payment was already processed.'];$subscription=$addon->subscription_id?Subscription::whereKey($addon->subscription_id)->where('status','active')->first():null;abort_unless($subscription&&$subscription->expires_at?->isFuture(),422,'The subscription for this add-on is no longer active.');$addon->update(['status'=>'active','payment_id'=>$payment['id'],'starts_at'=>now(),'expires_at'=>$subscription->expires_at]);$lockedOrder->update(['status'=>'paid','payment_id'=>$payment['id']]);return['ok'=>true,'message'=>'Payment successful. The add-on is now active until your subscription expires.'];}
            $existing = Subscription::where('payment_id', $payment['id'])->first();
            if ($existing) return ['ok' => true, 'message' => 'This payment was already processed. Your plan is active.'];
            $user = User::lockForUpdate()->findOrFail($lockedOrder->user_id);
            $startsAt = now();
            $base = $user->plan_expires_at?->isFuture() ? $user->plan_expires_at->copy() : $startsAt->copy();
            $snapshot=$lockedOrder->entitlement_snapshot;
            abort_unless(is_array($snapshot)&&($snapshot['total_amount_paise']??null)===$lockedOrder->amount,422,'The purchased entitlement snapshot is invalid.');
            $validityMonths = (int) $snapshot['validity_months'];
            $expiresAt = $base->addMonthsNoOverflow($validityMonths);
            $user->subscriptions()->where('status', 'active')->update(['status' => 'inactive']);
            $user->subscriptions()->create([
                'subscription_plan_id'=>$lockedOrder->subscription_plan_id,'plan' => $lockedOrder->plan, 'plan_version'=>$snapshot['plan_version'], 'starts_at' => $startsAt, 'expires_at' => $expiresAt,
                'amount' => $lockedOrder->amount / 100, 'currency' => $lockedOrder->currency,
                'payment_id' => $payment['id'], 'provider_order_id' => $lockedOrder->provider_order_id,
                'billing_cycle' => $lockedOrder->billing_cycle, 'status' => 'active',
                'notes' => json_encode(['method' => $payment['method'] ?? null]),
            ]);
            $subscription=$user->subscriptions()->where('payment_id',$payment['id'])->firstOrFail();
            $subscription->entitlementSnapshot()->create(['subscription_plan_id'=>$snapshot['subscription_plan_id'],'plan_code'=>$snapshot['plan_code'],'plan_name'=>$snapshot['plan_name'],'plan_version'=>$snapshot['plan_version'],'billing_interval'=>$snapshot['billing_interval'],'base_amount_paise'=>$snapshot['base_amount_paise'],'gst_amount_paise'=>$snapshot['gst_amount_paise'],'total_amount_paise'=>$snapshot['total_amount_paise'],'currency'=>$snapshot['currency'],'photo_limit'=>$snapshot['photo_limit'],'photo_reuse_limit'=>$snapshot['photo_reuse_limit'],'video_limit_mb'=>$snapshot['video_limit_mb'],'team_seat_limit'=>$snapshot['team_seat_limit'],'group_limit'=>$snapshot['group_limit'],'guest_limit'=>$snapshot['guest_limit'],'features'=>$snapshot['features'],'addons'=>$snapshot['addons'],'starts_at'=>$startsAt,'expires_at'=>$expiresAt]);
            $user->update(['plan' => $lockedOrder->plan, 'plan_expires_at' => $expiresAt]);
            $lockedOrder->update(['status' => 'paid', 'payment_id' => $payment['id'], 'paid_at' => $lockedOrder->paid_at ?: now(), 'failure_reason' => null]);
            app(\App\Services\AuditLogger::class)->log('subscription.payment_activated', $lockedOrder, null, [], ['status' => 'paid'], ['plan' => $lockedOrder->plan, 'billing_cycle' => $lockedOrder->billing_cycle, 'amount_paise' => $lockedOrder->amount]);
            return ['ok' => true, 'message' => 'Payment successful. Your '.ucfirst($lockedOrder->plan).' plan is now active.'];
        });
    }

    private function razorpay()
    {
        return Http::withBasicAuth(config('services.razorpay.key_id'), config('services.razorpay.key_secret'))->acceptJson()->timeout(15)->retry(2, 300);
    }

    private function credentialsConfigured(): bool
    {
        return filled(config('services.razorpay.key_id')) && filled(config('services.razorpay.key_secret'));
    }

    private function amountWithTax(int $baseAmount): int
    {
        return (int) round($baseAmount * (1 + config('services.razorpay.gst_percent', 18) / 100));
    }

    private function paymentsAllowedHere(): bool
    {
        return config('billing.mode') !== 'live' || app()->environment('production') || config('billing.allow_live_outside_production');
    }
}
