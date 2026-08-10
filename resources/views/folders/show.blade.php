@extends('layouts.app')
@section('title', $folder->name . ' - ' . $group->name)
@section('content')
<div class="page-wrap" style="max-width:100%;margin:0 auto;">
  <div style="display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;margin-bottom:1.25rem;">
    <div>
      <div style="font-size:12px;text-transform:uppercase;letter-spacing:.16em;color:#6366f1;font-weight:700;">Folder view</div>
      <h1 style="font-size:1.45rem;font-weight:800;color:#0f172a;">{{ $folder->name }}</h1>
      <p style="color:#64748b;">{{ $folder->description ?? 'Photos in this folder.' }}</p>
    </div>
    <div style="display:flex;gap:.6rem;flex-wrap:wrap;">
      <a href="{{ route('folders.index', $group) }}" class="btn btn-outline btn-sm"><i class="fa-solid fa-arrow-left"></i> Back to folders</a>
    </div>
  </div>

  @if($photos->isEmpty())
  <div class="empty-state">
    <div class="ei">📷</div>
    <h3>No photos in this folder yet</h3>
    <p>Upload photos to this folder from the main album view.</p>
  </div>
  @else
  <div class="photo-grid" style="column-width:240px;column-gap:.2rem;">
    @foreach($photos as $p)
    <div class="photo-card" style="display:inline-block;width:100%;margin:0 0 .05rem;">
      <img src="{{ $p->thumbnail_url }}" alt="" loading="lazy" style="display:block;width:100%;height:auto;">
    </div>
    @endforeach
  </div>
  <div style="margin-top:1.5rem;display:flex;justify-content:center;">{{ $photos->links() }}</div>
  @endif
</div>
@endsection
