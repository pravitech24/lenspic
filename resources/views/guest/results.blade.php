<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Your Photos – {{ $group->name }}</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Plus+Jakarta+Sans:wght@700;800&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0;}body{font-family:'Inter',sans-serif;background:#f8fafc;color:#0f172a;}
.wrap{max-width:900px;margin:0 auto;padding:2rem 1rem;}
.hero{text-align:center;padding:2rem;background:linear-gradient(135deg,#ecfdf5,#f0fdf4);border-radius:16px;margin-bottom:2rem;}
.photo-grid{column-count:4;column-gap:8px;}
.photo-card{display:inline-block;width:100%;margin:0 0 8px;overflow:hidden;cursor:pointer;break-inside:avoid;-webkit-column-break-inside:avoid;page-break-inside:avoid;}
.photo-card img{width:100%;height:auto;display:block;}
.btn{display:inline-flex;align-items:center;gap:6px;padding:.55rem 1.1rem;border-radius:8px;font-weight:600;font-size:14px;text-decoration:none;border:none;cursor:pointer;}
.btn-primary{background:#6366f1;color:#fff;}.btn-outline{background:transparent;border:1.5px solid #e2e8f0;color:#374151;}
.lb{display:none;position:fixed;inset:0;background:rgba(0,0,0,.95);z-index:999;align-items:center;justify-content:center;padding:1rem;}
.lb.open{display:flex;}
.lb-shell{width:min(1100px,100%);display:flex;align-items:center;justify-content:center;gap:1rem;position:relative;}
.lb img{max-height:82vh;max-width:min(92vw,900px);border-radius:0;object-fit:contain;box-shadow:0 20px 80px rgba(0,0,0,.35);}
.lb-nav{width:44px;height:44px;border:none;border-radius:50%;background:rgba(255,255,255,.12);color:#fff;cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0;backdrop-filter:blur(8px);}
.lb-nav:hover{background:rgba(255,255,255,.2);}
.lb-close{position:absolute;top:-.25rem;right:-.25rem;background:rgba(255,255,255,.12);border:none;color:#fff;width:40px;height:40px;border-radius:50%;font-size:18px;cursor:pointer;display:flex;align-items:center;justify-content:center;backdrop-filter:blur(8px);}
.lb-counter{display:inline-flex;align-items:center;gap:6px;padding:.35rem .7rem;border-radius:999px;background:rgba(255,255,255,.12);backdrop-filter:blur(8px);color:#e5e7eb;font-size:13px;}
@media(max-width:1100px){.photo-grid{column-count:3;}}
@media(max-width:768px){.photo-grid{column-count:2;}}
@media(max-width:480px){.photo-grid{column-count:1;}}
</style>
</head>
<body>
<div class="wrap">
  <div class="hero">
    <div style="font-size:3rem;margin-bottom:.75rem;">🎉</div>
    <h1 style="font-family:'Plus Jakarta Sans',sans-serif;font-size:1.4rem;font-weight:800;margin-bottom:.5rem;">Found {{ $photos->count() }} Photos of You!</h1>
    <p style="color:#64748b;margin-bottom:1.25rem;">Hello <strong>{{ $name }}</strong>! Here are your photos from <strong>{{ $group->name }}</strong>.</p>
    <div style="display:flex;gap:.75rem;justify-content:center;flex-wrap:wrap;">
      <a href="{{ route('register') }}" class="btn btn-primary">Save All to LensPic</a>
      <a href="{{ route('guest.group',$group) }}" class="btn btn-outline">View All Photos</a>
    </div>
  </div>
  @if($photos->isEmpty())
  <div style="text-align:center;padding:3rem;color:#94a3b8;"><div style="font-size:3rem;margin-bottom:1rem;">🔍</div><p>No photos found yet. Photos may still be uploading — check back soon!</p></div>
  @else
  <div class="photo-grid">@foreach($photos as $p)<div class="photo-card" onclick="openCarousel({{ $loop->index }})"><img src="{{ $p->thumbnail_url }}" loading="lazy"></div>@endforeach</div>
  @endif
  <div style="text-align:center;margin-top:2rem;color:#64748b;font-size:13.5px;">
    <p>Want to keep your photos forever? <a href="{{ route('register') }}" style="color:#6366f1;font-weight:600;">Create a LensPic account</a></p>
  </div>
</div>
<div class="lb" id="lb">
  <div class="lb-shell">
    <button class="lb-nav" onclick="moveCarousel(-1)" aria-label="Previous photo"><i class="fa-solid fa-chevron-left"></i></button>
    <div style="position:relative;display:flex;align-items:center;justify-content:center;max-width:100%;">
      <img id="lbImg" src="" alt="">
      <button class="lb-close" onclick="closeCarousel()" aria-label="Close">✕</button>
      <div style="position:absolute;left:0;right:0;bottom:-2.2rem;text-align:center;">
        <span class="lb-counter" id="lbCount"></span>
      </div>
    </div>
    <button class="lb-nav" onclick="moveCarousel(1)" aria-label="Next photo"><i class="fa-solid fa-chevron-right"></i></button>
  </div>
</div>
<script>
const galleryPhotos=@js($photos->map(fn($p)=>['url'=>$p->url,'thumb'=>$p->thumbnail_url])->values());
let carouselIndex=0;
function renderCarousel(){const item=galleryPhotos[carouselIndex];if(!item)return;document.getElementById('lbImg').src=item.url;document.getElementById('lbCount').textContent=(carouselIndex+1)+' / '+galleryPhotos.length;}
function openCarousel(index){carouselIndex=index;renderCarousel();document.getElementById('lb').classList.add('open');}
function moveCarousel(step){if(!galleryPhotos.length)return;carouselIndex=(carouselIndex+step+galleryPhotos.length)%galleryPhotos.length;renderCarousel();}
function closeCarousel(){document.getElementById('lb').classList.remove('open');}
document.addEventListener('keydown',e=>{if(e.key==='Escape')closeCarousel();if(e.key==='ArrowLeft')moveCarousel(-1);if(e.key==='ArrowRight')moveCarousel(1);});
</script>
</body>
</html>
