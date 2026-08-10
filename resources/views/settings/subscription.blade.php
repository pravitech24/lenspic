@extends('settings.layout')
@section('title','Subscription')
@section('settings-content')
<div style="margin-bottom:1.5rem;"><h1 style="font-family:'Plus Jakarta Sans',sans-serif;font-size:1.3rem;font-weight:800;">Subscription</h1></div>

<!-- Current Plan -->
<div class="card" style="margin-bottom:1.25rem;">
  <div class="card-header" style="display:flex;align-items:center;gap:.5rem;"><i class="fa-solid fa-crown" style="color:#f59e0b;"></i> Current Plan</div>
  <div class="card-body">
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem;">
      <div>
        <div style="font-size:1.5rem;font-weight:800;font-family:'Plus Jakarta Sans',sans-serif;margin-bottom:.25rem;">{{ $user->plan === 'free' ? 'No paid plan' : $user->plan_label.' Plan' }}</div>
        @if($user->plan !== 'free')
        <div style="font-size:13px;color:#64748b;">Active · Expires {{ $user->plan_expires_at?->format('d M Y') ?? 'N/A' }}</div>
        @else
        <div style="font-size:13px;color:#64748b;">No paid plan active — choose a plan to unlock more features</div>
        @endif
      </div>
      <a href="{{ route('pricing') }}" class="btn btn-primary btn-lg"><i class="fa-solid fa-arrow-up"></i> Upgrade Plan</a>
    </div>
    <hr style="border:none;border-top:1px solid #f1f5f9;margin:1.25rem 0;">
    @php $limits = $user->plan_limits; @endphp
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:1rem;">
      @foreach([['Photos Limit', number_format($limits['photos_per_group'] * 10), 'fa-images'],['Storage', $limits['storage_label'], 'fa-hard-drive'],['Groups', $limits['groups'] > 100 ? 'Unlimited' : $limits['groups'], 'fa-folder'],['Face AI', $user->plan !== 'free' ? 'Enabled' : 'Disabled', 'fa-face-smile']] as [$label,$val,$icon])
      <div style="background:#f8fafc;border-radius:10px;padding:1rem;text-align:center;">
        <div style="font-size:1.1rem;color:#6366f1;margin-bottom:.4rem;"><i class="fa-solid {{ $icon }}"></i></div>
        <div style="font-weight:700;font-size:1rem;">{{ $val }}</div>
        <div style="font-size:11.5px;color:#64748b;margin-top:2px;">{{ $label }}</div>
      </div>
      @endforeach
    </div>
  </div>
</div>

<!-- Plan Comparison -->
<div class="card">
  <div class="card-header">Compare Plans</div>
  <div style="overflow-x:auto;">
    <table style="width:100%;border-collapse:collapse;">
      <thead>
        <tr style="background:#f8fafc;">
          <th style="text-align:left;padding:.75rem 1.25rem;font-size:12px;font-weight:700;color:#64748b;border-bottom:1px solid #e2e8f0;">Feature</th>
          @foreach(['Standard','Essential','Premium'] as $p)
          <th style="text-align:center;padding:.75rem 1rem;font-size:12px;font-weight:700;color:{{ $user->plan_label === $p ? '#6366f1' : '#64748b' }};border-bottom:1px solid #e2e8f0;">
            {{ $p }}{{ $user->plan_label === $p ? ' ✓' : '' }}
          </th>
          @endforeach
        </tr>
      </thead>
      <tbody>
        @foreach([
          ['Price','₹699/mo','₹1,299/mo','₹2,299/mo'],
          ['Photos / quarter','30,000','60,000','1,25,000'],
          ['Videos','100','200','500'],
          ['Business Branding','✓','✓','✓'],
          ['Bulk Download','✓','✓','✓'],
          ['Client Favorites','✗','✓','✓'],
          ['Download Controls','✗','✓','✓'],
          ['Watermarking','✗','✓','✓'],
          ['Portfolio Website','✗','✓','✓'],
          ['Team Login','✗','✓','✓'],
          ['Digital Album','✗','✗','✓'],
          ['Sponsor Branding','✗','✗','✓'],
        ] as $row)
        <tr>
          <td style="padding:.7rem 1.25rem;font-size:13px;font-weight:500;border-bottom:1px solid #f8fafc;">{{ $row[0] }}</td>
          @foreach(array_slice($row,1) as $i=>$val)
          <td style="text-align:center;padding:.7rem 1rem;font-size:13px;border-bottom:1px solid #f8fafc;{{ $i===($user->plan==='standard'?0:($user->plan==='essential'?1:($user->plan==='premium'?2:-1))) ? 'background:#eef2ff;color:#4f46e5;font-weight:700;' : '' }}">
            @if($val==='✓')<i class="fa-solid fa-check" style="color:#10b981;"></i>
            @elseif($val==='✗')<i class="fa-solid fa-xmark" style="color:#cbd5e1;"></i>
            @else{{ $val }}@endif
          </td>
          @endforeach
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
  <div style="padding:1.25rem;text-align:center;">
    <a href="{{ route('pricing') }}" class="btn btn-primary">See Full Pricing →</a>
  </div>
</div>
@endsection
