@extends('super-admin.layout')
@section('title', 'Users Management')

@section('content')
<div class="page-header">
  <div class="page-title"><i class="fa-solid fa-users" style="margin-right:.5rem;"></i>Users Management</div>
</div>

<div class="card">
  <div class="card-body">
    <form method="GET" class="search-bar">
      <input type="text" name="search" placeholder="Search by name or email..." value="{{ request('search') }}">
      <select name="role" style="max-width:180px;">
        <option value="">All Roles</option>
        <option value="user" {{ request('role') === 'user' ? 'selected' : '' }}>User</option>
        <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>Admin</option>
        <option value="super_admin" {{ request('role') === 'super_admin' ? 'selected' : '' }}>Super Admin</option>
      </select>
      <button type="submit" class="btn btn-primary" style="margin-left:auto;"><i class="fa-solid fa-search"></i> Search</button>
    </form>

    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>User</th>
            <th>Email</th>
            <th>Phone</th>
            <th>Role</th>
            <th>Plan</th>
            <th>Storage</th>
            <th>Joined</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          @forelse($users as $user)
          <tr>
            <td>
              <div style="display:flex;align-items:center;gap:.5rem;">
                <img src="{{ $user->profile_photo_url }}" class="avatar">
                <strong>{{ $user->name }}</strong>
              </div>
            </td>
            <td>{{ $user->email }}</td>
            <td>{{ $user->phone ?? '-' }}</td>
            <td>
              <span class="badge badge-{{ $user->role === 'super_admin' ? 'red' : ($user->role === 'admin' ? 'blue' : 'yellow') }}">
                {{ ucfirst(str_replace('_', ' ', $user->role)) }}
              </span>
            </td>
            <td><span class="badge badge-blue">{{ ucfirst($user->plan_label) }}</span></td>
            <td>{{ $user->storage_used_human }} / {{ $user->plan_limits['storage_label'] }}</td>
            <td style="font-size:12px;color:var(--muted);">{{ $user->created_at->format('d M Y') }}</td>
            <td>
              <a href="{{ route('super-admin.users.show', $user) }}" class="btn btn-outline btn-sm"><i class="fa-solid fa-eye"></i></a>
            </td>
          </tr>
          @empty
          <tr><td colspan="8" style="text-align:center;color:var(--muted);padding:2rem;">No users found</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="pagination">{{ $users->links() }}</div>
  </div>
</div>

@endsection
