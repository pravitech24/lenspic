@extends('layouts.app')
@section('title','Login')
@section('content')
<div style="min-height:calc(100vh - 60px);display:flex;align-items:center;justify-content:center;padding:2rem 1rem;background:linear-gradient(135deg,#f0f4ff,#fdf2f8);">
<div style="width:100%;max-width:420px;">
  <div style="text-align:center;margin-bottom:2rem;">
    <div style="width:52px;height:52px;background:linear-gradient(135deg,#6366f1,#ec4899);border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:24px;margin:0 auto 1rem;">⚡</div>
    <h1 style="font-family:'Plus Jakarta Sans',sans-serif;font-size:1.6rem;font-weight:800;margin-bottom:.3rem;">Welcome back</h1>
    <p style="color:#64748b;font-size:14px;">Sign in to your LensPic account</p>
  </div>
  <div class="card">
    <div class="card-body" style="padding:1.75rem;">
      @if($errors->any())<div class="alert alert-error">{{ $errors->first() }}</div>@endif
      <form action="{{ route('login') }}" method="POST">
        @csrf
        <div class="form-group"><label>Email</label><input type="email" name="email" value="{{ old('email') }}" placeholder="you@example.com" required autofocus></div>
        <div class="form-group"><label>Password</label><input type="password" name="password" placeholder="••••••••" required></div>
        <div style="display:flex;align-items:center;margin-bottom:1.25rem;">
          <label style="display:flex;align-items:center;gap:6px;cursor:pointer;margin:0;color:#374151;font-size:13px;font-weight:400;">
            <input type="checkbox" name="remember"> Remember me
          </label>
        </div>
        <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;padding:.7rem;">Sign In</button>
      </form>
      <div style="display:flex;align-items:center;gap:1rem;margin:1.25rem 0;">
        <div style="flex:1;height:1px;background:#e2e8f0;"></div><span style="font-size:12px;color:#94a3b8;">OR</span><div style="flex:1;height:1px;background:#e2e8f0;"></div>
      </div>
      <a href="{{ route('register') }}" class="btn btn-outline" style="width:100%;justify-content:center;">
        <i class="fa-solid fa-mobile-screen-button"></i> Continue with Mobile OTP
      </a>
    </div>
  </div>
  <p style="text-align:center;margin-top:1rem;font-size:13.5px;color:#64748b;">No account? <a href="{{ route('register') }}" style="color:#6366f1;font-weight:600;">Create one</a></p>
</div>
</div>
@endsection
