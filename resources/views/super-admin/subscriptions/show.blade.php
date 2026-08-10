@extends('super-admin.layout')
@section('title', 'Subscription Details')

@section('content')
<div class="page-header">
  <div class="page-title">
    <i class="fa-solid fa-receipt" style="margin-right:.5rem;"></i>
    Subscription #{{ $subscription->id }}
  </div>
  <div>
    <a href="{{ route('super-admin.subscriptions') }}" class="btn btn-outline"><i class="fa-solid fa-arrow-left"></i> Back</a>
  </div>
</div>

<div class="grid grid-2">
  <!-- User Info -->
  <div class="card">
    <div class="card-header">User Information</div>
    <div class="card-body">
      <div style="display:flex;align-items:center;gap:1rem;margin-bottom:1.5rem;">
        <img src="{{ $subscription->user->profile_photo_url }}" class="avatar" style="width:48px;height:48px;">
        <div>
          <div style="font-weight:600;">{{ $subscription->user->name }}</div>
          <div style="font-size:13px;color:var(--muted);">{{ $subscription->user->email }}</div>
        </div>
      </div>
      <a href="{{ route('super-admin.users.show', $subscription->user) }}" class="btn btn-primary btn-sm" style="width:100%;text-align:center;">
        <i class="fa-solid fa-eye"></i> View User Profile
      </a>
    </div>
  </div>

  <!-- Subscription Status -->
  <div class="card">
    <div class="card-header">Status</div>
    <div class="card-body">
      <div style="margin-bottom:1rem;">
        <div style="font-size:12px;color:var(--muted);margin-bottom:.5rem;">Current Status</div>
        <div>
          <span class="badge badge-{{ $subscription->status === 'active' ? 'green' : ($subscription->status === 'suspended' ? 'yellow' : 'red') }}" style="font-size:13px;padding:.4rem .75rem;">
            {{ ucfirst($subscription->status) }}
          </span>
        </div>
      </div>
      <div>
        <div style="font-size:12px;color:var(--muted);margin-bottom:.5rem;">Active</div>
        <div>{{ $subscription->isActive() ? '✓ Yes' : '✗ No' }}</div>
      </div>
    </div>
  </div>
</div>

<!-- Subscription Details -->
<div class="card" style="margin-top:1.5rem;">
  <div class="card-header">Subscription Details</div>
  <div class="card-body">
    <div class="grid grid-4">
      <div>
        <div style="font-size:12px;color:var(--muted);margin-bottom:.5rem;">Plan</div>
        <div><span class="badge badge-blue">{{ ucfirst($subscription->plan) }}</span></div>
      </div>
      <div>
        <div style="font-size:12px;color:var(--muted);margin-bottom:.5rem;">Amount</div>
        <div style="font-weight:600;font-size:1.1rem;">${{ number_format($subscription->amount, 2) }}</div>
      </div>
      <div>
        <div style="font-size:12px;color:var(--muted);margin-bottom:.5rem;">Payment ID</div>
        <div style="font-family:monospace;font-size:12px;">{{ $subscription->payment_id ?? '-' }}</div>
      </div>
      <div>
        <div style="font-size:12px;color:var(--muted);margin-bottom:.5rem;">ID</div>
        <div style="font-family:monospace;font-size:12px;">{{ $subscription->id }}</div>
      </div>
    </div>
  </div>
</div>

<!-- Dates -->
<div class="card" style="margin-top:1.5rem;">
  <div class="card-header">Timeline</div>
  <div class="card-body">
    <div class="grid grid-3">
      <div>
        <div style="font-size:12px;color:var(--muted);margin-bottom:.5rem;">Started At</div>
        <div>
          @if($subscription->starts_at)
            @if(is_object($subscription->starts_at))
              {{ $subscription->starts_at->format('d M Y, H:i') }}
            @else
              {{ \Carbon\Carbon::parse($subscription->starts_at)->format('d M Y, H:i') }}
            @endif
          @else
            -
          @endif
        </div>
      </div>
      <div>
        <div style="font-size:12px;color:var(--muted);margin-bottom:.5rem;">Expires At</div>
        <div>
          @if($subscription->expires_at)
            @if(is_object($subscription->expires_at))
              {{ $subscription->expires_at->format('d M Y, H:i') }}
            @else
              {{ \Carbon\Carbon::parse($subscription->expires_at)->format('d M Y, H:i') }}
            @endif
          @else
            Never
          @endif
        </div>
      </div>
      <div>
        <div style="font-size:12px;color:var(--muted);margin-bottom:.5rem;">Created</div>
        <div>
          @if(is_object($subscription->created_at))
            {{ $subscription->created_at->format('d M Y, H:i') }}
          @else
            {{ \Carbon\Carbon::parse($subscription->created_at)->format('d M Y, H:i') }}
          @endif
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Notes -->
<div class="card" style="margin-top:1.5rem;">
  <div class="card-header">Notes</div>
  <div class="card-body">
    <div style="color:var(--muted);font-size:13px;white-space:pre-wrap;">
      {{ $subscription->notes ?? 'No notes' }}
    </div>
  </div>
