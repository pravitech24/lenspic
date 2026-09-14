@extends('layouts.app')
@section('title','Create your account')
@section('content')
<div class="onboard-shell"><div class="onboard-card">
  <div class="onboard-icon">🔐</div><h1>Create your account</h1><p>Choose where you would like to receive your 6-digit verification code.</p>
  @if($errors->any())<div class="alert alert-error">{{ $errors->first() }}</div>@endif
  <form method="POST" action="{{ route('send.otp') }}" data-loading-form id="signup-contact">@csrf
    <div class="channel-switch" role="radiogroup" aria-label="Verification method">
      <label><input type="radio" name="channel" value="mobile" @checked(old('channel','mobile')==='mobile')><span>Mobile</span></label>
      <label><input type="radio" name="channel" value="email" @checked(old('channel')==='email')><span>Email</span></label>
    </div>
    <div data-channel-panel="mobile">
      <label for="phone">Mobile number</label><div class="phone-row">
        <select name="country_code" aria-label="Country code"><option value="+91" @selected(old('country_code',$defaultCountryCode)==='+91')>🇮🇳 +91</option><option value="+1" @selected(old('country_code',$defaultCountryCode)==='+1')>🇺🇸 +1</option><option value="+44" @selected(old('country_code',$defaultCountryCode)==='+44')>🇬🇧 +44</option></select>
        <input id="phone" name="phone" type="tel" inputmode="tel" autocomplete="tel-national" value="{{ old('phone') }}" placeholder="98765 43210">
      </div>
      <small class="delivery-note">By requesting a verification code, you agree to receive a one-time authentication message on WhatsApp.</small>
    </div>
    <div data-channel-panel="email" hidden>
      <label for="email">Email address</label>
      <input id="email" name="email" type="email" autocomplete="email" value="{{ old('email') }}" placeholder="you@example.com">
      <small class="delivery-note">The code will be delivered through email.</small>
    </div>
    <button class="btn btn-primary btn-lg full" type="submit">Send verification code</button>
  </form>
  <small class="terms">By continuing, you agree to our Terms and Privacy Policy.</small>
  <a href="{{ route('login') }}" class="login-link">Already registered? Log in</a>
</div></div>
@endsection
@push('styles')<style>.onboard-shell{min-height:calc(100vh - 60px);display:grid;place-items:center;padding:24px;background:linear-gradient(135deg,#eef2ff,#fff1f2)}.onboard-card{width:min(100%,480px);background:#fff;padding:34px;border-radius:22px;box-shadow:var(--shadow-lg)}.onboard-card h1{text-align:center;font-size:28px;margin-bottom:6px}.onboard-card>p{text-align:center;color:var(--muted);margin-bottom:22px}.onboard-icon{font-size:32px;text-align:center;margin-bottom:10px}.channel-switch{display:grid;grid-template-columns:1fr 1fr;background:#f1f5f9;padding:5px;border-radius:12px;margin-bottom:22px}.channel-switch input{position:absolute;opacity:0}.channel-switch span{display:block;text-align:center;padding:10px;border-radius:9px;font-weight:700;color:var(--muted);cursor:pointer}.channel-switch input:checked+span{background:#fff;color:var(--p);box-shadow:0 2px 8px #0f172a18}.phone-row{display:grid;grid-template-columns:115px 1fr;gap:8px}.delivery-note{display:block;color:var(--muted);margin:8px 0 18px}.full{width:100%;justify-content:center;margin-top:4px}.terms{display:block;text-align:center;color:var(--muted);margin-top:18px}.login-link{display:block;text-align:center;margin-top:12px;color:var(--p);font-weight:600}@media(max-width:420px){.onboard-card{padding:24px}.phone-row{grid-template-columns:105px 1fr}}</style>@endpush
@push('scripts')<script>(()=>{const radios=[...document.querySelectorAll('input[name="channel"]')],panels=[...document.querySelectorAll('[data-channel-panel]')];function toggle(){const selected=radios.find(r=>r.checked)?.value||'mobile';panels.forEach(panel=>{const active=panel.dataset.channelPanel===selected;panel.hidden=!active;panel.querySelectorAll('input,select').forEach(input=>{input.disabled=!active;if(input.tagName==='INPUT')input.required=active})});const focus=document.querySelector(`[data-channel-panel="${selected}"] input`);if(document.activeElement?.name==='channel')focus?.focus()}radios.forEach(r=>r.addEventListener('change',toggle));toggle()})();</script>@endpush
