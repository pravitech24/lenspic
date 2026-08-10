@extends('super-admin.layout')
@section('title', 'Dashboard')

@section('content')
<div class="page-header">
  <div class="page-title"><i class="fa-solid fa-gauge-high" style="margin-right:.5rem;"></i>Dashboard Overview</div>
</div>

<!-- Stats Grid -->
<div class="grid grid-5">
  <div class="stat-card">
    <div class="stat-icon" style="background:rgba(99,102,241,.15);color:#a5b4fc;"><i class="fa-solid fa-users"></i></div>
    <div>
      <div class="stat-num">{{ $stats['total_users'] }}</div>
      <div class="stat-label">Total Users</div>
      <div class="stat-sub">{{ $stats['new_users_today'] }} new today</div>
    </div>
  </div>

  <div class="stat-card">
    <div class="stat-icon" style="background:rgba(239,68,68,.15);color:#f87171;"><i class="fa-solid fa-crown"></i></div>
    <div>
      <div class="stat-num">{{ $stats['super_admins'] }}</div>
      <div class="stat-label">Super Admins</div>
    </div>
  </div>

  <div class="stat-card">
    <div class="stat-icon" style="background:rgba(16,185,129,.15);color:#34d399;"><i class="fa-solid fa-shield"></i></div>
    <div>
      <div class="stat-num">{{ $stats['admins'] }}</div>
      <div class="stat-label">Admins</div>
    </div>
  </div>

  <div class="stat-card">
    <div class="stat-icon" style="background:rgba(245,158,11,.15);color:#fbbf24;"><i class="fa-solid fa-images"></i></div>
    <div>
      <div class="stat-num">{{ $stats['total_groups'] }}</div>
      <div class="stat-label">Total Groups</div>
    </div>
  </div>

  <div class="stat-card">
    <div class="stat-icon" style="background:rgba(168,85,247,.15);color:#d8b4fe;"><i class="fa-solid fa-image"></i></div>
    <div>
      <div class="stat-num">{{ number_format($stats['total_photos']) }}</div>
      <div class="stat-label">Total Photos</div>
    </div>
  </div>
</div>

<!-- Revenue & Subscriptions -->
<div class="grid grid-2" style="margin-top:1.5rem;">
  <div class="stat-card">
    <div class="stat-icon" style="background:rgba(34,197,94,.15);color:#86efac;"><i class="fa-solid fa-dollar-sign"></i></div>
    <div>
      <div class="stat-num">${{ number_format($stats['total_revenue'], 2) }}</div>
      <div class="stat-label">Total Revenue</div>
    </div>
  </div>

  <div class="stat-card">
    <div class="stat-icon" style="background:rgba(59,130,246,.15);color:#93c5fd;"><i class="fa-solid fa-receipt"></i></div>
    <div>
      <div class="stat-num">{{ $stats['total_subscriptions'] }}</div>
      <div class="stat-label">Active Subscriptions</div>
      <div class="stat-sub">{{ $stats['new_subscriptions_today'] }} new today</div>
    </div>
  </div>
</div>

<!-- Recent Users -->
<div class="card" style="margin-top:1.5rem;">
  <div class="card-header">
    <i class="fa-solid fa-users"></i> Recent Users
    <a href="{{ route('super-admin.users') }}" class="btn btn-outline btn-sm" style="margin-left:auto;">View All</a>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Name</th>
          <th>Email</th>
          <th>Role</th>
          <th>Storage Used</th>
          <th>Joined</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        @foreach($recentUsers as $user)
        <tr>
          <td>
            <div style="display:flex;align-items:center;gap:.5rem;">
              <img src="{{ $user->profile_photo_url }}" class="avatar">
              {{ $user->name }}
            </div>
          </td>
          <td>{{ $user->email }}</td>
          <td><span class="badge badge-{{ $user->role === 'super_admin' ? 'red' : ($user->role === 'admin' ? 'blue' : 'yellow') }}">{{ ucfirst(str_replace('_', ' ', $user->role)) }}</span></td>
          <td>{{ $user->storage_used_human }}</td>
          <td style="font-size:12px;color:var(--muted);">{{ $user->created_at->format('d M Y') }}</td>
          <td><a href="{{ route('super-admin.users.show', $user) }}" class="btn btn-outline btn-sm"><i class="fa-solid fa-eye"></i></a></td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</div>

<!-- Recent Groups -->
<div class="card" style="margin-top:1.5rem;">
  <div class="card-header">
    <i class="fa-solid fa-images"></i> Recent Groups
    <a href="{{ route('super-admin.groups') }}" class="btn btn-outline btn-sm" style="margin-left:auto;">View All</a>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Group Name</th>
          <th>Creator</th>
          <th>Photos</th>
          <th>Members</th>
          <th>Status</th>
          <th>Created</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        @foreach($recentGroups as $group)
        <tr>
          <td>{{ $group->name }}</td>
          <td>{{ $group->creator->name }}</td>
          <td>{{ $group->photos_count }}</td>
          <td>{{ $group->members_count }}</td>
          <td><span class="badge {{ $group->is_active ? 'badge-green' : 'badge-red' }}">{{ $group->is_active ? 'Active' : 'Inactive' }}</span></td>
          <td style="font-size:12px;color:var(--muted);">{{ $group->created_at->format('d M Y') }}</td>
          <td><a href="{{ route('super-admin.groups.show', $group) }}" class="btn btn-outline btn-sm"><i class="fa-solid fa-eye"></i></a></td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</div>

<!-- Active Subscriptions -->
<div class="card" style="margin-top:1.5rem;">
  <div class="card-header">
    <i class="fa-solid fa-credit-card"></i> Active Subscriptions
    <a href="{{ route('super-admin.subscriptions') }}" class="btn btn-outline btn-sm" style="margin-left:auto;">View All</a>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>User</th>
          <th>Plan</th>
          <th>Amount</th>
          <th>Status</th>
          <th>Expires</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        @foreach($activeSubscriptions as $sub)
        <tr>
          <td>
            <div style="display:flex;align-items:center;gap:.5rem;">
              <img src="{{ $sub->user->profile_photo_url }}" class="avatar">
              {{ $sub->user->name }}
            </div>
          </td>
          <td><span class="badge badge-blue">{{ ucfirst($sub->plan) }}</span></td>
          <td>${{ number_format($sub->amount, 2) }}</td>
          <td><span class="badge badge-green">{{ ucfirst($sub->status) }}</span></td>
          <td style="font-size:12px;color:var(--muted);">{{ $sub->expires_at ? $sub->expires_at->format('d M Y') : 'Unlimited' }}</td>
          <td><a href="{{ route('super-admin.subscriptions.show', $sub) }}" class="btn btn-outline btn-sm"><i class="fa-solid fa-eye"></i></a></td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</div>

@endsection
