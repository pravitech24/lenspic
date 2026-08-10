@extends('layouts.app')
@section('title',$group->name)
@section('content')
@if($isAdmin)<div style="margin:1rem 1.5rem" class="alert alert-success"><strong>Participant invitations</strong><span style="margin-left:auto"><a class="btn btn-sm btn-outline" href="{{ route('groups.access-invites.index',$group) }}">Share Group Invite</a></span></div>@endif
<div style="background:#fff;border-bottom:1px solid #e2e8f0;margin-bottom:1.5rem;">
  <div style="height:200px;background:linear-gradient(135deg,#667eea,#764ba2);position:relative;overflow:hidden;">
    @if($group->cover_photo)<img src="{{ $group->cover_photo_url }}" style="width:100%;height:100%;object-fit:cover;">@endif
    <div style="position:absolute;inset:0;background:linear-gradient(to bottom,transparent 40%,rgba(0,0,0,.7));"></div>
    <div style="position:absolute;bottom:1rem;left:1.5rem;color:#fff;">
      <div style="font-size:.8rem;opacity:.8;margin-bottom:4px;">{{ $group->getEventTypeLabel() }}</div>
      <h1 style="font-family:'Plus Jakarta Sans',sans-serif;font-size:1.6rem;font-weight:800;">{{ $group->name }}</h1>
      @if($group->event_date)<div style="font-size:13px;opacity:.85;margin-top:2px;"><i class="fa-regular fa-calendar"></i> {{ $group->event_date->format('d M Y') }}</div>@endif
    </div>
    @if($isAdmin)
    <div style="position:absolute;top:1rem;right:1rem;display:flex;gap:.5rem;">
      <div style="position:relative;">
        <button class="btn btn-sm" style="background:rgba(255,255,255,.9);color:#374151;" onclick="document.getElementById('adminMenu').style.display=document.getElementById('adminMenu').style.display==='block'?'none':'block'"><i class="fa-solid fa-gear"></i> Settings</button>
        <div id="adminMenu" style="position:absolute;top:100%;right:0;background:#fff;border:1px solid #e2e8f0;border-radius:8px;box-shadow:0 4px 12px rgba(0,0,0,.15);display:none;z-index:100;min-width:200px;margin-top:.5rem;">
          <a href="{{ route('groups.settings',$group) }}" style="display:block;padding:.75rem 1rem;color:#374151;text-decoration:none;font-size:14px;border-bottom:1px solid #f1f5f9;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background=''"><i class="fa-solid fa-sliders"></i> All Settings</a>
          <a href="{{ route('groups.members',$group) }}" style="display:block;padding:.75rem 1rem;color:#374151;text-decoration:none;font-size:14px;border-bottom:1px solid #f1f5f9;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background=''"><i class="fa-solid fa-users"></i> Manage Members</a>
          <form action="{{ route('groups.destroy',$group) }}" method="POST" style="display:block;" onsubmit="return confirm('Delete this group and all photos?')">@csrf @method('DELETE')<button type="submit" style="width:100%;padding:.75rem 1rem;color:#dc2626;background:none;border:none;text-align:left;cursor:pointer;font-size:14px;" onmouseover="this.style.background='#fef2f2'" onmouseout="this.style.background=''"><i class="fa-solid fa-trash"></i> Delete Group</button></form>
        </div>
      </div>
    </div>
    @endif
  </div>
  @if(auth()->user()->isTrial())
  <div style="max-width:1600px;margin:0 auto;padding:1rem 1.5rem 0;">
    <div style="background:#f8fafc;border:1px dashed #cbd5e1;border-radius:10px;padding:.8rem 1rem;font-size:13px;color:#475569;">Trial mode: uploads and sharing are active for up to 500 photos. Other tools stay disabled until you upgrade.</div>
  </div>
  @endif

  <div style="max-width:1600px;margin:0 auto;padding:.85rem 1.5rem;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.75rem;">
    <div style="display:flex;gap:1.5rem;flex-wrap:wrap;">
      <span style="font-size:13px;color:#64748b;"><i class="fa-solid fa-camera" style="color:#6366f1;"></i> {{ $photos->total() }} Photos</span>
      <span style="font-size:13px;color:#64748b;"><i class="fa-solid fa-users" style="color:#6366f1;"></i> {{ $members->count() }} Members</span>
      <span style="font-size:13px;color:#64748b;"><i class="fa-solid fa-user" style="color:#6366f1;"></i> By {{ $group->creator->name }}</span>
    </div>
    <div style="display:flex;gap:.5rem;flex-wrap:wrap;">
      @if($group->face_recognition_enabled && $isMember && auth()->user()->canAccessFeature('face_recognition'))
      <a href="{{ route('face.show',$group) }}" class="btn btn-outline btn-sm"><i class="fa-solid fa-face-smile"></i> Find My Photos</a>
      @endif
      @if(auth()->user()->canAccessFeature('settings'))
      <a href="{{ route('folders.index',$group) }}" class="btn btn-outline btn-sm"><i class="fa-solid fa-folder-open"></i> Folders</a>
      @else
      <button type="button" class="btn btn-outline btn-sm" disabled><i class="fa-solid fa-folder-open"></i> Folders</button>
      @endif
      <button class="btn btn-outline btn-sm" onclick="copyLink()"><i class="fa-solid fa-link"></i> Copy Link</button>
      <button class="btn btn-primary btn-sm" onclick="openShareModal()"><i class="fa-solid fa-share-nodes"></i> Share Album</button>
      @if(!$isMember && !$isAdmin)
      <form action="{{ route('groups.join',$group) }}" method="POST" style="display:inline;">@csrf<button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-user-plus"></i> Join Group</button></form>
      @endif
      @if($isMember && !$isAdmin)
      <form action="{{ route('groups.leave',$group) }}" method="POST" style="display:inline;" onsubmit="return confirm('Leave this group?')">@csrf<button type="submit" class="btn btn-outline btn-sm">Leave</button></form>
      @endif
    </div>
  </div>
