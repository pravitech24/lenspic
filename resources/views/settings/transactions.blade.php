@extends('settings.layout')
@section('title','Transactions')
@section('settings-content')
<div style="margin-bottom:1.5rem;"><h1 style="font-family:'Plus Jakarta Sans',sans-serif;font-size:1.3rem;font-weight:800;">Transactions <span class="badge badge-primary" style="font-size:10px;vertical-align:middle;">New</span></h1></div>
<div class="card">
  <div class="card-body" style="text-align:center;padding:3rem;">
    <div style="font-size:3rem;margin-bottom:1rem;opacity:.3;">🧾</div>
    <p style="color:#64748b;font-size:14px;">No transactions yet. Your billing history will appear here.</p>
    <a href="{{ route('pricing') }}" class="btn btn-primary" style="margin-top:1rem;">View Plans</a>
  </div>
</div>
@endsection
