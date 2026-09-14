<?php

namespace App\Http\Controllers;

use App\Models\{User, WalletTopUp};
use App\Services\{AuditLogger};
use App\Services\Team\TeamAuthorization;
use App\Services\Wallet\WalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, Http, Log};
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class WalletController extends Controller
{
    private function owner(): User
    {
        $actor = Auth::user();
        $owner = app(TeamAuthorization::class)->ownerFor($actor) ?? $actor;
        abort_unless(app(TeamAuthorization::class)->allows($actor, $owner, 'manage_wallet'), 403);
        return $owner;
    }

    public function index(Request $request, WalletService $wallets)
    {
        $owner = $this->owner();
        $wallet = $wallets->for($owner);
        $entries = $wallet->entries()->latest('occurred_at')
            ->when($request->type, fn ($query, $value) => $query->where('entry_type', $value))
            ->when($request->search, fn ($query, $value) => $query->where(fn ($nested) => $nested->where('description', 'like', '%'.addcslashes($value, '%_').'%')->orWhere('reference_id', 'like', '%'.addcslashes($value, '%_').'%')))
            ->paginate(20)->withQueryString();

        return Inertia::render('Settings/Wallet', [
            'section' => 'wallet', 'balance' => $wallets->format($wallet->available_credit_units), 'balanceUnits' => $wallet->available_credit_units,
            'rates' => config('wallet.usage_rates'), 'scale' => config('wallet.credit_scale'), 'suggestions' => array_map(fn ($value) => intdiv($value, 100), config('wallet.suggested_top_ups_minor')),
            'gstBasisPoints' => config('wallet.gst_basis_points'), 'paymentConfigured' => filled(config('services.razorpay.key_id')),
            'entries' => $entries->through(fn ($entry) => ['id' => $entry->uuid, 'date' => $entry->occurred_at->toIso8601String(), 'description' => $entry->description, 'added' => $entry->direction === 'credit' ? $wallets->format($entry->credit_units) : null, 'used' => $entry->direction === 'debit' ? $wallets->format($entry->credit_units) : null, 'balance' => $wallets->format($entry->balance_after_units), 'reference' => $entry->reference_id]),
            'unsupported' => ['mail' => 'No billable delivery-provider workflow exists; account and security email remains uncharged.', 'whatsapp' => 'Only authentication OTP messaging exists and remains uncharged.', 'liveness' => 'No external liveness provider is configured; face matching remains uncharged.', 'subscription' => 'Subscriptions currently use the existing Razorpay Plans checkout, not Wallet credits.'],
        ]);
    }

    public function store(Request $request, WalletService $wallets, AuditLogger $audit)
    {
        $owner = $this->owner();
        $data = $request->validate(['amount_rupees' => ['required', 'integer', 'min:5', 'max:100000'], 'idempotency_key' => ['required', 'uuid']]);
        abort_unless(filled(config('services.razorpay.key_id')) && filled(config('services.razorpay.key_secret')), 422, 'Razorpay is not configured.');
        $topUp = $wallets->pending($owner, $data['amount_rupees'] * 100, $data['idempotency_key']);
        if (! $topUp->provider_order_id) {
            $response = Http::withBasicAuth(config('services.razorpay.key_id'), config('services.razorpay.key_secret'))->acceptJson()->post('https://api.razorpay.com/v1/orders', ['amount' => $topUp->total_amount_minor, 'currency' => $topUp->currency, 'receipt' => 'wallet_'.$topUp->uuid, 'notes' => ['wallet_top_up_uuid' => $topUp->uuid, 'studio_owner_id' => (string) $owner->id]]);
            if ($response->failed()) {
                Log::error('Wallet Razorpay order creation failed', ['status' => $response->status(), 'studio_owner_id' => $owner->id]);
                throw ValidationException::withMessages(['amount_rupees' => 'Unable to start checkout right now.']);
            }
            $topUp->update(['provider_order_id' => $response->json('id'), 'status' => 'processing']);
        }
        $audit->log('wallet.top_up_initiated', $topUp, null, [], [], ['studio_owner_id' => $owner->id, 'credit_units' => $topUp->credit_units, 'money_amount_minor' => $topUp->base_amount_minor]);

        return response()->json([
            'key' => config('services.razorpay.key_id'), 'order_id' => $topUp->provider_order_id, 'amount' => $topUp->total_amount_minor, 'currency' => $topUp->currency,
            'top_up' => $topUp->uuid, 'credits' => $wallets->format($topUp->credit_units), 'name' => config('services.razorpay.display_name', 'PraviTech'),
            'description' => 'LensPic Wallet Top-up', 'image' => config('services.razorpay.logo_url'), 'prefill' => ['name' => $owner->name, 'email' => $owner->email, 'contact' => $owner->phone],
        ]);
    }

    public function show(WalletTopUp $topUp)
    {
        $owner = $this->owner();
        abort_unless($topUp->studio_owner_id === $owner->id, 404);
        return response()->json(['status' => $topUp->status, 'message' => match ($topUp->status) {'paid' => 'Credits added successfully.', 'failed', 'cancelled' => 'Payment was not completed. No credits were added.', default => 'Payment is awaiting confirmation.'}]);
    }
}