</div>

<div  style="padding-top:0;max-width:100%;margin:0 auto;">
  @if($isMember || $isAdmin)
  <div class="card" style="margin-bottom:1.5rem;border:1px solid #e2e8f0;border-radius:0;box-shadow:none;">
    <div class="card-body">
      <div class="upload-zone" id="upZone" style="border-radius:0;">
        <div class="icon"><i class="fa-solid fa-cloud-arrow-up"></i></div>
        <p style="font-weight:600;margin-bottom:.25rem;">Drag & drop or click to upload photos</p>
        <p style="font-size:12.5px;color:#94a3b8;">JPEG, PNG, WebP · Up to 20 files · 50MB each</p>
        <input type="file" id="fileInp" multiple accept="image/*" hidden>
      </div>
      <div id="upQueue" style="display:none;margin-top:1rem;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:.5rem;">
          <span id="upStatus" style="font-size:13px;font-weight:600;"></span>
          <button class="btn btn-outline btn-sm" onclick="clearQ()">Clear</button>
        </div>
        <div class="progress-bar"><div class="progress-bar-fill" id="upProg" style="width:0%"></div></div>
        <div id="upList" style="margin-top:.75rem;display:grid;grid-template-columns:repeat(auto-fill,minmax(70px,1fr));gap:6px;max-height:180px;overflow-y:auto;"></div>
        <button class="btn btn-primary" style="margin-top:.75rem;width:100%;justify-content:center;" id="upBtn" onclick="startUpload()" disabled>
          <i class="fa-solid fa-upload"></i> <span id="upBtnTxt">Upload</span>
        </button>
      </div>
    </div>
  </div>
  @endif

  <div style="display:flex;align-items:center;gap:0;margin-bottom:1.5rem;border-bottom:2px solid #e2e8f0;overflow-x:auto;">
    <button class="folder-tab active" onclick="filterByFolder(null)" style="padding:.8rem 1.2rem;border:none;background:none;color:#0f172a;font-weight:600;font-size:14px;cursor:pointer;border-bottom:3px solid #6366f1;margin-bottom:-2px;white-space:nowrap;">📷 All Photos</button>
    @foreach($folders as $folder)
    <button class="folder-tab" onclick="filterByFolder({{ $folder->id }})" data-folder="{{ $folder->id }}" style="padding:.8rem 1.2rem;border:none;background:none;color:#64748b;font-weight:600;font-size:14px;cursor:pointer;border-bottom:3px solid transparent;margin-bottom:-2px;white-space:nowrap;transition:all .2s ease;">
      <span style="display:inline-flex;align-items:center;gap:.4rem;">
        <span style="width:8px;height:8px;border-radius:999px;background:{{ $folder->color ?? '#6366f1' }};"></span>
        {{ $folder->name }}
        <span style="font-size:11px;color:#94a3b8;font-weight:500;margin-left:.3rem;">({{ $folder->photo_count }})</span>
      </span>
    </button>
    @endforeach
  </div>

  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem;flex-wrap:wrap;gap:.5rem;">
    <div style="display:flex;align-items:center;gap:.5rem;flex-wrap:wrap;">
      <span style="font-size:13px;color:#64748b;" id="selCount">Select photos by tapping the checkbox.</span>
    </div>
    <div style="display:flex;gap:.5rem;align-items:center;">
      <button class="btn btn-outline btn-sm" id="folderBtn" style="display:none;" onclick="openFolderModal()"><i class="fa-solid fa-folder-open"></i> Move to Folder</button>
      <button class="btn btn-outline btn-sm" id="shareSelBtn" style="display:none;" onclick="shareSelectedPhotos()"><i class="fa-solid fa-share-nodes"></i> Share Selected</button>
      <button class="btn btn-outline btn-sm" id="dlBtn" style="display:none;" onclick="bulkDl()"><i class="fa-solid fa-download"></i> Download Selected</button>
    </div>
  </div>

  @if($photos->isEmpty())
  <div class="empty-state"><div class="ei">📷</div><h3 style="font-size:1.1rem;font-weight:700;margin-bottom:.5rem;">No photos yet</h3><p>Be the first to upload!</p></div>
  @else
  <div class="photo-grid" id="pgrid" style="column-width:240px;column-gap:.2rem;">
    @foreach($photos as $p)
    <div class="photo-card" data-id="{{ $p->id }}" onclick="openCarousel({{ $loop->index }})" style="position:relative;background:#fff;border:1px solid #e2e8f0;overflow:hidden;box-shadow:none;display:inline-block;width:100%;margin:0 0 .05rem;break-inside:avoid;">
      <img src="{{ $p->thumbnail_url }}" alt="" loading="lazy" style="display:block;width:100%;height:auto;">
      <div style="position:absolute;inset:0;background:linear-gradient(to bottom,rgba(2,6,23,.16),transparent 34%,rgba(2,6,23,.45));pointer-events:none;"></div>
      <div class="photo-select-toggle" style="position:absolute;top:10px;left:10px;display:flex;align-items:center;gap:.5rem;z-index:2;opacity:0;transform:translateY(-4px);transition:all .16s ease;pointer-events:none;">
        <label style="display:inline-flex;align-items:center;justify-content:center;width:20px;height:20px;cursor:pointer;background:transparent;border:none;box-shadow:none;pointer-events:auto;">
          <input type="checkbox" class="psel" value="{{ $p->id }}" onclick="event.stopPropagation();updSel()" style="width:16px;height:16px;cursor:pointer;accent-color:#fff;">
        </label>
      </div>
      <div class="photo-hover-actions" style="position:absolute;bottom:10px;right:10px;display:flex;align-items:center;gap:.45rem;z-index:2;opacity:0;transform:translateY(6px);transition:all .16s ease;pointer-events:none;">
        <button onclick="event.stopPropagation();toggleLike({{ $p->id }},this)" style="pointer-events:auto;background:transparent;border:none;color:#fff;cursor:pointer;font-size:14px;padding:6px 8px;border-radius:0;text-shadow:0 1px 3px rgba(0,0,0,.35);" data-liked="{{ $p->isLikedBy(auth()->user())?'1':'0' }}">
          <i class="fa-{{ $p->isLikedBy(auth()->user())?'solid':'regular' }} fa-heart" style="{{ $p->isLikedBy(auth()->user())?'color:#fff':'' }}"></i> <span class="lc">{{ $p->likes_count }}</span>
        </button>
        <a href="{{ route('photos.download',[$group,$p]) }}" onclick="event.stopPropagation();" style="pointer-events:auto;display:inline-flex;align-items:center;justify-content:center;width:32px;height:32px;border-radius:0;background:transparent;border:none;color:#fff;text-decoration:none;text-shadow:0 1px 3px rgba(0,0,0,.35);"><i class="fa-solid fa-download"></i></a>
        @if($isAdmin || $p->uploader_id === auth()->id())
        <button onclick="event.stopPropagation();delPhoto({{ $p->id }},this.closest('.photo-card'))" style="pointer-events:auto;display:inline-flex;align-items:center;justify-content:center;width:32px;height:32px;border-radius:0;background:transparent;border:none;color:#fff;cursor:pointer;text-shadow:0 1px 3px rgba(0,0,0,.35);"><i class="fa-solid fa-trash"></i></button>
        @endif
      </div>
    </div>
    @endforeach
  </div>
  <div style="margin-top:1.5rem;display:flex;justify-content:center;">{{ $photos->links() }}</div>
  @endif
