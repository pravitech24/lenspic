<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>Pricing – LensPic</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
*{box-sizing:border-box;margin:0;padding:0;}
:root{--p:#6366f1;--s:#ec4899;--bg:#080814;--sur:#10101e;--sur2:#1a1d27;--border:rgba(255,255,255,.08);--muted:#94a3b8;--text:#f8fafc;}
body{font-family:'Inter',sans-serif;background:var(--bg);color:var(--text);line-height:1.6;}
nav{display:flex;align-items:center;justify-content:space-between;padding:1.2rem 2rem;position:sticky;top:0;z-index:50;background:rgba(8,8,20,.85);backdrop-filter:blur(16px);border-bottom:1px solid var(--border);}
.logo{display:flex;align-items:center;gap:10px;font-family:'Plus Jakarta Sans',sans-serif;font-weight:800;font-size:1.35rem;text-decoration:none;color:#fff;}
.logo-icon{width:36px;height:36px;background:linear-gradient(135deg,var(--p),var(--s));border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:18px;}
.nav-links{display:flex;align-items:center;gap:4px;}
.nav-links a{color:var(--muted);text-decoration:none;font-size:14px;font-weight:500;padding:.4rem .8rem;border-radius:8px;transition:all .15s;}
.nav-links a:hover{color:#fff;background:rgba(255,255,255,.07);}
.btn{display:inline-flex;align-items:center;gap:6px;padding:.55rem 1.1rem;border-radius:10px;font-weight:600;font-size:14px;text-decoration:none;border:none;cursor:pointer;transition:all .15s;}
.btn-ghost{background:rgba(255,255,255,.07);color:#fff;border:1px solid var(--border);}
.btn-ghost:hover{background:rgba(255,255,255,.12);}
.btn-primary{background:var(--p);color:#fff;}
.btn-primary:hover{background:#4f46e5;transform:translateY(-1px);box-shadow:0 8px 24px rgba(99,102,241,.35);}
.btn-lg{padding:.85rem 2rem;font-size:15.5px;border-radius:12px;}
.btn-white{background:#fff;color:#1e293b;font-weight:700;}
.btn-white:hover{background:#f1f5f9;transform:translateY(-1px);}

/* HERO */
.hero{text-align:center;padding:5rem 2rem 3rem;background:radial-gradient(ellipse 80% 50% at 50% -10%,rgba(99,102,241,.2),transparent 70%);}
.sl{color:var(--p);font-weight:600;font-size:13px;letter-spacing:.1em;text-transform:uppercase;margin-bottom:.75rem;}
h1{font-family:'Plus Jakarta Sans',sans-serif;font-size:clamp(2rem,5vw,3.5rem);font-weight:900;letter-spacing:-.03em;margin-bottom:1rem;}
.grad{background:linear-gradient(135deg,#a5b4fc,#f472b6);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;}
.hero p{color:var(--muted);font-size:1rem;max-width:520px;margin:0 auto 2rem;}

/* BILLING TOGGLE */
.toggle-wrap{display:flex;align-items:center;justify-content:center;gap:.75rem;margin-bottom:3rem;}
.toggle-label{font-size:14px;font-weight:500;color:var(--muted);}
.toggle-label.active{color:#fff;}
.pill-toggle{width:48px;height:26px;background:var(--p);border-radius:26px;cursor:pointer;position:relative;border:none;transition:background .2s;}
.pill-toggle::after{content:'';position:absolute;width:20px;height:20px;background:#fff;border-radius:50%;top:3px;left:3px;transition:transform .2s;}
.pill-toggle.yearly::after{transform:translateX(22px);}
.save-badge{background:rgba(16,185,129,.15);color:#34d399;border:1px solid rgba(16,185,129,.25);padding:2px 8px;border-radius:20px;font-size:11px;font-weight:700;}

/* PLANS */
.plans{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:1.25rem;max-width:1400px;margin:0 auto;padding:0 2rem;align-items:stretch;}
.plan{background:var(--sur);border:1px solid var(--border);border-radius:20px;padding:2rem;position:relative;transition:border-color .2s,transform .2s;height:100%;}
.plan:hover{border-color:rgba(99,102,241,.3);transform:translateY(-3px);}
.plan.popular{border-color:var(--p);background:linear-gradient(135deg,rgba(99,102,241,.08),rgba(236,72,153,.05));}
.popular-badge{position:absolute;top:-14px;left:50%;transform:translateX(-50%);background:linear-gradient(135deg,var(--p),var(--s));color:#fff;padding:4px 16px;border-radius:20px;font-size:12px;font-weight:700;white-space:nowrap;}
.plan-name{font-weight:700;font-size:1rem;margin-bottom:.4rem;}
.plan-desc{color:var(--muted);font-size:13px;margin-bottom:1.5rem;}
.plan-price{font-family:'Plus Jakarta Sans',sans-serif;font-size:2.8rem;font-weight:900;line-height:1;margin-bottom:.25rem;}
.plan-price sup{font-size:1.2rem;vertical-align:top;margin-top:.4rem;}
.plan-price .period{font-size:.9rem;font-weight:400;color:var(--muted);}
.plan-price-orig{font-size:.85rem;color:var(--muted);text-decoration:line-through;margin-bottom:1.5rem;}
.billing-note{font-size:12px;color:var(--muted);margin-bottom:1.5rem;}
.plan-divider{border:none;border-top:1px solid var(--border);margin:1.5rem 0;}
.features-list{list-style:none;display:flex;flex-direction:column;gap:.65rem;margin-bottom:2rem;}
.features-list li{display:flex;align-items:flex-start;gap:.6rem;font-size:13.5px;}
.features-list li .check{color:#34d399;flex-shrink:0;margin-top:1px;}
.features-list li .cross{color:#64748b;flex-shrink:0;margin-top:1px;}
.features-list li.muted{color:var(--muted);}
.included{display:flex;align-items:center;justify-content:center;gap:1.25rem;flex-wrap:wrap;margin:-1.5rem auto 2.5rem;padding:0 2rem;color:#c7d2fe;font-weight:700;font-size:13px;text-transform:uppercase;letter-spacing:.02em;}
.included span{display:inline-flex;align-items:center;gap:.45rem;}
.included i{color:#34d399;}
.included-title{text-align:center;color:var(--muted);font-size:15px;margin:-1rem 0 1.75rem;}
.included-title strong{color:#fff;}

/* COMPARE TABLE */
section{padding:5rem 2rem;}
.st{text-align:center;font-family:'Plus Jakarta Sans',sans-serif;font-size:clamp(1.5rem,3vw,2.2rem);font-weight:800;margin-bottom:.75rem;letter-spacing:-.02em;}
.ss{text-align:center;color:var(--muted);max-width:500px;margin:0 auto 2.5rem;font-size:15px;}
.compare{max-width:900px;margin:0 auto;background:var(--sur);border:1px solid var(--border);border-radius:16px;overflow:hidden;}
.compare table{width:100%;border-collapse:collapse;}
.compare th{padding:1rem 1.25rem;text-align:center;font-size:13px;font-weight:700;border-bottom:1px solid var(--border);background:var(--sur2);}
.compare th:first-child{text-align:left;}
.compare td{padding:.85rem 1.25rem;text-align:center;font-size:13.5px;border-bottom:1px solid rgba(255,255,255,.04);}
.compare td:first-child{text-align:left;font-weight:500;}
.compare tr:last-child td{border-bottom:none;}
.compare tr:hover td{background:rgba(255,255,255,.02);}
.check-icon{color:#34d399;font-size:15px;}
.cross-icon{color:#475569;font-size:15px;}
.highlight-col{background:rgba(99,102,241,.05);}

/* CUSTOM WEBSITE */
.custom-section{background:linear-gradient(135deg,rgba(99,102,241,.1),rgba(236,72,153,.08));border:1px solid rgba(99,102,241,.2);border-radius:24px;max-width:1060px;margin:0 auto;padding:3.5rem 2rem;overflow:hidden;position:relative;}
.custom-section::before{content:'';position:absolute;right:-60px;top:-60px;width:300px;height:300px;background:radial-gradient(circle,rgba(99,102,241,.15),transparent 70%);pointer-events:none;}
.custom-grid{display:grid;grid-template-columns:1fr 1fr;gap:3rem;align-items:center;}
.custom-features{display:flex;flex-direction:column;gap:.85rem;margin:1.5rem 0;}
.custom-feat{display:flex;align-items:flex-start;gap:.75rem;}
.custom-feat-icon{width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:16px;flex-shrink:0;}
.custom-feat h4{font-weight:600;font-size:14px;margin-bottom:2px;}
.custom-feat p{font-size:12.5px;color:var(--muted);}
.mock-website{background:var(--sur);border:1px solid var(--border);border-radius:16px;overflow:hidden;}
.mock-site-bar{height:36px;background:var(--sur2);display:flex;align-items:center;gap:6px;padding:0 .75rem;border-bottom:1px solid var(--border);}
.mock-dot{width:10px;height:10px;border-radius:50%;}
.mock-site-url{flex:1;background:rgba(255,255,255,.05);border-radius:5px;height:18px;margin:0 .75rem;display:flex;align-items:center;padding:0 .6rem;font-size:10px;color:var(--muted);}
.mock-site-body{padding:1.25rem;}
.mock-site-hero{height:80px;background:linear-gradient(135deg,#1e1b4b,#312e81);border-radius:10px;display:flex;align-items:center;justify-content:center;margin-bottom:1rem;}
.mock-site-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:6px;}
.mock-site-photo{aspect-ratio:1;border-radius:7px;}

/* FAQ */
.faq{max-width:700px;margin:0 auto;}
.faq-item{border-bottom:1px solid var(--border);padding:1.25rem 0;}
.faq-q{display:flex;align-items:center;justify-content:space-between;cursor:pointer;font-weight:600;font-size:14.5px;gap:1rem;}
.faq-q i{color:var(--muted);transition:transform .2s;flex-shrink:0;}
.faq-a{color:var(--muted);font-size:13.5px;margin-top:.75rem;display:none;line-height:1.7;}
.faq-item.open .faq-a{display:block;}
.faq-item.open .faq-q i{transform:rotate(180deg);}

/* CTA */
.cta-box{background:linear-gradient(135deg,rgba(99,102,241,.15),rgba(236,72,153,.1));border:1px solid rgba(99,102,241,.2);border-radius:24px;max-width:680px;margin:0 auto;padding:3.5rem 2rem;text-align:center;}
footer{border-top:1px solid var(--border);padding:2rem;text-align:center;color:var(--muted);font-size:13px;}

@media(max-width:1100px){.plans{grid-template-columns:repeat(2,minmax(0,1fr));}}
@media(max-width:768px){.custom-grid{grid-template-columns:1fr!important;}.nav-links{display:none;}.plans{grid-template-columns:1fr;}}
</style>
</head>
<body>

<nav>
  <a href="/" class="logo"><div class="logo-icon">⚡</div>LensPic</a>
  <div class="nav-links">
    <a href="/">Home</a>
    <a href="/pricing" style="color:#fff;">Pricing</a>
    <a href="#custom">Custom Website</a>
  </div>
  <div style="display:flex;gap:.5rem;">
    @auth
    <a href="{{ route('dashboard') }}" class="btn btn-ghost">Dashboard</a>
    @else
    <a href="{{ route('login') }}" class="btn btn-ghost">Login</a>
    <a href="{{ route('register') }}" class="btn btn-primary">Get Started</a>
    @endauth
  </div>
</nav>

<!-- HERO -->
<div class="hero">
  <div class="sl">Simple Pricing</div>
  <h1>Choose Your <span class="grad">Perfect Plan</span></h1>
  <p>Choose quarterly or yearly billing. No hidden fees, cancel anytime.</p>

  <!-- Billing toggle -->
  <div class="toggle-wrap">
    <span class="toggle-label active" id="quarterlyLabel">Quarterly</span>
    <button class="pill-toggle" id="billingToggle" onclick="toggleBilling()"></button>
    <span class="toggle-label" id="yearlyLabel">Yearly <span class="save-badge">Save 30%</span></span>
  </div>
</div>

<p class="included-title">All plans have <strong>Unlimited</strong></p>
<div class="included">
  <span><i class="fa-solid fa-circle-check"></i> Facial Recognition</span>
  <span><i class="fa-solid fa-circle-check"></i> User Registration</span>
  <span><i class="fa-solid fa-circle-check"></i> Album Creation</span>
  <span><i class="fa-solid fa-circle-check"></i> 24x7 WhatsApp Support</span>
</div>

<!-- PLANS -->
<div class="plans">

  @php
  $plans = [
    [
      'code' => 'basic',
      'name' => 'Basic',
      'desc' => 'A simple way to start sharing photos and organizing groups.',
      'popular' => false,
      'features' => [
        'Up to 1,000 photos per group',
        '20 GB storage',
        'Up to 10 groups',
        'Face recognition access',
      ],
    ],
    [
      'code' => 'standard',
      'name' => 'Standard',
      'desc' => 'All the basics for sharing photos using face recognition.',
      'popular' => false,
      'features' => [
        'Up to 3,000 photos per group',
        '100 GB storage',
        'Unlimited groups',
        'Business Branding',
      ],
    ],
    [
      'code' => 'essential',
      'name' => 'Essential',
      'desc' => 'All the essential features for professional photographers.',
      'popular' => true,
      'features' => [
        'Up to 6,000 photos per group',
        '250 GB storage',
        'Unlimited groups',
        'Business Branding',
        'View Client Favorites',
        'Switch On/Off Downloads',
        '50+ gallery themes',
        'Bulk Download',
        'Analytics and Participant Info',
        'Add Watermarks',
        'Portfolio Website',
        'Team Login & Controls',
      ],
    ],
    [
      'code' => 'premium',
      'name' => 'Premium',
      'desc' => 'Maximum storage and advanced event delivery controls.',
      'popular' => false,
      'features' => [
        'Up to 12,500 photos per group',
        '600 GB storage',
        'Unlimited groups',
        'Business Branding',
        'View Client Favorites',
        'Switch On/Off Downloads',
        '50+ gallery themes',
        'Anonymous Viewing',
        'Bulk Download',
        'Analytics and Participant Info',
        'Add Watermarks',
        'Portfolio Website',
        'Digital Album',
        'Sponsor Branding',
        'Team Login & Controls',
        'Instant Upload from Camera/Folder',
      ],
    ],
  ];
  @endphp

  @foreach($plans as $plan)
  <div class="plan {{ $plan['popular'] ? 'popular' : '' }}">
    @if($plan['popular'])<div class="popular-badge">Bestseller</div>@endif
    <div class="plan-name">{{ $plan['name'] }}</div>
    <div class="plan-desc">{{ $plan['desc'] }}</div>
    <div class="plan-price" id="{{ $plan['code'] }}Price"></div>
    <div class="plan-price-orig" id="{{ $plan['code'] }}Orig" style="visibility:hidden;"></div>
    <div class="billing-note" id="{{ $plan['code'] }}Note"></div>
    @auth
    <button type="button" class="btn {{ $plan['popular'] ? 'btn-primary' : 'btn-ghost' }} checkout-btn" data-plan="{{ $plan['code'] }}" style="width:100%;justify-content:center;margin-bottom:1.5rem;">Get Started <i class="fa-solid fa-arrow-right"></i></button>
    @else
    <a href="{{ route('register') }}" class="btn {{ $plan['popular'] ? 'btn-primary' : 'btn-ghost' }}" style="width:100%;justify-content:center;margin-bottom:1.5rem;">Get Started <i class="fa-solid fa-arrow-right"></i></a>
    @endauth
    <hr class="plan-divider">
    <ul class="features-list">
      @foreach($plan['features'] as $feature)
      <li><i class="fa-solid fa-check check"></i> {{ $feature }}</li>
      @endforeach
    </ul>
  </div>
  @endforeach

</div>

<!-- COMPARE TABLE -->
<section>
  <h2 class="st">Compare Plans</h2>
  <p class="ss">Everything side by side so you can make the right choice.</p>
  <div class="compare">
    <table>
      <thead>
        <tr>
          <th>Feature</th>
          <th>Basic</th>
          <th>Standard</th>
          <th style="color:#a5b4fc;">Essential</th>
          <th>Premium</th>
        </tr>
      </thead>
      <tbody>
        @php
        $rows = [
          ['Photos per group',        '1,000', '3,000', '6,000', '12,500'],
          ['Storage',                 '20 GB', '100 GB', '250 GB', '600 GB'],
          ['Groups',                  '10',    'Unlimited', 'Unlimited', 'Unlimited'],
          ['Facial Recognition',      true,    true,     true,     true],
          ['User Registration',       true,    true,     true,     true],
          ['Album Creation',          true,    true,     true,     true],
          ['Business Branding',       false,   true,     true,     true],
          ['Bulk Download',           true,    true,     true,     true],
          ['View Client Favorites',   false,   false,    true,     true],
          ['Switch On/Off Downloads', false,   false,    true,     true],
          ['50+ gallery themes',      false,   false,    true,     true],
          ['Analytics & Participants',false,   false,    true,     true],
          ['Add Watermarks',          false,   false,    true,     true],
          ['Portfolio Website',       false,   false,    true,     true],
          ['Team Login & Controls',   false,   false,    true,     true],
          ['Anonymous Viewing',       false,   false,    false,    true],
          ['Digital Album',           false,   false,    false,    true],
          ['Sponsor Branding',        false,   false,    false,    true],
          ['Instant Upload',          false,   false,    false,    true],
          ['WhatsApp Support',        true,    true,     true,     true],
        ];
        @endphp
        @foreach($rows as $row)
        <tr>
          <td>{{ $row[0] }}</td>
          @foreach(array_slice($row,1) as $val)
          <td>
            @if($val === true)<i class="fa-solid fa-check check-icon"></i>
            @elseif($val === false)<i class="fa-solid fa-xmark cross-icon"></i>
            @else<span style="font-weight:600;">{{ $val }}</span>
            @endif
          </td>
          @endforeach
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</section>

<!-- CUSTOM WEBSITE SECTION -->
<section id="custom" style="padding-top:0;">
  <div class="custom-section">
    <div class="custom-grid">
      <div>
        <div class="sl" style="text-align:left;">Personalised Website</div>
        <h2 style="font-family:'Plus Jakarta Sans',sans-serif;font-size:clamp(1.6rem,3vw,2.4rem);font-weight:900;letter-spacing:-.02em;margin-bottom:1rem;line-height:1.1;">
          Your Own<br><span class="grad">Photography Website</span>
        </h2>
        <p style="color:var(--muted);font-size:15px;margin-bottom:1.5rem;">With Essential and Premium, we help you present a fully personalised website — your brand, your domain, your style. Impress clients before they even open a single photo.</p>

        <div class="custom-features">
          <div class="custom-feat">
            <div class="custom-feat-icon" style="background:rgba(99,102,241,.15);color:#a5b4fc;">🌐</div>
            <div><h4>Custom Domain</h4><p>yourstudio.com — fully SSL secured and professional.</p></div>
          </div>
          <div class="custom-feat">
            <div class="custom-feat-icon" style="background:rgba(236,72,153,.15);color:#f9a8d4;">🎨</div>
            <div><h4>Your Branding</h4><p>Logo, colors, fonts — completely white-labeled. Clients see your brand, not LensPic.</p></div>
          </div>
          <div class="custom-feat">
            <div class="custom-feat-icon" style="background:rgba(245,158,11,.15);color:#fbbf24;">📸</div>
            <div><h4>Portfolio Gallery</h4><p>Stunning portfolio page to showcase your best work to potential clients.</p></div>
          </div>
          <div class="custom-feat">
            <div class="custom-feat-icon" style="background:rgba(16,185,129,.15);color:#34d399;">📩</div>
            <div><h4>Client Enquiry Form</h4><p>Built-in contact & booking form so clients can reach you directly.</p></div>
          </div>
          <div class="custom-feat">
            <div class="custom-feat-icon" style="background:rgba(139,92,246,.15);color:#c4b5fd;">⚡</div>
            <div><h4>Powered by LensPic AI</h4><p>All the AI photo delivery features built into your own branded platform.</p></div>
          </div>
        </div>

        <div style="display:flex;gap:.75rem;flex-wrap:wrap;">
          <a href="mailto:hello@lenspic.in" class="btn btn-primary btn-lg"><i class="fa-solid fa-envelope"></i> Request Your Website</a>
          <a href="{{ route('pricing') }}" class="btn btn-ghost btn-lg">View Plans</a>
        </div>
      </div>

      <!-- Mock website preview -->
      <div>
        <div class="mock-website">
          <div class="mock-site-bar">
            <div class="mock-dot" style="background:#ef4444;"></div>
            <div class="mock-dot" style="background:#f59e0b;"></div>
            <div class="mock-dot" style="background:#10b981;"></div>
            <div class="mock-site-url">🔒 yourphotostudio.com</div>
          </div>
          <div class="mock-site-body">
            <div class="mock-site-hero">
              <div style="text-align:center;">
                <div style="font-family:'Plus Jakarta Sans',sans-serif;font-weight:800;font-size:1.1rem;color:#fff;margin-bottom:4px;">📸 Rahul Photography</div>
                <div style="font-size:11px;color:rgba(255,255,255,.6);">Capturing memories since 2018</div>
              </div>
            </div>
            <div style="display:flex;gap:.5rem;margin-bottom:.75rem;flex-wrap:wrap;">
              @foreach(['Weddings','Portraits','Corporate','Travel'] as $tag)
              <span style="font-size:10px;background:rgba(99,102,241,.15);color:#a5b4fc;padding:2px 8px;border-radius:20px;font-weight:600;">{{ $tag }}</span>
              @endforeach
            </div>
            <div class="mock-site-grid">
              @foreach(['linear-gradient(135deg,#667eea,#764ba2)','linear-gradient(135deg,#f093fb,#f5576c)','linear-gradient(135deg,#4facfe,#00f2fe)','linear-gradient(135deg,#43e97b,#38f9d7)','linear-gradient(135deg,#fa709a,#fee140)','linear-gradient(135deg,#a18cd1,#fbc2eb)'] as $g)
              <div class="mock-site-photo" style="background:{{ $g }};"></div>
              @endforeach
            </div>
            <div style="margin-top:.85rem;background:rgba(99,102,241,.1);border:1px solid rgba(99,102,241,.2);border-radius:8px;padding:.65rem .85rem;font-size:11px;color:#a5b4fc;font-weight:600;text-align:center;">
              ⚡ Find Your Photos with AI →
            </div>
          </div>
        </div>
        <p style="text-align:center;font-size:12px;color:var(--muted);margin-top:.75rem;">Your studio website — fully built & managed by us</p>
      </div>
    </div>
  </div>
</section>

<!-- FAQ -->
<section>
  <h2 class="st">Frequently Asked Questions</h2>
  <p class="ss">Got questions? We've got answers.</p>
  <div class="faq">
    @php
    $faqs = [
      ['Can I switch plans anytime?', 'Yes! You can upgrade or downgrade your plan at any time. When upgrading, you get access immediately. When downgrading, changes take effect at the start of your next billing cycle.'],
      ['What payment methods do you accept?', 'We accept all major credit/debit cards, UPI, Net Banking, and wallets via Razorpay.'],
      ['Is my data safe?', 'Absolutely. All photos are encrypted at rest and in transit. We never share or sell your data. You own your photos — always.'],
      ['What happens when I hit my storage limit?', 'We\'ll notify you well before you hit the limit. You can upgrade your plan or purchase additional storage add-ons without losing any data.'],
      ['What\'s included in the personalised website?', 'We design and build a custom photography website on your domain with your branding. It includes a portfolio gallery, client enquiry form, AI-powered photo delivery, and full LensPic integration. Setup takes 5–7 business days.'],
      ['Can guests download photos without an account?', 'Yes! Guests can view and download their photos through your share link without creating any account. That\'s one of LensPic\'s core features.'],
      ['Do you offer refunds?', 'Yes, we offer a full refund within 7 days of payment if you\'re not satisfied. Just email us at billing@lenspic.in.'],
    ];
    @endphp
    @foreach($faqs as $i => $faq)
    <div class="faq-item" id="faq{{ $i }}">
      <div class="faq-q" onclick="toggleFaq({{ $i }})">
        <span>{{ $faq[0] }}</span>
        <i class="fa-solid fa-chevron-down"></i>
      </div>
      <div class="faq-a">{{ $faq[1] }}</div>
    </div>
    @endforeach
  </div>
</section>

<!-- CTA -->
<section style="padding-top:0;padding-bottom:5rem;">
  <div class="cta-box">
    <h2 style="font-family:'Plus Jakarta Sans',sans-serif;font-size:2rem;font-weight:900;letter-spacing:-.02em;margin-bottom:1rem;">Start with the right plan</h2>
    <p style="color:var(--muted);margin-bottom:2rem;font-size:15px;">Pick quarterly or yearly billing and get started.</p>
    <div style="display:flex;gap:.75rem;justify-content:center;flex-wrap:wrap;">
      <a href="{{ route('register') }}" class="btn btn-primary btn-lg"><i class="fa-solid fa-bolt"></i> Create Account</a>
      <a href="mailto:hello@lenspic.in" class="btn btn-ghost btn-lg"><i class="fa-solid fa-envelope"></i> Contact Sales</a>
    </div>
  </div>
</section>

<footer>
  <p>© {{ date('Y') }} LensPic &nbsp;·&nbsp; <a href="/" style="color:var(--muted);text-decoration:none;">Home</a> &nbsp;·&nbsp; <a href="/pricing" style="color:var(--muted);text-decoration:none;">Pricing</a> &nbsp;·&nbsp; <a href="mailto:hello@lenspic.in" style="color:var(--muted);text-decoration:none;">Contact</a></p>
</footer>

<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
let isYearly = false;

const billingRoutes = @json([
  'order' => route('billing.razorpay.order'),
  'verify' => route('billing.razorpay.verify'),
]);

const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
const priceState = {
  quarterly: {
    basic: { price: '₹999', period: '/per quarter + GST', note: 'Billed ₹999 quarterly' },
    standard: { price: '₹1,799', period: '/per quarter + GST', note: 'Billed ₹1,799 quarterly' },
    essential: { price: '₹3,599', period: '/per quarter + GST', note: 'Billed ₹3,599 quarterly' },
    premium: { price: '₹6,899', period: '/per quarter + GST', note: 'Billed ₹6,899 quarterly' },
  },
  yearly: {
    basic: { price: '₹2,999', orig: '₹3,996', period: '/per year + GST', note: 'Billed ₹2,999 yearly' },
    standard: { price: '₹5,999', orig: '₹7,196', period: '/per year + GST', note: 'Billed ₹5,999 yearly' },
    essential: { price: '₹11,999', orig: '₹14,396', period: '/per year + GST', note: 'Billed ₹11,999 yearly' },
    premium: { price: '₹22,999', orig: '₹27,596', period: '/per year + GST', note: 'Billed ₹22,999 yearly' },
  },
};

function updatePlanPrices() {
  const pricing = isYearly ? priceState.yearly : priceState.quarterly;
  ['basic', 'standard', 'essential', 'premium'].forEach((plan) => {
    const item = pricing[plan];
    document.getElementById(`${plan}Price`).innerHTML = `<sup>₹</sup>${item.price.replace('₹', '')} <span class="period">${item.period}</span>`;
    document.getElementById(`${plan}Orig`).textContent = isYearly ? item.orig : '';
    document.getElementById(`${plan}Orig`).style.visibility = isYearly ? 'visible' : 'hidden';
    document.getElementById(`${plan}Note`).textContent = item.note;
  });
}

function toggleBilling() {
  isYearly = !isYearly;
  const btn = document.getElementById('billingToggle');
  btn.classList.toggle('yearly', isYearly);
  document.getElementById('quarterlyLabel').classList.toggle('active', !isYearly);
  document.getElementById('yearlyLabel').classList.toggle('active', isYearly);
  updatePlanPrices();
}

function toggleFaq(i) {
  const item = document.getElementById('faq' + i);
  item.classList.toggle('open');
}

function submitVerification(payload, plan, cycle) {
  const form = document.createElement('form');
  form.method = 'POST';
  form.action = billingRoutes.verify;

  const fields = {
    _token: csrfToken,
    razorpay_payment_id: payload.razorpay_payment_id,
    razorpay_order_id: payload.razorpay_order_id,
    razorpay_signature: payload.razorpay_signature,
    plan: plan,
    cycle: cycle,
  };

  Object.entries(fields).forEach(([name, value]) => {
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = name;
    input.value = value;
    form.appendChild(input);
  });

  document.body.appendChild(form);
  form.submit();
}

async function launchCheckout(plan) {
  if (typeof Razorpay === 'undefined') {
    throw new Error('Checkout is unavailable right now. Please try again later.');
  }

  const cycle = isYearly ? 'yearly' : 'quarterly';
  const response = await fetch(billingRoutes.order, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      'X-CSRF-TOKEN': csrfToken,
    },
    body: JSON.stringify({ plan, cycle }),
  });

  const data = await response.json().catch(() => ({}));

  if (!response.ok) {
    throw new Error(data.message || 'Unable to start checkout right now.');
  }

  const options = {
    key: data.key,
    amount: data.amount,
    currency: data.currency,
    name: data.name,
    description: data.description,
    order_id: data.order_id,
    prefill: data.prefill,
    theme: { color: '#6366f1' },
    handler: function (payload) {
      submitVerification(payload, plan, cycle);
    },
  };

  const checkout = new Razorpay(options);
  checkout.on('payment.failed', function (response) {
    alert(response.error.description || 'Payment failed.');
  });
  checkout.open();
}

document.querySelectorAll('.checkout-btn').forEach((button) => {
  button.addEventListener('click', function () {
    launchCheckout(this.dataset.plan).catch((error) => alert(error.message));
  });
});

updatePlanPrices();
</script>
</body>
</html>
