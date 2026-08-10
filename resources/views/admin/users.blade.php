@extends('admin.layout')
@section('title','Users')
@section('content')
<div class="page-header">
  <h1 class="page-title">👥 Users <span style="font-size:1rem;color:var(--muted);font-weight:400;">({{ $users->total() }})</span></h1>
</div>
<div class="card">
  <div class="card-body" style="padding-bottom:0;">
    <form class="search-bar" method="GET">
      <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by name or email...">
      <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-search"></i> Search</button>
      @if(request('search'))<a href="{{ route('admin.users') }}" class="btn btn-outline btn-sm">Clear</a>@endif
    </form>
  </div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>User</th><th>Phone</th><th>Groups</th><th>Photos</th><th>Joined</th><th>Role</th><th>Actions</th></tr></thead>
      <tbody>
        @forelse($users as $u)
        <tr>
          <td>
            <div style="display:flex;align-items:center;gap:10px;">
              <img src="{{ $u->profile_photo_url }}" class="avatar">
              <div><div style="font-weight:600;">{{ $u->name }}</div><div style="font-size:11.5px;color:var(--muted);">{{ $u->email }}</div></div>
            </div>
          </td>
          <td style="color:var(--muted);font-size:13px;">{{ $u->phone ?? '—' }}</td>
          <td style="font-weight:600;">{{ $u->createdGroups()->count() }}</td>
          <td style="font-weight:600;">{{ $u->photos()->count() }}</td>
          <td style="font-size:12px;color:var(--muted);">{{ $u->created_at->format('d M Y') }}</td>
          <td>
            @if($u->is_admin)<span class="badge badge-blue">Admin</span>@else<span class="badge badge-green">User</span>@endif
          </td>
          <td>
            <div style="display:flex;gap:6px;">
              <a href="{{ route('admin.users.edit',$u) }}" class="btn btn-outline btn-sm"><i class="fa-solid fa-pen"></i></a>
              @if(!$u->is_admin)
              <form action="{{ route('admin.users.delete',$u) }}" method="POST" onsubmit="return confirm('Delete user {{ $u->name }}?')">@csrf @method('DELETE')
                <button type="submit" class="btn btn-danger btn-sm"><i class="fa-solid fa-trash"></i></button>
              </form>
              @endif
            </div>
          </td>
        </tr>
        @empty
        <tr><td colspan="7" style="text-align:center;color:var(--muted);padding:2rem;">No users found.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
  <div style="padding:1rem 1.25rem;">{{ $users->withQueryString()->links() }}</div>
</div>
@endsection