</div>

<div id="photoViewer" style="position:fixed;inset:0;background:#000;z-index:400;display:flex;align-items:center;justify-content:center;padding:1rem;opacity:0;visibility:hidden;pointer-events:none;transition:opacity .24s ease;">
  <div class="viewer-shell" style="width:100%;min-width:100%;display:flex;align-items:center;justify-content:center;position:relative;max-height:calc(100vh - 2rem);transform:scale(.98);transition:transform .24s ease;">
    <button type="button" onclick="moveCarousel(-1)" aria-label="Previous photo" style="position:absolute;left:-.35rem;top:50%;transform:translate(-50%,-50%);width:56px;height:56px;border:none;border-radius:0;background:rgba(255,255,255,.12);color:#fff;cursor:pointer;display:flex;align-items:center;justify-content:center;backdrop-filter:blur(8px);z-index:5;"><i class="fa-solid fa-chevron-left"></i></button>
    <div style="position:relative;width:min(100%,100%);display:flex;align-items:center;justify-content:center;padding:0 6rem 2.8rem;">
      <div style="position:absolute;top:1rem;right:1rem;display:flex;flex-direction:column;gap:.5rem;z-index:3;">
        <button type="button" onclick="zoomCarousel(-0.25)" aria-label="Zoom out" style="background:rgba(255,255,255,.12);border:none;color:#fff;width:42px;height:42px;border-radius:0;cursor:pointer;font-size:16px;display:flex;align-items:center;justify-content:center;backdrop-filter:blur(8px);"><i class="fa-solid fa-minus"></i></button>
        <button type="button" onclick="resetCarouselZoom()" aria-label="Reset zoom" style="background:rgba(255,255,255,.12);border:none;color:#fff;width:42px;height:42px;border-radius:0;cursor:pointer;font-size:14px;display:flex;align-items:center;justify-content:center;backdrop-filter:blur(8px);">1×</button>
        <button type="button" onclick="zoomCarousel(0.25)" aria-label="Zoom in" style="background:rgba(255,255,255,.12);border:none;color:#fff;width:42px;height:42px;border-radius:0;cursor:pointer;font-size:16px;display:flex;align-items:center;justify-content:center;backdrop-filter:blur(8px);"><i class="fa-solid fa-plus"></i></button>
        <a id="viewerDownload" href="#" class="btn btn-sm" style="background:rgba(255,255,255,.12);color:#fff;border:1px solid rgba(255,255,255,.2);backdrop-filter:blur(8px);display:flex;align-items:center;justify-content:center;width:42px;height:42px;padding:0;"><i class="fa-solid fa-download"></i></a>
        <button type="button" onclick="closeLB()" aria-label="Close photo viewer" style="background:rgba(255,255,255,.12);border:none;color:#fff;width:42px;height:42px;border-radius:0;cursor:pointer;font-size:18px;display:flex;align-items:center;justify-content:center;backdrop-filter:blur(8px);"><i class="fa-solid fa-xmark"></i></button>
      </div>
      <div style="display:flex;justify-content:center;align-items:center;min-height:calc(100vh - 140px);width:100%;">
        <img id="viewerImage" src="" alt="" style="display:block;max-width:calc(100vw - 220px);max-height:calc(100vh - 150px);width:auto;height:auto;object-fit:contain;transition:transform .12s ease;transform:scale(1);transform-origin:center center;cursor:zoom-in;">
      </div>
      <div style="position:absolute;left:50%;transform:translateX(-50%);bottom:0.4rem;text-align:center;color:#f8fafc;font-size:13px;z-index:3;">
        <div id="viewerMeta" style="display:inline-flex;align-items:center;gap:.5rem;flex-wrap:wrap;background:rgba(0,0,0,.35);padding:.4rem .7rem;border-radius:0;backdrop-filter:blur(6px);"></div>
      </div>
    </div>
    <button type="button" onclick="moveCarousel(1)" aria-label="Next photo" style="position:absolute;right:-.35rem;top:50%;transform:translate(50%,-50%);width:56px;height:56px;border:none;border-radius:0;background:rgba(255,255,255,.12);color:#fff;cursor:pointer;display:flex;align-items:center;justify-content:center;backdrop-filter:blur(8px);z-index:5;"><i class="fa-solid fa-chevron-right"></i></button>
  </div>
