@extends('layouts.app')
@section('title','Group Settings - ' . $group->name)
@section('content')

<div style="background:#fff;border-bottom:1px solid #e2e8f0;padding:1.5rem;">
  <div style="max-width:100%;margin:0 auto;display:flex;align-items:center;justify-content:space-between;gap:1rem;">
    <div>
      <h1 style="font-size:1.6rem;font-weight:800;color:#0f172a;margin:0;">⚙️ {{ $group->name }} Settings</h1>
      <p style="color:#64748b;margin:.5rem 0 0;font-size:13px;">Manage all aspects of your photo group</p>
    </div>
    <a href="{{ route('groups.show', $group) }}" class="btn btn-outline btn-sm"><i class="fa-solid fa-arrow-left"></i> Back to Album</a>
  </div>
</div>

<div style="max-width:100%;margin:0 auto;padding:2rem 1.5rem;display:grid;grid-template-columns:240px 1fr;gap:2rem;">
  <!-- Sidebar Navigation -->
  <div style="position:sticky;top:1.5rem;height:fit-content;">
    <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;overflow:hidden;">
      <button onclick="switchTab('general')" class="settings-tab active" data-tab="general" style="width:100%;padding:.85rem 1rem;border:none;background:none;text-align:left;cursor:pointer;border-left:3px solid #6366f1;background:#fff;color:#0f172a;font-weight:600;font-size:13px;"><i class="fa-solid fa-sliders"></i> General Settings</button>
      <button onclick="switchTab('participants')" class="settings-tab" data-tab="participants" style="width:100%;padding:.85rem 1rem;border:none;background:none;text-align:left;cursor:pointer;border-left:3px solid transparent;color:#64748b;font-weight:600;font-size:13px;"><i class="fa-solid fa-users"></i> Participants</button>
      <button onclick="switchTab('privacy')" class="settings-tab" data-tab="privacy" style="width:100%;padding:.85rem 1rem;border:none;background:none;text-align:left;cursor:pointer;border-left:3px solid transparent;color:#64748b;font-weight:600;font-size:13px;"><i class="fa-solid fa-lock"></i> Privacy Settings</button>
      <button onclick="switchTab('folders')" class="settings-tab" data-tab="folders" style="width:100%;padding:.85rem 1rem;border:none;background:none;text-align:left;cursor:pointer;border-left:3px solid transparent;color:#64748b;font-weight:600;font-size:13px;"><i class="fa-solid fa-folder"></i> Folders</button>
      <button onclick="switchTab('design')" class="settings-tab" data-tab="design" style="width:100%;padding:.85rem 1rem;border:none;background:none;text-align:left;cursor:pointer;border-left:3px solid transparent;color:#64748b;font-weight:600;font-size:13px;"><i class="fa-solid fa-palette"></i> Design</button>
      <button onclick="switchTab('download')" class="settings-tab" data-tab="download" style="width:100%;padding:.85rem 1rem;border:none;background:none;text-align:left;cursor:pointer;border-left:3px solid transparent;color:#64748b;font-weight:600;font-size:13px;"><i class="fa-solid fa-download"></i> View & Download</button>
      <button onclick="switchTab('flipbook')" class="settings-tab" data-tab="flipbook" style="width:100%;padding:.85rem 1rem;border:none;background:none;text-align:left;cursor:pointer;border-left:3px solid transparent;color:#64748b;font-weight:600;font-size:13px;"><i class="fa-solid fa-book"></i> Digital Flipbook</button>
      <button onclick="switchTab('branding')" class="settings-tab" data-tab="branding" style="width:100%;padding:.85rem 1rem;border:none;background:none;text-align:left;cursor:pointer;border-left:3px solid transparent;color:#64748b;font-weight:600;font-size:13px;"><i class="fa-solid fa-star"></i> Branding & Sponsors</button>
      <button onclick="switchTab('favorite')" class="settings-tab" data-tab="favorite" style="width:100%;padding:.85rem 1rem;border:none;background:none;text-align:left;cursor:pointer;border-left:3px solid transparent;color:#64748b;font-weight:600;font-size:13px;"><i class="fa-solid fa-heart"></i> Client Favorite</button>
    </div>
  </div>

  <!-- Content Area -->
  <div style="display:grid;gap:1.5rem;">

    <!-- General Settings -->
    <div id="general" class="settings-content" style="display:block;">
      <div class="card">
        <div class="card-header"><h2 style="font-size:1.1rem;font-weight:700;color:#0f172a;margin:0;"><i class="fa-solid fa-sliders"></i> General Settings</h2></div>
        <div class="card-body">
          <form action="{{ route('groups.update', $group) }}" method="POST" enctype="multipart/form-data">
            @csrf @method('PUT')
            <div style="display:grid;gap:1.2rem;">
              <div>
                <label style="display:block;font-size:13px;font-weight:700;margin-bottom:.5rem;color:#475569;">Group Name</label>
                <input type="text" name="name" value="{{ $group->name }}" required style="width:100%;padding:.8rem;border:1px solid #cbd5e1;border-radius:8px;font-size:14px;">
              </div>
              <div>
                <label style="display:block;font-size:13px;font-weight:700;margin-bottom:.5rem;color:#475569;">Event Type</label>
                <select name="event_type" required style="width:100%;padding:.8rem;border:1px solid #cbd5e1;border-radius:8px;font-size:14px;">
                  <option value="wedding" @selected($group->event_type === 'wedding')>💒 Wedding</option>
                  <option value="birthday" @selected($group->event_type === 'birthday')>🎂 Birthday</option>
                  <option value="event" @selected($group->event_type === 'event')>🎉 Event</option>
                  <option value="family" @selected($group->event_type === 'family')>👨‍👩‍👧‍👦 Family</option>
                  <option value="other" @selected($group->event_type === 'other')>📸 Other</option>
                </select>
              </div>
              <div>
                <label style="display:block;font-size:13px;font-weight:700;margin-bottom:.5rem;color:#475569;">Event Date</label>
                <input type="date" name="event_date" value="{{ $group->event_date?->format('Y-m-d') }}" style="width:100%;padding:.8rem;border:1px solid #cbd5e1;border-radius:8px;font-size:14px;">
              </div>
              <div>
                <label style="display:block;font-size:13px;font-weight:700;margin-bottom:.5rem;color:#475569;">Cover Photo</label>
                <input type="file" name="cover_photo" accept="image/*" style="width:100%;padding:.8rem;border:1px solid #cbd5e1;border-radius:8px;font-size:14px;">
              </div>
              <div style="display:flex;align-items:center;gap:.6rem;padding:.8rem;background:#f0f9ff;border-radius:8px;">
                <input type="hidden" name="allow_guest_upload" value="0">
                <input type="checkbox" name="allow_guest_upload" value="1" @checked($group->allow_guest_upload) id="guestUpload" style="cursor:pointer;">
                <label for="guestUpload" style="cursor:pointer;margin:0;font-size:13px;color:#475569;flex:1;">Allow guests to upload photos</label>
              </div>
              <button type="submit" class="btn btn-primary" style="justify-content:center;"><i class="fa-solid fa-save"></i> Save Changes</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Participants -->
    <div id="participants" class="settings-content" style="display:none;">
      <div class="card">
        <div class="card-header"><h2 style="font-size:1.1rem;font-weight:700;color:#0f172a;margin:0;"><i class="fa-solid fa-users"></i> Manage Participants</h2></div>
        <div class="card-body">
          <p style="color:#64748b;margin-bottom:1rem;font-size:13px;">View and manage group members</p>
          <a href="{{ route('groups.members', $group) }}" class="btn btn-primary"><i class="fa-solid fa-users"></i> Go to Members Page</a>
        </div>
      </div>
    </div>

    <!-- Privacy Settings -->
    <div id="privacy" class="settings-content" style="display:none;">
      <div class="card">
        <div class="card-header"><h2 style="font-size:1.1rem;font-weight:700;color:#0f172a;margin:0;"><i class="fa-solid fa-lock"></i> Privacy Settings</h2></div>
        <div class="card-body">
          <form action="{{ route('groups.update', $group) }}" method="POST">
            @csrf @method('PUT')
            <div style="display:grid;gap:1.2rem;">
              <div>
                <label style="display:block;font-size:13px;font-weight:700;margin-bottom:.5rem;color:#475569;">Group Privacy</label>
                <select name="privacy" required style="width:100%;padding:.8rem;border:1px solid #cbd5e1;border-radius:8px;font-size:14px;">
                  <option value="private" @selected($group->privacy === 'private')>🔒 Private - Only invited members</option>
                  <option value="link_only" @selected($group->privacy === 'link_only')>🔗 Link Only - Anyone with link</option>
                  <option value="public" @selected($group->privacy === 'public')>🌐 Public - Anyone can find</option>
                </select>
              </div>
              <div style="display:flex;align-items:center;gap:.6rem;padding:.8rem;background:#f0f9ff;border-radius:8px;">
                <input type="hidden" name="face_recognition_enabled" value="0">
                <input type="checkbox" name="face_recognition_enabled" value="1" @checked($group->face_recognition_enabled) id="faceRec" style="cursor:pointer;">
                <label for="faceRec" style="cursor:pointer;margin:0;font-size:13px;color:#475569;flex:1;">Enable face recognition to find photos</label>
              </div>
              <button type="submit" class="btn btn-primary" style="justify-content:center;"><i class="fa-solid fa-save"></i> Save Privacy Settings</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Folders -->
    <div id="folders" class="settings-content" style="display:none;">
      <div class="card">
        <div class="card-header"><h2 style="font-size:1.1rem;font-weight:700;color:#0f172a;margin:0;"><i class="fa-solid fa-folder"></i> Manage Folders</h2></div>
        <div class="card-body">
          <p style="color:#64748b;margin-bottom:1rem;font-size:13px;">Organize photos into folders</p>
          <a href="{{ route('folders.index', $group) }}" class="btn btn-primary"><i class="fa-solid fa-folder-open"></i> Go to Folders</a>
        </div>
      </div>
    </div>

    <!-- Design -->
    <div id="design" class="settings-content" style="display:none;">
      <div class="card">
        <div class="card-header"><h2 style="font-size:1.1rem;font-weight:700;color:#0f172a;margin:0;"><i class="fa-solid fa-palette"></i> Design & Appearance</h2></div>
        <div class="card-body">
          <form action="{{ route('groups.update', $group) }}" method="POST">
            @csrf @method('PUT')
            <div style="display:grid;gap:1.2rem;">
              <div style="display:flex;align-items:center;gap:.6rem;padding:.8rem;background:#f0f9ff;border-radius:8px;">
                <input type="hidden" name="watermark_enabled" value="0">
                <input type="checkbox" name="watermark_enabled" value="1" @checked($group->watermark_enabled) id="watermark" style="cursor:pointer;">
                <label for="watermark" style="cursor:pointer;margin:0;font-size:13px;color:#475569;flex:1;">Add watermark to photos</label>
              </div>
              <div>
                <label style="display:block;font-size:13px;font-weight:700;margin-bottom:.5rem;color:#475569;">Watermark Text</label>
                <input type="text" name="watermark_text" value="{{ $group->watermark_text ?? '' }}" placeholder="© Your Name or Brand" style="width:100%;padding:.8rem;border:1px solid #cbd5e1;border-radius:8px;font-size:14px;">
              </div>
              <button type="submit" class="btn btn-primary" style="justify-content:center;"><i class="fa-solid fa-save"></i> Save Design Settings</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- View & Download -->
    <div id="download" class="settings-content" style="display:none;">
      <div class="card">
        <div class="card-header"><h2 style="font-size:1.1rem;font-weight:700;color:#0f172a;margin:0;"><i class="fa-solid fa-download"></i> View & Download Options</h2></div>
        <div class="card-body">
          <div style="display:grid;gap:1rem;color:#64748b;font-size:13px;line-height:1.6;">
            <div style="padding:1rem;background:#f0f9ff;border-left:4px solid #6366f1;border-radius:4px;">
              <p style="margin:0;font-weight:600;color:#0f172a;">📥 Bulk Download</p>
              <p style="margin:.3rem 0 0;">Members can download multiple photos at once as a ZIP file</p>
            </div>
            <div style="padding:1rem;background:#f0f9ff;border-left:4px solid #6366f1;border-radius:4px;">
              <p style="margin:0;font-weight:600;color:#0f172a;">🖼️ Gallery View</p>
              <p style="margin:.3rem 0 0;">Choose how photos are displayed in the gallery</p>
            </div>
            <div style="padding:1rem;background:#f0f9ff;border-left:4px solid #6366f1;border-radius:4px;">
              <p style="margin:0;font-weight:600;color:#0f172a;">📊 Slideshow</p>
              <p style="margin:.3rem 0 0;">Automatic slideshow of photos with custom timing</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Digital Flipbook -->
    <div id="flipbook" class="settings-content" style="display:none;">
      <div class="card">
        <div class="card-header"><h2 style="font-size:1.1rem;font-weight:700;color:#0f172a;margin:0;"><i class="fa-solid fa-book"></i> Digital Flipbook</h2></div>
        <div class="card-body">
          <div style="display:grid;gap:1rem;color:#64748b;font-size:13px;line-height:1.6;">
            <div style="padding:1rem;background:#fff3cd;border-left:4px solid #fbbf24;border-radius:4px;">
              <p style="margin:0;font-weight:600;color:#78350f;">🔄 Coming Soon</p>
              <p style="margin:.3rem 0 0;">Create interactive flipbook view of all photos</p>
            </div>
            <div style="color:#94a3b8;font-size:12px;padding:1rem;background:#f8fafc;border-radius:8px;">
              Digital flipbook feature will allow you to create an interactive, page-turning experience for your photo collection.
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Branding & Sponsors -->
    <div id="branding" class="settings-content" style="display:none;">
      <div class="card">
        <div class="card-header"><h2 style="font-size:1.1rem;font-weight:700;color:#0f172a;margin:0;"><i class="fa-solid fa-star"></i> Branding & Sponsors</h2></div>
        <div class="card-body">
          <div style="display:grid;gap:1rem;color:#64748b;font-size:13px;line-height:1.6;">
            <div style="padding:1rem;background:#fff3cd;border-left:4px solid #fbbf24;border-radius:4px;">
              <p style="margin:0;font-weight:600;color:#78350f;">🏷️ Coming Soon</p>
              <p style="margin:.3rem 0 0;">Add sponsor logos and branding to your photo gallery</p>
            </div>
            <div style="color:#94a3b8;font-size:12px;padding:1rem;background:#f8fafc;border-radius:8px;">
              Showcase sponsors and brands in your album with custom placement and styling.
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Client Favorite -->
    <div id="favorite" class="settings-content" style="display:none;">
      <div class="card">
        <div class="card-header"><h2 style="font-size:1.1rem;font-weight:700;color:#0f172a;margin:0;"><i class="fa-solid fa-heart"></i> Client Favorite</h2></div>
        <div class="card-body">
          <div style="display:grid;gap:1rem;color:#64748b;font-size:13px;line-height:1.6;">
            <div style="padding:1rem;background:#fff3cd;border-left:4px solid #fbbf24;border-radius:4px;">
              <p style="margin:0;font-weight:600;color:#78350f;">⭐ Coming Soon</p>
              <p style="margin:.3rem 0 0;">Let members mark their favorite photos</p>
            </div>
            <div style="color:#94a3b8;font-size:12px;padding:1rem;background:#f8fafc;border-radius:8px;">
              Allow group members to mark and collect their favorite photos for easy access and curation.
            </div>
          </div>
        </div>
      </div>
    </div>

  </div>
</div>

<style>
.settings-tab {
  transition: all .2s ease;
}
.settings-tab:hover {
  background: #f1f5f9 !important;
}
.settings-tab.active {
  border-left-color: #6366f1 !important;
  background: #fff !important;
  color: #0f172a !important;
}
.settings-content {
  animation: fadeIn .3s ease;
}
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(8px); }
  to { opacity: 1; transform: translateY(0); }
}
</style>

<script>
function switchTab(tabName) {
  // Hide all content
  document.querySelectorAll('.settings-content').forEach(el => el.style.display = 'none');
  // Show selected content
  document.getElementById(tabName).style.display = 'block';
  // Update active tab styling
  document.querySelectorAll('.settings-tab').forEach(tab => tab.classList.remove('active'));
  document.querySelector(`[data-tab="${tabName}"]`).classList.add('active');
}
</script>

@endsection
