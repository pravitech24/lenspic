@extends('layouts.app')
@section('title','Create Group')
@section('content')
<div class="page-wrap" style="max-width:100%;">
  <div class="page-header"><h1 class="page-title">Create New Group</h1></div>
  <div class="card"><div class="card-body" style="padding:2rem;">
    @if($errors->any())<div class="alert alert-error">{{ $errors->first() }}</div>@endif
    <form action="{{ route('groups.store') }}" method="POST" enctype="multipart/form-data">
      @csrf
      <div class="form-group">
        <label>Cover Photo</label>
        <div class="upload-zone" onclick="document.getElementById('cov').click()" id="covZone">
          <img id="covImg" style="display:none;width:100%;height:180px;object-fit:cover;border-radius:8px;">
          <div id="covPh"><div class="icon"><i class="fa-solid fa-image"></i></div><p style="font-weight:600;margin-bottom:.2rem;">Click to upload cover</p><p style="font-size:12px;color:#94a3b8;">Recommended 1200×400, max 5MB</p></div>
        </div>
        <input type="file" name="cover_photo" id="cov" accept="image/*" hidden onchange="prevCover(this)">
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
        <div class="form-group" style="grid-column:1/-1;"><label>Group Name *</label><input type="text" name="name" value="{{ old('name') }}" placeholder="e.g. Priya & Rahul's Wedding" required autofocus></div>
        <div class="form-group"><label>Event Type *</label>
          <select name="event_type" required>
            <option value="">Select...</option>
            @foreach(['wedding'=>'💍 Wedding','birthday'=>'🎂 Birthday','corporate'=>'🏢 Corporate','graduation'=>'🎓 Graduation','travel'=>'✈️ Travel','family'=>'👨‍👩‍👧 Family','festival'=>'🎉 Festival','sports'=>'⚽ Sports','other'=>'📸 Other'] as $v=>$l)
            <option value="{{ $v }}" {{ old('event_type')===$v?'selected':'' }}>{{ $l }}</option>
            @endforeach
          </select>
        </div>
        <div class="form-group"><label>Event Date</label><input type="date" name="event_date" value="{{ old('event_date') }}"></div>
        <div class="form-group" style="grid-column:1/-1;"><label>Description</label><textarea name="description" rows="2" placeholder="A few words about this event...">{{ old('description') }}</textarea></div>
        <div class="form-group"><label>Location <span style="font-weight:400">(optional)</span></label><input type="text" name="location" value="{{ old('location') }}"></div>
        <div class="form-group"><label>Membership limit <span style="font-weight:400">(optional)</span></label><input type="number" name="membership_limit" min="2" value="{{ old('membership_limit') }}"></div>
        <div class="form-group"><label>Privacy</label>
          <select name="privacy">
            <option value="link_only" selected>🔗 Link Only (Recommended)</option>
            <option value="public">🌍 Public</option>
            <option value="private">🔒 Private</option>
          </select>
        </div>
      </div>
      <hr style="border:none;border-top:1px solid #e2e8f0;margin:1.25rem 0;">
      <div class="form-group"><label style="font-weight:700;color:#0f172a">Participant Access Options</label><p style="font-size:12px;color:#64748b;margin-bottom:.6rem">Enable at least one invitation type.</p><label style="display:flex;gap:.6rem;align-items:center;margin:.5rem 0"><input type="checkbox" name="access_options[]" value="partial_access" checked> Partial Access — own matched photos and Highlights</label><label style="display:flex;gap:.6rem;align-items:center;margin:.5rem 0"><input type="checkbox" name="access_options[]" value="full_access" checked> Full Access — all shared photos and folders</label></div>
      <hr style="border:none;border-top:1px solid #e2e8f0;margin:1.25rem 0;">
      <div style="display:flex;flex-direction:column;gap:.85rem;">
        <label style="display:flex;align-items:center;justify-content:space-between;cursor:pointer;margin:0;">
          <div><div style="font-weight:500;font-size:13.5px;">Allow Guest Uploads</div><div style="font-size:12px;color:#64748b;">Guests can upload without an account</div></div>
          <label class="toggle"><input type="checkbox" name="allow_guest_upload"><span class="toggle-slider"></span></label>
        </label>
        <label style="display:flex;align-items:center;justify-content:space-between;cursor:pointer;margin:0;">
          <div><div style="font-weight:500;font-size:13.5px;">AI Face Recognition</div><div style="font-size:12px;color:#64748b;">Auto-find photos of each person</div></div>
          <label class="toggle"><input type="checkbox" name="face_recognition_enabled"><span class="toggle-slider"></span></label>
        </label>
        <label style="display:flex;align-items:center;justify-content:space-between;cursor:pointer;margin:0;">
          <div><div style="font-weight:500;font-size:13.5px;">Watermark Photos</div><div style="font-size:12px;color:#64748b;">Add your studio name to photos</div></div>
          <label class="toggle"><input type="checkbox" name="watermark_enabled" onchange="document.getElementById('wtg').style.display=this.checked?'block':'none'"><span class="toggle-slider"></span></label>
        </label>
        <div id="wtg" style="display:none;" class="form-group"><label>Watermark Text</label><input type="text" name="watermark_text" placeholder="© Studio Name 2024" maxlength="50"></div>
      </div>
      <div style="display:flex;gap:.75rem;margin-top:2rem;">
        <button type="submit" class="btn btn-primary btn-lg" style="flex:1;justify-content:center;"><i class="fa-solid fa-plus"></i> Create Group</button>
        <a href="{{ route('groups.index') }}" class="btn btn-outline btn-lg">Cancel</a>
      </div>
    </form>
  </div></div>
</div>
@push('scripts')
<script>
function prevCover(i){if(i.files[0]){const r=new FileReader();r.onload=e=>{document.getElementById('covImg').src=e.target.result;document.getElementById('covImg').style.display='block';document.getElementById('covPh').style.display='none';};r.readAsDataURL(i.files[0]);}}
</script>
@endpush
@endsection
