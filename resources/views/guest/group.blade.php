<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>{{ $group->name }} – LensPic</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Plus+Jakarta+Sans:wght@700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
*{box-sizing:border-box;margin:0;padding:0;}body{font-family:'Inter',sans-serif;background:#f8fafc;color:#0f172a;}
.topbar{background:#fff;border-bottom:1px solid #e2e8f0;padding:.85rem 1.5rem;display:flex;align-items:center;justify-content:space-between;}
.logo{font-family:'Plus Jakarta Sans',sans-serif;font-weight:800;font-size:1.2rem;color:#6366f1;text-decoration:none;display:flex;align-items:center;gap:8px;}
.logo-icon{width:30px;height:30px;background:linear-gradient(135deg,#6366f1,#ec4899);border-radius:8px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:14px;}
.btn{display:inline-flex;align-items:center;gap:6px;padding:.5rem 1rem;border-radius:8px;font-weight:600;font-size:13.5px;text-decoration:none;border:none;cursor:pointer;transition:all .15s;}
.btn-primary{background:#6366f1;color:#fff;}.btn-primary:hover{background:#4f46e5;}
.btn-outline{background:transparent;border:1.5px solid #e2e8f0;color:#374151;}
.hero{height:180px;background:linear-gradient(135deg,#667eea,#764ba2);position:relative;overflow:hidden;}
.hero img{width:100%;height:100%;object-fit:cover;}
.hero-overlay{position:absolute;inset:0;background:linear-gradient(to bottom,transparent 30%,rgba(0,0,0,.65));}
.hero-info{position:absolute;bottom:1rem;left:1.5rem;color:#fff;}
.hero-info h1{font-family:'Plus Jakarta Sans',sans-serif;font-size:1.4rem;font-weight:800;}
.wrap{max-width:1060px;margin:0 auto;padding:1.5rem;}
.photo-grid{column-count:4;column-gap:12px;max-width:100%;}
.photo-card{display:inline-block;width:100%;margin:0 0 12px;overflow:hidden;cursor:pointer;background:#f1f5f9;border-radius:16px;box-shadow:0 2px 12px rgba(15,23,42,.08);break-inside:avoid;-webkit-column-break-inside:avoid;page-break-inside:avoid;transition:transform .22s cubic-bezier(.2,.8,.2,1),box-shadow .22s ease;}
.photo-card img{width:100%;height:auto;display:block;transition:transform .22s cubic-bezier(.2,.8,.2,1),filter .22s ease;object-fit:cover;}
.photo-card:hover{transform:translateY(-2px) scale(1.01);box-shadow:0 12px 28px rgba(15,23,42,.16);} 
.photo-card:hover img{transform:scale(1.03);filter:saturate(1.03);} 
.tab-bar{display:flex;gap:4px;background:#f1f5f9;border-radius:10px;padding:4px;margin-bottom:1.5rem;}
.tab{flex:1;padding:.5rem;border-radius:7px;text-align:center;font-size:13.5px;font-weight:600;cursor:pointer;color:#64748b;border:none;background:none;}
.tab.active{background:#fff;color:#6366f1;box-shadow:0 1px 3px rgba(0,0,0,.1);}
.panel{background:#fff;border-radius:12px;box-shadow:0 1px 3px rgba(0,0,0,.08);padding:1.5rem;margin-bottom:1.5rem;text-align:center;}
.form-group{margin-bottom:.85rem;text-align:left;}
label{display:block;font-weight:500;font-size:13px;color:#64748b;margin-bottom:.3rem;}
input[type=text],input[type=tel],input[type=file]{width:100%;padding:.6rem .9rem;border:1.5px solid #e2e8f0;border-radius:8px;font-size:14px;outline:none;font-family:inherit;}
input:focus{border-color:#6366f1;box-shadow:0 0 0 3px rgba(99,102,241,.1);}
.lb{display:none;position:fixed;inset:0;background:rgba(0,0,0,.95);z-index:999;align-items:center;justify-content:center;padding:1rem;}
.lb.open{display:flex;}
.lb-shell{width:min(1100px,100%);display:flex;align-items:center;justify-content:center;gap:1rem;position:relative;}
.lb img{max-height:82vh;max-width:min(92vw,900px);border-radius:0;object-fit:contain;box-shadow:0 20px 80px rgba(0,0,0,.35);}
.lb-nav{width:44px;height:44px;border:none;border-radius:50%;background:rgba(255,255,255,.12);color:#fff;cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0;backdrop-filter:blur(8px);}
.lb-nav:hover{background:rgba(255,255,255,.2);}
.lb-meta{position:absolute;left:0;right:0;bottom:-2.2rem;text-align:center;color:#e5e7eb;font-size:13px;}
.lb-close{position:absolute;top:-.25rem;right:-.25rem;background:rgba(255,255,255,.12);border:none;color:#fff;width:40px;height:40px;border-radius:50%;font-size:18px;cursor:pointer;display:flex;align-items:center;justify-content:center;backdrop-filter:blur(8px);}
.lb-counter{display:inline-flex;align-items:center;gap:6px;padding:.35rem .7rem;border-radius:999px;background:rgba(255,255,255,.12);backdrop-filter:blur(8px);}
@media(max-width:1100px){.photo-grid{column-count:3;}}
@media(max-width:768px){.photo-grid{column-count:2;}}
@media(max-width:480px){.photo-grid{column-count:1;}}
</style>
</head>
<body>
<div class="topbar">
  <a href="/" class="logo">
    @if(!empty($branding['logo_url']))
      <img src="{{ $branding['logo_url'] }}" alt="{{ $branding['business_name'] ?? 'Business' }} logo" style="height:32px;max-width:120px;object-fit:contain">
    @else
      <div class="logo-icon">⚡</div>LensPic
    @endif
  </a>
  <a href="{{ route('register') }}" class="btn btn-primary" style="font-size:13px;">Create Account</a>
</div>
<div style="position:absolute;right:1.5rem;top:84px;z-index:4"><a href="{{ route('guest.flipbook',$group) }}" class="btn btn-primary" style="font-size:13px">Open Digital Flipbook</a></div>
<div class="hero">
  @if($group->cover_photo)<img src="{{ asset('storage/'.$group->cover_photo) }}">@endif
  <div class="hero-overlay"></div>
  <div class="hero-info">
    <div style="font-size:12px;opacity:.8;margin-bottom:2px;">{{ $group->getEventTypeLabel() }}</div>
    <h1>{{ $group->name }}</h1>
    @if($group->event_date)<div style="font-size:12.5px;opacity:.85;margin-top:2px;"><i class="fa-regular fa-calendar"></i> {{ $group->event_date->format('d M Y') }}</div>@endif
  </div>
</div>
<div class="wrap">
  @if(!empty($branding['phone']) || !empty($branding['email']) || !empty($branding['website']) || !empty($branding['instagram_url']) || !empty($branding['facebook_url']))
  <aside style="display:flex;gap:.9rem;align-items:center;flex-wrap:wrap;margin-bottom:1rem;padding:.8rem 1rem;border:1px solid #e2e8f0;border-radius:12px;background:#fff;font-size:13px;">
    @if(!empty($branding['business_name']))<strong>{{ $branding['business_name'] }}</strong>@endif
    @if(!empty($branding['phone']))<a href="tel:{{ preg_replace('/\s+/', '', $branding['phone']) }}">{{ $branding['phone'] }}</a>@endif
    @if(!empty($branding['email']))<a href="mailto:{{ $branding['email'] }}">{{ $branding['email'] }}</a>@endif
    @if(!empty($branding['website']))<a href="{{ $branding['website'] }}" target="_blank" rel="noopener">Website</a>@endif
    @if(!empty($branding['instagram_url']))<a href="{{ $branding['instagram_url'] }}" target="_blank" rel="noopener">Instagram</a>@endif
    @if(!empty($branding['facebook_url']))<a href="{{ $branding['facebook_url'] }}" target="_blank" rel="noopener">Facebook</a>@endif
  </aside>
  @endif
  @if ($errors->any() || session('error'))
  <div style="margin-bottom:1rem;padding:1rem;border-radius:12px;background:#fef2f2;border:1px solid #fecaca;color:#991b1b;font-size:14px;">
    {{ session('error') ?: $errors->first() }}
  </div>
  @endif
  <div style="display:flex;gap:1.5rem;margin-bottom:1.25rem;flex-wrap:wrap;">
    <span style="font-size:13px;color:#64748b;"><i class="fa-solid fa-camera" style="color:#6366f1;"></i> {{ $photos->total() }} photos</span>
    <span style="font-size:13px;color:#64748b;"><i class="fa-solid fa-users" style="color:#6366f1;"></i> {{ $group->members_count }} members</span>
  </div>
  <div class="tab-bar">
    <button class="tab active" onclick="showTab('photos',this)">All Photos</button>
    <button class="tab" onclick="showTab('selfie',this)">🤳 Find My Photos</button>
    @if($group->allow_guest_upload)<button class="tab" onclick="showTab('upload',this)">⬆️ Upload</button>@endif
  </div>

  <div id="tab-photos">
    @if($photos->isEmpty())
    <div style="text-align:center;padding:4rem;color:#94a3b8;"><div style="font-size:3rem;margin-bottom:1rem;">📷</div><p>No photos yet. Check back soon!</p></div>
    @else
    <div class="photo-grid">
      @foreach($photos as $p)<div class="photo-card" onclick="openCarousel({{ $loop->index }})"><img src="{{ $p->thumbnail_url }}" loading="lazy"></div>@endforeach
    </div>
    <div style="margin-top:1rem;display:flex;justify-content:center;">{{ $photos->links() }}</div>
    @endif
  </div>

  <div id="tab-selfie" style="display:none;">
    <div class="panel">
      <div style="font-size:2.5rem;margin-bottom:.75rem;">🤖</div>
      <h2 style="font-weight:700;margin-bottom:.5rem;">Find Photos with You</h2>
      <p style="color:#64748b;font-size:14px;margin-bottom:1.5rem;">Join this Group with your invitation, then choose Find My Photos to review consent before providing a selfie.</p>
      <a href="{{ route('biometric.entry', $group) }}" class="btn btn-primary">Find My Photos</a>

    </div>
  </div>

  @if($group->allow_guest_upload)
  <div id="tab-upload" style="display:none;">
    <div class="panel">
      <div style="font-size:2.5rem;margin-bottom:.75rem;">⬆️</div>
      <h2 style="font-weight:700;margin-bottom:.5rem;">Upload Photos</h2>
      <p style="color:#64748b;font-size:14px;margin-bottom:1.5rem;">Share your photos with the group. No account needed!</p>
      <form action="{{ route('photos.store',['group'=>$group->id]) }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div style="max-width:380px;margin:0 auto;">
          <div class="form-group"><label>Your Name</label><input type="text" name="uploader_name" placeholder="Rahul Kumar" required></div>
          <div style="border:2px dashed #e2e8f0;border-radius:12px;padding:2rem;cursor:pointer;text-align:center;margin-bottom:1rem;" onclick="document.getElementById('gp').click();">
            <div style="font-size:2.5rem;margin-bottom:.5rem;">🖼️</div>
            <p style="font-size:13px;color:#64748b;">Tap to select photos</p>
            <div id="gpc" style="margin-top:.5rem;font-weight:600;color:#6366f1;"></div>
          </div>
          <input type="file" name="photos[]" id="gp" accept="image/*" multiple hidden onchange="document.getElementById('gpc').textContent=this.files.length+' file(s) selected'">
          <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;padding:.7rem;"><i class="fa-solid fa-upload"></i> Upload Photos</button>
        </div>
      </form>
    </div>
  </div>
  @endif
</div>

<div class="lb" id="lb" style="position:fixed;inset:0;background:#000;z-index:999;display:flex;align-items:center;justify-content:center;padding:1rem;opacity:0;visibility:hidden;pointer-events:none;transition:opacity .24s ease;">
  <div class="viewer-shell" style="width:min(100%, 1600px);display:flex;align-items:center;justify-content:center;gap:1.25rem;position:relative;max-height:calc(100vh - 2rem);transform:scale(.98);transition:transform .24s ease;">
    <button class="lb-nav" onclick="moveCarousel(-1)" aria-label="Previous photo" style="width:46px;height:46px;border-radius:50%;background:rgba(255,255,255,.12);backdrop-filter:blur(8px);flex-shrink:0;margin-right:1.25rem;"><i class="fa-solid fa-chevron-left"></i></button>
    <div style="position:relative;width:min(100%,1200px);display:flex;align-items:center;justify-content:center;">
      <div style="position:absolute;top:1rem;right:1rem;display:flex;gap:.5rem;z-index:3;">
        <a id="viewerDownload" href="#" class="btn btn-sm" style="background:rgba(255,255,255,.12);color:#fff;border:1px solid rgba(255,255,255,.2);backdrop-filter:blur(8px);"><i class="fa-solid fa-download"></i></a>
        <button class="lb-close" onclick="closeCarousel()" aria-label="Close" style="background:rgba(255,255,255,.12);border:none;color:#fff;width:40px;height:40px;border-radius:50%;font-size:18px;cursor:pointer;display:flex;align-items:center;justify-content:center;backdrop-filter:blur(8px);">✕</button>
      </div>
      <div style="display:flex;justify-content:center;align-items:center;min-height:calc(100vh - 120px);padding:0 0 2.5rem;">
        <img id="lbImg" src="" alt="" style="display:block;max-width:calc(100vw - 90px);max-height:calc(100vh - 110px);width:auto;height:auto;object-fit:contain;">
      </div>
      <div style="position:absolute;left:50%;transform:translateX(-50%);bottom:0.4rem;text-align:center;color:#f8fafc;font-size:13px;z-index:3;">
        <div id="viewerMeta" style="display:inline-flex;align-items:center;gap:.5rem;flex-wrap:wrap;background:rgba(0,0,0,.35);padding:.4rem .7rem;border-radius:999px;backdrop-filter:blur(6px);"></div>
      </div>
    </div>
    <button class="lb-nav" onclick="moveCarousel(1)" aria-label="Next photo" style="width:46px;height:46px;border-radius:50%;background:rgba(255,255,255,.12);backdrop-filter:blur(8px);flex-shrink:0;margin-left:1.25rem;"><i class="fa-solid fa-chevron-right"></i></button>
  </div>
</div>

<script>
const csrf=document.querySelector('meta[name=csrf-token]').content;
const galleryPhotos=@js($photos->map(fn($p)=>['id'=>$p->id,'url'=>$p->url,'thumb'=>$p->thumbnail_url])->values());
let carouselIndex=0;
function showTab(n,btn){document.querySelectorAll('[id^=tab-]').forEach(t=>t.style.display='none');document.getElementById('tab-'+n).style.display='block';document.querySelectorAll('.tab').forEach(t=>t.classList.remove('active'));btn.classList.add('active');}
function showPrev(i){if(i.files[0]){const r=new FileReader();r.onload=e=>{document.getElementById('sp').src=e.target.result;document.getElementById('sp').style.display='block';document.getElementById('sph').style.display='none';};r.readAsDataURL(i.files[0]);}}
function renderCarousel(){const item=galleryPhotos[carouselIndex];if(!item)return;document.getElementById('lbImg').src=item.url;document.getElementById('lbImg').alt='Photo '+(carouselIndex+1);document.getElementById('viewerMeta').innerHTML='<span style="display:inline-flex;align-items:center;gap:6px;padding:.4rem .7rem;border-radius:999px;background:rgba(255,255,255,.14);"><i class="fa-solid fa-image"></i> '+(carouselIndex+1)+' / '+galleryPhotos.length+'</span>';document.getElementById('viewerDownload').href='/groups/'+{{ $group->id }}+'/photos/'+item.id+'/download';}
function openCarousel(index){carouselIndex=index;const viewer=document.getElementById('lb');if(!viewer)return;viewer.style.opacity='1';viewer.style.visibility='visible';viewer.style.pointerEvents='auto';viewer.querySelector('.viewer-shell').style.transform='scale(1)';document.body.style.overflow='hidden';renderCarousel();}
function moveCarousel(step){if(!galleryPhotos.length)return;carouselIndex=(carouselIndex+step+galleryPhotos.length)%galleryPhotos.length;renderCarousel();}
function closeCarousel(){const viewer=document.getElementById('lb');if(!viewer)return;viewer.querySelector('.viewer-shell').style.transform='scale(.96)';viewer.style.opacity='0';viewer.style.visibility='hidden';viewer.style.pointerEvents='none';document.body.style.overflow='';}
document.addEventListener('keydown',e=>{if(e.key==='Escape')closeCarousel();if(e.key==='ArrowLeft')moveCarousel(-1);if(e.key==='ArrowRight')moveCarousel(1);});
document.getElementById('lb')?.addEventListener('click',function(e){if(e.target===this)closeCarousel();});
</script>
</body>
</html>
