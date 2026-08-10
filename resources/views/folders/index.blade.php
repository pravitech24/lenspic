@extends('layouts.app')
@section('title', 'Folders - ' . $group->name)
@section('content')
<div class="page-wrap" style="max-width:100%;margin:0 auto;">
  <div style="display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;margin-bottom:2rem;">
    <div>
      <div style="font-size:12px;text-transform:uppercase;letter-spacing:.16em;color:#6366f1;font-weight:700;">Folder Organization</div>
      <h1 style="font-size:2rem;font-weight:800;color:#0f172a;">{{ $group->name }} Folders</h1>
    </div>
    <div style="display:flex;gap:.6rem;flex-wrap:wrap;">
      <a href="{{ route('groups.show', $group) }}" class="btn btn-outline btn-sm"><i class="fa-solid fa-arrow-left"></i> Back to Album</a>
      <button class="btn btn-primary btn-sm" onclick="document.getElementById('createFolderForm').style.display='block'"><i class="fa-solid fa-folder-plus"></i> New Folder</button>
    </div>
  </div>

  <div id="createFolderForm" style="display:none;" class="card" style="margin-bottom:2rem;background:linear-gradient(135deg,#f0f9ff,#f5f3ff);">
    <div class="card-body">
      <h3 style="margin:0 0 1.2rem;font-size:1.1rem;color:#0f172a;">Create New Folder</h3>
      <form action="{{ route('folders.store', $group) }}" method="POST" class="grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:1rem;">
        @csrf
        <div style="grid-column:1 / -1;">
          <label style="display:block;font-size:13px;font-weight:700;margin-bottom:.4rem;color:#475569;">Folder name *</label>
          <input name="name" required placeholder="e.g., Wedding Day, Beach Trip..." style="width:100%;padding:.8rem;border:1px solid #cbd5e1;border-radius:8px;font-size:14px;">
        </div>
        <div style="grid-column:1 / -1;">
          <label style="display:block;font-size:13px;font-weight:700;margin-bottom:.4rem;color:#475569;">Description</label>
          <textarea name="description" rows="2" placeholder="Optional description..." style="width:100%;padding:.8rem;border:1px solid #cbd5e1;border-radius:8px;font-size:14px;"></textarea>
        </div>
        <div>
          <label style="display:block;font-size:13px;font-weight:700;margin-bottom:.4rem;color:#475569;">Folder color</label>
          <div style="display:flex;gap:.5rem;align-items:center;">
            <input type="color" name="color" value="#6366f1" style="width:50px;height:50px;padding:0;border:2px solid #e2e8f0;border-radius:8px;cursor:pointer;">
            <span id="colorPreview" style="display:inline-flex;align-items:center;justify-content:center;width:50px;height:50px;border-radius:8px;background:#6366f1;color:#fff;font-size:20px;"><i class="fa-solid fa-folder"></i></span>
          </div>
        </div>
        <div>
          <label style="display:block;font-size:13px;font-weight:700;margin-bottom:.4rem;color:#475569;">Order</label>
          <input type="number" name="display_order" value="0" min="0" style="width:100%;padding:.8rem;border:1px solid #cbd5e1;border-radius:8px;">
        </div>
        <div style="display:flex;align-items:center;gap:.6rem;padding-top:.5rem;">
          <label style="display:flex;align-items:center;gap:.6rem;cursor:pointer;">
            <input type="checkbox" name="highlighted" value="1">
            <span style="font-size:13px;font-weight:600;color:#475569;">⭐ Highlight as featured</span>
          </label>
        </div>
        <div style="grid-column:1 / -1;display:flex;justify-content:flex-end;align-items:end;gap:.5rem;padding-top:.5rem;">
          <button type="button" class="btn btn-outline btn-sm" onclick="document.getElementById('createFolderForm').style.display='none'">Cancel</button>
          <button type="submit" class="btn btn-primary btn-sm">Create Folder</button>
        </div>
      </form>
    </div>
  </div>

  @if($folders->isEmpty())
  <div style="text-align:center;padding:3rem 1rem;">
    <div style="font-size:4rem;margin-bottom:1rem;">📁</div>
    <h3 style="font-size:1.25rem;font-weight:700;color:#0f172a;margin-bottom:.5rem;">No folders yet</h3>
    <p style="color:#64748b;margin-bottom:1.5rem;">Create your first folder to organize photos by theme, event, or category.</p>
    <button class="btn btn-primary" onclick="document.getElementById('createFolderForm').style.display='block'"><i class="fa-solid fa-folder-plus"></i> Create Folder</button>
  </div>
  @else
  <table style="width:100%;border-collapse:collapse;background:#fff;border:1px solid #e2e8f0;border-radius:8px;overflow:hidden;">
    <thead>
      <tr style="background:#f8fafc;border-bottom:1px solid #e2e8f0;">
        <th style="padding:1rem;text-align:left;font-weight:700;color:#475569;font-size:13px;text-transform:uppercase;letter-spacing:.05em;">Folder</th>
        <th style="padding:1rem;text-align:center;font-weight:700;color:#475569;font-size:13px;text-transform:uppercase;letter-spacing:.05em;">Photos</th>
        <th style="padding:1rem;text-align:center;font-weight:700;color:#475569;font-size:13px;text-transform:uppercase;letter-spacing:.05em;">Updated</th>
        <th style="padding:1rem;text-align:right;font-weight:700;color:#475569;font-size:13px;text-transform:uppercase;letter-spacing:.05em;">Actions</th>
      </tr>
    </thead>
    <tbody>
      @foreach($folders as $folder)
      <tr style="border-bottom:1px solid #e2e8f0;transition:all .2s ease;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='#fff'">
        <td style="padding:1.2rem;">
          <div style="display:flex;align-items:center;gap:.8rem;">
            <div style="width:32px;height:32px;border-radius:8px;background:{{ $folder->color ?? '#6366f1' }};display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;">
              @if($folder->highlighted)
              <i class="fa-solid fa-star"></i>
              @else
              <i class="fa-solid fa-folder"></i>
              @endif
            </div>
            <div>
              <div style="font-weight:700;color:#0f172a;">{{ $folder->name }}</div>
              @if($folder->description)
              <div style="font-size:12px;color:#64748b;margin-top:.2rem;">{{ Str::limit($folder->description, 50) }}</div>
              @endif
            </div>
          </div>
        </td>
        <td style="padding:1.2rem;text-align:center;color:#0f172a;font-weight:600;">{{ $folder->photo_count }}</td>
        <td style="padding:1.2rem;text-align:center;color:#64748b;font-size:13px;">{{ $folder->updated_at->format('M d, Y') }}</td>
        <td style="padding:1.2rem;text-align:right;">
          <div style="display:flex;gap:.5rem;justify-content:flex-end;">
            <a href="{{ route('folders.show', [$group, $folder]) }}" class="btn btn-outline btn-sm" style="padding:.4rem .6rem;"><i class="fa-solid fa-eye"></i></a>
            <button type="button" class="btn btn-outline btn-sm" onclick="editFolderModal({{ $folder->id }}, '{{ $folder->name }}', '{{ $folder->description }}', '{{ $folder->color }}')" style="padding:.4rem .6rem;"><i class="fa-solid fa-pen"></i></button>
            <form action="{{ route('folders.destroy', [$group, $folder]) }}" method="POST" onsubmit="return confirm('Delete this folder?')" style="display:inline;">@csrf @method('DELETE')<button class="btn btn-outline btn-sm" style="padding:.4rem .6rem;"><i class="fa-solid fa-trash"></i></button></form>
          </div>
        </td>
      </tr>
      @endforeach
    </tbody>
  </table>
  @endif
