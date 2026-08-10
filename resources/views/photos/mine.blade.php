@extends('layouts.app')
@section('title','My Photos')
@section('content')
<div class="page-wrap">
  <div class="page-header"><h1 class="page-title">📷 My Photos in {{ $group->name }}</h1><a href="{{ route('groups.show',$group) }}" class="btn btn-outline btn-sm">← Back</a></div>
  @if($photos->isEmpty())<div class="empty-state"><div class="ei">📸</div><p>No photos uploaded yet.</p></div>
  @else
  <div class="photo-grid">@foreach($photos as $p)<a href="{{ route('photos.show',[$group,$p]) }}" class="photo-card"><img src="{{ $p->thumbnail_url }}" loading="lazy"><div class="photo-overlay"><span style="color:#fff;font-size:11px;"><i class="fa-solid fa-heart"></i> {{ $p->likes_count }}</span></div></a>@endforeach</div>
  <div style="margin-top:1.5rem;">{{ $photos->links() }}</div>
  @endif
</div>
@endsection
