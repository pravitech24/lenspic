@extends('layouts.app')
@section('title','Join a Group')
@section('content')
<main class="join-shell">
  <section class="join-card" id="code-step" aria-labelledby="join-title">
    <div class="join-icon" aria-hidden="true"><i class="fa-solid fa-key"></i></div>
    <h1 id="join-title">Join a Group</h1>
    <p>Enter the 6-character invitation code shared by your photographer.</p>
    <div class="inline-error" id="error" role="alert"><i class="fa-solid fa-circle-exclamation"></i><span></span></div>

    <form id="code-form">
      <fieldset class="code-fieldset">
        <legend>Invitation code</legend>
        <input type="text" id="code" name="code" class="code-input" inputmode="text" maxlength="6" minlength="6" autocomplete="one-time-code" autocapitalize="characters" spellcheck="false" placeholder="A7K3Q9" aria-describedby="code-help error" autofocus>
        <div class="code-meta" id="code-help"><span>Letters and numbers</span><span id="code-count">0 / 6</span></div>
      </fieldset>
      <button type="submit" class="btn btn-primary btn-lg full" id="continue" disabled><span>Continue</span><i class="fa-solid fa-arrow-right"></i></button>
    </form>
  </section>

  <section class="join-card preview" id="preview-step" hidden>
    <img id="cover" alt="Event cover">
    <span class="access" id="access"></span><h1 id="group-name"></h1><p id="photographer"></p><p id="date"></p>
    <div class="auth-box"><strong>Please log in or create an account to join this group.</strong>@guest<a class="btn btn-outline full disabled" aria-disabled="true" title="Google authentication is not configured"><i class="fa-brands fa-google"></i> Continue with Google</a><a href="{{ route('login') }}" class="btn btn-primary full"><i class="fa-solid fa-envelope"></i> Continue with Email</a><a href="{{ route('login') }}" class="text-link">Login</a><a href="{{ route('register') }}" class="text-link">Create Account with Mobile</a>@else<a href="{{ route('groups.join.complete') }}" class="btn btn-primary btn-lg full">Continue to Join</a>@endguest</div>
    <button class="back-link" id="change-code">← Use another code</button>
  </section>
</main>
@endsection

