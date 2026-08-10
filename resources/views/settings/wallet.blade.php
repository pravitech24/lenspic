@extends('settings.layout')
@section('title','LensPic Wallet')
@section('settings-content')
<div style="margin-bottom:1.5rem;"><h1 style="font-family:'Plus Jakarta Sans',sans-serif;font-size:1.3rem;font-weight:800;">LensPic Wallet</h1></div>
<div class="card" style="margin-bottom:1.25rem;">
  <div class="card-body" style="text-align:center;padding:2.5rem;">
    <div style="width:80px;height:80px;background:linear-gradient(135deg,#6366f1,#ec4899);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:2rem;margin:0 auto 1rem;">💳</div>
    <div style="font-size:2.5rem;font-weight:900;font-family:'Plus Jakarta Sans',sans-serif;margin-bottom:.25rem;">₹0.00</div>
    <div style="color:#64748b;font-size:14px;margin-bottom:1.5rem;">Available Balance</div>
    <button class="btn btn-primary btn-lg" style="opacity:.5;cursor:not-allowed;" disabled><i class="fa-solid fa-plus"></i> Add Money</button>
    <p style="margin-top:.75rem;font-size:12px;color:#94a3b8;">Wallet feature coming soon</p>
  </div>
</div>
@endsection
