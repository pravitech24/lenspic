@extends('admin.layout')
@section('title','Groups')
@section('content')
<div class="page-header">
  <h1 class="page-title">📸 Groups <span style="font-size:1rem;color:var(--muted);font-weight:400;">({{ $groups->total() }})</span></h1>
</div>
<div class="card">
  <div class="card-body" style="padding-bottom:0;">
    <form class="search-bar" method="GET">
      <input type="text" name="search" value="{{ request('search') }}" placeholder="Search groups...">
      <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-search"></i> Search</button>
      @if(request('search'))<a href="{{ route('admin.groups') }}" class="btn btn-outline btn-sm">Clear</a>@endif
    </form>
  </div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Group</th><th>Creator</th><th>Photos</th><th>Members</th><th>Privacy</th><th>Status</th><th>Created</th><th>Actions</th></tr></thead>
      <tbody>
        @forelse($groups as $g)
        <tr>
          <td>
            <div style="font-weight:600;max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $g->name }}</div>
            <div style="font-size:11px;color:var(--muted);">{{ $g->getEventTypeLabel() }}</div>
          </td>
          <td style="font-size:13px;">{{ $g->creator->name }}</td>
          <td style="font-weight:700;">{{ $g->photos_count }}</td>
          <td style="font-weight:700;">{{ $g->members_count }}</td>
          <td><span class="badge {{ $g->privacy==='public'?'badge-green':($g->privacy==='private'?'badge-red':'badge-yellow') }}">{{ ucfirst($g->privacy) }}</span></td>
          <td>
            <form action="{{ route('admin.groups.toggle',$g) }}" method="POST" style="display:inline;">@csrf
              <button type="submit" class="badge {{ $g->is_active?'badge-green':'badge-red' }}" style="border:none;cursor:pointer;">{{ $g->is_active?'Active':'Inactive' }}</button>
            </form>
          </td>
          <td style="font-size:12px;color:var(--muted);">{{ $g->created_at->format('d M Y') }}</td>
          <td>
            <div style="display:flex;gap:6px;">
              <a href="{{ route('groups.show',$g) }}" class="btn btn-outline btn-sm" target="_blank"><i class="fa-solid fa-eye"></i></a>
              <form action="{{ route('admin.groups.delete',$g) }}" method="POST" onsubmit="return confirm('Delete group {{ $g->name }} and all its photos?')">@csrf @method('DELETE')
                <button type="submit" class="btn btn-danger btn-sm"><i class="fa-solid fa-trash"></i></button>
              </form>
            </div>
          </td>
        </tr>
        @empty
        <tr><td colspan="8" style="text-align:center;color:var(--muted);padding:2rem;">No groups found.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
  <div style="padding:1rem 1.25rem;">{{ $groups->withQueryString()->links() }}</div>
</div>
@endsection