</div>

<!-- Edit Form -->
<div class="card" style="margin-top:1.5rem;">
  <div class="card-header">Update Subscription</div>
  <div class="card-body">
    <form action="{{ route('super-admin.subscriptions.update', $subscription) }}" method="POST">
      @csrf @method('PUT')

      <div class="grid grid-2">
        <div class="form-group">
          <label>Plan</label>
          <select name="plan" required>
            <option value="free" {{ $subscription->plan === 'free' ? 'selected' : '' }}>Free</option>
            <option value="standard" {{ $subscription->plan === 'standard' ? 'selected' : '' }}>Standard</option>
            <option value="essential" {{ $subscription->plan === 'essential' ? 'selected' : '' }}>Essential</option>
            <option value="premium" {{ $subscription->plan === 'premium' ? 'selected' : '' }}>Premium</option>
            <option value="pro" {{ $subscription->plan === 'pro' ? 'selected' : '' }}>Pro</option>
            <option value="business" {{ $subscription->plan === 'business' ? 'selected' : '' }}>Business</option>
            <option value="enterprise" {{ $subscription->plan === 'enterprise' ? 'selected' : '' }}>Enterprise</option>
          </select>
        </div>

        <div class="form-group">
          <label>Status</label>
          <select name="status" required>
            <option value="active" {{ $subscription->status === 'active' ? 'selected' : '' }}>Active</option>
            <option value="inactive" {{ $subscription->status === 'inactive' ? 'selected' : '' }}>Inactive</option>
            <option value="suspended" {{ $subscription->status === 'suspended' ? 'selected' : '' }}>Suspended</option>
            <option value="cancelled" {{ $subscription->status === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
          </select>
        </div>

        <div class="form-group">
          <label>Amount ($)</label>
          <input type="number" name="amount" step="0.01" value="{{ $subscription->amount }}" placeholder="0.00">
        </div>

        <div class="form-group">
          <label>Expires At</label>
          <input type="datetime-local" name="expires_at" value="{{ $subscription->expires_at ? $subscription->expires_at->format('Y-m-d\TH:i') : '' }}">
        </div>
      </div>

      <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Save Changes</button>
    </form>
  </div>
</div>

<!-- Quick Actions -->
<div class="card" style="margin-top:1.5rem;">
  <div class="card-header">Quick Actions</div>
  <div class="card-body">
    <div style="display:flex;gap:.5rem;flex-wrap:wrap;">
      @if($subscription->status !== 'active')
      <form action="{{ route('super-admin.subscriptions.activate', $subscription) }}" method="POST" style="display:inline;">
        @csrf
        <button type="submit" class="btn btn-success"><i class="fa-solid fa-check"></i> Activate</button>
      </form>
      @endif

      @if($subscription->status !== 'suspended')
      <form action="{{ route('super-admin.subscriptions.suspend', $subscription) }}" method="POST" style="display:inline;">
        @csrf
        <button type="submit" class="btn btn-warning"><i class="fa-solid fa-pause"></i> Suspend</button>
      </form>
      @endif

      @if($subscription->status !== 'cancelled')
      <form action="{{ route('super-admin.subscriptions.cancel', $subscription) }}" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure?');">
        @csrf
        <button type="submit" class="btn btn-danger"><i class="fa-solid fa-times"></i> Cancel</button>
      </form>
      @endif
    </div>
  </div>
</div>

@endsection
