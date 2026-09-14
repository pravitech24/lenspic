@extends('layouts.marketing')
@section('content')
<section class="page-hero"><div class="marketing-container text-center"><span class="eyebrow-pill">Plans and pricing</span><h1 class="mx-auto">{{ $title }}</h1><p class="mx-auto">{{ $description }}</p>@include('components.whatsapp-support')</div></section>
<section class="marketing-section"><div class="marketing-container">
    <div class="toggle-group" aria-label="Billing cycle"><button type="button" data-plan-toggle="quarterly" aria-pressed="true">Quarterly</button><button type="button" data-plan-toggle="yearly" aria-pressed="false">Yearly</button></div>
    <div class="price-grid">
        @foreach($plans as $plan)
        <article class="price-card">
            <div class="flex flex-1 flex-col">
                <h2 class="text-xl font-bold">{{ $plan['name'] }}</h2><p class="text-sm text-slate-500">{{ $plan['description'] }}</p>
                @foreach($plan['cycles'] as $cycle)
                <div data-plan-price="{{ $cycle['cycle'] }}" @if($cycle['cycle']!=='quarterly') hidden @endif>
                    <div class="price-value">₹{{ number_format($cycle['base_amount']/100, 2) }} <small>+ GST</small></div>
                    <p class="text-sm text-slate-500">₹{{ number_format($cycle['base_amount']/100,2) }} base + ₹{{ number_format($cycle['gst_amount']/100,2) }} GST · /{{ $cycle['cycle']==='yearly'?'year':'quarter' }}</p>
                    <p class="text-sm font-bold">Total ₹{{ number_format($cycle['total_amount']/100,2) }}</p>
                </div>
                @endforeach
                <ul class="my-6">
                    <li class="plan-feature"><span class="plan-feature__icon">@include('marketing.partials.plan-feature-icon',['name'=>'images'])</span><strong class="plan-feature__text">Store up to {{ number_format($plan['entitlements']['photo_limit']) }} photos</strong></li>
                    @if($plan['entitlements']['video_storage_limit_mb'] > 0)<li class="plan-feature"><span class="plan-feature__icon">@include('marketing.partials.plan-feature-icon',['name'=>'video'])</span><span class="plan-feature__text">Store up to {{ number_format($plan['entitlements']['video_storage_limit_mb']/1000) }} GB of videos</span></li>@endif
                    @if($plan['entitlements']['photo_delete_reupload_limit'] > $plan['entitlements']['photo_limit'])<li class="plan-feature"><span class="plan-feature__icon">@include('marketing.partials.plan-feature-icon',['name'=>'refresh'])</span><span class="plan-feature__text">Reuse up to {{ number_format($plan['entitlements']['photo_delete_reupload_limit']) }} photos</span></li>@endif
                    @foreach($plan['features'] as $feature)<li class="plan-feature"><span class="plan-feature__icon">@include('marketing.partials.plan-feature-icon',['name'=>$feature['icon']])</span><span class="plan-feature__text">{{ $feature['label'] }}</span></li>@endforeach
                </ul>
                @foreach($plan['optional_addons'] as $addon)<div class="plan-addon"><span class="plan-feature__icon">@include('marketing.partials.plan-feature-icon',['name'=>'plus-circle'])</span><span><strong>Optional add-on</strong>{{ $addon['name'] }} — ₹{{ number_format($addon['base_amount']/100, 2) }} + GST</span></div>@endforeach
            </div>
            <div class="mt-auto pt-6">@auth<a class="button button-primary w-full" href="{{ route('settings.subscription') }}">@if(auth()->user()->activeSubscription?->isActive() && auth()->user()->activeSubscription->plan===$plan['code'])<span class="plan-action-icon">@include('marketing.partials.plan-feature-icon',['name'=>'check-circle'])</span> Current Plan @else Get Started @endif</a>@else<a class="button button-primary w-full" href="{{ route('register') }}">Get Started</a>@endauth</div>
        </article>
        @endforeach
    </div>
    <p class="mt-8 text-center text-sm text-slate-500">Payments are processed in INR through secure checkout. Review our <a class="font-bold text-indigo" href="{{ route('marketing.refunds') }}">refund policy</a>.</p>
</div></section>
@endsection
