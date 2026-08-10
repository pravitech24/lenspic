@extends('super-admin.layout')
@section('title', 'User Details')

@section('content')
<div class="page-header">
  <div class="page-title">
    <img src="{{ $user->profile_photo_url }}" class="avatar" style="width:40px;height:40px;margin-right:.5rem;">
    {{ $user->name }}
  </div>
  <div>
    <a href="{{ route('super-admin.users.edit', $user) }}" class="btn btn-primary"><i class="fa-solid fa-edit"></i> Edit</a>
    <a href="{{ route('super-admin.users') }}" class="btn btn-outline"><i class="fa-solid fa-arrow-left"></i> Back</a>
  </div>
</div>

<div class="grid grid-3">
  <div class="card">
    <div class="card-header">User Info</div>
    <div class="card-body">
      <div style="margin-bottom:1rem;">
        <div style="font-size:12px;color:var(--muted);margin-bottom:.3rem;">Email</div>
        <div>{{ $user->email }}</div>
      </div>
      <div style="margin-bottom:1rem;">
        <div style="font-size:12px;color:var(--muted);margin-bottom:.3rem;">Phone</div>
        <div>{{ $user->phone ?? '-' }}</div>
      </div>
      <div style="margin-bottom:1rem;">
        <div style="font-size:12px;color:var(--muted);margin-bottom:.3rem;">Role</div>
        <div><span class="badge badge-{{ $user->role === 'super_admin' ? 'red' : ($user->role === 'admin' ? 'blue' : 'yellow') }}">{{ ucfirst(str_replace('_', ' ', $user->role)) }}</span></div>
      </div>
      <div>
        <div style="font-size:12px;color:var(--muted);margin-bottom:.3rem;">Member Since</div>
        <div>{{ $user->created_at->format('d M Y, H:i') }}</div>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-header">Storage</div>
    <div class="card-body">
      <div style="margin-bottom:1.5rem;">
        <div style="display:flex;justify-content:space-between;margin-bottom:.5rem;">
          <span style="font-size:12px;color:var(--muted);">Usage</span>
          <span style="font-weight:600;">{{ $storage }}</span>
        </div>
        <div style="width:100%;height:8px;background:rgba(255,255,255,.1);border-radius:4px;overflow:hidden;">
          <div style="width:{{ $user->storage_percent }}%;height:100%;background:var(--p);"></div>
        </div>
        <div style="font-size:12px;color:var(--muted);margin-top:.5rem;">
          {{ $user->plan_limits['storage_label'] }} total limit
        </div>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-header">Plan Info</div>
    <div class="card-body">
      <div style="margin-bottom:1rem;">
        <div style="font-size:12px;color:var(--muted);margin-bottom:.3rem;">Current Plan</div>
        <div><span class="badge badge-blue">{{ ucfirst($user->plan_label) }}</span></div>
      </div>
      <div style="margin-bottom:1rem;">
        <div style="font-size:12px;color:var(--muted);margin-bottom:.3rem;">Expires</div>
        <div>{{ $user->plan_expires_at ? $user->plan_expires_at->format('d M Y') : 'Never' }}</div>
      </div>
      <div>
        <div style="font-size:12px;color:var(--muted);margin-bottom:.3rem;">Groups</div>
        <div>{{ $user->plan_limits['groups'] }} allowed</div>
      </div>
    </div>
  </div>
</div>

<!-- Subscriptions -->
<div class="card" style="margin-top:1.5rem;">
  <div class="card-header">Subscription History</div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Plan</th>
          <th>Amount</th>
          <th>Status</th>
          <th>Started</th>
          <th>Expires</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        @forelse($subscriptions as $sub)
        <tr>
          <td><span class="badge badge-blue">{{ ucfirst($sub->plan) }}</span></td>
          <td>${{ number_format($sub->amount, 2) }}</td>
          <td><span class="badge badge-{{ $sub->status === 'active' ? 'green' : 'red' }}">{{ ucfirst($sub->status) }}</span></td>
          <td>{{ $sub->starts_at ? $sub->starts_at->format('d M Y') : '-' }}</td>
          <td>{{ $sub->expires_at ? $sub->expires_at->format('d M Y') : 'Unlimited' }}</td>
          <td><a href="{{ route('super-admin.subscriptions.show', $sub) }}" class="btn btn-outline btn-sm"><i class="fa-solid fa-eye"></i></a></td>
        </tr>
        @empty
        <tr><td colspan="6" style="text-align:center;color:var(--muted);padding:1.5rem;">No subscriptions</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

<!-- Groups Created -->
<div class="card" style="margin-top:1.5rem;">
  <div class="card-header">Groups Created</div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Group Name</th>
          <th>Members</th>
          <th>Status</th>
          <th>Created</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        @forelse($groups as $group)
        <tr>
          <td>{{ $group->name }}</td>
          <td>{{ $group->members->count() }}</td>
          <td><span class="badge {{ $group->is_active ? 'badge-green' : 'badge-red' }}">{{ $group->is_active ? 'Active' : 'Inactive' }}</span></td>
          <td>{{ $group->created_at->format('d M Y') }}</td>
          <td><a href="{{ route('super-admin.groups.show', $group) }}" class="btn btn-outline btn-sm"><i class="fa-solid fa-eye"></i></a></td>
        </tr>
        @empty
        <tr><td colspan="5" style="text-align:center;color:var(--muted);padding:1.5rem;">No groups created</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

<!-- Actions -->
<div class="card" style="margin-top:1.5rem;">
  <div class="card-header">User Actions</div>
  <div class="card-body">
    <div style="display:flex;gap:.5rem;flex-wrap:wrap;">
      @if($user->role !== 'super_admin')
        @if($user->role !== 'admin')
        <form action="{{ route('super-admin.users.promote-admin', $user) }}" method="POST" style="display:inline;">
          @csrf
          <button type="submit" class="btn btn-success"><i class="fa-solid fa-arrow-up"></i> Promote to Admin</button>
        </form>
        @endif
        <form action="{{ route('super-admin.users.promote-super-admin', $user) }}" method="POST" style="display:inline;">
          @csrf
          <button type="submit" class="btn btn-primary"><i class="fa-solid fa-crown"></i> Promote to Super Admin</button>
        </form>
        <form action="{{ route('super-admin.users.suspend', $user) }}" method="POST" style="display:inline;">
          @csrf
          <button type="submit" class="btn btn-warning"><i class="fa-solid fa-ban"></i> Suspend</button>
        </form>
        <form action="{{ route('super-admin.users.ban', $user) }}" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure?');">
          @csrf
          <button type="submit" class="btn btn-danger"><i class="fa-solid fa-block"></i> Ban</button>
        </form>
        <form action="{{ route('super-admin.users.delete', $user) }}" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure? This cannot be undone.');">
          @csrf @method('DELETE')
          <button type="submit" class="btn btn-danger"><i class="fa-solid fa-trash"></i> Delete</button>
        </form>
      @else
      <div style="color:var(--muted);font-size:13px;">Super admin users cannot be demoted or deleted</div>
      @endif
    </div>
  </div>
</div>

@endsection
