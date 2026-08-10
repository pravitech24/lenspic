<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title','LensPic') – Smart Photo Sharing</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
:root{--p:#6366f1;--pd:#4f46e5;--s:#ec4899;--bg:#f8fafc;--text:#0f172a;--muted:#64748b;--border:#e2e8f0;--radius:12px;--shadow:0 1px 3px rgba(0,0,0,.08),0 4px 12px rgba(0,0,0,.04);--shadow-lg:0 4px 20px rgba(0,0,0,.12);}
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'Inter',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;font-size:14px;line-height:1.6;}
.navbar{background:rgba(255,255,255,.96);backdrop-filter:blur(12px);border-bottom:1px solid var(--border);position:sticky;top:0;z-index:100;height:60px;padding:0 1.5rem;display:flex;align-items:center;}
.nav-inner{/*max-width:1600px;*/margin:0 auto;width:100%;display:flex;align-items:center;justify-content:space-between;gap:1rem;}
.logo{display:flex;align-items:center;gap:8px;font-family:'Plus Jakarta Sans',sans-serif;font-weight:800;font-size:1.25rem;color:var(--p);text-decoration:none;}
.logo-icon{width:32px;height:32px;background:linear-gradient(135deg,var(--p),var(--s));border-radius:8px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:15px;}
.nav-links{display:flex;align-items:center;gap:2px;list-style:none;}
.nav-links a{text-decoration:none;color:var(--muted);font-weight:500;padding:.4rem .75rem;border-radius:8px;font-size:13.5px;transition:all .15s;}
.nav-links a:hover,.nav-links a.active{color:var(--p);background:#eef2ff;}
.nav-actions{display:flex;align-items:center;gap:.5rem;}
.btn{display:inline-flex;align-items:center;gap:.4rem;padding:.5rem 1rem;border-radius:8px;font-weight:600;font-size:13.5px;cursor:pointer;text-decoration:none;border:none;transition:all .15s;line-height:1;}
.btn-primary{background:var(--p);color:#fff;}.btn-primary:hover{background:var(--pd);transform:translateY(-1px);}
.btn-outline{background:transparent;border:1.5px solid var(--border);color:var(--text);}.btn-outline:hover{border-color:var(--p);color:var(--p);}
.btn-danger{background:#ef4444;color:#fff;}.btn-danger:hover{background:#dc2626;}
.btn-sm{padding:.35rem .7rem;font-size:12.5px;}.btn-lg{padding:.75rem 1.5rem;font-size:15px;}
.avatar{width:34px;height:34px;border-radius:50%;object-fit:cover;border:2px solid var(--border);cursor:pointer;}
.dropdown{position:relative;}
.dropdown-menu{position:absolute;right:0;top:calc(100% + 8px);background:#fff;border:1px solid var(--border);border-radius:12px;box-shadow:var(--shadow-lg);min-width:200px;display:none;z-index:200;overflow:hidden;}
.dropdown.open .dropdown-menu{display:block;}
.dropdown-menu a,.dropdown-menu button{display:flex;align-items:center;gap:8px;padding:10px 14px;color:var(--text);text-decoration:none;font-size:13.5px;width:100%;background:none;border:none;cursor:pointer;}
.dropdown-menu a:hover,.dropdown-menu button:hover{background:var(--bg);}
.dropdown-menu hr{border:none;border-top:1px solid var(--border);margin:4px 0;}
.dropdown-menu .plan-chip{font-size:10px;padding:1px 6px;border-radius:4px;font-weight:700;margin-left:auto;}
.page-wrap{/*max-width:1600px;*/margin:0 auto;padding:2rem 1.5rem;}
.page-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;flex-wrap:wrap;gap:1rem;}
.page-title{font-family:'Plus Jakarta Sans',sans-serif;font-size:1.5rem;font-weight:700;}
.card{background:#fff;border-radius:var(--radius);box-shadow:var(--shadow);overflow:hidden;}
.card-body{padding:1.25rem;}.card-header{padding:1rem 1.25rem;border-bottom:1px solid var(--border);}
.grid{display:grid;gap:1rem;}.grid-2{grid-template-columns:repeat(2,1fr);}.grid-3{grid-template-columns:repeat(3,1fr);}.grid-4{grid-template-columns:repeat(4,1fr);}
@media(max-width:768px){.grid-3,.grid-4{grid-template-columns:repeat(2,1fr);}}
@media(max-width:480px){.grid-2,.grid-3,.grid-4{grid-template-columns:1fr;}}
.photo-grid{column-count:4;column-gap:4px;max-width:100%;}
.photo-card{position:relative;display:inline-block;width:100%;margin:0 0 4px;overflow:hidden;cursor:pointer;background:#f1f5f9;border-radius:0;box-shadow:none;break-inside:avoid;-webkit-column-break-inside:avoid;page-break-inside:avoid;transition:transform .16s ease,filter .16s ease;}
.photo-card img{width:100%;height:auto;display:block;transition:transform .16s ease,filter .16s ease;object-fit:cover;}
.photo-card:hover img{transform:scale(1.005);filter:saturate(1.01);} 
.photo-overlay{position:absolute;inset:0;background:linear-gradient(to top,rgba(0,0,0,.35),transparent 55%);opacity:0;transition:opacity .15s ease;display:flex;align-items:flex-end;padding:8px;}
.photo-card:hover .photo-overlay{opacity:1;}
.form-group{margin-bottom:1rem;}
label{display:block;font-weight:500;font-size:13px;color:var(--muted);margin-bottom:.35rem;}
input[type=text],input[type=email],input[type=password],input[type=tel],input[type=date],select,textarea{width:100%;padding:.6rem .9rem;border:1.5px solid var(--border);border-radius:8px;font-size:14px;font-family:inherit;color:var(--text);background:#fff;outline:none;transition:border-color .15s,box-shadow .15s;}
input:focus,select:focus,textarea:focus{border-color:var(--p);box-shadow:0 0 0 3px rgba(99,102,241,.1);}
.alert{padding:.75rem 1rem;border-radius:8px;font-size:13.5px;margin-bottom:1rem;display:flex;align-items:center;gap:.5rem;}
.alert-success{background:#ecfdf5;color:#065f46;border:1px solid #a7f3d0;}
.alert-error{background:#fef2f2;color:#991b1b;border:1px solid #fecaca;}
.badge{display:inline-flex;align-items:center;padding:2px 8px;border-radius:20px;font-size:11px;font-weight:600;}
.badge-primary{background:#eef2ff;color:var(--p);}.badge-success{background:#ecfdf5;color:#10b981;}
.upload-zone{border:2px dashed var(--border);border-radius:12px;padding:2.5rem 2rem;text-align:center;cursor:pointer;transition:all .2s;}
.upload-zone:hover,.upload-zone.drag-over{border-color:var(--p);background:#eef2ff;}
.upload-zone .icon{font-size:2.5rem;color:var(--muted);margin-bottom:.75rem;}
.progress-bar{height:6px;background:var(--bg);border-radius:3px;overflow:hidden;}
.progress-bar-fill{height:100%;background:linear-gradient(90deg,var(--p),var(--s));border-radius:3px;transition:width .3s;}
.spinner{display:inline-block;width:18px;height:18px;border:2.5px solid rgba(99,102,241,.2);border-top-color:var(--p);border-radius:50%;animation:spin .7s linear infinite;}
@keyframes spin{to{transform:rotate(360deg);}}
.group-card{background:#fff;border-radius:12px;overflow:hidden;box-shadow:var(--shadow);transition:transform .15s,box-shadow .15s;text-decoration:none;color:inherit;display:block;}
.group-card:hover{transform:translateY(-2px);box-shadow:var(--shadow-lg);}
.group-card-cover{height:130px;background:linear-gradient(135deg,#667eea,#764ba2);position:relative;overflow:hidden;}
.group-card-cover img{width:100%;height:100%;object-fit:cover;}
.group-card-body{padding:.9rem 1rem;}
.group-card-title{font-weight:700;font-size:14px;margin-bottom:4px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.group-card-meta{display:flex;gap:.75rem;font-size:12px;color:var(--muted);flex-wrap:wrap;}
.section-title{font-family:'Plus Jakarta Sans',sans-serif;font-size:1rem;font-weight:700;margin-bottom:1rem;}
.empty-state{text-align:center;padding:4rem 2rem;color:var(--muted);}
.empty-state .ei{font-size:3rem;margin-bottom:1rem;opacity:.4;}
.toggle{position:relative;display:inline-block;width:40px;height:22px;}
.toggle input{opacity:0;width:0;height:0;}
.toggle-slider{position:absolute;inset:0;background:#cbd5e1;border-radius:22px;cursor:pointer;transition:.2s;}
.toggle-slider:before{content:'';position:absolute;width:16px;height:16px;background:#fff;border-radius:50%;left:3px;top:3px;transition:.2s;}
.toggle input:checked+.toggle-slider{background:var(--p);}
.toggle input:checked+.toggle-slider:before{transform:translateX(18px);}
.toast-container{position:fixed;bottom:1.5rem;right:1.5rem;z-index:500;display:flex;flex-direction:column;gap:.5rem;}
.toast{background:#1e293b;color:#fff;padding:.75rem 1rem;border-radius:10px;font-size:13.5px;box-shadow:var(--shadow-lg);max-width:300px;animation:slideUp .2s ease;}
@keyframes slideUp{from{transform:translateY(20px);opacity:0;}to{transform:translateY(0);opacity:1;}}
@media(max-width:1100px){.photo-grid{column-count:3;}}
@media(max-width:768px){.photo-grid{column-count:2;}}
@media(max-width:640px){.nav-links{display:none;}.page-wrap{padding:1rem;}}
@media(max-width:480px){.photo-grid{column-count:1;}}
</style>
@stack('styles')
</head>
<body>
<nav class="navbar">
<div class="nav-inner">
  <a href="{{ route('home') }}" class="logo"><div class="logo-icon">📸</div>LensPic</a>
  @auth
  <ul class="nav-links">
    <li><a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard')?'active':'' }}"><i class="fa-solid fa-house"></i> Home</a></li>
    <li><a href="{{ route('groups.index') }}" class="{{ request()->routeIs('groups.*')?'active':'' }}"><i class="fa-solid fa-images"></i> Groups</a></li>
    <li><a href="{{ route('pricing') }}" class="{{ request()->routeIs('pricing')?'active':'' }}"><i class="fa-solid fa-tags"></i> Pricing</a></li>
  </ul>
  @endauth
  <div class="nav-actions">
    @auth
    @if(auth()->user()->canAccessFeature('create_group'))
    <a href="{{ route('groups.create') }}" class="btn btn-primary btn-sm"><i class="fa-solid fa-plus"></i> New Group</a>
    @else
    <span class="btn btn-primary btn-sm" style="opacity:.75;cursor:not-allowed;"><i class="fa-solid fa-plus"></i> New Group</span>
    @endif
    <div class="dropdown">
      <img src="{{ auth()->user()->profile_photo_url }}" class="avatar" onclick="this.closest('.dropdown').classList.toggle('open')">
      <div class="dropdown-menu">
        <!-- Plan badge -->
        <div style="padding:10px 14px 6px;border-bottom:1px solid #f1f5f9;margin-bottom:4px;">
          <div style="font-weight:700;font-size:13px;">{{ auth()->user()->name }}</div>
          <div style="font-size:11px;margin-top:2px;">
            <span style="background:linear-gradient(135deg,#6366f1,#ec4899);-webkit-background-clip:text;-webkit-text-fill-color:transparent;font-weight:700;">{{ auth()->user()->plan === 'free' ? 'No paid plan' : auth()->user()->plan_label.' Plan' }}</span>
          </div>
        </div>
        <a href="{{ route('settings.profile') }}"><i class="fa-solid fa-gear"></i> Settings</a>
        <a href="{{ route('settings.subscription') }}"><i class="fa-solid fa-crown" style="color:#f59e0b;"></i> Subscription</a>
        <a href="{{ route('groups.index') }}"><i class="fa-solid fa-images"></i> My Groups</a>
        <a href="{{ route('pricing') }}"><i class="fa-solid fa-tags"></i> Plans & Pricing</a>
        @if(auth()->user()->is_admin)
        <hr>
        <a href="{{ route('admin.dashboard') }}" style="color:#6366f1;"><i class="fa-solid fa-shield-halved"></i> Admin Panel</a>
        @endif
        <hr>
        <form action="{{ route('logout') }}" method="POST">@csrf
          <button type="submit"><i class="fa-solid fa-arrow-right-from-bracket"></i> Logout</button>
        </form>
      </div>
    </div>
    @else
    <a href="{{ route('groups.join-code') }}" class="btn btn-outline btn-sm">Join a Group →</a>
    <a href="{{ route('pricing') }}" class="btn btn-outline btn-sm">Pricing</a>
    <a href="{{ route('login') }}" class="btn btn-outline btn-sm">Login</a>
    <a href="{{ route('register') }}" class="btn btn-primary btn-sm">Sign Up</a>
    @endauth
  </div>
</div>
</nav>
<main>
@if(session('success'))<div style="max-width:100%;margin:1rem auto;padding:0 1.5rem;"><div class="alert alert-success"><i class="fa-solid fa-check-circle"></i> {{ session('success') }}</div></div>@endif
@if(session('error'))<div style="max-width:100%;margin:1rem auto;padding:0 1.5rem;"><div class="alert alert-error"><i class="fa-solid fa-circle-xmark"></i> {{ session('error') }}</div></div>@endif
@yield('content')
</main>
<div class="toast-container" id="toasts"></div>
<script>
function toast(msg){const t=document.createElement('div');t.className='toast';t.textContent=msg;document.getElementById('toasts').appendChild(t);setTimeout(()=>t.remove(),3500);}
const csrf=document.querySelector('meta[name=csrf-token]')?.content;
async function post(url,data={}){const r=await fetch(url,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf,'Accept':'application/json'},body:JSON.stringify(data)});return r.json();}
document.addEventListener('click',e=>{if(!e.target.closest('.dropdown'))document.querySelectorAll('.dropdown').forEach(d=>d.classList.remove('open'));});
</script>
@stack('scripts')
</body>
</html>
