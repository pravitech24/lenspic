@extends('layouts.app')
@section('title','Dashboard')
@section('content')
<div class="page-wrap">
  @if(auth()->user()->isTrial())
  <div style="background:linear-gradient(135deg,#0f172a,#4338ca);border-radius:16px;padding:1rem 1.25rem;margin-bottom:1rem;color:#fff;display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;">
    <div>
      <div style="font-size:12px;font-weight:700;letter-spacing:.16em;text-transform:uppercase;opacity:.8;">Trial mode</div>
      <div style="font-size:14px;margin-top:.2rem;">Uploads and sharing are enabled for up to 500 photos. Creating groups and advanced settings stay disabled.</div>
    </div>
    <span style="padding:.45rem .7rem;border-radius:999px;background:rgba(255,255,255,.16);font-size:12px;font-weight:700;">{{ auth()->user()->plan_label }}</span>
  </div>
  @endif

  <div style="background:linear-gradient(135deg,#6366f1,#8b5cf6,#ec4899);border-radius:16px;padding:1.75rem 2rem;margin-bottom:2rem;color:#fff;position:relative;overflow:hidden;">
    <div style="position:absolute;right:-20px;top:-20px;width:160px;height:160px;background:rgba(255,255,255,.07);border-radius:50%;"></div>
    <h2 style="font-family:'Plus Jakarta Sans',sans-serif;font-size:1.4rem;font-weight:800;margin-bottom:.3rem;position:relative;">
      Good {{ now()->hour < 12 ? 'morning' : (now()->hour < 17 ? 'afternoon' : 'evening') }}, {{ explode(' ', auth()->user()->name)[0] }}! 👋
    </h2>
    <p style="opacity:.85;font-size:14px;position:relative;">{{ $totalGroups }} groups · {{ $totalPhotos }} photos uploaded</p>
    <div style="display:flex;gap:.75rem;margin-top:1.25rem;position:relative;flex-wrap:wrap;">
      @if(auth()->user()->canAccessFeature('create_group'))
      <a href="{{ route('groups.create') }}" style="background:#fff;color:#6366f1;padding:.5rem 1rem;border-radius:8px;font-weight:700;font-size:13px;text-decoration:none;"><i class="fa-solid fa-plus"></i> New Group</a>
      @else
      <span style="background:rgba(255,255,255,.16);color:#fff;padding:.5rem 1rem;border-radius:8px;font-weight:700;font-size:13px;opacity:.8;cursor:not-allowed;"><i class="fa-solid fa-plus"></i> New Group (disabled)</span>
      @endif
      <a href="{{ route('groups.index') }}" style="background:rgba(255,255,255,.15);color:#fff;padding:.5rem 1rem;border-radius:8px;font-weight:600;font-size:13px;text-decoration:none;border:1px solid rgba(255,255,255,.2);">All Groups</a>
    </div>
  </div>

  <div class="grid grid-4" style="margin-bottom:2rem;">
    @foreach([['My Groups',$myGroups->count(),'fa-images','#6366f1'],['Joined',$joinedGroups->count(),'fa-users','#ec4899'],['Photos',$totalPhotos,'fa-camera','#f59e0b'],['Total Groups',$totalGroups,'fa-layer-group','#10b981']] as [$l,$v,$icon,$c])
    <div class="card"><div class="card-body" style="display:flex;align-items:center;gap:.85rem;">
      <div style="width:44px;height:44px;background:{{ $c }}1a;border-radius:12px;display:flex;align-items:center;justify-content:center;color:{{ $c }};font-size:1.1rem;flex-shrink:0;"><i class="fa-solid {{ $icon }}"></i></div>
      <div><div style="font-size:1.4rem;font-weight:800;line-height:1;">{{ $v }}</div><div style="font-size:12px;color:#64748b;margin-top:1px;">{{ $l }}</div></div>
    </div></div>
    @endforeach
  </div>

  <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;" class="db-grid">
    <div>
      <div class="section-title" style="display:flex;align-items:center;justify-content:space-between;">My Groups <a href="{{ route('groups.index') }}" style="font-size:12px;color:#6366f1;font-weight:600;text-decoration:none;">View all →</a></div>
      @if($myGroups->isEmpty())
      <div class="card"><div class="empty-state" style="padding:2rem;"><div class="ei">📸</div><p style="font-size:13.5px;margin-bottom:1rem;">No groups yet.</p><a href="{{ route('groups.create') }}" class="btn btn-primary btn-sm">Create Group</a></div></div>
      @else
      <div style="display:flex;flex-direction:column;gap:.65rem;">
        @foreach($myGroups as $g)
        <a href="{{ route('groups.show',$g) }}" class="card" style="text-decoration:none;display:block;">
          <div style="display:flex;align-items:center;gap:.85rem;padding:.85rem 1rem;">
            <div style="width:42px;height:42px;border-radius:10px;overflow:hidden;flex-shrink:0;background:linear-gradient(135deg,#667eea,#764ba2);">
              @if($g->cover_photo)<img src="{{ $g->cover_photo_url }}" style="width:100%;height:100%;object-fit:cover;">@else<div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:18px;">📸</div>@endif
            </div>
            <div style="flex:1;min-width:0;">
              <div style="font-weight:600;font-size:13.5px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $g->name }}</div>
              <div style="font-size:12px;color:#64748b;">{{ $g->photos_count }} photos · {{ $g->members_count }} members</div>
            </div>
            <span style="font-size:11px;color:#94a3b8;">{{ $g->created_at->diffForHumans() }}</span>
          </div>
        </a>
        @endforeach
      </div>
      @endif
    </div>
    <div>
      <div class="section-title" style="display:flex;align-items:center;justify-content:space-between;">Joined Groups <a href="{{ route('groups.index') }}" style="font-size:12px;color:#6366f1;font-weight:600;text-decoration:none;">View all →</a></div>
      @if($joinedGroups->isEmpty())
      <div class="card"><div class="empty-state" style="padding:2rem;"><div class="ei">🤝</div><p style="font-size:13.5px;">Join a group using a share link.</p></div></div>
      @else
      <div style="display:flex;flex-direction:column;gap:.65rem;">
        @foreach($joinedGroups as $g)
        <a href="{{ route('groups.show',$g) }}" class="card" style="text-decoration:none;display:block;">
          <div style="display:flex;align-items:center;gap:.85rem;padding:.85rem 1rem;">
            <div style="width:42px;height:42px;border-radius:10px;overflow:hidden;flex-shrink:0;background:linear-gradient(135deg,#f093fb,#f5576c);">
              @if($g->cover_photo)<img src="{{ $g->cover_photo_url }}" style="width:100%;height:100%;object-fit:cover;">@else<div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:18px;">📸</div>@endif
            </div>
            <div style="flex:1;min-width:0;">
              <div style="font-weight:600;font-size:13.5px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $g->name }}</div>
              <div style="font-size:12px;color:#64748b;">{{ $g->photos_count }} photos · by {{ $g->creator->name }}</div>
            </div>
          </div>
        </a>
        @endforeach
      </div>
      @endif
    </div>
  </div>

  @if($recentPhotos->count() > 0)
  <div style="margin-top:2rem;">
    <div class="section-title">Recent Photos</div>
    <div class="photo-grid">
      @foreach($recentPhotos as $p)
      <a href="{{ route('photos.show',[$p->group_id,$p]) }}" class="photo-card">
        <img src="{{ $p->thumbnail_url }}" loading="lazy">
        <div class="photo-overlay"><span style="color:#fff;font-size:11px;">{{ $p->uploader?->name ?? 'Guest' }}</span></div>
      </a>
      @endforeach
    </div>
  </div>
  @endif
</div>
@push('styles')<style>@media(max-width:640px){.db-grid{grid-template-columns:1fr!important;}}</style>@endpush
@endsection
