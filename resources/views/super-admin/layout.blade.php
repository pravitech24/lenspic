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
:root{--p:#5145e5;--pd:#4338ca;--s:#ec4899;--danger:#ef4444;--success:#10b981;--warning:#f59e0b;--bg:#f5f6f8;--sur:#fff;--sur2:#f8fafc;--sidebar:#111421;--border:#e2e8f0;--text:#171925;--muted:#64748b;--radius:12px;}
body{font-family:'Inter',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;display:flex;}
/* SIDEBAR */
.sidebar{width:260px;height:100vh;background:var(--sidebar);border-right:1px solid rgba(255,255,255,.08);display:flex;flex-direction:column;position:fixed;top:0;left:0;z-index:50;}
.sidebar-logo{padding:1.5rem 1.25rem 1rem;display:flex;align-items:center;gap:10px;border-bottom:1px solid rgba(255,255,255,.08);}
.logo-icon{width:34px;height:34px;background:linear-gradient(135deg,var(--p),var(--s));border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:16px;flex-shrink:0;}
.logo-text{font-family:'Plus Jakarta Sans',sans-serif;font-weight:800;font-size:1.1rem;color:#fff;}
.logo-badge{font-size:9px;background:rgba(99,102,241,.22);color:#c7d2fe;padding:2px 6px;border-radius:4px;font-weight:700;margin-left:5px;}
.sidebar-nav{flex:1;padding:1rem 0.75rem;display:flex;flex-direction:column;gap:2px;overflow-y:auto;}
.nav-section{font-size:10px;font-weight:700;color:rgba(255,255,255,.42);letter-spacing:.08em;text-transform:uppercase;padding:.5rem .5rem .3rem;margin-top:.5rem;}
.nav-item{display:flex;align-items:center;gap:10px;padding:.65rem .75rem;border-radius:10px;color:rgba(255,255,255,.68);text-decoration:none;font-size:13.5px;font-weight:500;transition:all .15s;}
.nav-item:hover{background:rgba(255,255,255,.06);color:#fff;}
.nav-item.active{background:var(--p);color:#fff;}
.nav-item i{width:18px;text-align:center;font-size:14px;}
.sidebar-footer{padding:1rem .75rem;border-top:1px solid rgba(255,255,255,.08);}
.sidebar-user{display:flex;align-items:center;gap:10px;}
.sidebar-user img{width:34px;height:34px;border-radius:50%;object-fit:cover;border:2px solid rgba(255,255,255,.12);}
.sidebar-user-info{flex:1;min-width:0;}
.sidebar-user-name{font-size:13px;font-weight:600;color:#fff;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.sidebar-user-role{font-size:11px;color:#a5b4fc;}
.sidebar-logout{width:100%;margin-top:.75rem;display:flex;align-items:center;justify-content:center;gap:.5rem;padding:.6rem .75rem;border-radius:8px;border:1px solid rgba(239,68,68,.3);background:rgba(239,68,68,.1);color:#f87171;font:600 13px 'Inter',sans-serif;cursor:pointer}.sidebar-logout:hover,.sidebar-logout:focus-visible{background:var(--danger);color:#fff;outline:none}.sidebar-logout:focus-visible{box-shadow:0 0 0 3px rgba(239,68,68,.25)}
/* MAIN */
.main{margin-left:260px;flex:1;min-height:100vh;display:flex;flex-direction:column;}
.topbar{background:rgba(255,255,255,.94);border-bottom:1px solid var(--border);padding:.85rem 1.75rem;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:30;backdrop-filter:blur(10px);}
.topbar-title{font-family:'Plus Jakarta Sans',sans-serif;font-weight:700;font-size:1.1rem;}
.content{padding:1.75rem;flex:1;}
/* Cards */
.card{background:var(--sur);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden;box-shadow:0 1px 2px rgba(15,23,42,.03);}
.card-body{padding:1.25rem;}
.card-header{padding:.9rem 1.25rem;border-bottom:1px solid var(--border);font-weight:600;font-size:14px;}
/* Stat cards */
.stat-card{background:var(--sur);border:1px solid var(--border);border-radius:var(--radius);padding:1.25rem;display:flex;align-items:center;gap:1rem;box-shadow:0 1px 2px rgba(15,23,42,.03);}
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
td{padding:.75rem 1rem;font-size:13.5px;border-bottom:1px solid #f1f5f9;vertical-align:middle;}
tr:last-child td{border-bottom:none;}
tr:hover td{background:#f8fafc;}
/* Badges */
.badge{display:inline-flex;align-items:center;padding:2px 8px;border-radius:20px;font-size:11px;font-weight:600;}
.badge-green{background:rgba(16,185,129,.15);color:#34d399;}
.badge-red{background:rgba(239,68,68,.15);color:#f87171;}
.badge-blue{background:rgba(99,102,241,.12);color:#4f46e5;}
.badge-yellow{background:rgba(245,158,11,.15);color:#fbbf24;}
.badge-orange{background:rgba(249,115,22,.15);color:#fb923c;}
/* Buttons */
.btn{display:inline-flex;align-items:center;gap:.4rem;padding:.45rem .9rem;border-radius:7px;font-weight:600;font-size:13px;cursor:pointer;text-decoration:none;border:none;transition:all .15s;line-height:1;}
.btn-primary{background:var(--p);color:#fff;}.btn-primary:hover{background:var(--pd);}
.btn-danger{background:rgba(239,68,68,.15);color:#f87171;border:1px solid rgba(239,68,68,.2);}.btn-danger:hover{background:var(--danger);color:#fff;}
.btn-outline{background:#fff;border:1px solid var(--border);color:#475569;}.btn-outline:hover{color:var(--p);border-color:#a5b4fc;background:#eef2ff;}
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
.pagination a:hover{background:#eef2ff;color:var(--p);}
.pagination .active span{background:var(--p);color:#fff;border-color:var(--p);}
.powered-by{font-size:11px;color:rgba(255,255,255,.4);text-align:center;padding-top:.5rem;border-top:1px solid rgba(255,255,255,.08);}
@media(max-width:1200px){.grid-5{grid-template-columns:repeat(3,1fr);}.grid-4{grid-template-columns:repeat(2,1fr);}}
.mobile-menu-button,.sidebar-overlay{display:none}.admin-breadcrumb{font-size:11px;color:var(--p);font-weight:700;text-transform:uppercase;letter-spacing:.08em;margin-bottom:.35rem}.super-context{font-size:11px;color:var(--p);font-weight:700}
@media(max-width:768px){.sidebar{width:min(86vw,300px);transform:translateX(-105%);transition:transform .2s ease;box-shadow:0 20px 50px rgba(0,0,0,.45)}body.admin-nav-open .sidebar{transform:translateX(0)}.sidebar-overlay{display:block;position:fixed;inset:0;background:rgba(0,0,0,.62);z-index:40;opacity:0;visibility:hidden;transition:.2s}body.admin-nav-open .sidebar-overlay{opacity:1;visibility:visible}.mobile-menu-button{display:inline-flex;background:#fff;color:var(--text);border:1px solid var(--border);border-radius:8px;width:38px;height:38px;align-items:center;justify-content:center}.main{margin-left:0;width:100%}.content{padding:1rem}.topbar{padding:.7rem 1rem}.grid-2,.grid-3,.grid-4,.grid-5{grid-template-columns:1fr}}
</style>
@stack('styles')
</head>
<body>
@php
  $dashboardActive=request()->routeIs('super-admin.dashboard');
  $allUsersActive=request()->routeIs('super-admin.users*')&&!request('type');
  $photographersActive=request()->routeIs('super-admin.users*')&&request('type')==='photographers';
  $teamMembersActive=request()->routeIs('super-admin.users*')&&request('type')==='team-members';
  $groupMembersActive=request()->routeIs('super-admin.users*')&&request('type')==='group-members';
  $groupsActive=request()->routeIs('super-admin.groups*');
  $subscriptionsActive=request()->routeIs('super-admin.subscriptions*');
  $plansActive=request()->routeIs('super-admin.plans.index','super-admin.plans.edit','super-admin.plans.preview','super-admin.plans.audits');
  $featuresActive=request()->routeIs('super-admin.plans.features*');
  $paymentsActive=request()->routeIs('super-admin.plans.payments');
  $rolesActive=request()->routeIs('super-admin.roles.*','super-admin.permissions.*','super-admin.role-history.*');
  $assignmentsActive=request()->routeIs('super-admin.role-assignments.*');
  $reportsActive=request()->routeIs('super-admin.reports*');
@endphp

<!-- SIDEBAR -->
<aside class="sidebar" id="super-admin-sidebar" aria-label="Super Admin navigation">
    <div class="sidebar-logo">
    <div class="logo-icon"><i class="fa-solid fa-shield-halved" aria-hidden="true"></i></div>
    <div><span class="logo-text">LensPic</span><span class="logo-badge">SUPER ADMIN</span></div>
  </div>

  <nav class="sidebar-nav">
    <div class="nav-section">Overview</div>
    <a href="{{ route('super-admin.whatsapp-deliveries') }}" class="nav-item {{ request()->routeIs('super-admin.whatsapp-deliveries') ? 'active' : '' }}"><i class="fa-brands fa-whatsapp"></i> WhatsApp deliveries</a>
    <a href="{{ route('super-admin.dashboard') }}" class="nav-item {{ $dashboardActive?'active':'' }}" @if($dashboardActive) aria-current="page" @endif>
      <i class="fa-solid fa-gauge-high"></i> Dashboard
    </a>

    <div class="nav-section">User Management</div>
    <a href="{{ route('super-admin.users') }}" class="nav-item {{ $allUsersActive?'active':'' }}" @if($allUsersActive) aria-current="page" @endif>
      <i class="fa-solid fa-users"></i> All Users
    </a>
    <a href="{{ route('super-admin.users', ['type'=>'photographers']) }}" class="nav-item {{ $photographersActive?'active':'' }}" @if($photographersActive) aria-current="page" @endif><i class="fa-solid fa-camera"></i> Photographers</a>
    <a href="{{ route('super-admin.users', ['type'=>'team-members']) }}" class="nav-item {{ $teamMembersActive?'active':'' }}" @if($teamMembersActive) aria-current="page" @endif><i class="fa-solid fa-user-shield"></i> Team Members</a>
    <a href="{{ route('super-admin.users', ['type'=>'group-members']) }}" class="nav-item {{ $groupMembersActive?'active':'' }}" @if($groupMembersActive) aria-current="page" @endif><i class="fa-solid fa-user-group"></i> Group Members</a>

    <div class="nav-section">Group &amp; Media Management</div>
    <a href="{{ route('super-admin.groups') }}" class="nav-item {{ $groupsActive?'active':'' }}" @if($groupsActive) aria-current="page" @endif>
      <i class="fa-solid fa-images"></i> Groups
      <span style="margin-left:auto;font-size:11px;background:rgba(245,158,11,.2);padding:2px 6px;border-radius:4px;color:#fbbf24;">Manage</span>
    </a>

    <div class="nav-section">Subscriptions &amp; Billing</div>
    <a href="{{ route('super-admin.subscriptions') }}" class="nav-item {{ $subscriptionsActive?'active':'' }}" @if($subscriptionsActive) aria-current="page" @endif>
      <i class="fa-solid fa-credit-card"></i> Subscriptions
    </a>
    <a href="{{ route('super-admin.plans.index') }}" class="nav-item {{ $plansActive?'active':'' }}" @if($plansActive) aria-current="page" @endif>
      <i class="fa-solid fa-layer-group"></i> Plans
    </a>
    <a href="{{ route('super-admin.plans.features') }}" class="nav-item {{ $featuresActive?'active':'' }}" @if($featuresActive) aria-current="page" @endif>
      <i class="fa-solid fa-list-check"></i> Plan Features
    </a>
    <a href="{{ route('super-admin.plans.payments') }}" class="nav-item {{ $paymentsActive?'active':'' }}" @if($paymentsActive) aria-current="page" @endif>
      <i class="fa-solid fa-receipt"></i> Plan Payments
    </a>

    <div class="nav-section">Governance</div>
    <a href="{{ route('super-admin.roles.index') }}" class="nav-item {{ $rolesActive?'active':'' }}" @if($rolesActive) aria-current="page" @endif><i class="fa-solid fa-user-lock"></i> Roles &amp; Permissions</a>
    <a href="{{ route('super-admin.role-assignments.index') }}" class="nav-item {{ $assignmentsActive?'active':'' }}" @if($assignmentsActive) aria-current="page" @endif><i class="fa-solid fa-user-gear"></i> User Assignments</a>
    <a href="{{ route('super-admin.reports') }}" class="nav-item {{ $reportsActive?'active':'' }}" @if($reportsActive) aria-current="page" @endif>
      <i class="fa-solid fa-chart-line"></i> Reports
      <span style="margin-left:auto;font-size:11px;background:rgba(59,130,246,.2);padding:2px 6px;border-radius:4px;color:#93c5fd;">Data</span>
    </a>

    <div class="nav-section">Account</div>
    <a href="{{ route('home') }}" class="nav-item">
      <i class="fa-solid fa-globe"></i> View Public Website
    </a>
  </nav>

  <div class="sidebar-footer">
    <div class="sidebar-user">
      <img src="{{ auth()->user()->profile_photo_url }}" alt="">
      <div class="sidebar-user-info">
        <div class="sidebar-user-name">{{ auth()->user()->name }}</div>
        <div class="sidebar-user-role">Super Admin</div>
      </div>
    </div>
    <form action="{{ route('logout') }}" method="POST">@csrf
      <button type="submit" class="sidebar-logout"><i class="fa-solid fa-arrow-right-from-bracket" aria-hidden="true"></i><span>Logout</span></button>
    </form>
    <div class="powered-by">Powered by PraviTech</div>
  </div>
</aside>

<!-- MAIN -->
<button class="sidebar-overlay" type="button" data-admin-nav-close tabindex="-1" aria-label="Close navigation"></button>
<div class="main">
  <div class="topbar">
    <div style="display:flex;align-items:center;gap:.75rem"><button type="button" class="mobile-menu-button" data-admin-nav-open aria-label="Open Super Admin navigation" aria-controls="super-admin-sidebar" aria-expanded="false"><i class="fa-solid fa-bars"></i></button><div><div class="topbar-title">@yield('title','Dashboard')</div><div class="super-context">LensPic Super Admin</div></div></div>
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
const adminSidebar=document.querySelector('.sidebar'),adminOpen=document.querySelector('[data-admin-nav-open]'),adminClose=document.querySelector('[data-admin-nav-close]');let adminLastFocus=null;
function setAdminNav(open){document.body.classList.toggle('admin-nav-open',open);adminOpen?.setAttribute('aria-expanded',String(open));if(open){adminLastFocus=document.activeElement;adminSidebar?.querySelector('a,button')?.focus()}else adminLastFocus?.focus()}
adminOpen?.addEventListener('click',()=>setAdminNav(true));adminClose?.addEventListener('click',()=>setAdminNav(false));adminSidebar?.querySelectorAll('a').forEach(link=>link.addEventListener('click',()=>setAdminNav(false)));document.addEventListener('keydown',event=>{if(event.key==='Escape'&&document.body.classList.contains('admin-nav-open'))setAdminNav(false);if(event.key==='Tab'&&document.body.classList.contains('admin-nav-open')){const items=[...adminSidebar.querySelectorAll('a,button:not([disabled])')];if(!items.length)return;const first=items[0],last=items.at(-1);if(event.shiftKey&&document.activeElement===first){event.preventDefault();last.focus()}else if(!event.shiftKey&&document.activeElement===last){event.preventDefault();first.focus()}}});
</script>
@stack('scripts')
</body>
</html>
