@extends('layouts.marketing')
@push('head')
<meta property="og:image" content="{{ asset('images/marketing/lenspic-slider-private-sharing-desktop.webp') }}">
<script type="application/ld+json">{!! json_encode(['@context'=>'https://schema.org','@type'=>'BreadcrumbList','itemListElement'=>[['@type'=>'ListItem','position'=>1,'name'=>'Solutions & Use Cases','item'=>route('marketing.solutions')],['@type'=>'ListItem','position'=>2,'name'=>$data['nav'],'item'=>url()->current()]]], JSON_UNESCAPED_SLASHES) !!}</script>
@endpush
@section('content')
@include('marketing.partials.solution-header')
<section class="marketing-section"><div class="marketing-container"><div class="section-heading left"><span class="eyebrow">The delivery challenge</span><h2>Built for the way this audience works</h2><p>{{ $data['intro'] }}</p></div><div class="card-grid">@foreach($data['challenges'] as $challenge)<article class="marketing-card"><span class="icon-box" aria-hidden="true">{{ $loop->iteration }}</span><h3>{{ $challenge }}</h3></article>@endforeach</div></div></section>
@php
$solutionVisuals = [
    'photographers' => ['solution-photographers','A professional photographer using LensPic to upload and organize a private wedding gallery.'],
    'weddings' => ['solution-weddings','A wedding photographer presenting a private wedding gallery across laptop and phone.'],
    'celebrations' => ['solution-celebrations','A family viewing private birthday photographs with favourites and permitted downloads.'],
    'corporate' => ['solution-corporate','A corporate photographer delivering conference and networking photographs through controlled galleries.'],
    'institutions' => ['solution-institutions','An authorized photographer organizing graduation, sports and cultural programme photographs.'],
    'conferences' => ['solution-conferences','Conference and community attendees viewing an organized private gallery on tablet and phone.'],
];
$solutionVisual = $solutionVisuals[$key];
@endphp
<section class="marketing-section section-muted"><div class="marketing-container solution-split"><div><div class="section-heading left"><span class="eyebrow">How LensPic helps</span><h2>One controlled workflow from upload to delivery</h2></div><div class="space-y-4">@foreach($data['solutions'] as $item)<div class="solution-check"><span aria-hidden="true">✓</span><p>{{ $item }}</p></div>@endforeach</div></div><picture class="solution-photo"><source media="(max-width: 767px)" srcset="{{ asset('images/marketing/lenspic-'.$solutionVisual[0].'-mobile.png') }}"><img src="{{ asset('images/marketing/lenspic-'.$solutionVisual[0].'.png') }}" width="1400" height="933" loading="lazy" alt="{{ $solutionVisual[1] }}"></picture></div></section>
@include('marketing.partials.solution-features')
@include('marketing.partials.solution-workflow')
<section class="marketing-section section-muted"><div class="marketing-container solution-privacy"><div><span class="eyebrow">Privacy and permissions</span><h2>Member choice stays central</h2><p>{{ $data['privacy'] }}</p></div><a class="button button-secondary" href="{{ route('marketing.biometric-consent') }}">Review consent information</a></div></section>
@include('marketing.partials.solution-faq')
@include('marketing.partials.solution-cta')
@endsection
