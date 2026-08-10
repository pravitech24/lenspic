@extends('super-admin.layout')
@section('title', 'Subscriptions')

@section('content')
<div class="page-header">
  <div class="page-title"><i class="fa-solid fa-credit-card" style="margin-right:.5rem;"></i>Subscriptions Management</div>
</div>

<div class="card">
  <div class="card-body">
    <form method="GET" class="search-bar">
      <select name="plan" style="max-width:180px;">
        <option value="">All Plans</option>
        <option value="free" {{ request('plan') === 'free' ? 'selected' : '' }}>Free</option>
        <option value="standard" {{ request('plan') === 'standard' ? 'selected' : '' }}>Standard</option>
        <option value="essential" {{ request('plan') === 'essential' ? 'selected' : '' }}>Essential</option>
        <option value="premium" {{ request('plan') === 'premium' ? 'selected' : '' }}>Premium</option>
      </select>
      <select name="status" style="max-width:180px;">
        <option value="">All Status</option>
        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
        <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
        <option value="suspended" {{ request('status') === 'suspended' ? 'selected' : '' }}>Suspended</option>
        <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
      </select>
      <button type="submit" class="btn btn-primary" style="margin-left:auto;"><i class="fa-solid fa-filter"></i> Filter</button>
    </form>

    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>User</th>
            <th>Plan</th>
            <th>Amount</th>
            <th>Status</th>
            <th>Started</th>
            <th>Expires</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          @forelse($subscriptions as $sub)
          <tr>
            <td>
              <div style="display:flex;align-items:center;gap:.5rem;">
                <img src="{{ $sub->user->profile_photo_url }}" class="avatar">
                {{ $sub->user->name }}
              </div>
            </td>
            <td><span class="badge badge-blue">{{ ucfirst($sub->plan) }}</span></td>
            <td>${{ number_format($sub->amount, 2) }}</td>
            <td><span class="badge badge-{{ $sub->status === 'active' ? 'green' : ($sub->status === 'suspended' ? 'yellow' : 'red') }}">{{ ucfirst($sub->status) }}</span></td>
            <td>{{ $sub->starts_at ? $sub->starts_at->format('d M Y') : '-' }}</td>
            <td>{{ $sub->expires_at ? $sub->expires_at->format('d M Y') : 'Unlimited' }}</td>
            <td><a href="{{ route('super-admin.subscriptions.show', $sub) }}" class="btn btn-outline btn-sm"><i class="fa-solid fa-eye"></i></a></td>
          </tr>
          @empty
          <tr><td colspan="7" style="text-align:center;color:var(--muted);padding:2rem;">No subscriptions found</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="pagination">{{ $subscriptions->links() }}</div>
  </div>
</div>

@endsection
