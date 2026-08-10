@extends('layouts.app')
@section('content')
<div style="margin:0 auto;padding:2rem 1.5rem;display:grid;grid-template-columns:240px 1fr;gap:1.75rem;align-items:start;" class="settings-wrap">

  <!-- Sidebar -->
  <aside>
    <div class="card" style="overflow:visible;">
      <div style="padding:1.25rem 1rem;border-bottom:1px solid #e2e8f0;display:flex;align-items:center;gap:.75rem;">
        <img src="{{ auth()->user()->profile_photo_url }}" style="width:42px;height:42px;border-radius:50%;object-fit:cover;border:2px solid #e2e8f0;">
        <div style="min-width:0;">
          <div style="font-weight:700;font-size:13.5px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ auth()->user()->name }}</div>
          <div style="font-size:11px;margin-top:1px;">
            <span style="background:linear-gradient(135deg,#6366f1,#ec4899);-webkit-background-clip:text;-webkit-text-fill-color:transparent;font-weight:700;">{{ auth()->user()->plan === 'free' ? 'No paid plan' : auth()->user()->plan_label.' Plan' }}</span>
          </div>
        </div>
      </div>

      <div style="padding:.5rem 0;">
        <div style="font-size:10px;font-weight:700;color:#94a3b8;letter-spacing:.08em;text-transform:uppercase;padding:.5rem 1rem .3rem;">Business Settings</div>

        @php
        $links = [
          ['settings.profile',      'fa-user',          'Your Profile'],
          ['settings.branding',     'fa-palette',       'Business Branding'],
          ['settings.watermark',    'fa-water',         'Watermark'],
          ['settings.portfolio',    'fa-globe',         'Portfolio'],
          ['settings.wallet',       'fa-wallet',        'LensPic Wallet'],
          ['settings.transactions', 'fa-receipt',       'Transactions',  'New'],
          ['settings.team',         'fa-users',         'Team Login',    'New'],
        ];
        @endphp

        @foreach($links as $link)
        <a href="{{ route($link[0]) }}" style="display:flex;align-items:center;justify-content:space-between;gap:8px;padding:.6rem 1rem;font-size:13px;font-weight:500;text-decoration:none;color:{{ request()->routeIs($link[0]) ? '#6366f1' : '#374151' }};background:{{ request()->routeIs($link[0]) ? '#eef2ff' : 'transparent' }};transition:all .15s;" onmouseover="if(!this.style.background.includes('eef'))this.style.background='#f8fafc'" onmouseout="if(!this.style.background.includes('eef'))this.style.background='transparent'">
          <span style="display:flex;align-items:center;gap:8px;"><i class="fa-solid {{ $link[1] }}" style="width:16px;text-align:center;font-size:13px;"></i> {{ $link[2] }}</span>
          @if(isset($link[3]))<span style="font-size:10px;background:#6366f1;color:#fff;padding:1px 6px;border-radius:4px;font-weight:700;">{{ $link[3] }}</span>@endif
        </a>
        @endforeach

        <div style="font-size:10px;font-weight:700;color:#94a3b8;letter-spacing:.08em;text-transform:uppercase;padding:.75rem 1rem .3rem;margin-top:.25rem;">Account</div>
        <a href="{{ route('settings.subscription') }}" style="display:flex;align-items:center;gap:8px;padding:.6rem 1rem;font-size:13px;font-weight:500;text-decoration:none;color:{{ request()->routeIs('settings.subscription') ? '#6366f1' : '#374151' }};background:{{ request()->routeIs('settings.subscription') ? '#eef2ff' : 'transparent' }};">
          <i class="fa-solid fa-crown" style="width:16px;text-align:center;font-size:13px;color:#f59e0b;"></i> Subscription
        </a>
        <a href="{{ route('pricing') }}" style="display:flex;align-items:center;gap:8px;padding:.6rem 1rem;font-size:13px;font-weight:500;text-decoration:none;color:#374151;">
          <i class="fa-solid fa-tags" style="width:16px;text-align:center;font-size:13px;"></i> Plans & Pricing
        </a>
      </div>
    </div>
  </aside>

  <!-- Content -->
  <div>
    @if(session('success'))<div class="alert alert-success" style="margin-bottom:1.25rem;"><i class="fa-solid fa-check-circle"></i> {{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-error" style="margin-bottom:1.25rem;"><i class="fa-solid fa-circle-xmark"></i> {{ $errors->first() }}</div>@endif
    @yield('settings-content')
  </div>
</div>
@push('styles')
<style>@media(max-width:768px){.settings-wrap{grid-template-columns:1fr!important;}}</style>
@endpush
@endsection
