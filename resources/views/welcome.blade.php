<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>LensPic – Smart Photo Sharing</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Plus+Jakarta+Sans:wght@700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
*{box-sizing:border-box;margin:0;padding:0;}
:root{--p:#6366f1;--s:#ec4899;--bg:#080814;--sur:#10101e;--border:rgba(255,255,255,.08);--muted:#94a3b8;--text:#f8fafc;}
body{font-family:'Inter',sans-serif;background:var(--bg);color:var(--text);overflow-x:hidden;}
nav{display:flex;align-items:center;justify-content:space-between;padding:1.2rem 2rem;position:sticky;top:0;z-index:50;background:rgba(8,8,20,.85);backdrop-filter:blur(16px);border-bottom:1px solid var(--border);}
.logo{display:flex;align-items:center;gap:10px;font-family:'Plus Jakarta Sans',sans-serif;font-weight:800;font-size:1.35rem;text-decoration:none;color:#fff;}
.logo-icon{width:36px;height:36px;background:linear-gradient(135deg,var(--p),var(--s));border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:18px;}
.btn{display:inline-flex;align-items:center;gap:6px;padding:.55rem 1.1rem;border-radius:10px;font-weight:600;font-size:14px;text-decoration:none;border:none;cursor:pointer;transition:all .15s;}
.btn-ghost{background:rgba(255,255,255,.07);color:#fff;border:1px solid var(--border);}
.btn-ghost:hover{background:rgba(255,255,255,.12);}
.btn-primary{background:var(--p);color:#fff;}
.btn-primary:hover{background:#4f46e5;transform:translateY(-1px);box-shadow:0 8px 24px rgba(99,102,241,.35);}
.btn-lg{padding:.8rem 2rem;font-size:15.5px;border-radius:12px;}
.hero{text-align:center;padding:7rem 2rem 4rem;background:radial-gradient(ellipse 80% 60% at 50% -10%,rgba(99,102,241,.2) 0%,transparent 70%);}
.hero-badge{display:inline-flex;align-items:center;gap:6px;background:rgba(99,102,241,.12);border:1px solid rgba(99,102,241,.3);color:#a5b4fc;padding:.35rem .9rem;border-radius:100px;font-size:12.5px;font-weight:600;margin-bottom:2rem;}
h1{font-family:'Plus Jakarta Sans',sans-serif;font-size:clamp(2.5rem,6vw,5rem);font-weight:900;line-height:1.06;margin-bottom:1.5rem;letter-spacing:-.03em;}
.grad{background:linear-gradient(135deg,#a5b4fc,#f472b6);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;}
.hero p{color:var(--muted);font-size:clamp(1rem,2.5vw,1.2rem);max-width:580px;margin:0 auto 2.5rem;}
.hero-cta{display:flex;gap:1rem;justify-content:center;flex-wrap:wrap;}
.mock{max-width:880px;margin:4rem auto 0;background:var(--sur);border-radius:20px;border:1px solid var(--border);overflow:hidden;box-shadow:0 32px 80px rgba(0,0,0,.5);}
.mock-bar{height:40px;background:rgba(255,255,255,.04);display:flex;align-items:center;gap:6px;padding:0 1rem;border-bottom:1px solid var(--border);}
.dot{width:11px;height:11px;border-radius:50%;}
.mock-url{flex:1;background:rgba(255,255,255,.05);border-radius:6px;height:22px;margin:0 1rem;display:flex;align-items:center;padding:0 .75rem;font-size:11px;color:var(--muted);}
.mock-body{display:grid;grid-template-columns:220px 1fr;height:380px;}
.mock-side{background:rgba(255,255,255,.03);border-right:1px solid var(--border);padding:1rem;}
.mock-item{display:flex;align-items:center;gap:8px;padding:8px 10px;border-radius:8px;font-size:12px;color:var(--muted);margin-bottom:4px;}
.mock-item.a{background:rgba(99,102,241,.15);color:#a5b4fc;}
.mock-photos{padding:1rem;display:grid;grid-template-columns:repeat(4,1fr);gap:6px;align-content:start;overflow:hidden;}
.mp{aspect-ratio:1;border-radius:8px;}
section{padding:5rem 2rem;}
.sl{text-align:center;color:var(--p);font-weight:600;font-size:13px;letter-spacing:.1em;text-transform:uppercase;margin-bottom:.75rem;}
.st{text-align:center;font-family:'Plus Jakarta Sans',sans-serif;font-size:clamp(1.75rem,3.5vw,2.75rem);font-weight:800;margin-bottom:1rem;letter-spacing:-.02em;}
.ss{text-align:center;color:var(--muted);max-width:540px;margin:0 auto 3rem;font-size:15px;}
.steps{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1.25rem;max-width:960px;margin:0 auto;}
.step{background:var(--sur);border:1px solid var(--border);border-radius:16px;padding:1.5rem;}
.step-n{width:34px;height:34px;background:linear-gradient(135deg,var(--p),var(--s));border-radius:10px;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:14px;margin-bottom:1rem;}
.step h3{font-weight:700;margin-bottom:.4rem;font-size:15px;}
.step p{color:var(--muted);font-size:13px;}
.feats{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:1.25rem;max-width:100%;margin:0 auto;}
.feat{background:var(--sur);border:1px solid var(--border);border-radius:16px;padding:1.5rem;transition:border-color .2s;}
.feat:hover{border-color:rgba(99,102,241,.4);}
.feat-icon{font-size:1.75rem;margin-bottom:.75rem;}
.feat h3{font-weight:700;margin-bottom:.4rem;font-size:15px;}
.feat p{color:var(--muted);font-size:13px;}
.stats{display:flex;justify-content:center;gap:4rem;flex-wrap:wrap;}
.stat-n{font-family:'Plus Jakarta Sans',sans-serif;font-size:2.5rem;font-weight:900;background:linear-gradient(135deg,var(--p),var(--s));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;}
.stat-l{color:var(--muted);font-size:13px;}
.cta-box{background:linear-gradient(135deg,rgba(99,102,241,.15),rgba(236,72,153,.1));border:1px solid rgba(99,102,241,.2);border-radius:24px;max-width:680px;margin:0 auto;padding:3.5rem 2rem;text-align:center;}
footer{border-top:1px solid var(--border);padding:2rem;text-align:center;color:var(--muted);font-size:13px;}
</style>
</head>
<body>
<nav>
  <a href="/" class="logo"><div class="logo-icon">⚡</div>LensPic</a>
  <div style="display:flex;gap:.5rem;">
    @auth
    <a href="{{ route('dashboard') }}" class="btn btn-ghost">Dashboard</a>
    @else
    <a href="{{ route('groups.join-code') }}" class="btn btn-ghost">Join a Group →</a>
    <a href="{{ route('login') }}" class="btn btn-ghost">Login</a>
    <a href="{{ route('register') }}" class="btn btn-primary">Get Started</a>
    @endauth
  </div>
</nav>

<section class="hero">
  <div class="hero-badge">⚡ AI-Powered Photo Sharing</div>
  <h1>Share Photos<br><span class="grad">Instantly & Smartly</span></h1>
  <p>Create a group, invite friends, upload photos. AI finds your face and delivers your photos — no scrolling through hundreds of shots.</p>
  <div class="hero-cta">
    <a href="{{ route('register') }}" class="btn btn-primary btn-lg"><i class="fa-solid fa-bolt"></i> Start Sharing</a>
    <a href="{{ route('login') }}" class="btn btn-ghost btn-lg">Sign In</a>
  </div>
  <p style="margin-top:1rem;font-size:14px;">Already have a group code? <a href="{{ route('groups.join-code') }}" style="color:#a5b4fc;font-weight:700;text-decoration:none;">Join a Group →</a></p>
  <div class="mock">
    <div class="mock-bar">
      <div class="dot" style="background:#ef4444"></div><div class="dot" style="background:#f59e0b"></div><div class="dot" style="background:#10b981"></div>
      <div class="mock-url">🔒 lenspic.in/groups/wedding2024</div>
    </div>
    <div class="mock-body">
      <div class="mock-side">
        <div style="font-size:10px;color:var(--muted);margin-bottom:10px;font-weight:600;letter-spacing:.05em;">MY GROUPS</div>
        <div class="mock-item a">💍 Priya's Wedding</div>
        <div class="mock-item">🎂 Rahul's Birthday</div>
        <div class="mock-item">✈️ Goa Trip</div>
        <div class="mock-item">🎓 Graduation</div>
        <div style="margin-top:16px;font-size:10px;color:var(--muted);margin-bottom:10px;font-weight:600;">STATS</div>
        <div style="font-size:13px;color:#a5b4fc;font-weight:700;">342 Photos</div>
        <div style="font-size:12px;color:var(--muted);">68 Members</div>
      </div>
      <div class="mock-photos">
        @foreach(['linear-gradient(135deg,#667eea,#764ba2)','linear-gradient(135deg,#f093fb,#f5576c)','linear-gradient(135deg,#4facfe,#00f2fe)','linear-gradient(135deg,#43e97b,#38f9d7)','linear-gradient(135deg,#fa709a,#fee140)','linear-gradient(135deg,#a18cd1,#fbc2eb)','linear-gradient(135deg,#ffecd2,#fcb69f)','linear-gradient(135deg,#a1c4fd,#c2e9fb)','linear-gradient(135deg,#d4fc79,#96e6a1)','linear-gradient(135deg,#f6d365,#fda085)','linear-gradient(135deg,#96fbc4,#f9f586)','linear-gradient(135deg,#89f7fe,#66a6ff)'] as $g)
        <div class="mp" style="background:{{ $g }}"></div>
        @endforeach
      </div>
    </div>
  </div>
</section>

<section style="padding:2rem 2rem 4rem;">
  <div class="stats">
    <div class="stat"><div class="stat-n">500K+</div><div class="stat-l">Photos Shared</div></div>
    <div class="stat"><div class="stat-n">50K+</div><div class="stat-l">Events Created</div></div>
    <div class="stat"><div class="stat-n">99%</div><div class="stat-l">Face Match Accuracy</div></div>
    <div class="stat"><div class="stat-n">2</div><div class="stat-l">Billing Options</div></div>
  </div>
</section>

<section style="background:var(--sur);border-top:1px solid var(--border);border-bottom:1px solid var(--border);">
  <div class="sl">How It Works</div>
  <h2 class="st">Simple as 1-2-3</h2>
  <p class="ss">From upload to delivery in minutes. No app download needed for guests.</p>
  <div class="steps">
    <div class="step"><div class="step-n">1</div><h3>Create a Group</h3><p>Set up a photo group for your event in seconds.</p></div>
    <div class="step"><div class="step-n">2</div><h3>Share the Link</h3><p>Send your unique link to everyone. No app install needed.</p></div>
    <div class="step"><div class="step-n">3</div><h3>Upload Photos</h3><p>Drag & drop up to 100 photos at once in full quality.</p></div>
    <div class="step"><div class="step-n">4</div><h3>AI Finds You</h3><p>Take a selfie — AI finds all your photos instantly.</p></div>
  </div>
</section>

<section>
  <div class="sl">Features</div>
  <h2 class="st">Everything You Need</h2>
  <p class="ss">Powerful tools for photographers and guests alike.</p>
  <div class="feats">
    <div class="feat"><div class="feat-icon">🤖</div><h3>AI Face Recognition</h3><p>Upload a selfie and instantly find all photos you appear in.</p></div>
    <div class="feat"><div class="feat-icon">⚡</div><h3>Instant Delivery</h3><p>Photos available for download the moment they're uploaded.</p></div>
    <div class="feat"><div class="feat-icon">📦</div><h3>Bulk Upload</h3><p>Upload up to 100 photos at once. JPEG, PNG, WebP supported.</p></div>
    <div class="feat"><div class="feat-icon">🔗</div><h3>No App Required</h3><p>Guests access via a simple link — no registration needed.</p></div>
    <div class="feat"><div class="feat-icon">🔒</div><h3>Privacy Controls</h3><p>Public, private, or link-only groups. Regenerate links anytime.</p></div>
    <div class="feat"><div class="feat-icon">📥</div><h3>Full Quality Downloads</h3><p>Download individual photos or full albums as ZIP.</p></div>
  </div>
</section>

<section style="padding-top:0;">
  <div class="cta-box">
    <h2 style="font-family:'Plus Jakarta Sans',sans-serif;font-size:2.2rem;font-weight:900;letter-spacing:-.02em;margin-bottom:1rem;">Ready to share smarter?</h2>
    <p style="color:var(--muted);margin-bottom:2rem;">Create your first photo group in 30 seconds with a quarterly or yearly plan.</p>
    <a href="{{ route('register') }}" class="btn btn-primary btn-lg"><i class="fa-solid fa-bolt"></i> Create Your First Group</a>
  </div>
</section>
<footer><p>© {{ date('Y') }} LensPic — AI-Powered Group Photo Sharing</p></footer>
</body>
</html>
