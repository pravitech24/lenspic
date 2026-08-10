@extends('layouts.app')
@section('title','Profile')
@section('content')
<div class="page-wrap" 
  style="max-width:100%;"
  >
  <h1 class="page-title" style="margin-bottom:1.5rem;">My Profile</h1>
  <div class="grid grid-3" style="margin-bottom:2rem;">
    @foreach([['Groups','🗂️',$stats['groups']],['Photos','📸',$stats['photos']],['Likes','❤️',$stats['likes']]] as [$l,$icon,$v])
    <div class="card"><div class="card-body" style="text-align:center;padding:1.25rem 1rem;">
      <div style="font-size:1.5rem;margin-bottom:.25rem;">{{ $icon }}</div>
      <div style="font-size:1.6rem;font-weight:800;line-height:1;">{{ $v }}</div>
      <div style="font-size:12px;color:#64748b;margin-top:2px;">{{ $l }}</div>
    </div></div>
    @endforeach
  </div>
  <div class="card" style="margin-bottom:1.25rem;"><div class="card-body" style="display:flex;align-items:center;gap:1.25rem;flex-wrap:wrap;">
    <img src="{{ auth()->user()->profile_photo_url }}" style="width:80px;height:80px;border-radius:50%;object-fit:cover;border:3px solid #e2e8f0;">
    <div>
      <div style="font-weight:700;font-size:1rem;margin-bottom:.25rem;">{{ auth()->user()->name }}</div>
      <div style="font-size:13px;color:#64748b;margin-bottom:.75rem;">{{ auth()->user()->email }}</div>
      <form action="{{ route('profile.photo') }}" method="POST" enctype="multipart/form-data" id="pf">@csrf
        <input type="file" name="photo" accept="image/*" id="pi" hidden onchange="document.getElementById('pf').submit()">
        <button type="button" onclick="document.getElementById('pi').click()" class="btn btn-outline btn-sm"><i class="fa-solid fa-camera"></i> Change Photo</button>
      </form>
    </div>
  </div></div>
  <div class="card"><div class="card-header"><strong>Edit Profile</strong></div>
    <div class="card-body">
      @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
      @if($errors->any())<div class="alert alert-error">{{ $errors->first() }}</div>@endif
      <form action="{{ route('profile.update') }}" method="POST">@csrf @method('PUT')
        <div class="form-group"><label>Full Name</label><input type="text" name="name" value="{{ old('name',auth()->user()->name) }}" required></div>
        <div class="form-group"><label>Email</label><input type="email" name="email" value="{{ old('email',auth()->user()->email) }}" required></div>
        <div class="form-group"><label>Phone</label><input type="tel" name="phone" value="{{ old('phone',auth()->user()->phone) }}" placeholder="+91 9876543210"></div>
        <button type="submit" class="btn btn-primary">Save Changes</button>
      </form>
    </div>
  </div>
</div>
@endsection
