@extends('admin.layout')
@section('title','Dashboard')
@section('content')

<!-- Stat Cards -->
<div class="grid grid-4" style="margin-bottom:1.75rem;">
  <div class="stat-card">
    <div class="stat-icon" style="background:rgba(99,102,241,.15);color:#a5b4fc;"><i class="fa-solid fa-users"></i></div>
    <div><div class="stat-num">{{ number_format($stats['total_users']) }}</div><div class="stat-label">Total Users</div><div class="stat-sub">+{{ $stats['new_users_today'] }} today</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:rgba(236,72,153,.15);color:#f9a8d4;"><i class="fa-solid fa-images"></i></div>
    <div><div class="stat-num">{{ number_format($stats['total_groups']) }}</div><div class="stat-label">Total Groups</div><div class="stat-sub">+{{ $stats['new_groups_today'] }} today</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:rgba(245,158,11,.15);color:#fbbf24;"><i class="fa-solid fa-camera"></i></div>
    <div><div class="stat-num">{{ number_format($stats['total_photos']) }}</div><div class="stat-label">Total Photos</div><div class="stat-sub">+{{ $stats['new_photos_today'] }} today</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:rgba(16,185,129,.15);color:#34d399;"><i class="fa-solid fa-shield-halved"></i></div>
    <div><div class="stat-num">Active</div><div class="stat-label">System Status</div><div class="stat-sub">All systems normal</div></div>
  </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;">
  <!-- Recent Users -->
  <div class="card">
    <div class="card-header" style="display:flex;align-items:center;justify-content:space-between;">
      Recent Users
      <a href="{{ route('admin.users') }}" class="btn btn-outline btn-sm">View All</a>
    </div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>User</th><th>Joined</th><th>Role</th></tr></thead>
        <tbody>
          @foreach($recentUsers as $u)
          <tr>
            <td>
              <div style="display:flex;align-items:center;gap:8px;">
                <img src="{{ $u->profile_photo_url }}" class="avatar">
                <div><div style="font-weight:600;font-size:13px;">{{ $u->name }}</div><div style="font-size:11px;color:var(--muted);">{{ $u->email }}</div></div>
              </div>
            </td>
            <td style="color:var(--muted);font-size:12px;">{{ $u->created_at->diffForHumans() }}</td>
            <td>
              @if($u->is_admin)<span class="badge badge-blue">Admin</span>@else<span class="badge badge-green">User</span>@endif
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>

  <!-- Recent Groups -->
  <div class="card">
    <div class="card-header" style="display:flex;align-items:center;justify-content:space-between;">
      Recent Groups
      <a href="{{ route('admin.groups') }}" class="btn btn-outline btn-sm">View All</a>
    </div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Group</th><th>Photos</th><th>Status</th></tr></thead>
        <tbody>
          @foreach($recentGroups as $g)
          <tr>
            <td>
              <div style="font-weight:600;font-size:13px;max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $g->name }}</div>
              <div style="font-size:11px;color:var(--muted);">by {{ $g->creator->name }}</div>
            </td>
            <td style="font-weight:600;">{{ $g->photos_count }}</td>
            <td>
              @if($g->is_active)<span class="badge badge-green">Active</span>@else<span class="badge badge-red">Inactive</span>@endif
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection
