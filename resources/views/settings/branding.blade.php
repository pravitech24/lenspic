@extends('settings.layout')
@section('title','Business Branding')
@section('settings-content')
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;flex-wrap:wrap;gap:.75rem;">
  <h1 style="font-family:'Plus Jakarta Sans',sans-serif;font-size:1.3rem;font-weight:800;">Business Branding</h1>
  <button form="brandForm" type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Save</button>
</div>

@if($user->plan === 'free')
<div style="background:linear-gradient(135deg,#fff7ed,#fef3c7);border:1px solid #fcd34d;border-radius:12px;padding:1.25rem;margin-bottom:1.5rem;display:flex;align-items:center;gap:1rem;">
  <div style="font-size:1.5rem;">🔒</div>
  <div><div style="font-weight:700;margin-bottom:.2rem;">Upgrade to Standard</div>
  <div style="font-size:13px;color:#92400e;">Business Branding is available on all paid plans. <a href="{{ route('pricing') }}" style="color:#d97706;font-weight:700;">View Plans →</a></div></div>
</div>
@endif

<div class="card">
  <div class="card-body" style="padding:1.75rem;">
    <form id="brandForm" action="{{ route('settings.branding.update') }}" method="POST" enctype="multipart/form-data">
      @csrf
      <div class="form-group">
        <label>Studio / Business Name</label>
        <input type="text" name="studio_name" value="{{ old('studio_name', $user->meta['studio_name'] ?? '') }}" placeholder="e.g. Rahul Photography" {{ $user->plan === 'free' ? 'disabled' : '' }}>
      </div>
      <div class="form-group">
        <label>Tagline</label>
        <input type="text" name="studio_tagline" value="{{ old('studio_tagline', $user->meta['studio_tagline'] ?? '') }}" placeholder="Capturing memories since 2018" {{ $user->plan === 'free' ? 'disabled' : '' }}>
      </div>
      <div class="form-group">
        <label>Brand Color</label>
        <div style="display:flex;align-items:center;gap:.75rem;">
          <input type="color" name="brand_color" value="{{ $user->meta['brand_color'] ?? '#6366f1' }}" style="width:48px;height:40px;border-radius:8px;padding:2px;cursor:pointer;" {{ $user->plan === 'free' ? 'disabled' : '' }}>
          <input type="text" value="{{ $user->meta['brand_color'] ?? '#6366f1' }}" style="flex:1;" readonly>
        </div>
      </div>
      <div class="form-group">
        <label>Studio Logo</label>
        @if(!empty($user->meta['logo']))
        <div style="margin-bottom:.75rem;"><img src="{{ asset('storage/'.$user->meta['logo']) }}" style="height:60px;object-fit:contain;border:1px solid #e2e8f0;border-radius:8px;padding:8px;"></div>
        @endif
        <div class="upload-zone" onclick="document.getElementById('logoInput').click()" style="padding:1.25rem;" {{ $user->plan === 'free' ? '' : '' }}>
          <div style="font-size:1.5rem;margin-bottom:.35rem;">🏷️</div>
          <p style="font-size:13px;color:#64748b;">Click to upload logo (PNG/SVG recommended)</p>
        </div>
        <input type="file" id="logoInput" name="logo" accept="image/*" hidden {{ $user->plan === 'free' ? 'disabled' : '' }}>
      </div>
      @if($user->plan === 'free')
      <div style="text-align:center;padding:1rem;"><a href="{{ route('pricing') }}" class="btn btn-primary"><i class="fa-solid fa-arrow-up"></i> Upgrade to Enable Branding</a></div>
      @endif
    </form>
  </div>
</div>
@endsection
