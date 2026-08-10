@extends('admin.layout')
@section('title','Edit User')
@section('content')
<div style="max-width:580px;">
  <div class="page-header">
    <h1 class="page-title">Edit User</h1>
    <a href="{{ route('admin.users') }}" class="btn btn-outline btn-sm">← Back</a>
  </div>
  <div class="card"><div class="card-body">
    <div style="display:flex;align-items:center;gap:1rem;padding:1rem;background:var(--sur2);border-radius:10px;margin-bottom:1.5rem;">
      <img src="{{ $user->profile_photo_url }}" style="width:56px;height:56px;border-radius:50%;object-fit:cover;">
      <div><div style="font-weight:700;">{{ $user->name }}</div><div style="font-size:13px;color:var(--muted);">Member since {{ $user->created_at->format('d M Y') }}</div></div>
    </div>
    <form action="{{ route('admin.users.update',$user) }}" method="POST">
      @csrf @method('PUT')
      @if($errors->any())<div class="alert alert-error">{{ $errors->first() }}</div>@endif
      <div class="form-group"><label>Full Name</label><input type="text" name="name" value="{{ old('name',$user->name) }}" required></div>
      <div class="form-group"><label>Email</label><input type="email" name="email" value="{{ old('email',$user->email) }}" required></div>
      <div class="form-group"><label>Phone</label><input type="tel" name="phone" value="{{ old('phone',$user->phone) }}" placeholder="+91 9876543210"></div>
      <div class="form-group"><label>New Password <span style="color:var(--muted);font-weight:400;">(leave blank to keep current)</span></label><input type="password" name="password" placeholder="Min 8 characters"></div>
      <div style="display:flex;align-items:center;justify-content:space-between;padding:.85rem 1rem;background:var(--sur2);border-radius:8px;margin-bottom:1rem;">
        <div>
          <div style="font-weight:600;font-size:13.5px;">Super Admin</div>
          <div style="font-size:12px;color:var(--muted);">Grant full admin access to this user</div>
        </div>
        <label class="toggle"><input type="checkbox" name="is_admin" {{ $user->is_admin?'checked':'' }}><span class="toggle-sl"></span></label>
      </div>
      <div style="display:flex;gap:.75rem;">
        <button type="submit" class="btn btn-primary" style="flex:1;justify-content:center;">Save Changes</button>
        <a href="{{ route('admin.users') }}" class="btn btn-outline">Cancel</a>
      </div>
    </form>
  </div></div>
</div>
@endsection
