@if(request()->routeIs('home'))
@php
    $slides = config("marketing.hero_sliders.{$slider}", []);
@endphp
@if(count($slides))
@push('head')
<link rel="preload" as="image" href="{{ asset('images/marketing/'.$slides[0][2].'-desktop.webp') }}" media="(min-width: 1024px)" type="image/webp">
<link rel="preload" as="image" href="{{ asset('images/marketing/'.$slides[0][2].'-tablet.webp') }}" media="(min-width: 641px) and (max-width: 1023px)" type="image/webp">
<link rel="preload" as="image" href="{{ asset('images/marketing/'.$slides[0][2].'-mobile.webp') }}" media="(max-width: 640px)" type="image/webp">
@endpush
<section class="hero-carousel" aria-roledescription="carousel" aria-label="{{ $title }} highlights" tabindex="0" data-home-hero-slider data-hero-carousel data-interval="5500">
    <div class="hero-carousel-track">
        @foreach($slides as $slide)
        @php
            $presentation = $slide[9] ?? [];
            $contentPosition = in_array($presentation['position'] ?? 'left', ['left', 'center', 'right'], true) ? ($presentation['position'] ?? 'left') : 'left';
            $contentWidth = in_array($presentation['width'] ?? 'standard', ['standard', 'wide', 'center'], true) ? ($presentation['width'] ?? 'standard') : 'standard';
            $overlayDirection = in_array($presentation['overlay'] ?? 'left', ['left', 'left-strong', 'center', 'right'], true) ? ($presentation['overlay'] ?? 'left') : 'left';
            $textTheme = in_array($presentation['theme'] ?? 'light', ['light', 'dark'], true) ? ($presentation['theme'] ?? 'light') : 'light';
        @endphp
        <article class="hero-slide" data-hero-slide aria-roledescription="slide" aria-label="{{ $loop->iteration }} of {{ count($slides) }}" @if(!$loop->first) hidden @endif>
            <picture class="hero-slide-media">
                <source media="(max-width: 640px)" srcset="{{ asset('images/marketing/'.$slide[2].'-mobile.webp') }}" type="image/webp">
                <source media="(max-width: 1023px)" srcset="{{ asset('images/marketing/'.$slide[2].'-tablet.webp') }}" type="image/webp">
                <img class="image-focus-{{ $slide[8] ?? 'center' }}" src="{{ asset('images/marketing/'.$slide[2].'-desktop.webp') }}" width="1600" height="900" alt="{{ $slide[3] }}" @if($loop->first) fetchpriority="high" loading="eager" @else loading="lazy" decoding="async" @endif>
            </picture>
            <span class="hero-slide-overlay hero-overlay-{{ $overlayDirection }}" aria-hidden="true"></span>
            <div class="marketing-container hero-slide-content hero-content-{{ $contentPosition }} hero-content-width-{{ $contentWidth }} hero-content-theme-{{ $textTheme }}">
                <div><span class="eyebrow-pill">LensPic</span><h1>{{ $slide[0] }}</h1><p>{{ $slide[1] }}</p><div class="cta-row"><a class="button button-primary" href="{{ route($slide[5]) }}">{{ $slide[4] }}</a><a class="button button-light" href="{{ route($slide[7]) }}">{{ $slide[6] }}</a></div></div>
            </div>
        </article>
        @endforeach
    </div>
    <div class="hero-carousel-controls" hidden data-carousel-controls>
        <button type="button" class="carousel-arrow carousel-arrow-prev" data-carousel-prev aria-label="Previous slide">
            <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
        </button>
        <div class="carousel-dots" aria-label="Choose slide">@foreach($slides as $slide)<button type="button" data-carousel-dot="{{ $loop->index }}" aria-label="Go to slide {{ $loop->iteration }}" @if($loop->first) aria-current="true" @endif></button>@endforeach</div>
        <button type="button" class="carousel-toggle" data-carousel-toggle aria-label="Pause slideshow" aria-pressed="false">
            <span data-carousel-pause-icon><svg aria-hidden="true" viewBox="0 0 24 24" fill="currentColor"><path d="M7 5h3v14H7zm7 0h3v14h-3z"/></svg></span>
            <span data-carousel-play-icon hidden><svg aria-hidden="true" viewBox="0 0 24 24" fill="currentColor"><path d="m8 5 11 7-11 7z"/></svg></span>
        </button>
        <button type="button" class="carousel-arrow carousel-arrow-next" data-carousel-next aria-label="Next slide">
            <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
        </button>
    </div>
    <p class="sr-only" data-carousel-status aria-live="polite">Slide 1 of {{ count($slides) }}</p>
</section>
@endif
@endif
