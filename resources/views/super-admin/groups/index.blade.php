@extends('super-admin.layout')
@section('title', 'Groups Management')

@section('content')
<div class="page-header">
  <div class="page-title"><i class="fa-solid fa-images" style="margin-right:.5rem;"></i>Groups Management</div>
</div>

<div class="card">
  <div class="card-body">
    <form method="GET" class="search-bar">
      <input type="text" name="search" placeholder="Search groups..." value="{{ request('search') }}">
      <select name="status" style="max-width:180px;">
        <option value="">All Status</option>
        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
        <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
      </select>
      <button type="submit" class="btn btn-primary" style="margin-left:auto;"><i class="fa-solid fa-search"></i> Search</button>
    </form>

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
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          @forelse($groups as $group)
          <tr>
            <td><strong>{{ $group->name }}</strong></td>
            <td>{{ $group->creator->name }}</td>
            <td>{{ $group->photos_count }}</td>
            <td>{{ $group->members_count }}</td>
            <td><span class="badge {{ $group->is_active ? 'badge-green' : 'badge-red' }}">{{ $group->is_active ? 'Active' : 'Inactive' }}</span></td>
            <td style="font-size:12px;color:var(--muted);">{{ $group->created_at->format('d M Y') }}</td>
            <td><a href="{{ route('super-admin.groups.show', $group) }}" class="btn btn-outline btn-sm"><i class="fa-solid fa-eye"></i></a></td>
          </tr>
          @empty
          <tr><td colspan="7" style="text-align:center;color:var(--muted);padding:2rem;">No groups found</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="pagination">{{ $groups->links() }}</div>
  </div>
</div>

@endsection
