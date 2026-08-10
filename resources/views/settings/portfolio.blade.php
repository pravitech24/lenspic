@extends('settings.layout')
@section('title','Portfolio')
@section('settings-content')
<div style="margin-bottom:1.5rem;"><h1 style="font-family:'Plus Jakarta Sans',sans-serif;font-size:1.3rem;font-weight:800;">Portfolio</h1></div>
<div class="card" style="margin-bottom:1.25rem;">
  <div class="card-body" style="text-align:center;padding:3rem 2rem;">
    <div style="font-size:3rem;margin-bottom:1rem;">🌐</div>
    <h2 style="font-weight:800;font-size:1.2rem;margin-bottom:.5rem;">Your Personalised Photography Website</h2>
    <p style="color:#64748b;max-width:500px;margin:0 auto 1.5rem;font-size:14px;">With Essential and Premium plans, we help you launch a fully branded photography website on your own domain — portfolio, booking form, AI photo delivery and more.</p>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:1rem;max-width:700px;margin:0 auto 2rem;text-align:left;">
      @foreach(['🌐 Custom Domain (yourname.com)','🎨 Your Logo & Brand Colors','📸 Portfolio Gallery','📩 Client Booking Form','⚡ AI-Powered Photo Delivery','📊 Analytics Dashboard'] as $feat)
      <div style="display:flex;align-items:center;gap:.5rem;font-size:13px;font-weight:500;"><span>{{ $feat }}</span></div>
      @endforeach
    </div>
    @if(in_array($user->plan,['essential','premium','pro','business','enterprise']))
    <a href="mailto:hello@lenspic.in?subject=Portfolio Website Setup" class="btn btn-primary btn-lg"><i class="fa-solid fa-envelope"></i> Request Setup</a>
    @else
    <a href="{{ route('pricing') }}" class="btn btn-primary btn-lg"><i class="fa-solid fa-arrow-up"></i> Upgrade to Essential</a>
    <p style="margin-top:.75rem;font-size:12px;color:#94a3b8;">Available on Essential and Premium plans</p>
    @endif
  </div>
</div>
@endsection
