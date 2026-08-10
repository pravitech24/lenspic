@extends('settings.layout')
@section('title','Profile Settings')
@section('settings-content')

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;flex-wrap:wrap;gap:.75rem;">
  <h1 style="font-family:'Plus Jakarta Sans',sans-serif;font-size:1.3rem;font-weight:800;">Your Profile</h1>
  <button form="profileForm" type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Save</button>
</div>

<div style="display:grid;grid-template-columns:1fr 340px;gap:1.25rem;align-items:start;" class="prof-grid">

  <!-- Left: Form -->
  <div class="card">
    <div class="card-body" style="padding:1.75rem;">
      <form id="profileForm" action="{{ route('settings.profile.update') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <!-- Profile photo -->
        <div style="display:flex;align-items:center;gap:1.25rem;margin-bottom:1.75rem;padding-bottom:1.5rem;border-bottom:1px solid #f1f5f9;">
          <div style="position:relative;">
            <img src="{{ $user->profile_photo_url }}" id="photoPreview" style="width:80px;height:80px;border-radius:50%;object-fit:cover;border:3px solid #e2e8f0;">
            <button type="button" onclick="document.getElementById('photoInput').click()" style="position:absolute;bottom:0;right:0;width:26px;height:26px;background:#6366f1;border:none;border-radius:50%;color:#fff;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:11px;">
              <i class="fa-solid fa-camera"></i>
            </button>
            <input type="file" id="photoInput" name="profile_photo" accept="image/*" hidden onchange="prevPhoto(this)">
          </div>
          <div>
            <div style="font-weight:700;">{{ $user->name }}</div>
            <div style="font-size:13px;color:#64748b;margin-top:2px;">{{ $user->email }}</div>
            <div style="font-size:12px;margin-top:4px;"><span style="background:linear-gradient(135deg,#6366f1,#ec4899);-webkit-background-clip:text;-webkit-text-fill-color:transparent;font-weight:700;">{{ $user->plan === 'free' ? 'No paid plan' : $user->plan_label.' Plan' }}</span>
              @if($user->plan_expires_at) <span style="color:#94a3b8;">· Expires {{ $user->plan_expires_at->format('d/m/Y') }}</span>@endif
            </div>
          </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
          <div class="form-group">
            <label>First Name</label>
            <input type="text" name="first_name" value="{{ old('first_name', $user->first_name) }}" placeholder="Milan" required>
          </div>
          <div class="form-group">
            <label>Last Name</label>
            <input type="text" name="last_name" value="{{ old('last_name', $user->last_name) }}" placeholder="Jajal">
          </div>
          <div class="form-group" style="grid-column:1/-1;">
            <label>Email ID</label>
            <input type="email" name="email" value="{{ old('email', $user->email) }}" required>
          </div>
          <div class="form-group" style="grid-column:1/-1;">
            <label>Phone Number</label>
            <input type="tel" name="phone" value="{{ old('phone', $user->phone) }}" placeholder="9876543210">
          </div>
          <div class="form-group" style="grid-column:1/-1;">
            <label>Password <span style="color:#94a3b8;font-weight:400;">(leave blank to keep current)</span></label>
            <input type="password" name="password" placeholder="New password (min 8 characters)">
          </div>
          <div class="form-group" style="grid-column:1/-1;">
            <label>Confirm Password</label>
            <input type="password" name="password_confirmation" placeholder="Repeat new password">
          </div>
        </div>
      </form>
    </div>
  </div>

  <!-- Right: Storage + Subscription -->
  <div style="display:flex;flex-direction:column;gap:1.25rem;">

    <!-- Storage Utilization -->
    <div class="card">
      <div class="card-header" style="display:flex;align-items:center;gap:.5rem;">
        <i class="fa-solid fa-hard-drive" style="color:#6366f1;"></i> Storage Utilization
      </div>
      <div class="card-body">
        <div style="font-size:12px;color:#64748b;margin-bottom:1rem;display:flex;align-items:center;gap:.4rem;">
          <i class="fa-solid fa-circle-info"></i> Deleted images will be reduced from upload count post 24 hours
        </div>

        <!-- Tabs -->
        <div style="display:flex;gap:0;border-bottom:2px solid #e2e8f0;margin-bottom:1rem;">
          <button onclick="showStorageTab('limit')" id="tabLimit" style="padding:.5rem 1rem;font-size:13px;font-weight:600;border:none;background:none;cursor:pointer;color:#6366f1;border-bottom:2px solid #6366f1;margin-bottom:-2px;">Storage Limit</button>
          <button onclick="showStorageTab('delete')" id="tabDelete" style="padding:.5rem 1rem;font-size:13px;font-weight:600;border:none;background:none;cursor:pointer;color:#94a3b8;border-bottom:2px solid transparent;margin-bottom:-2px;">Delete & Re-upload Limit</button>
        </div>

        <div id="storageLimitTab">
          @php $limits = $user->plan_limits; @endphp
          <div style="display:flex;align-items:center;gap:.5rem;margin-bottom:.5rem;">
            <i class="fa-regular fa-image" style="color:#6366f1;"></i>
            <span style="font-size:13.5px;font-weight:600;">{{ number_format($user->photos()->count()) }} of {{ number_format($limits['photos_per_group'] * 10) }} Photos</span>
          </div>
          <div style="background:#f1f5f9;border-radius:4px;height:8px;margin-bottom:1rem;">
            <div style="height:100%;background:linear-gradient(90deg,#6366f1,#ec4899);border-radius:4px;width:{{ min(100, ($user->photos()->count() / max(1, $limits['photos_per_group'] * 10)) * 100) }}%;"></div>
          </div>

          <div style="display:flex;align-items:center;gap:.5rem;margin-bottom:.5rem;">
            <i class="fa-regular fa-hard-drive" style="color:#6366f1;"></i>
            <span style="font-size:13.5px;font-weight:600;">{{ $user->storage_used_human }} of {{ $limits['storage_label'] }}</span>
          </div>
          <div style="background:#f1f5f9;border-radius:4px;height:8px;">
            <div style="height:100%;background:linear-gradient(90deg,#6366f1,#ec4899);border-radius:4px;width:{{ $user->storage_percent }}%;"></div>
          </div>

          <a href="{{ route('settings.subscription') }}" style="display:block;margin-top:1rem;font-size:12.5px;color:#6366f1;font-weight:600;text-decoration:none;">Additional Info →</a>
        </div>

        <div id="deleteTab" style="display:none;">
          <div style="font-size:13px;color:#64748b;text-align:center;padding:1rem 0;">
            <i class="fa-solid fa-rotate" style="font-size:2rem;color:#e2e8f0;display:block;margin-bottom:.5rem;"></i>
            No re-upload activity this month.
          </div>
        </div>
      </div>
    </div>

    <!-- Subscription -->
    <div class="card">
      <div class="card-header" style="display:flex;align-items:center;gap:.5rem;">
        <i class="fa-solid fa-crown" style="color:#f59e0b;"></i> Subscription
      </div>
      <div class="card-body">
        <div style="background:linear-gradient(135deg,#f0f4ff,#fdf4ff);border-radius:10px;padding:1rem;margin-bottom:1rem;">
          <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:.5rem;">
            <div>
              <div style="font-weight:700;font-size:14px;">{{ $user->plan === 'free' ? 'No paid plan' : $user->plan_label }}</div>
              <div style="font-size:12px;color:#64748b;">
                @if($user->plan !== 'free') Active · {{ ucfirst($user->meta['billing_cycle'] ?? 'yearly') }} &nbsp;·&nbsp; Expires {{ $user->plan_expires_at?->format('d/m/Y') ?? 'N/A' }}
                @else No paid plan active @endif
              </div>
            </div>
            <a href="{{ route('pricing') }}" class="btn btn-primary btn-sm">Upgrade</a>
          </div>
          <div style="font-size:12px;color:#6366f1;font-weight:600;margin-bottom:.5rem;">Includes {{ count($user->plan_limits) }} features</div>
          <div style="font-size:12.5px;color:#374151;">
            <div style="display:flex;align-items:center;gap:.4rem;margin-bottom:3px;"><i class="fa-solid fa-check" style="color:#10b981;font-size:11px;"></i> Store {{ number_format($user->plan_limits['photos_per_group'] * 10) }} photos</div>
            @if($user->plan !== 'free')<div style="display:flex;align-items:center;gap:.4rem;"><i class="fa-solid fa-check" style="color:#10b981;font-size:11px;"></i> Business Branding</div>@endif
          </div>
          <div style="display:flex;gap:.75rem;margin-top:.75rem;">
            <a href="{{ route('settings.subscription') }}" style="font-size:12px;color:#6366f1;font-weight:600;text-decoration:none;">View All</a>
            <a href="{{ route('pricing') }}" style="font-size:12px;color:#6366f1;font-weight:600;text-decoration:none;">Add Features</a>
          </div>
        </div>
      </div>
    </div>

  </div>