</div>

<div id="folderModal" style="position:fixed;inset:0;background:rgba(2,6,23,.7);display:none;align-items:center;justify-content:center;padding:1rem;z-index:500;">
  <div style="width:min(100%, 420px);background:#fff;border-radius:8px;box-shadow:0 20px 25px -5px rgba(0,0,0,.1);overflow:hidden;">
    <div style="display:flex;align-items:center;justify-content:space-between;padding:1.2rem;border-bottom:1px solid #e2e8f0;">
      <div style="font-weight:800;color:#0f172a;">Move Photos to Folder</div>
      <button type="button" onclick="closeFolderModal()" style="width:36px;height:36px;border:none;border-radius:8px;background:#f8fafc;color:#64748b;cursor:pointer;"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div style="padding:1.2rem;display:grid;gap:1rem;">
      <div>
        <label style="display:block;font-size:13px;font-weight:700;margin-bottom:.6rem;">Select a folder:</label>
        <select id="targetFolderSelect" style="width:100%;padding:.8rem;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;">
          <option value="">-- No folder (unassign) --</option>
          @forelse($group->folders as $f)
          <option value="{{ $f->id }}">{{ $f->name }}</option>
          @empty
          <option disabled>No folders available</option>
          @endforelse
        </select>
      </div>
      <div style="display:flex;gap:.5rem;justify-content:flex-end;">
        <button type="button" class="btn btn-outline btn-sm" onclick="closeFolderModal()">Cancel</button>
        <button type="button" class="btn btn-primary btn-sm" onclick="bulkMoveToFolder()">Move</button>
      </div>
    </div>
  </div>
</div>

