<?php

namespace App\Http\Controllers;

use App\Models\RazorpayOrder;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PaymentStatusController extends Controller
{
    public function show(Request $request, RazorpayOrder $order)
    {
        $this->owned($request, $order);
        $order->load('subscription.entitlementSnapshot');
        return Inertia::render('Billing/PaymentStatus', ['payment' => $this->present($order)]);
    }

    public function receipt(Request $request, RazorpayOrder $order)
    {
        $this->owned($request, $order);
        abort_unless($order->status === 'paid', 404);
        $order->load('user', 'subscription.entitlementSnapshot');
        return response()->view('billing.receipt', ['order' => $order, 'payment' => $this->present($order), 'merchant' => config('services.razorpay.display_name', 'PraviTech')], 200, ['Cache-Control' => 'private, no-store']);
    }

    public function cancel(Request $request, RazorpayOrder $order)
    {
        $this->owned($request, $order);
        if ($order->status === 'created') $order->update(['status' => 'cancelled', 'cancelled_at' => now()]);
        return response()->noContent();
    }

    private function owned(Request $request, RazorpayOrder $order): void
    {
        abort_unless($order->user_id === $request->user()->id, 404);
    }

    private function present(RazorpayOrder $order): array
    {
        $snapshot = $order->entitlement_snapshot ?: [];
        $subscription = $order->subscription;
        return [
            'reference' => $order->uuid, 'status' => $order->status, 'plan' => $snapshot['plan_name'] ?? ucfirst($order->plan),
            'billing_cycle' => ucfirst($order->billing_cycle), 'base_amount' => $this->money((int) $order->base_amount_paise),
            'gst_amount' => $this->money((int) $order->gst_amount_paise), 'gst_rate' => $order->base_amount_paise ? round($order->gst_amount_paise * 100 / $order->base_amount_paise, 2) : 0,
            'total_amount' => $this->money((int) $order->amount), 'payment_id' => $order->status === 'paid' ? $order->payment_id : null,
            'payment_at' => $order->paid_at?->toIso8601String(), 'activation_at' => $subscription?->starts_at?->toIso8601String(),
            'expires_at' => $subscription?->expires_at?->toIso8601String(), 'failure_reason' => $order->status === 'failed' ? $order->failure_reason : null,
            'dashboard_url' => route('dashboard'), 'subscription_url' => route('settings.subscription'),
            'receipt_url' => $order->status === 'paid' ? route('billing.payment.receipt', $order->uuid) : null,
        ];
    }

    private function money(int $paise): string
    {
        return '₹'.number_format($paise / 100, 2, '.', ',');
    }
}