</div>

@push('styles')<style>@media(max-width:768px){.prof-grid{grid-template-columns:1fr!important;}}</style>@endpush
@push('scripts')
<script>
function prevPhoto(i){if(i.files[0]){const r=new FileReader();r.onload=e=>document.getElementById('photoPreview').src=e.target.result;r.readAsDataURL(i.files[0]);}}
function showStorageTab(t){
  document.getElementById('storageLimitTab').style.display=t==='limit'?'block':'none';
  document.getElementById('deleteTab').style.display=t==='delete'?'block':'none';
  document.getElementById('tabLimit').style.cssText='padding:.5rem 1rem;font-size:13px;font-weight:600;border:none;background:none;cursor:pointer;'+(t==='limit'?'color:#6366f1;border-bottom:2px solid #6366f1;margin-bottom:-2px;':'color:#94a3b8;border-bottom:2px solid transparent;margin-bottom:-2px;');
  document.getElementById('tabDelete').style.cssText='padding:.5rem 1rem;font-size:13px;font-weight:600;border:none;background:none;cursor:pointer;'+(t==='delete'?'color:#6366f1;border-bottom:2px solid #6366f1;margin-bottom:-2px;':'color:#94a3b8;border-bottom:2px solid transparent;margin-bottom:-2px;');
}
</script>
@endpush
@endsection
