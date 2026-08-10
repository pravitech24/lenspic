@extends('layouts.app')
@section('title','Find My Photos')
@section('content')
<div class="page-wrap" style="max-width:100%;">
  <div class="page-header"><h1 class="page-title">🤖 Find My Photos</h1><a href="{{ route('groups.show',$group) }}" class="btn btn-outline btn-sm">← Back</a></div>
  <div class="card"><div class="card-body" style="text-align:center;padding:2rem;">
    <div style="font-size:3rem;margin-bottom:1rem;">🤳</div>
    <h2 style="font-weight:700;margin-bottom:.5rem;">Upload Your Selfie</h2>
    <p style="color:#64748b;font-size:14px;margin-bottom:1.5rem;">AI scans all group photos and finds every photo you appear in.</p>
    @if($selfie)
    <div style="margin-bottom:1.5rem;"><p style="font-size:13px;color:#10b981;font-weight:600;margin-bottom:.5rem;">✅ Selfie on file</p>
    <img src="{{ asset('storage/'.$selfie) }}" style="width:90px;height:90px;border-radius:50%;object-fit:cover;border:3px solid #10b981;"></div>
    @endif

    @if($group->face_recognition_enabled && auth()->user()->canAccessFeature('face_recognition'))
    <form action="{{ route('face.selfie',$group) }}" method="POST" enctype="multipart/form-data" id="sf">
      @csrf
      <div id="sprev" style="display:none;margin-bottom:1rem;"><img id="simg" style="width:140px;height:140px;border-radius:50%;object-fit:cover;border:4px solid #6366f1;"></div>
      <div style="display:flex;flex-direction:column;gap:.75rem;align-items:center;">
        <button type="button" onclick="startCam()" class="btn btn-primary btn-lg" id="camBtn"><i class="fa-solid fa-camera"></i> Take Selfie</button>
        <span style="color:#94a3b8;font-size:13px;">or</span>
        <button type="button" onclick="document.getElementById('sinp').click()" class="btn btn-outline"><i class="fa-solid fa-upload"></i> Upload Photo</button>
        <input type="file" name="selfie" id="sinp" accept="image/*" capture="user" hidden onchange="prevSelfie(this)">
      </div>
      <div id="camView" style="display:none;margin-top:1.5rem;">
        <video id="vid" autoplay playsinline style="width:220px;height:220px;object-fit:cover;border-radius:50%;border:4px solid #6366f1;"></video>
        <canvas id="cvs" style="display:none;"></canvas>
        <div style="display:flex;gap:.75rem;justify-content:center;margin-top:1rem;">
          <button type="button" onclick="capture()" class="btn btn-primary">📸 Capture</button>
          <button type="button" onclick="stopCam()" class="btn btn-outline">Cancel</button>
        </div>
      </div>
      <div id="subBtn" style="display:none;margin-top:1.5rem;">
        <button type="submit" class="btn btn-primary btn-lg"><i class="fa-solid fa-magnifying-glass"></i> Find My Photos</button>
      </div>
    </form>
    @else
    <div style="background:linear-gradient(135deg,#0f172a,#4338ca);border-radius:12px;padding:1rem 1.25rem;margin-bottom:1rem;color:#fff;display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;">
      <div style="flex:1;min-width:0;">
        <div style="font-size:12px;font-weight:700;letter-spacing:.16em;text-transform:uppercase;opacity:.9;">Face Recognition Disabled</div>
        <div style="font-size:14px;margin-top:.2rem;">Face recognition is not available on your current plan. Upgrade to enable AI-powered photo search for this group.</div>
      </div>
      <div style="display:flex;gap:.5rem;align-items:center;flex-shrink:0;">
        <a href="{{ route('pricing') }}" class="btn btn-white">Upgrade</a>
        <a href="{{ route('groups.show',$group) }}" class="btn btn-outline">Back</a>
      </div>
    </div>
    @endif
  </div></div>
  @if($selfie)
  <div class="card" style="margin-top:1.5rem;"><div class="card-body" style="text-align:center;">
    <button onclick="findPhotos()" class="btn btn-primary btn-lg" id="findBtn"><i class="fa-solid fa-wand-magic-sparkles"></i> Search Photos Now</button>
    <div id="results" style="display:none;margin-top:1.5rem;"></div>
  </div></div>
  @endif
</div>
<div id="photoViewer" style="position:fixed;inset:0;background:#000;z-index:400;display:flex;align-items:center;justify-content:center;padding:1rem;opacity:0;visibility:hidden;pointer-events:none;transition:opacity .24s ease;">
  <div class="viewer-shell" style="width:100%;min-width:100%;display:flex;align-items:center;justify-content:center;position:relative;max-height:calc(100vh - 2rem);transform:scale(.98);transition:transform .24s ease;">
    <button type="button" onclick="moveCarousel(-1)" aria-label="Previous photo" style="position:absolute;left:-.35rem;top:50%;transform:translate(-50%,-50%);width:56px;height:56px;border:none;border-radius:50%;background:rgba(255,255,255,.12);color:#fff;cursor:pointer;display:flex;align-items:center;justify-content:center;backdrop-filter:blur(8px);z-index:5;"><i class="fa-solid fa-chevron-left"></i></button>
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
    <button type="button" onclick="moveCarousel(1)" aria-label="Next photo" style="position:absolute;right:-.35rem;top:50%;transform:translate(50%,-50%);width:56px;height:56px;border:none;border-radius:50%;background:rgba(255,255,255,.12);color:#fff;cursor:pointer;display:flex;align-items:center;justify-content:center;backdrop-filter:blur(8px);z-index:5;"><i class="fa-solid fa-chevron-right"></i></button>
  </div>
