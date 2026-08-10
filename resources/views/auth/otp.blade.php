@extends('layouts.app')
@section('title','Verify your account')
@section('content')
<div class="onboard-shell"><div class="onboard-card"><div class="onboard-icon">✉️</div><h1>Enter verification code</h1><p>Enter the 6-digit code sent to <strong>{{ $destination }}</strong>.</p>
@if($errors->any())<div class="alert alert-error">{{ $errors->first() }}</div>@endif
<form method="POST" action="{{ route('verify.otp') }}" id="otp-form">@csrf
  <label for="otp">Verification code</label>
  <input id="otp" class="otp-input" name="otp" type="text" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" placeholder="000000" value="{{ old('otp') }}" required autofocus>
  <small class="code-help"><span>6 digits</span><span id="code-count">{{ strlen(old('otp','')) }} / 6</span></small>
  <button class="btn btn-primary btn-lg full" id="verify" @disabled(strlen(old('otp',''))!==6)>Verify & continue</button>
</form>
<form method="POST" action="{{ route('send.otp') }}" id="resend">@csrf
  <input type="hidden" name="channel" value="{{ $channel }}">
  @if($channel==='mobile')<input type="hidden" name="country_code" value="{{ $countryCode }}"><input type="hidden" name="phone" value="{{ $nationalNumber }}">@else<input type="hidden" name="email" value="{{ $destination }}">@endif
  <button class="link-btn" id="resend-btn" disabled>Resend in <span id="count">{{ $resendSeconds }}</span>s</button>
</form><a href="{{ route('register') }}" class="change">Change {{ $channel==='email'?'email address':'mobile number' }}</a>
</div></div>
@endsection
@push('styles')<style>.onboard-shell{min-height:calc(100vh - 60px);display:grid;place-items:center;padding:24px;background:linear-gradient(135deg,#eef2ff,#fff1f2)}.onboard-card{width:min(100%,470px);background:#fff;padding:34px;border-radius:22px;box-shadow:var(--shadow-lg);text-align:center}.onboard-card h1{font-size:27px}.onboard-card p{color:var(--muted);margin:6px 0 24px}.onboard-card label{display:block;text-align:left}.otp-input{width:100%;height:64px;text-align:center;font-size:28px;font-weight:800;letter-spacing:.55em;padding-left:.55em;border:2px solid #cbd5e1;border-radius:14px;text-transform:uppercase}.otp-input:focus{border-color:var(--p);box-shadow:0 0 0 4px #6366f11a}.code-help{display:flex;justify-content:space-between;color:var(--muted);margin:7px 0 20px}.full{width:100%;justify-content:center}.link-btn{border:0;background:none;color:var(--p);margin:18px auto 8px;cursor:pointer}.link-btn:disabled{color:var(--muted)}.change{display:block;color:var(--muted);font-size:13px}</style>@endpush
@push('scripts')<script>const otp=document.getElementById('otp'),button=document.getElementById('verify'),counter=document.getElementById('code-count');function sync(){otp.value=otp.value.replace(/\D/g,'').slice(0,6);counter.textContent=otp.value.length+' / 6';button.disabled=otp.value.length!==6}otp.addEventListener('input',sync);sync();let n={{ $resendSeconds }},timer=setInterval(()=>{n--;document.getElementById('count').textContent=n;if(n<=0){clearInterval(timer);const b=document.getElementById('resend-btn');b.disabled=false;b.textContent='Resend code'}},1000);</script>@endpush
