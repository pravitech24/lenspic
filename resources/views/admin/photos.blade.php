@extends('admin.layout')
@section('title','Photos')
@section('content')
<div class="page-header">
  <h1 class="page-title">📷 Photos <span style="font-size:1rem;color:var(--muted);font-weight:400;">({{ $photos->total() }})</span></h1>
</div>
<div class="card">
  <div class="card-body" style="padding-bottom:0;">
    <form class="search-bar" method="GET">
      <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by filename...">
      <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-search"></i> Search</button>
      @if(request('search'))<a href="{{ route('admin.photos') }}" class="btn btn-outline btn-sm">Clear</a>@endif
    </form>
  </div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Photo</th><th>Group</th><th>Uploader</th><th>Size</th><th>Views</th><th>Downloads</th><th>Uploaded</th><th></th></tr></thead>
      <tbody>
        @forelse($photos as $p)
        <tr>
          <td>
            <div style="display:flex;align-items:center;gap:10px;">
              <img src="{{ $p->thumbnail_url }}" style="width:44px;height:44px;border-radius:6px;object-fit:cover;">
              <div style="font-size:12px;color:var(--muted);max-width:120px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $p->original_filename }}</div>
            </div>
          </td>
          <td style="font-size:13px;max-width:120px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $p->group->name }}</td>
          <td style="font-size:13px;">{{ $p->uploader?->name ?? 'Guest' }}</td>
          <td style="font-size:12px;color:var(--muted);">{{ $p->file_size_human }}</td>
          <td>{{ $p->views_count }}</td>
          <td>{{ $p->downloads_count }}</td>
          <td style="font-size:12px;color:var(--muted);">{{ $p->created_at->format('d M Y') }}</td>
          <td>
            <form action="{{ route('admin.photos.delete',$p) }}" method="POST" onsubmit="return confirm('Delete this photo permanently?')">@csrf @method('DELETE')
              <button type="submit" class="btn btn-danger btn-sm"><i class="fa-solid fa-trash"></i></button>
            </form>
          </td>
        </tr>
        @empty
        <tr><td colspan="8" style="text-align:center;color:var(--muted);padding:2rem;">No photos found.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
  <div style="padding:1rem 1.25rem;">{{ $photos->withQueryString()->links() }}</div>
</div>
@endsection
