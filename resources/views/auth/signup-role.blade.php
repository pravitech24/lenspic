@extends('layouts.app')
@section('title','Choose account type')
@section('content')
<div class="role-shell"><div class="role-wrap"><h1>How would you like to use the platform?</h1><p>Choose the experience that best fits you.</p>@if($errors->any())<div class="alert alert-error">{{ $errors->first() }}</div>@endif
<form method="POST" action="{{ route('signup.role.store') }}">@csrf<div class="role-grid">
@foreach(['user'=>['👤','I am a User','Join groups, access event photos and discover your memories.'],'photographer'=>['📷','I am a Photographer','Create groups, upload event photos and share them with participants.']] as $value=>$option)
<label class="role-card"><input type="radio" name="role" value="{{ $value }}" @checked(old('role',$selectedRole)===$value)><span class="role-icon">{{ $option[0] }}</span><strong>{{ $option[1] }}</strong><span>{{ $option[2] }}</span></label>
@endforeach</div><button class="btn btn-primary btn-lg full" id="continue" disabled>Continue</button></form></div></div>
@endsection
@push('styles')<style>.role-shell{min-height:calc(100vh - 60px);display:grid;place-items:center;padding:24px;background:linear-gradient(135deg,#eef2ff,#fdf2f8)}.role-wrap{width:min(100%,760px);text-align:center}.role-wrap>p{color:var(--muted);margin:6px 0 28px}.role-grid{display:grid;grid-template-columns:1fr 1fr;gap:18px;margin-bottom:22px}.role-card{display:flex;flex-direction:column;align-items:flex-start;text-align:left;gap:8px;background:#fff;border:2px solid var(--border);border-radius:18px;padding:26px;cursor:pointer;box-shadow:var(--shadow)}.role-card:has(input:checked){border-color:var(--p);background:#eef2ff}.role-card input{position:absolute;opacity:0}.role-card strong{font-size:18px;color:var(--text)}.role-card span:last-child{color:var(--muted)}.role-icon{font-size:32px}.full{width:100%;justify-content:center}@media(max-width:600px){.role-grid{grid-template-columns:1fr}}</style>@endpush
@push('scripts')<script>const radios=document.querySelectorAll('[name=role]'),button=document.getElementById('continue');function sync(){button.disabled=!document.querySelector('[name=role]:checked')}radios.forEach(x=>x.onchange=sync);sync()</script>@endpush
