@extends('layouts.app')
@section('title','Edit Group')
@section('content')
<div class="page-wrap" style="max-width:100%;">
  <div class="page-header"><h1 class="page-title">Edit Group</h1><a href="{{ route('groups.show',$group) }}" class="btn btn-outline btn-sm">← Back</a></div>
  <div class="card"><div class="card-body" style="padding:2rem;">
    @if($errors->any())<div class="alert alert-error">{{ $errors->first() }}</div>@endif
    <form action="{{ route('groups.update',$group) }}" method="POST" enctype="multipart/form-data">
      @csrf @method('PUT')
      <div class="form-group">
        <label>Cover Photo</label>
        <div class="upload-zone" onclick="document.getElementById('cov').click();" style="padding:1rem;">
          @if($group->cover_photo)<img src="{{ $group->cover_photo_url }}" style="width:100%;height:160px;object-fit:cover;border-radius:8px;" id="covImg">
          @else<img id="covImg" style="display:none;width:100%;height:160px;object-fit:cover;border-radius:8px;"><div id="covPh" style="padding:1.5rem 0;text-align:center;"><div style="font-size:1.5rem;margin-bottom:4px;">🖼️</div><p style="font-size:13px;color:#64748b;">Click to upload</p></div>@endif
        </div>
        <input type="file" name="cover_photo" id="cov" accept="image/*" hidden onchange="prevCov(this)">
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
        <div class="form-group" style="grid-column:1/-1;"><label>Group Name *</label><input type="text" name="name" value="{{ old('name',$group->name) }}" required></div>
        <div class="form-group"><label>Event Type *</label>
          <select name="event_type" required>
            @foreach(['wedding'=>'💍 Wedding','birthday'=>'🎂 Birthday','corporate'=>'🏢 Corporate','graduation'=>'🎓 Graduation','travel'=>'✈️ Travel','family'=>'👨‍👩‍👧 Family','festival'=>'🎉 Festival','sports'=>'⚽ Sports','other'=>'📸 Other'] as $v=>$l)
            <option value="{{ $v }}" {{ old('event_type',$group->event_type)===$v?'selected':'' }}>{{ $l }}</option>
            @endforeach
          </select>
        </div>
        <div class="form-group"><label>Event Date</label><input type="date" name="event_date" value="{{ old('event_date',$group->event_date?->format('Y-m-d')) }}"></div>
        <div class="form-group" style="grid-column:1/-1;"><label>Description</label><textarea name="description" rows="2">{{ old('description',$group->description) }}</textarea></div>
        <div class="form-group"><label>Privacy</label>
          <select name="privacy">
            <option value="link_only" {{ old('privacy',$group->privacy)==='link_only'?'selected':'' }}>🔗 Link Only</option>
            <option value="public" {{ old('privacy',$group->privacy)==='public'?'selected':'' }}>🌍 Public</option>
            <option value="private" {{ old('privacy',$group->privacy)==='private'?'selected':'' }}>🔒 Private</option>
          </select>
        </div>
      </div>
      <hr style="border:none;border-top:1px solid #e2e8f0;margin:1.25rem 0;">
      <div style="display:flex;flex-direction:column;gap:.85rem;">
        <label style="display:flex;align-items:center;justify-content:space-between;cursor:pointer;margin:0;"><div><div style="font-weight:500;font-size:13.5px;">Allow Guest Uploads</div></div><label class="toggle"><input type="hidden" name="allow_guest_upload" value="0"><input type="checkbox" name="allow_guest_upload" value="1" {{ $group->allow_guest_upload?'checked':'' }}><span class="toggle-slider"></span></label></label>
        <label style="display:flex;align-items:center;justify-content:space-between;cursor:pointer;margin:0;"><div><div style="font-weight:500;font-size:13.5px;">AI Face Recognition</div></div><label class="toggle"><input type="hidden" name="face_recognition_enabled" value="0"><input type="checkbox" name="face_recognition_enabled" value="1" {{ $group->face_recognition_enabled?'checked':'' }}><span class="toggle-slider"></span></label></label>
        <label style="display:flex;align-items:center;justify-content:space-between;cursor:pointer;margin:0;"><div><div style="font-weight:500;font-size:13.5px;">Watermark Photos</div></div><label class="toggle"><input type="hidden" name="watermark_enabled" value="0"><input type="checkbox" name="watermark_enabled" value="1" {{ $group->watermark_enabled?'checked':'' }} onchange="document.getElementById('wtg').style.display=this.checked?'block':'none'"><span class="toggle-slider"></span></label></label>
        <div id="wtg" style="display:{{ $group->watermark_enabled?'block':'none' }};" class="form-group"><label>Watermark Text</label><input type="text" name="watermark_text" value="{{ old('watermark_text',$group->watermark_text) }}" placeholder="© Studio Name 2024"></div>
      </div>
      <div style="display:flex;gap:.75rem;margin-top:2rem;">
        <button type="submit" class="btn btn-primary btn-lg" style="flex:1;justify-content:center;">Save Changes</button>
        <a href="{{ route('groups.show',$group) }}" class="btn btn-outline btn-lg">Cancel</a>
      </div>
    </form>
    <hr style="border:none;border-top:1px solid #e2e8f0;margin:1.5rem 0;">
    <div style="font-weight:600;margin-bottom:.75rem;">Participant Invitations</div>
    <p style="color:#64748b;margin-bottom:.75rem">Manage separate Partial and Full Access codes, links, limits, and QR cards.</p>
    <a href="{{ route('groups.access-invites.index',$group) }}" class="btn btn-primary"><i class="fa-solid fa-share-nodes"></i> Share Group Invite</a>
    <hr style="border:none;border-top:1px solid #e2e8f0;margin:1.5rem 0;">
    <div style="font-weight:600;color:#ef4444;margin-bottom:.75rem;">Danger Zone</div>
    <form action="{{ route('groups.destroy',$group) }}" method="POST" onsubmit="return confirm('Delete group and ALL photos? This CANNOT be undone!')">@csrf @method('DELETE')
      <button type="submit" class="btn btn-danger btn-sm"><i class="fa-solid fa-trash"></i> Delete Group</button>
    </form>
  </div></div>
</div>
@push('scripts')
<script>function prevCov(i){if(i.files[0]){const r=new FileReader();r.onload=e=>{document.getElementById('covImg').src=e.target.result;document.getElementById('covImg').style.display='block';const ph=document.getElementById('covPh');if(ph)ph.style.display='none';};r.readAsDataURL(i.files[0]);}}</script>
@endpush
@endsection
