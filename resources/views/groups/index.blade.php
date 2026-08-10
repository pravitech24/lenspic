@extends('layouts.app')
@section('title','My Groups')
@section('content')
<div class="page-wrap">
  <div class="page-header">
    <h1 class="page-title">📸 My Groups</h1>
    @if(auth()->user()->canAccessFeature('create_group'))
    <a href="{{ route('groups.create') }}" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Create Group</a>
    @else
    <button type="button" class="btn btn-primary" disabled><i class="fa-solid fa-plus"></i> Create Group</button>
    @endif
  </div>
  <div class="card" style="margin-bottom:1.5rem;background:linear-gradient(135deg,#eef2ff,#fdf4ff);">
    <div class="card-body" style="display:flex;align-items:center;gap:1rem;flex-wrap:wrap;">
      <div style="flex:1;min-width:200px;"><div style="font-weight:600;margin-bottom:.2rem;">Have a group link?</div><div style="font-size:13px;color:#64748b;">Paste it to join instantly.</div></div>
      <div style="display:flex;gap:.5rem;flex:1;min-width:240px;">
        <input type="url" id="joinUrl" placeholder="https://..." style="flex:1;">
        <button onclick="joinByLink()" class="btn btn-primary">Join</button>
      </div>
    </div>
  </div>

  @if(auth()->user()->isTrial())
  <div class="card" style="margin-bottom:1.5rem;border:1px dashed #cbd5e1;background:#f8fafc;">
    <div class="card-body" style="font-size:13px;color:#475569;">Trial accounts can upload and share photos up to 500 photos. Group creation and settings remain disabled until you upgrade.</div>
  </div>
  @endif

  @if($myGroups->isEmpty() && $joinedGroups->isEmpty())
  <div class="empty-state"><div class="ei">📷</div><h3 style="font-size:1.1rem;font-weight:700;margin-bottom:.5rem;">No groups yet</h3><p style="margin-bottom:1.25rem;">Create your first group to start sharing.</p>@if(auth()->user()->canAccessFeature('create_group'))<a href="{{ route('groups.create') }}" class="btn btn-primary">Create First Group</a>@else<button type="button" class="btn btn-primary" disabled>Create First Group</button>@endif</div>
  @endif

  @if($myGroups->isNotEmpty())
  <div class="section-title">Groups I Created ({{ $myGroups->count() }})</div>
  <div class="grid grid-3" style="margin-bottom:2rem;">
    @foreach($myGroups as $g)
    <a href="{{ route('groups.show',$g) }}" class="group-card">
      <div class="group-card-cover">
        @if($g->cover_photo)<img src="{{ $g->cover_photo_url }}">@else
        <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:2.5rem;">{{ ['💍','🎂','✈️','🎓','🏢','👨‍👩‍👧','🎉'][$loop->index % 7] }}</div>
        @endif
        <div style="position:absolute;top:8px;right:8px;"><span class="badge badge-primary">{{ ucfirst($g->privacy) }}</span></div>
      </div>
      <div class="group-card-body">
        <div class="group-card-title">{{ $g->name }}</div>
        <div class="group-card-meta">
          <span><i class="fa-solid fa-camera"></i> {{ $g->photos_count }}</span>
          <span><i class="fa-solid fa-users"></i> {{ $g->members_count }}</span>
          @if($g->event_date)<span><i class="fa-regular fa-calendar"></i> {{ $g->event_date->format('d M Y') }}</span>@endif
        </div>
      </div>
    </a>
    @endforeach
  </div>
  @endif

  @if($joinedGroups->isNotEmpty())
  <div class="section-title">Groups I Joined ({{ $joinedGroups->count() }})</div>
  <div class="grid grid-3">
    @foreach($joinedGroups as $g)
    <a href="{{ route('groups.show',$g) }}" class="group-card">
      <div class="group-card-cover">
        @if($g->cover_photo)<img src="{{ $g->cover_photo_url }}">@else
        <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:2.5rem;background:linear-gradient(135deg,#f093fb,#f5576c);">📸</div>
        @endif
      </div>
      <div class="group-card-body">
        <div class="group-card-title">{{ $g->name }}</div>
        <div class="group-card-meta"><span><i class="fa-solid fa-camera"></i> {{ $g->photos_count }}</span><span>By {{ $g->creator->name }}</span></div>
      </div>
    </a>
    @endforeach
  </div>
  @endif
</div>
@push('scripts')
<script>
function joinByLink(){const url=document.getElementById('joinUrl').value.trim();if(!url)return;const m=url.match(/\/g\/([a-zA-Z0-9]+)/);if(m)window.location.href='/g/'+m[1];else toast('Invalid LensPic link.');}
</script>
@endpush
@endsection
