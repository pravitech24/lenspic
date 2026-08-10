@extends('layouts.app')
@section('title','Members')
@section('content')
<div class="page-wrap" style="max-width:100%;">
  <div class="page-header"><h1 class="page-title">👥 Members ({{ $members->total() }})</h1><a href="{{ route('groups.show',$group) }}" class="btn btn-outline btn-sm">← Back</a></div>
  @if($isAdmin)
  <div class="card" style="margin-bottom:1.5rem;"><div class="card-body">
    <div style="font-weight:600;margin-bottom:.75rem;">Invite by Email</div>
    <form action="{{ route('groups.invite',$group) }}" method="POST">@csrf
      <div style="display:flex;gap:.5rem;"><input type="text" name="emails" placeholder="email1@example.com, email2@example.com" style="flex:1;"><button type="submit" class="btn btn-primary">Invite</button></div>
    </form>
  </div></div>
  @endif
  <div class="card"><div class="card-body" style="padding:0;">
    @foreach($members as $m)
    <div style="display:flex;align-items:center;gap:.85rem;padding:.9rem 1.25rem;border-bottom:1px solid #f1f5f9;">
      <img src="{{ $m->profile_photo_url }}" style="width:40px;height:40px;border-radius:50%;object-fit:cover;">
      <div style="flex:1;">
        <div style="font-weight:600;font-size:13.5px;">{{ $m->name }}</div>
        <div style="font-size:12px;color:#64748b;">{{ $m->email }} · Joined {{ \Carbon\Carbon::parse($m->pivot->joined_at)->diffForHumans() }}</div>
      </div>
      <span class="badge badge-primary">{{ ucfirst($m->pivot->role) }}</span>
      @if($isAdmin && $m->id !== $group->creator_id && $m->id !== auth()->id())
      <form action="{{ route('groups.members.remove',[$group,$m]) }}" method="POST" onsubmit="return confirm('Remove member?')">@csrf @method('DELETE')
        <button type="submit" class="btn btn-sm" style="background:none;color:#ef4444;border:1px solid #fecaca;border-radius:6px;padding:.25rem .5rem;"><i class="fa-solid fa-user-minus"></i></button>
      </form>
      @endif
    </div>
    @endforeach
  </div></div>
  <div style="margin-top:1rem;">{{ $members->links() }}</div>
</div>
@endsection
