<?php

namespace App\Http\Controllers;

use App\Models\RazorpayOrder;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class BillingController extends Controller
{
    private const PLANS = [
        'basic' => ['quarterly' => ['amount' => 99900, 'label' => 'Basic Quarterly'], 'yearly' => ['amount' => 299900, 'label' => 'Basic Yearly']],
        'standard' => ['quarterly' => ['amount' => 179900, 'label' => 'Standard Quarterly'], 'yearly' => ['amount' => 599900, 'label' => 'Standard Yearly']],
        'essential' => ['quarterly' => ['amount' => 359900, 'label' => 'Essential Quarterly'], 'yearly' => ['amount' => 1199900, 'label' => 'Essential Yearly']],
        'premium' => ['quarterly' => ['amount' => 689900, 'label' => 'Premium Quarterly'], 'yearly' => ['amount' => 2299900, 'label' => 'Premium Yearly']],
    ];

    public function createOrder(Request $request)
    {
        $validated = $request->validate(['plan' => ['required', 'in:basic,standard,essential,premium'], 'cycle' => ['required', 'in:quarterly,yearly']]);
        if (!$this->credentialsConfigured()) return response()->json(['message' => 'Razorpay credentials are not configured.'], 422);

        $plan = self::PLANS[$validated['plan']][$validated['cycle']];
        $amount = $this->amountWithTax($plan['amount']);
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
        RazorpayOrder::create([
            'user_id' => Auth::id(), 'provider_order_id' => $order['id'], 'plan' => $validated['plan'],
            'billing_cycle' => $validated['cycle'], 'amount' => $amount, 'currency' => 'INR', 'status' => 'created',
        ]);

        return response()->json([
            'key' => config('services.razorpay.key_id'), 'order_id' => $order['id'], 'amount' => $amount,
            'currency' => 'INR', 'plan' => $validated['plan'], 'cycle' => $validated['cycle'],
            'name' => config('app.name', 'Kwikpic'), 'description' => $plan['label'].' (including GST)',
            'prefill' => ['name' => Auth::user()->name, 'email' => Auth::user()->email, 'contact' => Auth::user()->phone],
        ]);
    }

    public function verify(Request $request)
    {
        $validated = $request->validate([
            'razorpay_payment_id' => ['required', 'string', 'max:100'], 'razorpay_order_id' => ['required', 'string', 'max:100'],
            'razorpay_signature' => ['required', 'string', 'size:64'], 'plan' => ['required', 'in:basic,standard,essential,premium'],
            'cycle' => ['required', 'in:quarterly,yearly'],
        ]);
        if (!$this->credentialsConfigured()) return redirect()->route('pricing')->withErrors('Razorpay credentials are not configured.');

        $localOrder = RazorpayOrder::where('provider_order_id', $validated['razorpay_order_id'])->where('user_id', Auth::id())->first();
        if (!$localOrder || $localOrder->plan !== $validated['plan'] || $localOrder->billing_cycle !== $validated['cycle']) {
            return redirect()->route('pricing')->withErrors('The payment order does not match this account or plan.');
        }
        $signature = hash_hmac('sha256', $localOrder->provider_order_id.'|'.$validated['razorpay_payment_id'], config('services.razorpay.key_secret'));
        if (!hash_equals($signature, $validated['razorpay_signature'])) return redirect()->route('pricing')->withErrors('Payment verification failed.');

        try {
            $paymentResponse = $this->razorpay()->get('https://api.razorpay.com/v1/payments/'.$validated['razorpay_payment_id']);
            $orderResponse = $this->razorpay()->get('https://api.razorpay.com/v1/orders/'.$localOrder->provider_order_id);
        } catch (Throwable $exception) {
            report($exception);
            return redirect()->route('pricing')->withErrors('Unable to confirm the payment right now.');
        }
        if ($paymentResponse->failed() || $orderResponse->failed()) return redirect()->route('pricing')->withErrors('Unable to confirm the payment.');

        $result = $this->activate($localOrder, $paymentResponse->json(), $orderResponse->json());
        if (!$result['ok']) return redirect()->route('pricing')->withErrors($result['message']);
        return redirect()->route('settings.subscription')->with('success', $result['message']);
    }

    public function webhook(Request $request)
    {
        $secret = config('services.razorpay.webhook_secret');
        $received = (string) $request->header('X-Razorpay-Signature');
        if (!$secret || !$received || !hash_equals(hash_hmac('sha256', $request->getContent(), $secret), $received)) {
            return response()->json(['message' => 'Invalid webhook signature.'], 401);
        }
        $payload = $request->json()->all();
        if (($payload['event'] ?? null) !== 'payment.captured') return response()->json(['message' => 'Event ignored.']);
        $payment = data_get($payload, 'payload.payment.entity');
        $localOrder = RazorpayOrder::where('provider_order_id', $payment['order_id'] ?? null)->first();
        if (!$localOrder) return response()->json(['message' => 'Order not found.'], 404);

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

    private function activate(RazorpayOrder $localOrder, array $payment, array $order): array
    {
        if (($payment['id'] ?? null) === null || ($payment['status'] ?? null) !== 'captured') return ['ok' => false, 'message' => 'Payment has not been captured.'];
        if (($payment['order_id'] ?? null) !== $localOrder->provider_order_id || (int) ($payment['amount'] ?? 0) !== $localOrder->amount || ($payment['currency'] ?? null) !== $localOrder->currency) return ['ok' => false, 'message' => 'Payment details do not match the order.'];
        if (($order['id'] ?? null) !== $localOrder->provider_order_id || (int) ($order['amount'] ?? 0) !== $localOrder->amount || ($order['currency'] ?? null) !== $localOrder->currency || ($order['status'] ?? null) !== 'paid') return ['ok' => false, 'message' => 'The Razorpay order is not paid.'];

        return DB::transaction(function () use ($localOrder, $payment) {
            $lockedOrder = RazorpayOrder::lockForUpdate()->findOrFail($localOrder->id);
            $existing = Subscription::where('payment_id', $payment['id'])->first();
            if ($existing) return ['ok' => true, 'message' => 'This payment was already processed. Your plan is active.'];
            $user = User::lockForUpdate()->findOrFail($lockedOrder->user_id);
            $startsAt = now();
            $base = $user->plan_expires_at?->isFuture() ? $user->plan_expires_at->copy() : $startsAt->copy();
            $expiresAt = $lockedOrder->billing_cycle === 'yearly' ? $base->addYearNoOverflow() : $base->addMonthsNoOverflow(3);
            $user->subscriptions()->where('status', 'active')->update(['status' => 'inactive']);
            $user->subscriptions()->create([
                'plan' => $lockedOrder->plan, 'starts_at' => $startsAt, 'expires_at' => $expiresAt,
                'amount' => $lockedOrder->amount / 100, 'currency' => $lockedOrder->currency,
                'payment_id' => $payment['id'], 'provider_order_id' => $lockedOrder->provider_order_id,
                'billing_cycle' => $lockedOrder->billing_cycle, 'status' => 'active',
                'notes' => json_encode(['method' => $payment['method'] ?? null]),
            ]);
            $user->update(['plan' => $lockedOrder->plan, 'plan_expires_at' => $expiresAt]);
            $lockedOrder->update(['status' => 'paid', 'payment_id' => $payment['id']]);
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
}
