@extends('layouts.app')
@section('title','Joining group')
@section('content')<div class="joining"><div><span class="spinner big"></span><h1>Joining your group…</h1><p>We’re confirming your invitation. Please don’t close this page.</p><div class="alert alert-error" id="join-error" hidden></div></div></div>@endsection
@push('styles')<style>.joining{min-height:calc(100vh - 60px);display:grid;place-items:center;text-align:center;padding:24px}.joining p{color:var(--muted)}.big{width:42px;height:42px;margin-bottom:18px}</style>@endpush
@push('scripts')<script>(async()=>{try{const r=await fetch('{{ route('groups.join-api') }}',{method:'POST',headers:{'Accept':'application/json','X-CSRF-TOKEN':csrf});const b=await r.json();if(!r.ok)throw new Error(b.message||Object.values(b.errors||{})[0]?.[0]||'Unable to join this group.');location.href=b.redirect_url}catch(e){const box=document.getElementById('join-error');box.textContent=e.message;box.hidden=false}})()</script>@endpush