<div id="shareModal" style="position:fixed;inset:0;background:rgba(2,6,23,.7);display:none;align-items:center;justify-content:center;padding:1rem;z-index:500;">
  <div style="width:min(100%, 920px);background:#fff;border-radius:0;box-shadow:none;overflow:hidden;max-height:90vh;overflow-y:auto;">
    <div style="display:flex;align-items:center;justify-content:space-between;padding:1.1rem 1.2rem;border-bottom:1px solid #e2e8f0;">
      <div>
        <div style="font-size:11px;font-weight:700;letter-spacing:.18em;text-transform:uppercase;color:#6366f1;">Share album</div>
        <div style="font-size:1.1rem;font-weight:800;color:#0f172a;">Create a beautiful invite</div>
      </div>
      <button type="button" onclick="closeShareModal()" style="width:36px;height:36px;border:none;border-radius:0;background:#f8fafc;color:#64748b;cursor:pointer;"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:1rem;padding:1.2rem;">
      <div style="background:linear-gradient(145deg,#f8fbff,#eef4ff);border:1px solid #dbeafe;border-radius:0;padding:1rem;">
        <div style="font-size:1rem;font-weight:800;color:#0f172a;margin-bottom:.3rem;">Viewer Access</div>
        <div style="font-size:13px;color:#64748b;margin-bottom:.8rem;">Guests can upload a selfie and only view/download the photos where they are detected.</div>
        <ul style="margin:0 0 .8rem 1rem;padding:0;color:#475569;font-size:13px;line-height:1.6;">
          <li>AI Face Recognition</li>
          <li>View only own photos</li>
          <li>Access Highlights folder</li>
        </ul>
        <div style="display:flex;align-items:center;gap:.5rem;margin-bottom:.75rem;flex-wrap:wrap;">
          <span id="viewerCode" style="font-size:14px;font-weight:700;letter-spacing:.15em;padding:.4rem .7rem;border-radius:0;background:#fff;border:1px solid #dbeafe;color:#1d4ed8;"></span>
          <button type="button" onclick="copyInviteCode('viewer')" class="btn btn-outline btn-sm"><i class="fa-solid fa-copy"></i> Copy Code</button>
        </div>
        <div style="display:flex;gap:.5rem;flex-wrap:wrap;">
          <button type="button" onclick="copyInviteLink('viewer')" class="btn btn-outline btn-sm"><i class="fa-solid fa-link"></i> Copy Link</button>
          <button type="button" onclick="shareInvite('viewer')" class="btn btn-outline btn-sm"><i class="fa-solid fa-paper-plane"></i> Share</button>
          <button type="button" onclick="regenerateInviteCode('viewer')" class="btn btn-outline btn-sm"><i class="fa-solid fa-arrows-rotate"></i> Regenerate</button>
        </div>
      </div>
      <div style="background:linear-gradient(145deg,#fefce8,#fff7ed);border:1px solid #fde68a;border-radius:0;padding:1rem;">
        <div style="font-size:1rem;font-weight:800;color:#0f172a;margin-bottom:.3rem;">Guest Access</div>
        <div style="font-size:13px;color:#64748b;margin-bottom:.8rem;">Guests can browse the complete gallery and download approved photos.</div>
        <ul style="margin:0 0 .8rem 1rem;padding:0;color:#475569;font-size:13px;line-height:1.6;">
          <li>View all folders</li>
          <li>Download allowed photos</li>
          <li>Face recognition available</li>
        </ul>
        <div style="display:flex;align-items:center;gap:.5rem;margin-bottom:.75rem;flex-wrap:wrap;">
          <span id="guestCode" style="font-size:14px;font-weight:700;letter-spacing:.15em;padding:.4rem .7rem;border-radius:0;background:#fff;border:1px solid #fde68a;color:#b45309;"></span>
          <button type="button" onclick="copyInviteCode('guest')" class="btn btn-outline btn-sm"><i class="fa-solid fa-copy"></i> Copy Code</button>
        </div>
        <div style="display:flex;gap:.5rem;flex-wrap:wrap;">
          <button type="button" onclick="copyInviteLink('guest')" class="btn btn-outline btn-sm"><i class="fa-solid fa-link"></i> Copy Link</button>
          <button type="button" onclick="shareInvite('guest')" class="btn btn-outline btn-sm"><i class="fa-solid fa-paper-plane"></i> Share</button>
          <button type="button" onclick="regenerateInviteCode('guest')" class="btn btn-outline btn-sm"><i class="fa-solid fa-arrows-rotate"></i> Regenerate</button>
        </div>
      </div>
    </div>
    <div style="padding:0 1.2rem 1.2rem;display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:1rem;align-items:center;">
      <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:0;padding:1rem;">
        <div style="font-weight:700;color:#0f172a;margin-bottom:.45rem;">QR Preview</div>
        <img id="inviteQr" src="" alt="Invite QR" style="width:180px;height:180px;object-fit:contain;border-radius:0;background:#fff;border:1px solid #e2e8f0;padding:.5rem;">
        <div style="display:flex;gap:.5rem;flex-wrap:wrap;margin-top:.75rem;">
          <button type="button" onclick="downloadQr()" class="btn btn-outline btn-sm"><i class="fa-solid fa-download"></i> Download QR</button>
          <button type="button" onclick="window.print()" class="btn btn-outline btn-sm"><i class="fa-solid fa-print"></i> Print QR</button>
        </div>
      </div>
      <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:0;padding:1rem;">
        <div style="font-weight:700;color:#0f172a;margin-bottom:.45rem;">Invite link</div>
        <input id="shareLinkInput" type="text" readonly style="width:100%;padding:.7rem .8rem;border:1px solid #e2e8f0;border-radius:0;margin-bottom:.65rem;">
        <div style="display:flex;gap:.5rem;flex-wrap:wrap;">
          <button type="button" onclick="copyInviteLink('viewer')" class="btn btn-outline btn-sm"><i class="fa-solid fa-copy"></i> Copy Link</button>
          <button type="button" onclick="shareInvite('viewer')" class="btn btn-outline btn-sm"><i class="fa-brands fa-whatsapp"></i> WhatsApp</button>
          <button type="button" onclick="shareInvite('guest')" class="btn btn-outline btn-sm"><i class="fa-brands fa-telegram"></i> Telegram</button>
        </div>
      </div>
    </div>
  </div>
</div>

<style>
  .photo-card:hover .photo-hover-actions,
  .photo-card:focus-within .photo-hover-actions,
  .photo-card:hover .photo-select-toggle,
  .photo-card:focus-within .photo-select-toggle {
    opacity: 1 !important;
    transform: translateY(0) !important;
    pointer-events: auto;
  }
  .folder-tab:hover {
    color: #0f172a !important;
  }
</style>