</div>

<div id="editFolderModal" style="position:fixed;inset:0;background:rgba(2,6,23,.7);display:none;align-items:center;justify-content:center;padding:1rem;z-index:500;">
  <div style="width:min(100%, 500px);background:#fff;border-radius:12px;box-shadow:0 25px 50px -12px rgba(0,0,0,.15);overflow:hidden;">
    <div style="display:flex;align-items:center;justify-content:space-between;padding:1.5rem;border-bottom:1px solid #e2e8f0;">
      <div style="font-weight:800;color:#0f172a;font-size:1.1rem;">Edit Folder</div>
      <button type="button" onclick="closeEditFolderModal()" style="width:36px;height:36px;border:none;border-radius:8px;background:#f8fafc;color:#64748b;cursor:pointer;font-size:18px;display:flex;align-items:center;justify-content:center;"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <form id="editFolderForm" method="POST" style="padding:1.5rem;display:grid;gap:1.2rem;">
      @csrf @method('PUT')
      <div>
        <label style="display:block;font-size:13px;font-weight:700;margin-bottom:.5rem;color:#475569;">Folder name</label>
        <input id="editFolderName" name="name" required style="width:100%;padding:.8rem;border:1px solid #cbd5e1;border-radius:8px;font-size:14px;">
      </div>
      <div>
        <label style="display:block;font-size:13px;font-weight:700;margin-bottom:.5rem;color:#475569;">Description</label>
        <textarea id="editFolderDesc" name="description" rows="2" style="width:100%;padding:.8rem;border:1px solid #cbd5e1;border-radius:8px;font-size:14px;"></textarea>
      </div>
      <div>
        <label style="display:block;font-size:13px;font-weight:700;margin-bottom:.5rem;color:#475569;">Folder color</label>
        <div style="display:flex;gap:.5rem;align-items:center;">
          <input id="editFolderColor" type="color" name="color" style="width:50px;height:50px;padding:0;border:2px solid #e2e8f0;border-radius:8px;cursor:pointer;">
          <span id="editColorPreview" style="display:inline-flex;align-items:center;justify-content:center;width:50px;height:50px;border-radius:8px;background:#6366f1;color:#fff;font-size:20px;"><i class="fa-solid fa-folder"></i></span>
        </div>
      </div>
      <div style="display:flex;gap:.5rem;justify-content:flex-end;padding-top:.5rem;">
        <button type="button" class="btn btn-outline btn-sm" onclick="closeEditFolderModal()">Cancel</button>
        <button type="submit" class="btn btn-primary btn-sm">Save Changes</button>
      </div>
    </form>
  </div>
</div>

<style>
</style>

<script>
document.querySelector('input[name="color"]')?.addEventListener('change', function() {
  document.getElementById('colorPreview').style.background = this.value;
});
document.getElementById('editFolderColor')?.addEventListener('change', function() {
  document.getElementById('editColorPreview').style.background = this.value;
});
function editFolderModal(id, name, desc, color) {
  document.getElementById('editFolderName').value = name;
  document.getElementById('editFolderDesc').value = desc;
  document.getElementById('editFolderColor').value = color;
  document.getElementById('editColorPreview').style.background = color;
  const form = document.getElementById('editFolderForm');
  form.action = '/groups/{{ $group->id }}/folders/' + id;
  document.getElementById('editFolderModal').style.display = 'flex';
}
function closeEditFolderModal() {
  document.getElementById('editFolderModal').style.display = 'none';
}
</script>
@endsection
