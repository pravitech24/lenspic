@extends('layouts.auth')
@section('title','Verify your account')
@section('eyebrow','Secure verification')
@section('heading','Enter verification code')
@section('description')
Enter the 6-digit code sent to <strong>{{ $maskedDestination ?? $destination }}</strong>.
@endsection
@section('content')
<div class="auth-card">
    @if($channel==='sms')
        <p class="auth-help" role="status">Verification code sent successfully by SMS.</p>
    @elseif(($authenticationChannel ?? null)==='whatsapp')
        <p class="auth-help">Code sent on WhatsApp. By requesting a verification code, you agree to receive a one-time authentication message on WhatsApp.</p>
    @endif
    @if($errors->any())
        <div class="auth-error" role="alert">{{ $errors->first() }}</div>
    @endif
    <form method="POST" action="{{ route('verify.otp') }}" id="otp-form">
        @csrf
        <label for="otp">Verification code</label>
        <input id="otp" name="otp" type="text" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" placeholder="000000" required autofocus style="height:64px;text-align:center;font-size:26px;font-weight:800;letter-spacing:.45em;padding-left:.45em">
        <div style="display:flex;justify-content:space-between;margin-top:7px" class="auth-help"><span>6 digits</span><span id="code-count">0 / 6</span></div>
        <button id="verify" disabled>Verify &amp; continue</button>
    </form>
    <form method="POST" action="{{ route('send.otp') }}" id="resend">
        @csrf
        <input type="hidden" name="request_id" value="{{ (string) \Illuminate\Support\Str::uuid() }}">
        <input type="hidden" name="channel" value="{{ $channel }}">
        @if($channel==='sms')
            <input type="hidden" name="resend" value="1">
        @elseif($channel==='mobile')
            <input type="hidden" name="country_code" value="{{ $countryCode }}">
            <input type="hidden" name="phone" value="{{ $nationalNumber }}">
        @else
            <input type="hidden" name="email" value="{{ $destination }}">
        @endif
        <button class="auth-inline-button" id="resend-btn" disabled>Resend in <span id="count">{{ $resendSeconds }}</span>s</button>
    </form>
    <p id="otp-status" class="auth-help" role="status" aria-live="polite"></p>
    <a href="{{ route('register') }}" class="auth-link">Change verification method or contact details</a>
</div>
@endsection
@push('scripts')
<script>
const otp=document.getElementById('otp'),button=document.getElementById('verify'),counter=document.getElementById('code-count'),status=document.getElementById('otp-status');
function sync(){otp.value=otp.value.replace(/\D/g,'').slice(0,6);counter.textContent=otp.value.length+' / 6';button.disabled=otp.value.length!==6}
otp.addEventListener('input',sync);sync();
let n={{ $resendSeconds }},timer;
const resendButton=document.getElementById('resend-btn');
function tick(){document.getElementById('count').textContent=Math.max(0,n);if(n<=0){clearInterval(timer);resendButton.disabled=false;resendButton.textContent='Resend code'}}
tick();if(n>0)timer=setInterval(()=>{n--;tick()},1000);
document.getElementById('otp-form').addEventListener('submit',function(event){if(this.dataset.busy){event.preventDefault();return}this.dataset.busy='true';this.setAttribute('aria-busy','true');button.disabled=true;button.textContent='Verifying…';status.textContent='Verifying your code…'});
document.getElementById('resend').addEventListener('submit',function(event){if(this.dataset.busy){event.preventDefault();return}this.dataset.busy='true';this.setAttribute('aria-busy','true');resendButton.disabled=true;resendButton.textContent='Requesting code…';status.textContent='Requesting verification code…'});
</script>
@endpush
