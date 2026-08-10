@extends('layouts.app')
@section('title','Create Account')
@section('content')
<div style="min-height:calc(100vh - 60px);display:flex;align-items:center;justify-content:center;padding:2rem 1rem;background:linear-gradient(135deg,#f0f4ff,#fdf2f8);">
<div style="width:100%;max-width:440px;">
  <div style="text-align:center;margin-bottom:2rem;">
    <div style="width:52px;height:52px;background:linear-gradient(135deg,#6366f1,#ec4899);border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:24px;margin:0 auto 1rem;">⚡</div>
    <h1 style="font-family:'Plus Jakarta Sans',sans-serif;font-size:1.6rem;font-weight:800;margin-bottom:.3rem;">Create your account</h1>
    <p style="color:#64748b;font-size:14px;">Start sharing photos smarter</p>
  </div>
  <div class="card">
    <div class="card-body" style="padding:1.75rem;">
      @if($errors->any())<div class="alert alert-error">{{ $errors->first() }}</div>@endif
      <form action="{{ route('register') }}" method="POST">
        @csrf
        <div class="form-group"><label>Full Name</label><input type="text" name="name" value="{{ old('name') }}" placeholder="Priya Sharma" required autofocus></div>
        <div class="form-group"><label>Email Address</label><input type="email" name="email" value="{{ old('email') }}" placeholder="you@example.com" required></div>
        <div class="form-group"><label>Phone <span style="color:#94a3b8;font-weight:400;">(optional)</span></label><input type="tel" name="phone" value="{{ old('phone') }}" placeholder="+91 9876543210"></div>
        <div class="form-group"><label>Password</label><input type="password" name="password" placeholder="Min 8 characters" required></div>
        <div class="form-group"><label>Confirm Password</label><input type="password" name="password_confirmation" placeholder="Repeat password" required></div>
        <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;padding:.7rem;margin-top:.25rem;">Create Account</button>
      </form>
    </div>
  </div>
  <p style="text-align:center;margin-top:1rem;font-size:13.5px;color:#64748b;">Already have an account? <a href="{{ route('login') }}" style="color:#6366f1;font-weight:600;">Sign In</a></p>
</div>
</div>
@endsection
