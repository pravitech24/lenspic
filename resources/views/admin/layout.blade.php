<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>Admin – @yield('title','Dashboard') | LensPic</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
*{box-sizing:border-box;margin:0;padding:0;}
:root{--p:#6366f1;--pd:#4f46e5;--s:#ec4899;--danger:#ef4444;--success:#10b981;--bg:#0f1117;--sur:#1a1d27;--sur2:#22263a;--border:rgba(255,255,255,.08);--text:#f1f5f9;--muted:#94a3b8;--radius:10px;}
body{font-family:'Inter',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;display:flex;}
/* SIDEBAR */
.sidebar{width:240px;min-height:100vh;background:var(--sur);border-right:1px solid var(--border);display:flex;flex-direction:column;position:fixed;top:0;left:0;z-index:50;}
.sidebar-logo{padding:1.25rem 1.25rem 1rem;display:flex;align-items:center;gap:10px;border-bottom:1px solid var(--border);}
.logo-icon{width:34px;height:34px;background:linear-gradient(135deg,var(--p),var(--s));border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:16px;flex-shrink:0;}
.logo-text{font-family:'Plus Jakarta Sans',sans-serif;font-weight:800;font-size:1.1rem;color:#fff;}
.logo-badge{font-size:10px;background:var(--p);color:#fff;padding:1px 6px;border-radius:4px;font-weight:700;margin-left:4px;}
.sidebar-nav{flex:1;padding:1rem 0.75rem;display:flex;flex-direction:column;gap:2px;}
.nav-section{font-size:10px;font-weight:700;color:var(--muted);letter-spacing:.08em;text-transform:uppercase;padding:.5rem .5rem .3rem;margin-top:.5rem;}
.nav-item{display:flex;align-items:center;gap:10px;padding:.6rem .75rem;border-radius:8px;color:var(--muted);text-decoration:none;font-size:13.5px;font-weight:500;transition:all .15s;}
.nav-item:hover{background:rgba(255,255,255,.06);color:#fff;}
.nav-item.active{background:rgba(99,102,241,.15);color:#a5b4fc;}
.nav-item i{width:18px;text-align:center;font-size:14px;}
.sidebar-footer{padding:1rem .75rem;border-top:1px solid var(--border);}
.sidebar-user{display:flex;align-items:center;gap:10px;}
.sidebar-user img{width:34px;height:34px;border-radius:50%;object-fit:cover;border:2px solid var(--border);}
.sidebar-user-info{flex:1;min-width:0;}
.sidebar-user-name{font-size:13px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.sidebar-user-role{font-size:11px;color:var(--p);}
/* MAIN */
.main{margin-left:240px;flex:1;min-height:100vh;display:flex;flex-direction:column;}
.topbar{background:var(--sur);border-bottom:1px solid var(--border);padding:.85rem 1.75rem;display:flex;align-items:center;justify-content:space-between;}
.topbar-title{font-family:'Plus Jakarta Sans',sans-serif;font-weight:700;font-size:1.1rem;}
.content{padding:1.75rem;flex:1;}
/* Cards */
.card{background:var(--sur);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden;}
.card-body{padding:1.25rem;}
.card-header{padding:.9rem 1.25rem;border-bottom:1px solid var(--border);font-weight:600;font-size:14px;}
/* Stat cards */
.stat-card{background:var(--sur);border:1px solid var(--border);border-radius:var(--radius);padding:1.25rem;display:flex;align-items:center;gap:1rem;}
.stat-icon{width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.2rem;flex-shrink:0;}
.stat-num{font-size:1.6rem;font-weight:800;line-height:1;}
.stat-label{font-size:12px;color:var(--muted);margin-top:2px;}
.stat-sub{font-size:11px;color:var(--success);margin-top:3px;}
/* Grid */
.grid{display:grid;gap:1rem;}
.grid-2{grid-template-columns:repeat(2,1fr);}
.grid-3{grid-template-columns:repeat(3,1fr);}
.grid-4{grid-template-columns:repeat(4,1fr);}
/* Table */
.table-wrap{overflow-x:auto;}
table{width:100%;border-collapse:collapse;}
th{text-align:left;padding:.65rem 1rem;font-size:11.5px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.05em;border-bottom:1px solid var(--border);}
td{padding:.75rem 1rem;font-size:13.5px;border-bottom:1px solid rgba(255,255,255,.04);vertical-align:middle;}
tr:last-child td{border-bottom:none;}
tr:hover td{background:rgba(255,255,255,.02);}
/* Badges */
.badge{display:inline-flex;align-items:center;padding:2px 8px;border-radius:20px;font-size:11px;font-weight:600;}
.badge-green{background:rgba(16,185,129,.15);color:#34d399;}
.badge-red{background:rgba(239,68,68,.15);color:#f87171;}
.badge-blue{background:rgba(99,102,241,.15);color:#a5b4fc;}
.badge-yellow{background:rgba(245,158,11,.15);color:#fbbf24;}
/* Buttons */
.btn{display:inline-flex;align-items:center;gap:.4rem;padding:.45rem .9rem;border-radius:7px;font-weight:600;font-size:13px;cursor:pointer;text-decoration:none;border:none;transition:all .15s;line-height:1;}
.btn-primary{background:var(--p);color:#fff;}.btn-primary:hover{background:var(--pd);}
.btn-danger{background:rgba(239,68,68,.15);color:#f87171;border:1px solid rgba(239,68,68,.2);}.btn-danger:hover{background:var(--danger);color:#fff;}
.btn-outline{background:transparent;border:1px solid var(--border);color:var(--muted);}.btn-outline:hover{color:#fff;border-color:rgba(255,255,255,.2);}
.btn-sm{padding:.3rem .65rem;font-size:12px;}
.btn-success{background:rgba(16,185,129,.15);color:#34d399;border:1px solid rgba(16,185,129,.2);}.btn-success:hover{background:var(--success);color:#fff;}
/* Forms */
.form-group{margin-bottom:1rem;}
label{display:block;font-size:12.5px;font-weight:600;color:var(--muted);margin-bottom:.35rem;}
input[type=text],input[type=email],input[type=password],input[type=tel],select,textarea{width:100%;padding:.6rem .9rem;background:var(--sur2);border:1px solid var(--border);border-radius:8px;font-size:14px;font-family:inherit;color:var(--text);outline:none;transition:border-color .15s;}
input:focus,select:focus,textarea:focus{border-color:var(--p);box-shadow:0 0 0 3px rgba(99,102,241,.15);}
/* Search bar */
.search-bar{display:flex;align-items:center;gap:.75rem;margin-bottom:1.25rem;}
.search-bar input{flex:1;max-width:320px;}
/* Alert */
.alert{padding:.75rem 1rem;border-radius:8px;font-size:13.5px;margin-bottom:1rem;display:flex;align-items:center;gap:.5rem;}
.alert-success{background:rgba(16,185,129,.1);color:#34d399;border:1px solid rgba(16,185,129,.2);}
.alert-error{background:rgba(239,68,68,.1);color:#f87171;border:1px solid rgba(239,68,68,.2);}
/* Avatar */
.avatar{width:32px;height:32px;border-radius:50%;object-fit:cover;}
/* Page header */
.page-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;flex-wrap:wrap;gap:.75rem;}
.page-title{font-family:'Plus Jakarta Sans',sans-serif;font-size:1.3rem;font-weight:700;}
/* Pagination */
.pagination{display:flex;gap:4px;flex-wrap:wrap;margin-top:1rem;}
.pagination a,.pagination span{padding:.4rem .75rem;border-radius:7px;font-size:13px;text-decoration:none;border:1px solid var(--border);color:var(--muted);}
.pagination a:hover{background:rgba(255,255,255,.06);color:#fff;}
.pagination .active span{background:var(--p);color:#fff;border-color:var(--p);}
/* Toggle */
.toggle{position:relative;display:inline-block;width:38px;height:20px;}
.toggle input{opacity:0;width:0;height:0;}
.toggle-sl{position:absolute;inset:0;background:#374151;border-radius:20px;cursor:pointer;transition:.2s;}
.toggle-sl:before{content:'';position:absolute;width:14px;height:14px;background:#fff;border-radius:50%;left:3px;top:3px;transition:.2s;}
.toggle input:checked+.toggle-sl{background:var(--p);}
.toggle input:checked+.toggle-sl:before{transform:translateX(18px);}
.toast-container{position:fixed;bottom:1.5rem;right:1.5rem;z-index:999;display:flex;flex-direction:column;gap:.5rem;}
.toast{background:#1e293b;color:#fff;padding:.75rem 1rem;border-radius:10px;font-size:13.5px;max-width:300px;}
</style>
@stack('styles')
</head>
<body>

<!-- SIDEBAR -->
<aside class="sidebar">
  <div class="sidebar-logo">
    <div class="logo-icon">⚡</div>
    <div><span class="logo-text">LensPic</span><span class="logo-badge">ADMIN</span></div>
  </div>

  <nav class="sidebar-nav">
    <div class="nav-section">Overview</div>
    <a href="{{ route('admin.dashboard') }}" class="nav-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
      <i class="fa-solid fa-gauge-high"></i> Dashboard
    </a>

    <div class="nav-section">Manage</div>
    <a href="{{ route('admin.users') }}" class="nav-item {{ request()->routeIs('admin.users*') ? 'active' : '' }}">
      <i class="fa-solid fa-users"></i> Users
    </a>
    <a href="{{ route('admin.groups') }}" class="nav-item {{ request()->routeIs('admin.groups*') ? 'active' : '' }}">
      <i class="fa-solid fa-images"></i> Groups
    </a>
    <a href="{{ route('admin.photos') }}" class="nav-item {{ request()->routeIs('admin.photos*') ? 'active' : '' }}">
      <i class="fa-solid fa-camera"></i> Photos
    </a>

    <div class="nav-section">App</div>
    <a href="{{ route('dashboard') }}" class="nav-item">
      <i class="fa-solid fa-arrow-left"></i> Back to App
    </a>
  </nav>

  <div class="sidebar-footer">
    <div class="sidebar-user">
      <img src="{{ auth()->user()->profile_photo_url }}" alt="">
      <div class="sidebar-user-info">
        <div class="sidebar-user-name">{{ auth()->user()->name }}</div>
        <div class="sidebar-user-role">Super Admin</div>
      </div>
      <form action="{{ route('logout') }}" method="POST">@csrf
        <button type="submit" style="background:none;border:none;color:var(--muted);cursor:pointer;font-size:14px;" title="Logout"><i class="fa-solid fa-arrow-right-from-bracket"></i></button>
      </form>
    </div>
  </div>
</aside>

<!-- MAIN -->
<div class="main">
  <div class="topbar">
    <div class="topbar-title">@yield('title','Dashboard')</div>
    <div style="display:flex;align-items:center;gap:.75rem;">
      <span style="font-size:12px;color:var(--muted);">{{ now()->format('d M Y, H:i') }}</span>
      <a href="{{ route('groups.create') }}" class="btn btn-primary btn-sm"><i class="fa-solid fa-plus"></i> New Group</a>
    </div>
  </div>

  <div class="content">
    @if(session('success'))<div class="alert alert-success"><i class="fa-solid fa-check-circle"></i> {{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-error"><i class="fa-solid fa-circle-xmark"></i> {{ session('error') }}</div>@endif
    @yield('content')
  </div>
</div>

<div class="toast-container" id="toasts"></div>
<script>
const csrf = document.querySelector('meta[name=csrf-token]')?.content;
function toast(msg){const t=document.createElement('div');t.className='toast';t.textContent=msg;document.getElementById('toasts').appendChild(t);setTimeout(()=>t.remove(),3000);}
async function post(url,data={}){const r=await fetch(url,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf,'Accept':'application/json'},body:JSON.stringify(data)});return r.json();}
</script>
@stack('scripts')
</body>
</html>
