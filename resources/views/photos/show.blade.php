@extends('layouts.app')
@section('title','Photo')
@section('content')
<div class="page-wrap" style="max-width:100%;padding-top:1.5rem;">
  <div style="display:grid;grid-template-columns:1fr 320px;gap:1.5rem;" class="pdetail">
    <div><img src="{{ $photo->url }}" style="width:100%;border-radius:12px;object-fit:contain;background:#000;max-height:70vh;"></div>
    <div style="display:flex;flex-direction:column;gap:1rem;">
      <div class="card"><div class="card-body" style="display:flex;gap:.5rem;flex-wrap:wrap;">
        <button onclick="toggleLike()" class="btn btn-outline btn-sm" id="likeBtn" style="{{ $isLiked?'color:#ec4899;border-color:#ec4899;':'' }}">
          <i class="fa-{{ $isLiked?'solid':'regular' }} fa-heart"></i> <span id="lc">{{ $photo->likes_count }}</span>
        </button>
        <a href="{{ route('photos.download',[$group,$photo]) }}" class="btn btn-primary btn-sm"><i class="fa-solid fa-download"></i> Download</a>
        @if($isAdmin || $photo->uploader_id === auth()->id())
        <form action="{{ route('photos.destroy',[$group,$photo]) }}" method="POST" onsubmit="return confirm('Delete?')">@csrf @method('DELETE')<button class="btn btn-danger btn-sm"><i class="fa-solid fa-trash"></i></button></form>
        @endif
      </div></div>
      <div class="card"><div class="card-header"><strong>Details</strong></div>
        <div class="card-body" style="font-size:13px;">
          <table style="width:100%;border-collapse:collapse;">
            @foreach([['Uploaded by',$photo->uploader?->name??'Guest'],['File',$photo->original_filename],['Size',$photo->file_size_human],['Dimensions',$photo->width&&$photo->height?$photo->width.'×'.$photo->height:'N/A'],['Uploaded',$photo->created_at->format('d M Y, H:i')],['Views',$photo->views_count],['Downloads',$photo->downloads_count]] as [$l,$v])
            <tr><td style="padding:5px 0;color:#64748b;width:45%;">{{ $l }}</td><td style="padding:5px 0;font-weight:500;word-break:break-all;">{{ $v }}</td></tr>
            @endforeach
          </table>
        </div>
      </div>
      <div class="card"><div class="card-header"><strong>Folder</strong></div>
        <div class="card-body" style="display:flex;flex-direction:column;gap:.5rem;">
          <p style="font-size:13px;color:#64748b;margin:0;">Assign this photo to a folder for better organization.</p>
          <select id="folderSelect" style="width:100%;padding:.7rem .8rem;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;">
            <option value="">No folder</option>
            @foreach($group->folders as $f)
            <option value="{{ $f->id }}" {{ $photo->folder_id === $f->id ? 'selected' : '' }}>{{ $f->name }}</option>
            @endforeach
          </select>
          <button onclick="assignFolder()" class="btn btn-primary btn-sm">Save</button>
        </div>
      </div>
      <div class="card" style="flex:1;"><div class="card-header"><strong>Comments ({{ $photo->comments->count() }})</strong></div>
        <div class="card-body" style="padding:0;">
          <div style="max-height:220px;overflow-y:auto;padding:.75rem 1rem;">
            @forelse($photo->comments as $c)
            <div style="display:flex;gap:8px;margin-bottom:.85rem;"><img src="{{ $c->user->profile_photo_url }}" style="width:28px;height:28px;border-radius:50%;flex-shrink:0;">
              <div><span style="font-weight:600;font-size:12.5px;">{{ $c->user->name }}</span><span style="font-size:11px;color:#94a3b8;margin-left:4px;">{{ $c->created_at->diffForHumans() }}</span><p style="font-size:13px;margin-top:2px;">{{ $c->body }}</p></div>
            </div>
            @empty<p style="color:#94a3b8;font-size:13px;text-align:center;padding:1rem 0;">No comments yet.</p>@endforelse
          </div>
          <div style="padding:.75rem 1rem;border-top:1px solid #f1f5f9;"><div style="display:flex;gap:.5rem;"><input type="text" id="cmt" placeholder="Add a comment..." style="flex:1;font-size:13px;padding:.4rem .75rem;"><button onclick="addComment()" class="btn btn-primary btn-sm">Post</button></div></div>
        </div>
      </div>
      <a href="{{ route('groups.show',$group) }}" class="btn btn-outline btn-sm">← Back to Group</a>
    </div>
  </div>
</div>
@push('styles')<style>@media(max-width:768px){.pdetail{grid-template-columns:1fr!important;}}</style>@endpush
@push('scripts')
<script>
const gid={{ $group->id }},pid={{ $photo->id }};
async function toggleLike(){const r=await post('/groups/'+gid+'/photos/'+pid+'/like',{});const btn=document.getElementById('likeBtn');const i=btn.querySelector('i');i.className=r.liked?'fa-solid fa-heart':'fa-regular fa-heart';btn.style.cssText=r.liked?'color:#ec4899;border-color:#ec4899;':'';document.getElementById('lc').textContent=r.count;}
async function assignFolder(){const folderId=document.getElementById('folderSelect').value;const r=await post('/groups/'+gid+'/photos/'+pid+'/folder',{folder_id:folderId||null});if(r.assigned){toast('Photo folder updated!');}}
function addComment(){const v=document.getElementById('cmt').value.trim();if(!v)return;toast('Comment posted!');document.getElementById('cmt').value='';}
</script>
@endpush
@endsection