@push('scripts')
<script>
const gid={{ $group->id }};
const upUrl='{{ route("photos.store",$group) }}';
let selFiles=[],curId=null,carouselIndex=0,zoomLevel=1,panX=0,panY=0,isDragging=false,dragStartX=0,dragStartY=0;
let inviteState=@js($inviteState);
let currentFolderId=null;
const galleryPhotos=@js($photos->map(fn($p)=>['id'=>$p->id,'url'=>$p->url,'uploader'=>$p->uploader?->name ?? 'Guest','likes'=>$p->likes_count,'folder_id'=>$p->folder_id])->values());
const allPhotos=JSON.parse(JSON.stringify(galleryPhotos));
const zone=document.getElementById('upZone'),finp=document.getElementById('fileInp');
if(zone&&finp){
  zone.addEventListener('click',()=>finp.click());
  zone.addEventListener('dragover',e=>{e.preventDefault();zone.classList.add('drag-over');});
  zone.addEventListener('dragleave',()=>zone.classList.remove('drag-over'));
  zone.addEventListener('drop',e=>{e.preventDefault();zone.classList.remove('drag-over');handleFiles(e.dataTransfer.files,true);});
  finp.addEventListener('change',()=>handleFiles(finp.files,true));
}
function handleFiles(files,append=true){
  const incoming=Array.from(files||[]);
  selFiles=append?[...selFiles,...incoming]:incoming;
  const q=document.getElementById('upQueue'),list=document.getElementById('upList'),btn=document.getElementById('upBtn');
  q.style.display='block';
  if(!append||list.children.length===0){list.innerHTML='';}
  document.getElementById('upStatus').textContent=selFiles.length+' file(s) ready';
  const existingChildren=[...list.children];
  const seen=new Set(existingChildren.map(c=>c.dataset.name||''));
  selFiles.forEach(f=>{const key=f.name+f.size+f.lastModified; if(seen.has(key)) return; const d=document.createElement('div');d.dataset.name=key;d.style.cssText='aspect-ratio:1;border-radius:0;overflow:hidden;background:#f1f5f9;';const img=document.createElement('img');img.style.cssText='width:100%;height:100%;object-fit:cover;';const r=new FileReader();r.onload=e=>img.src=e.target.result;r.readAsDataURL(f);d.appendChild(img);list.appendChild(d);seen.add(key);});
  btn.disabled=false;document.getElementById('upBtnTxt').textContent='Upload '+selFiles.length+' Photo(s)';
  finp.value='';
}
function startUpload(){
  const btn=document.getElementById('upBtn');btn.disabled=true;
  const batchSize=4;
  const batches=[];
  for(let i=0;i<selFiles.length;i+=batchSize)batches.push(selFiles.slice(i,i+batchSize));
  let completed=0;
  const totalFiles=selFiles.length;
  const updateProgress=(done,total)=>{const p=total?Math.round(done/total*100):0;document.getElementById('upProg').style.width=p+'%';document.getElementById('upStatus').textContent='Uploading... '+p+'%';};
  const uploadNext=(index)=>{
    if(index>=batches.length){toast('✅ '+totalFiles+' photo(s) uploaded!');setTimeout(()=>location.reload(),1200);return;}
    const batch=batches[index];
    const fd=new FormData();fd.append('_token',csrf);batch.forEach(f=>fd.append('photos[]',f));
    const xhr=new XMLHttpRequest();xhr.open('POST',upUrl,true);xhr.setRequestHeader('X-CSRF-TOKEN',csrf);xhr.setRequestHeader('Accept','application/json');xhr.setRequestHeader('X-Requested-With','XMLHttpRequest');
    xhr.upload.addEventListener('progress',e=>{if(e.lengthComputable){const totalBytes=batch.reduce((sum,f)=>sum+f.size,0);const uploadedBytes=Math.min(e.loaded,totalBytes);const p=Math.round((completed+uploadedBytes/Math.max(totalBytes,1))/totalFiles*100);document.getElementById('upProg').style.width=p+'%';document.getElementById('upStatus').textContent='Uploading... '+p+'%';}});
    xhr.onload=()=>{
      let responseText=xhr.responseText||'';
      let parsed=null;
      try{parsed=responseText?JSON.parse(responseText):null;}catch(e){}
      if(xhr.status>=200&&xhr.status<300){
        completed+=batch.length;
        updateProgress(completed,totalFiles);
        uploadNext(index+1);
        return;
      }
      if(xhr.status===401||xhr.status===403){toast('Please sign in again to upload photos.');setTimeout(()=>{window.location.href='{{ route("login") }}?redirect='+encodeURIComponent(window.location.pathname);},900);return;}
      let msg='Upload failed.';
      if(parsed?.message){msg=parsed.message;}else if(parsed?.error){msg=parsed.error;}else if(responseText && /<!DOCTYPE|<html/i.test(responseText)){msg='Please sign in again to upload photos.';}else if(responseText){msg=responseText;}
      toast(msg);btn.disabled=false;
    };
    xhr.onerror=()=>{toast('Upload failed. Please check the server response.');btn.disabled=false;};
    xhr.send(fd);
  };
  updateProgress(0,totalFiles);
  uploadNext(0);
}
function clearQ(){selFiles=[];document.getElementById('upQueue').style.display='none';document.getElementById('fileInp').value='';}
function applyCarouselTransform(){const viewerImage=document.getElementById('viewerImage');if(!viewerImage)return;viewerImage.style.transform='translate('+panX+'px, '+panY+'px) scale('+zoomLevel+')';viewerImage.style.cursor=zoomLevel>1?(isDragging?'grabbing':'grab'):'zoom-in';}
function resetCarouselZoom(){zoomLevel=1;panX=0;panY=0;isDragging=false;applyCarouselTransform();}
function zoomCarousel(step){zoomLevel=Math.min(3,Math.max(1,zoomLevel+step));applyCarouselTransform();}
function renderCarousel(){const item=galleryPhotos[carouselIndex];if(!item)return;curId=item.id;const viewerImage=document.getElementById('viewerImage');const viewerMeta=document.getElementById('viewerMeta');const viewerDownload=document.getElementById('viewerDownload');if(!viewerImage||!viewerMeta||!viewerDownload)return;viewerImage.src=item.url;viewerImage.alt=item.uploader+' photo';viewerMeta.innerHTML='<span style="display:inline-flex;align-items:center;gap:6px;padding:.4rem .7rem;border-radius:999px;background:rgba(255,255,255,.14);"><i class="fa-solid fa-image"></i> '+(carouselIndex+1)+' / '+galleryPhotos.length+'</span><span style="display:inline-flex;align-items:center;gap:6px;padding:.4rem .7rem;border-radius:999px;background:rgba(255,255,255,.14);"><i class="fa-solid fa-user"></i> '+item.uploader+'</span><span style="display:inline-flex;align-items:center;gap:6px;padding:.4rem .7rem;border-radius:999px;background:rgba(255,255,255,.14);"><i class="fa-solid fa-heart"></i> '+item.likes+'</span>';viewerDownload.href='/groups/'+gid+'/photos/'+item.id+'/download';resetCarouselZoom();}
function openCarousel(index){carouselIndex=index;const viewer=document.getElementById('photoViewer');if(!viewer)return;viewer.style.opacity='1';viewer.style.visibility='visible';viewer.style.pointerEvents='auto';viewer.querySelector('.viewer-shell').style.transform='scale(1)';document.body.style.overflow='hidden';renderCarousel();}
function moveCarousel(step){if(!galleryPhotos.length)return;carouselIndex=(carouselIndex+step+galleryPhotos.length)%galleryPhotos.length;renderCarousel();}
function closeLB(){const viewer=document.getElementById('photoViewer');if(!viewer)return;viewer.querySelector('.viewer-shell').style.transform='scale(.96)';viewer.style.opacity='0';viewer.style.visibility='hidden';viewer.style.pointerEvents='none';document.body.style.overflow='';resetCarouselZoom();}
function kh(e){if(e.key==='Escape')closeLB();if(e.key==='ArrowLeft')moveCarousel(-1);if(e.key==='ArrowRight')moveCarousel(1);} 
document.addEventListener('keydown',kh);
document.getElementById('photoViewer')?.addEventListener('click',function(e){if(e.target===this)closeLB();});
const viewerImage=document.getElementById('viewerImage');
viewerImage?.addEventListener('wheel',function(e){if(document.getElementById('photoViewer')?.style.visibility!=='visible')return;e.preventDefault();zoomCarousel(e.deltaY<0?0.25:-0.25);},{passive:false});
viewerImage?.addEventListener('dblclick',function(){if(zoomLevel>1){resetCarouselZoom();}else{zoomCarousel(0.75);}});
viewerImage?.addEventListener('mousedown',function(e){if(zoomLevel<=1)return;isDragging=true;dragStartX=e.clientX;dragStartY=e.clientY;applyCarouselTransform();});
document.addEventListener('mousemove',function(e){if(!isDragging||zoomLevel<=1||!viewerImage)return;panX+=e.clientX-dragStartX;panY+=e.clientY-dragStartY;dragStartX=e.clientX;dragStartY=e.clientY;applyCarouselTransform();});
document.addEventListener('mouseup',function(){if(isDragging){isDragging=false;applyCarouselTransform();}});
async function toggleLike(id,btn){const r=await post('/groups/'+gid+'/photos/'+id+'/like',{});const i=btn.querySelector('i');const lc=btn.querySelector('.lc');i.className=r.liked?'fa-solid fa-heart':'fa-regular fa-heart';i.style.color=r.liked?'#ec4899':'';if(lc)lc.textContent=r.count;const idx=galleryPhotos.findIndex(p=>p.id===id);if(idx>-1){galleryPhotos[idx].likes=r.count;if(idx===carouselIndex)renderCarousel();}}
async function toggleLikeById(id,btn){const r=await post('/groups/'+gid+'/photos/'+id+'/like',{});const i=btn.querySelector('i');i.className=r.liked?'fa-solid fa-heart':'fa-regular fa-heart';i.style.color=r.liked?'#ec4899':'';const idx=galleryPhotos.findIndex(p=>p.id===id);if(idx>-1){galleryPhotos[idx].likes=r.count;if(idx===carouselIndex)renderCarousel();}}
async function delPhoto(id,card){if(!confirm('Delete this photo?'))return;const r=await fetch('/groups/'+gid+'/photos/'+id,{method:'DELETE',headers:{'X-CSRF-TOKEN':csrf,'Accept':'application/json'}});if(r.ok){card.remove();toast('Deleted.');}}
function updSel(){const s=document.querySelectorAll('.psel:checked');const countLabel=document.getElementById('selCount');const folderBtn=document.getElementById('folderBtn');const shareBtn=document.getElementById('shareSelBtn');const dlBtn=document.getElementById('dlBtn');const hasSelection=s.length>0;folderBtn.style.display=hasSelection?'inline-flex':'none';shareBtn.style.display=hasSelection?'inline-flex':'none';dlBtn.style.display=hasSelection?'inline-flex':'none';countLabel.textContent=hasSelection?s.length+' selected':'Select photos by tapping the checkbox.';}
function bulkDl(){const ids=Array.from(document.querySelectorAll('.psel:checked')).map(c=>c.value);if(!ids.length)return;const f=document.createElement('form');f.method='POST';f.action='/groups/'+gid+'/bulk-download';f.innerHTML='<input name="_token" value="'+csrf+'">'+ids.map(id=>'<input name="photo_ids[]" value="'+id+'">').join('');document.body.appendChild(f);f.submit();f.remove();}
function shareSelectedPhotos(){const ids=Array.from(document.querySelectorAll('.psel:checked')).map(c=>c.value);if(!ids.length)return;const urls=ids.map(id=>window.location.origin+'/groups/'+gid+'/photos/'+id);const text='Shared photos from '+document.title;const shareData={title:document.title,text,urls};if(navigator.share){navigator.share(shareData).catch(()=>{});}else{navigator.clipboard.writeText(urls.join('\n')).then(()=>toast('Selected photo links copied.')) .catch(()=>toast('Sharing is not available here.'));}}
function copyLink(){navigator.clipboard.writeText('{{ $group->share_url }}').then(()=>toast('Link copied! 📋'));}
function filterByFolder(folderId){
  currentFolderId=folderId;
  const tabs=document.querySelectorAll('.folder-tab');
  tabs.forEach(t=>{t.style.color='#64748b';t.style.borderBottomColor='transparent';});
  event.target.closest('.folder-tab').style.color='#0f172a';
  event.target.closest('.folder-tab').style.borderBottomColor='#6366f1';
  const pgrid=document.getElementById('pgrid');
  if(!pgrid)return;
  const cards=pgrid.querySelectorAll('.photo-card');
  if(folderId===null){
    cards.forEach(c=>c.style.display='inline-block');
  }else{
    cards.forEach(c=>{
      const photoId=parseInt(c.dataset.id);
      const photo=allPhotos.find(p=>p.id===photoId);
      c.style.display=(photo&&photo.folder_id===folderId)?'inline-block':'none';
    });
  }
}
function openFolderModal(){document.getElementById('folderModal').style.display='flex';}
function closeFolderModal(){document.getElementById('folderModal').style.display='none';}
async function bulkMoveToFolder(){const ids=Array.from(document.querySelectorAll('.psel:checked')).map(c=>c.value);if(!ids.length){toast('No photos selected');return;}const folderId=document.getElementById('targetFolderSelect').value;const r=await post('/groups/'+gid+'/photos/bulk-folder',{photo_ids:ids,folder_id:folderId||null});if(r&&r.moved){toast('✓ '+ids.length+' photo(s) moved!');closeFolderModal();document.querySelectorAll('.psel:checked').forEach(c=>c.checked=false);updSel();setTimeout(()=>location.reload(),1200);}else{toast('Failed to move photos');}}
function openShareModal(){document.getElementById('shareModal').style.display='flex';generateInviteData();}
function closeShareModal(){document.getElementById('shareModal').style.display='none';}
function generateInviteData(){const viewer=inviteState.viewer,guest=inviteState.guest;if(!viewer||!guest){toast('Invitations are not available for this group.');return}document.getElementById('viewerCode').textContent=viewer.code;document.getElementById('guestCode').textContent=guest.code;document.getElementById('shareLinkInput').value=viewer.link;document.getElementById('inviteQr').src='https://api.qrserver.com/v1/create-qr-code/?size=180x180&data='+encodeURIComponent(viewer.link);}
async function regenerateInviteCode(type){const invite=inviteState[type];if(!invite)return;try{const response=await fetch(invite.regenerateUrl,{method:'POST',headers:{'X-CSRF-TOKEN':csrf,'Accept':'application/json','Content-Type':'application/json'},body:JSON.stringify({regenerate:'both'})});const data=await response.json();if(!response.ok)throw new Error(data.message||'Could not regenerate this invitation.');invite.code=data.code;invite.link=data.url;document.getElementById(type==='viewer'?'viewerCode':'guestCode').textContent=invite.code;document.getElementById('shareLinkInput').value=invite.link;document.getElementById('inviteQr').src='https://api.qrserver.com/v1/create-qr-code/?size=180x180&data='+encodeURIComponent(invite.link);toast('Invite code regenerated.');}catch(error){toast(error.message);}}
async function copyToClipboard(value){
  if(!value)return false;
  if(navigator.clipboard&&window.isSecureContext){
    try{await navigator.clipboard.writeText(value);return true;}catch(error){}
  }
  const field=document.createElement('textarea');
  field.value=value;field.setAttribute('readonly','');field.style.cssText='position:fixed;left:-9999px;top:0;opacity:0';
  document.body.appendChild(field);field.focus();field.select();field.setSelectionRange(0,field.value.length);
  let copied=false;try{copied=document.execCommand('copy');}catch(error){}field.remove();return copied;
}
async function copyInviteCode(type){const code=inviteState[type].code;if(!code)return;const copied=await copyToClipboard(code);toast(copied?'Invite code copied.':'Could not copy the code. Please select it manually.');
}
async function copyInviteLink(type){const link=inviteState[type].link;if(!link)return;const copied=await copyToClipboard(link);toast(copied?'Invite link copied.':'Could not copy the link. Please select it manually.');
}
document.addEventListener('click',(e)=>{const menu=document.getElementById('adminMenu');if(menu&&!e.target.closest('#adminMenu')&&!e.target.closest('button[onclick*="adminMenu"]')){menu.style.display='none';}});
function shareInvite(type){const link=inviteState[type].link;if(!link)return;const text='Join this album';if(navigator.share){navigator.share({title:'Album invite',text,url:link}).catch(()=>{});}else{window.open('https://wa.me/?text='+encodeURIComponent(link),'_blank');}}
function downloadQr(){const qr=document.getElementById('inviteQr');if(!qr.src)return;const a=document.createElement('a');a.href=qr.src;a.download='invite-qr.png';a.click();}
</script>
@endpush
@endsection
