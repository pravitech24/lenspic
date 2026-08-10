@extends('settings.layout')
@section('title','Team Login')
@section('settings-content')
<div style="margin-bottom:1.5rem;"><h1 style="font-family:'Plus Jakarta Sans',sans-serif;font-size:1.3rem;font-weight:800;">Team Login <span class="badge badge-primary" style="font-size:10px;vertical-align:middle;">New</span></h1></div>
@if(!in_array($user->plan,['essential','premium','pro','business','enterprise']))
<div style="background:linear-gradient(135deg,#fff7ed,#fef3c7);border:1px solid #fcd34d;border-radius:12px;padding:1.25rem;margin-bottom:1.5rem;display:flex;align-items:center;gap:1rem;">
  <div style="font-size:1.5rem;">🔒</div>
  <div><div style="font-weight:700;margin-bottom:.2rem;">Upgrade to Essential</div>
  <div style="font-size:13px;color:#92400e;">Team Login is available on Essential and Premium plans. <a href="{{ route('pricing') }}" style="color:#d97706;font-weight:700;">Upgrade Now →</a></div></div>
</div>
@endif
<div class="card">
  <div class="card-header" style="display:flex;align-items:center;justify-content:space-between;">
    Team Members
    <button class="btn btn-primary btn-sm" {{ !in_array($user->plan,['essential','premium','pro','business','enterprise']) ? 'disabled' : '' }}><i class="fa-solid fa-plus"></i> Invite Member</button>
  </div>
  <div class="card-body" style="text-align:center;padding:3rem;">
    <div style="font-size:3rem;margin-bottom:1rem;opacity:.3;">👥</div>
    <p style="color:#64748b;font-size:14px;">No team members yet. Invite photographers to collaborate.</p>
  </div>
</div>
@endsection
