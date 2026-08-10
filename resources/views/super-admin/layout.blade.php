<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>Super Admin – @yield('title','Dashboard') | LensPic</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
*{box-sizing:border-box;margin:0;padding:0;}
:root{--p:#6366f1;--pd:#4f46e5;--s:#ec4899;--danger:#ef4444;--success:#10b981;--warning:#f59e0b;--bg:#0f1117;--sur:#1a1d27;--sur2:#22263a;--border:rgba(255,255,255,.08);--text:#f1f5f9;--muted:#94a3b8;--radius:10px;}
body{font-family:'Inter',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;display:flex;}
/* SIDEBAR */
.sidebar{width:260px;min-height:100vh;background:var(--sur);border-right:1px solid var(--border);display:flex;flex-direction:column;position:fixed;top:0;left:0;z-index:50;}
.sidebar-logo{padding:1.25rem 1.25rem 1rem;display:flex;align-items:center;gap:10px;border-bottom:1px solid var(--border);}
.logo-icon{width:34px;height:34px;background:linear-gradient(135deg,var(--p),var(--s));border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:16px;flex-shrink:0;}
.logo-text{font-family:'Plus Jakarta Sans',sans-serif;font-weight:800;font-size:1.1rem;color:#fff;}
.logo-badge{font-size:10px;background:var(--danger);color:#fff;padding:1px 6px;border-radius:4px;font-weight:700;margin-left:4px;}
.sidebar-nav{flex:1;padding:1rem 0.75rem;display:flex;flex-direction:column;gap:2px;overflow-y:auto;}
.nav-section{font-size:10px;font-weight:700;color:var(--muted);letter-spacing:.08em;text-transform:uppercase;padding:.5rem .5rem .3rem;margin-top:.5rem;}
.nav-item{display:flex;align-items:center;gap:10px;padding:.6rem .75rem;border-radius:8px;color:var(--muted);text-decoration:none;font-size:13.5px;font-weight:500;transition:all .15s;}
.nav-item:hover{background:rgba(255,255,255,.06);color:#fff;}
.nav-item.active{background:rgba(239,68,68,.15);color:#f87171;}
.nav-item i{width:18px;text-align:center;font-size:14px;}
.sidebar-footer{padding:1rem .75rem;border-top:1px solid var(--border);}
.sidebar-user{display:flex;align-items:center;gap:10px;}
.sidebar-user img{width:34px;height:34px;border-radius:50%;object-fit:cover;border:2px solid var(--border);}
.sidebar-user-info{flex:1;min-width:0;}
.sidebar-user-name{font-size:13px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.sidebar-user-role{font-size:11px;color:var(--danger);}
/* MAIN */
.main{margin-left:260px;flex:1;min-height:100vh;display:flex;flex-direction:column;}
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
.grid-5{grid-template-columns:repeat(5,1fr);}
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
.badge-orange{background:rgba(249,115,22,.15);color:#fb923c;}
/* Buttons */
.btn{display:inline-flex;align-items:center;gap:.4rem;padding:.45rem .9rem;border-radius:7px;font-weight:600;font-size:13px;cursor:pointer;text-decoration:none;border:none;transition:all .15s;line-height:1;}
.btn-primary{background:var(--p);color:#fff;}.btn-primary:hover{background:var(--pd);}
.btn-danger{background:rgba(239,68,68,.15);color:#f87171;border:1px solid rgba(239,68,68,.2);}.btn-danger:hover{background:var(--danger);color:#fff;}
.btn-outline{background:transparent;border:1px solid var(--border);color:var(--muted);}.btn-outline:hover{color:#fff;border-color:rgba(255,255,255,.2);}
.btn-sm{padding:.3rem .65rem;font-size:12px;}
.btn-success{background:rgba(16,185,129,.15);color:#34d399;border:1px solid rgba(16,185,129,.2);}.btn-success:hover{background:var(--success);color:#fff;}
.btn-warning{background:rgba(245,158,11,.15);color:#fbbf24;border:1px solid rgba(245,158,11,.2);}.btn-warning:hover{background:var(--warning);color:#fff;}
/* Forms */
.form-group{margin-bottom:1rem;}
label{display:block;font-size:12.5px;font-weight:600;color:var(--muted);margin-bottom:.35rem;}
input[type=text],input[type=email],input[type=password],input[type=tel],select,textarea{width:100%;padding:.6rem .9rem;background:var(--sur2);border:1px solid var(--border);border-radius:8px;font-size:14px;font-family:inherit;color:var(--text);outline:none;transition:border-color .15s;}
input:focus,select:focus,textarea:focus{border-color:var(--p);box-shadow:0 0 0 3px rgba(99,102,241,.15);}
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
.powered-by{font-size:11px;color:var(--muted);text-align:center;padding-top:.5rem;border-top:1px solid var(--border);}
@media(max-width:1200px){.grid-5{grid-template-columns:repeat(3,1fr);}.grid-4{grid-template-columns:repeat(2,1fr);}}
@media(max-width:768px){.sidebar{width:0;overflow:hidden;}.main{margin-left:0;}.grid-2,.grid-3,.grid-4,.grid-5{grid-template-columns:1fr;}}
</style>
@stack('styles')
</head>
<body>

<!-- SIDEBAR -->
<aside class="sidebar">
  <div class="sidebar-logo">
    <div class="logo-icon">⚡</div>
    <div><span class="logo-text">LensPic</span><span class="logo-badge">SUPER</span></div>
  </div>

  <nav class="sidebar-nav">
    <div class="nav-section">Overview</div>
    <a href="{{ route('super-admin.dashboard') }}" class="nav-item {{ request()->routeIs('super-admin.dashboard') ? 'active' : '' }}">
      <i class="fa-solid fa-gauge-high"></i> Dashboard
    </a>

    <div class="nav-section">Administration</div>
    <a href="{{ route('super-admin.users') }}" class="nav-item {{ request()->routeIs('super-admin.users*') ? 'active' : '' }}">
      <i class="fa-solid fa-users"></i> Users
      <span style="margin-left:auto;font-size:11px;background:rgba(239,68,68,.2);padding:2px 6px;border-radius:4px;color:#f87171;">Manage</span>
    </a>
    <a href="{{ route('super-admin.subscriptions') }}" class="nav-item {{ request()->routeIs('super-admin.subscriptions*') ? 'active' : '' }}">
      <i class="fa-solid fa-credit-card"></i> Subscriptions
      <span style="margin-left:auto;font-size:11px;background:rgba(34,197,94,.2);padding:2px 6px;border-radius:4px;color:#86efac;">Plans</span>
    </a>
    <a href="{{ route('super-admin.groups') }}" class="nav-item {{ request()->routeIs('super-admin.groups*') ? 'active' : '' }}">
      <i class="fa-solid fa-images"></i> Groups
      <span style="margin-left:auto;font-size:11px;background:rgba(245,158,11,.2);padding:2px 6px;border-radius:4px;color:#fbbf24;">Manage</span>
    </a>

    <div class="nav-section">Analytics</div>
    <a href="{{ route('super-admin.reports') }}" class="nav-item {{ request()->routeIs('super-admin.reports') ? 'active' : '' }}">
      <i class="fa-solid fa-chart-line"></i> Reports
      <span style="margin-left:auto;font-size:11px;background:rgba(59,130,246,.2);padding:2px 6px;border-radius:4px;color:#93c5fd;">Data</span>
    </a>

    <div class="nav-section">Quick Actions</div>
    <a href="{{ route('super-admin.users', ['role' => 'super_admin']) }}" class="nav-item">
      <i class="fa-solid fa-crown"></i> Super Admins
    </a>
    <a href="{{ route('super-admin.users', ['role' => 'admin']) }}" class="nav-item">
      <i class="fa-solid fa-shield"></i> Admins
    </a>
    <a href="{{ route('super-admin.subscriptions', ['status' => 'active']) }}" class="nav-item">
      <i class="fa-solid fa-check-circle"></i> Active Plans
    </a>

    <div class="nav-section">App</div>
    <a href="{{ route('dashboard') }}" class="nav-item">
      <i class="fa-solid fa-arrow-left"></i> Back to App
    </a>
    <a href="{{ route('home') }}" class="nav-item">
      <i class="fa-solid fa-house"></i> Home
    </a>
  </nav>

  <div class="sidebar-footer">
    <div class="sidebar-user">
      <img src="{{ auth()->user()->profile_photo_url }}" alt="">
      <div class="sidebar-user-info">
        <div class="sidebar-user-name">{{ auth()->user()->name }}</div>
        <div class="sidebar-user-role">{{ ucfirst(str_replace('_', ' ', auth()->user()->role)) }}</div>
      </div>
      <form action="{{ route('logout') }}" method="POST">@csrf
        <button type="submit" style="background:none;border:none;color:var(--muted);cursor:pointer;font-size:14px;" title="Logout"><i class="fa-solid fa-arrow-right-from-bracket"></i></button>
      </form>
    </div>
    <div class="powered-by">Powered by PraviTech</div>
  </div>
</aside>

<!-- MAIN -->
<div class="main">
  <div class="topbar">
    <div class="topbar-title">@yield('title','Dashboard')</div>
    <div style="display:flex;align-items:center;gap:.75rem;">
      <span style="font-size:12px;color:var(--muted);">{{ now()->format('d M Y, H:i') }}</span>
    </div>
  </div>

  <div class="content">
    @if(session('success'))<div class="alert alert-success"><i class="fa-solid fa-check-circle"></i> {{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-error"><i class="fa-solid fa-circle-xmark"></i> {{ session('error') }}</div>@endif
    @yield('content')
  </div>
</div>

<script>
const csrf = document.querySelector('meta[name=csrf-token]')?.content;
async function post(url,data={}){const r=await fetch(url,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf,'Accept':'application/json'},body:JSON.stringify(data)});return r.json();}
</script>
@stack('scripts')
</body>
</html>