</div>
@push('scripts')
<script>
let stream=null;
let galleryPhotos=[];
let curId=null,carouselIndex=0,zoomLevel=1,panX=0,panY=0,isDragging=false,dragStartX=0,dragStartY=0;
function prevSelfie(i){if(i.files[0]){const r=new FileReader();r.onload=e=>{document.getElementById('simg').src=e.target.result;document.getElementById('sprev').style.display='block';document.getElementById('subBtn').style.display='block';};r.readAsDataURL(i.files[0]);}}
async function startCam(){try{stream=await navigator.mediaDevices.getUserMedia({video:{facingMode:'user'}});document.getElementById('vid').srcObject=stream;document.getElementById('camView').style.display='block';document.getElementById('camBtn').style.display='none';}catch(e){toast('Camera unavailable. Please upload a photo.');}}
function capture(){const v=document.getElementById('vid'),c=document.getElementById('cvs');c.width=400;c.height=400;c.getContext('2d').drawImage(v,0,0,400,400);c.toBlob(b=>{const f=new File([b],'selfie.jpg',{type:'image/jpeg'});const dt=new DataTransfer();dt.items.add(f);document.getElementById('sinp').files=dt.files;document.getElementById('simg').src=c.toDataURL();document.getElementById('sprev').style.display='block';document.getElementById('subBtn').style.display='block';stopCam();},'image/jpeg',.9);}
function stopCam(){if(stream){stream.getTracks().forEach(t=>t.stop());stream=null;}document.getElementById('camView').style.display='none';document.getElementById('camBtn').style.display='inline-flex';}
async function findPhotos(){const btn=document.getElementById('findBtn');btn.disabled=true;btn.innerHTML='<span class="spinner"></span> Searching...';const r=await post('{{ route("face.recognize",$group) }}',{});const c=document.getElementById('results');c.style.display='block';const matchedPhotos=Array.isArray(r.photos)?r.photos.slice(0,3):[];if(matchedPhotos.length){galleryPhotos = matchedPhotos.map(p=>({id:p.id,url:p.url,thumb:p.thumbnail_url,uploader:'',likes:0}));c.innerHTML = '<p style="font-weight:700;margin-bottom:.75rem;color:#10b981;">✅ Found '+galleryPhotos.length+' photos!</p><div class="photo-grid">'+galleryPhotos.map((p,i)=>'<button type="button" onclick="openCarousel('+i+')" class="photo-card" style="border:none;padding:0;background:transparent;"><img src="'+p.thumb+'" loading="lazy" style="width:100%;height:100%;object-fit:cover;"></button>').join('')+'</div>';}else{c.innerHTML='<p style="color:'+(r.message?'#dc2626':'#64748b')+';">'+(r.message||'No matches found yet. Make sure photos are uploaded.')+'</p>';}btn.disabled=false;btn.innerHTML='<i class="fa-solid fa-wand-magic-sparkles"></i> Search Again';}
function applyCarouselTransform(){const viewerImage=document.getElementById('viewerImage');if(!viewerImage)return;viewerImage.style.transform='translate('+panX+'px, '+panY+'px) scale('+zoomLevel+')';viewerImage.style.cursor=zoomLevel>1?(isDragging?'grabbing':'grab'):'zoom-in';}
function resetCarouselZoom(){zoomLevel=1;panX=0;panY=0;isDragging=false;applyCarouselTransform();}
function zoomCarousel(step){zoomLevel=Math.min(3,Math.max(1,zoomLevel+step));applyCarouselTransform();}
function renderCarousel(){const item=galleryPhotos[carouselIndex];if(!item)return;curId=item.id;const viewerImage=document.getElementById('viewerImage');const viewerMeta=document.getElementById('viewerMeta');const viewerDownload=document.getElementById('viewerDownload');if(!viewerImage||!viewerMeta||!viewerDownload)return;viewerImage.src=item.url;viewerImage.alt='Photo '+(carouselIndex+1);viewerMeta.innerHTML='<span style="display:inline-flex;align-items:center;gap:6px;padding:.4rem .7rem;border-radius:999px;background:rgba(255,255,255,.14);"><i class="fa-solid fa-image"></i> '+(carouselIndex+1)+' / '+galleryPhotos.length+'</span>'+(item.uploader?'<span style="display:inline-flex;align-items:center;gap:6px;padding:.4rem .7rem;border-radius:999px;background:rgba(255,255,255,.14);"><i class="fa-solid fa-user"></i> '+item.uploader+'</span>':'')+(item.likes?'<span style="display:inline-flex;align-items:center;gap:6px;padding:.4rem .7rem;border-radius:999px;background:rgba(255,255,255,.14);"><i class="fa-solid fa-heart"></i> '+item.likes+'</span>':'');viewerDownload.href='/groups/'+{{ $group->id }}+'/photos/'+item.id+'/download';resetCarouselZoom();}
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
</script>
@endpush
@endsection
