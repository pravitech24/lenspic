@php
    $headerEyebrow = $eyebrow ?? 'LensPic';
    $headerTitle = $heading ?? $title;
    $headerDescription = $intro ?? $description;
    $headerAlign = in_array($align ?? 'left', ['left', 'center'], true) ? ($align ?? 'left') : 'left';
    $headerTheme = in_array($theme ?? 'default', ['default', 'soft', 'legal'], true) ? ($theme ?? 'default') : 'default';
    $headerActions = $actions ?? [];
    $headerVisual = $visual ?? null;
@endphp
<header class="inner-page-header inner-page-header-{{ $headerTheme }} text-{{ $headerAlign }}" data-inner-page-header>
    <div class="marketing-container">
        @if(!empty($breadcrumb))<nav class="inner-breadcrumb" aria-label="Breadcrumb"><a href="{{ route('home') }}">Home</a><span aria-hidden="true">/</span><span>{{ $breadcrumb }}</span></nav>@endif
        <div @class(['inner-header-layout','has-visual'=>(bool)$headerVisual])>
        <div class="inner-header-copy @if($headerAlign === 'center' && !$headerVisual) mx-auto @endif">
            <span class="inner-header-label">@if(!empty($icon))<span aria-hidden="true">{{ $icon }}</span>@endif {{ $headerEyebrow }}</span>
            <h1>{{ $headerTitle }}</h1>
            <p>{{ $headerDescription }}</p>
            @if(count($headerActions))<div class="cta-row @if($headerAlign === 'center') justify-center @endif">@foreach($headerActions as $action)<a class="button {{ $loop->first ? 'button-primary' : 'button-secondary' }}" href="{{ route($action[1]) }}">{{ $action[0] }}</a>@endforeach</div>@endif
        </div>
        @if($headerVisual)<figure class="inner-header-visual"><picture><source media="(max-width: 767px)" srcset="{{ asset('images/marketing/'.$headerVisual['mobile']) }}"><img src="{{ asset('images/marketing/'.$headerVisual['desktop']) }}" width="{{ $headerVisual['width'] }}" height="{{ $headerVisual['height'] }}" alt="{{ $headerVisual['alt'] }}" loading="eager" fetchpriority="high"></picture></figure>@endif
        </div>
    </div>
</header>
