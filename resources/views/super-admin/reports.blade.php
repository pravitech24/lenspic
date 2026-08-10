@extends('super-admin.layout')
@section('title', 'Reports & Analytics')

@section('content')
<div class="page-header">
  <div class="page-title"><i class="fa-solid fa-chart-line" style="margin-right:.5rem;"></i>Reports & Analytics</div>
  <button onclick="exportReports()" class="btn btn-primary"><i class="fa-solid fa-download"></i> Export</button>
</div>

<!-- User Activity Report -->
<div class="card">
  <div class="card-header">User Activity</div>
  <div class="card-body">
    <div class="grid grid-4">
      <div class="stat-card">
        <div class="stat-icon" style="background:rgba(99,102,241,.15);color:#a5b4fc;"><i class="fa-solid fa-users"></i></div>
        <div>
          <div class="stat-num">{{ $userActivityReport['total_users'] }}</div>
          <div class="stat-label">Total Users</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon" style="background:rgba(34,197,94,.15);color:#86efac;"><i class="fa-solid fa-plus-circle"></i></div>
        <div>
          <div class="stat-num">{{ $userActivityReport['new_users_today'] }}</div>
          <div class="stat-label">New Today</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon" style="background:rgba(59,130,246,.15);color:#93c5fd;"><i class="fa-solid fa-calendar-days"></i></div>
        <div>
          <div class="stat-num">{{ $userActivityReport['new_users_week'] }}</div>
          <div class="stat-label">New This Week</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon" style="background:rgba(245,158,11,.15);color:#fbbf24;"><i class="fa-solid fa-person-walking"></i></div>
        <div>
          <div class="stat-num">{{ $userActivityReport['active_users'] }}</div>
          <div class="stat-label">Active (30d)</div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Group Activity Report -->
<div class="card" style="margin-top:1.5rem;">
  <div class="card-header">Group Activity</div>
  <div class="card-body">
    <div class="grid grid-4">
      <div class="stat-card">
        <div class="stat-icon" style="background:rgba(168,85,247,.15);color:#d8b4fe;"><i class="fa-solid fa-images"></i></div>
        <div>
          <div class="stat-num">{{ $groupActivityReport['total_groups'] }}</div>
          <div class="stat-label">Total Groups</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon" style="background:rgba(236,72,153,.15);color:#f472b6;"><i class="fa-solid fa-plus-circle"></i></div>
        <div>
          <div class="stat-num">{{ $groupActivityReport['new_groups_today'] }}</div>
          <div class="stat-label">New Today</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon" style="background:rgba(16,185,129,.15);color:#34d399;"><i class="fa-solid fa-circle-check"></i></div>
        <div>
          <div class="stat-num">{{ $groupActivityReport['active_groups'] }}</div>
          <div class="stat-label">Active</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon" style="background:rgba(107,114,128,.15);color:#d1d5db;"><i class="fa-solid fa-archive"></i></div>
        <div>
          <div class="stat-num">{{ $groupActivityReport['archived_groups'] }}</div>
          <div class="stat-label">Archived</div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Storage Usage Report -->
<div class="card" style="margin-top:1.5rem;">
  <div class="card-header">Storage Usage</div>
  <div class="card-body">
    <div class="grid grid-4">
      <div class="stat-card">
        <div class="stat-icon" style="background:rgba(59,130,246,.15);color:#93c5fd;"><i class="fa-solid fa-database"></i></div>
        <div>
          <div class="stat-num">{{ round($storageUsageReport['total_storage_used'] / 1073741824, 1) }} GB</div>
          <div class="stat-label">Total Used</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon" style="background:rgba(34,197,94,.15);color:#86efac;"><i class="fa-solid fa-chart-pie"></i></div>
        <div>
          <div class="stat-num">{{ round($storageUsageReport['average_storage_per_user'] / 1048576, 1) }} MB</div>
          <div class="stat-label">Avg Per User</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon" style="background:rgba(245,158,11,.15);color:#fbbf24;"><i class="fa-solid fa-arrow-trend-up"></i></div>
        <div>
          <div class="stat-num">{{ round($storageUsageReport['max_storage_user'] / 1048576, 1) }} MB</div>
          <div class="stat-label">Max User</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon" style="background:rgba(168,85,247,.15);color:#d8b4fe;"><i class="fa-solid fa-users"></i></div>
        <div>
          <div class="stat-num">{{ $storageUsageReport['total_users'] }}</div>
          <div class="stat-label">Users</div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Subscription Report -->
<div class="card" style="margin-top:1.5rem;">
  <div class="card-header">Subscription Report</div>
  <div class="card-body">
    <div class="grid grid-2">
      <div class="stat-card">
        <div class="stat-icon" style="background:rgba(34,197,94,.15);color:#86efac;"><i class="fa-solid fa-receipt"></i></div>
        <div>
          <div class="stat-num">{{ $subscriptionReport['total_active'] }}</div>
          <div class="stat-label">Active Subscriptions</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon" style="background:rgba(34,197,94,.15);color:#86efac;"><i class="fa-solid fa-dollar-sign"></i></div>
        <div>
          <div class="stat-num">${{ number_format($subscriptionReport['total_revenue'], 2) }}</div>
          <div class="stat-label">Total Revenue</div>
        </div>
      </div>
    </div>

    <div style="margin-top:1.5rem;">
      <h3 style="font-size:14px;font-weight:600;margin-bottom:1rem;">Breakdown by Plan</h3>
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Plan</th>
              <th>Count</th>
            </tr>
          </thead>
          <tbody>
            @forelse($subscriptionReport['by_plan'] as $plan)
            <tr>
              <td><span class="badge badge-blue">{{ ucfirst($plan->plan) }}</span></td>
              <td>{{ $plan->count }}</td>
            </tr>
            @empty
            <tr><td colspan="2" style="text-align:center;color:var(--muted);padding:1rem;">No subscription data</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<script>
async function exportReports() {
  const response = await fetch('{{ route("super-admin.reports.export") }}', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
    }
  });
  const data = await response.json();
  const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = `reports-${new Date().toISOString().split('T')[0]}.json`;
  a.click();
}
</script>

@endsection