@push('styles')
<style>
  .join-shell{min-height:calc(100vh - 60px);display:grid;place-items:center;padding:40px 20px;background:radial-gradient(circle at 50% 42%,#fff 0,#f8f7ff 38%,#f2f4ff 100%)}
  .join-card{box-sizing:border-box;width:min(100%,460px);background:rgba(255,255,255,.97);padding:38px 36px 30px;border:1px solid #e8e7f2;border-radius:24px;box-shadow:0 22px 55px rgba(50,50,93,.12),0 4px 14px rgba(15,23,42,.06);text-align:center;animation:enter .25s ease}
  .join-card h1{margin:0;color:#111827;font-size:28px;line-height:1.2;letter-spacing:-.025em}.join-card>p{max-width:340px;color:var(--muted);margin:9px auto 25px;font-size:14px;line-height:1.55}
  .join-icon{width:54px;height:54px;display:grid;place-items:center;margin:0 auto 16px;border-radius:16px;background:linear-gradient(145deg,#eef2ff,#e5e7ff);color:#5b5ff0;font-size:20px}
  .code-fieldset{min-width:0;margin:0 0 22px;padding:0;border:0;text-align:left}.code-fieldset legend{display:block;width:100%;margin-bottom:9px;color:#344054;font-size:13px;font-weight:700}
  .code-input{box-sizing:border-box;width:100%;height:62px;padding:0 20px;text-align:center;color:#111827;background:#fff;border:1.5px solid #d0d5dd;border-radius:12px;font-size:25px;font-weight:800;letter-spacing:.28em;line-height:1;text-transform:uppercase;caret-color:#6366f1;transition:border-color .15s,box-shadow .15s,background .15s}
  .code-input::placeholder{color:#c5cad3}.code-input:hover{border-color:#a5b4fc}.code-input:focus{outline:0;border-color:#6366f1;box-shadow:0 0 0 4px rgba(99,102,241,.13)}.code-input.complete{background:#f7f7ff;border-color:#818cf8;color:#4338ca}
  .code-meta{display:flex;justify-content:space-between;margin-top:8px;color:#98a2b3;font-size:11px}.full{width:100%;justify-content:center;gap:9px;margin-top:0;min-height:48px}.full:disabled{cursor:not-allowed;opacity:.55}
  .inline-error{display:none;align-items:flex-start;gap:9px;background:#fff1f0;border:1px solid #ffccc7;color:#b42318;padding:11px 13px;border-radius:10px;margin-bottom:18px;text-align:left;font-size:13px;line-height:1.4}.inline-error.show{display:flex}
  .preview>img{width:calc(100% + 72px);height:190px;object-fit:cover;margin:-38px -36px 22px;display:none;border-radius:24px 24px 0 0}.access{display:inline-block;background:#eef2ff;color:var(--p);padding:4px 10px;border-radius:99px;font-weight:700}.auth-box{display:grid;gap:8px;padding:18px;background:#f8fafc;border-radius:14px}.auth-box strong{margin-bottom:5px}.text-link{color:var(--p);font-weight:600}.disabled{opacity:.55;cursor:not-allowed}.back-link{border:0;background:none;color:var(--muted);margin-top:18px;cursor:pointer}@keyframes enter{from{opacity:0;transform:translateY(8px)}}
  @media(max-width:520px){.join-shell{padding:24px 14px}.join-card{padding:30px 18px 24px;border-radius:20px}.code-input{height:56px;font-size:22px}.preview>img{width:calc(100% + 36px);margin:-30px -18px 20px}}
</style>
@endpush

@push('scripts')
<script>
(() => {
  const input=document.getElementById('code'),button=document.getElementById('continue'),form=document.getElementById('code-form'),error=document.getElementById('error'),count=document.getElementById('code-count');
  const clean=value=>value.toUpperCase().replace(/[^A-Z0-9]/g,'').slice(0,6);
  const valid=value=>value.length===6&&/[A-Z]/.test(value)&&/[0-9]/.test(value);
  function sync(){input.value=clean(input.value);count.textContent=`${input.value.length} / 6`;button.disabled=!valid(input.value);input.classList.toggle('complete',valid(input.value));error.classList.remove('show')}
  input.addEventListener('input',sync);
  input.addEventListener('paste',event=>{event.preventDefault();input.value=clean(event.clipboardData.getData('text'));sync()});
  form.addEventListener('submit',async event=>{event.preventDefault();sync();if(!valid(input.value)){error.querySelector('span').textContent='Enter a 6-character code containing both letters and numbers.';error.classList.add('show');input.focus();return}button.disabled=true;button.innerHTML='<span class="spinner"></span> Checking…';try{const response=await fetch('{{ route('groups.validate-code') }}',{method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':csrf},body:JSON.stringify({code:input.value})});const body=await response.json();if(!response.ok)throw new Error(body.message||body.errors?.code?.[0]||'Unable to validate this code.');const data=body.data;document.getElementById('group-name').textContent=data.group_name;document.getElementById('photographer').textContent='by '+data.photographer_name;document.getElementById('date').textContent=data.event_date||'';document.getElementById('access').textContent=data.access_type;if(data.cover_image){const cover=document.getElementById('cover');cover.src=data.cover_image;cover.style.display='block'}document.getElementById('code-step').hidden=true;document.getElementById('preview-step').hidden=false}catch(exception){error.querySelector('span').textContent=exception.message;error.classList.add('show')}finally{button.disabled=!valid(input.value);button.innerHTML='<span>Continue</span><i class="fa-solid fa-arrow-right"></i>'}});
  document.getElementById('change-code').addEventListener('click',()=>{document.getElementById('preview-step').hidden=true;document.getElementById('code-step').hidden=false;input.focus()});sync();
})();
</script>
@endpush
