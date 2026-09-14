@extends('layouts.marketing')
@section('content')
@include('marketing.partials.legal-header')
<section class="marketing-section"><div class="prose-public">
@foreach($policy['sections'] as $section)
<section aria-labelledby="policy-section-{{ $loop->iteration }}"><h2 id="policy-section-{{ $loop->iteration }}">{{ $section[0] }}</h2>
@if(is_array($section[1]))<ul>@foreach($section[1] as $item)<li>{{ $item }}</li>@endforeach</ul>@else<p>{{ $section[1] }}</p>@endif
</section>
@endforeach
@if($key==='biometric-consent')<div class="cta-row"><a class="button button-secondary" href="{{ route('marketing.privacy') }}">Privacy Policy</a><a class="button button-primary" href="{{ route('marketing.deletion') }}">Data Deletion</a></div>@elseif($key==='data-deletion')<div class="cta-row"><a class="button button-primary" href="{{ auth()->check()?route('dashboard'):route('login') }}">{{ auth()->check()?'Go to workspace':'Sign in' }}</a><a class="button button-secondary" href="{{ route('marketing.contact',['type'=>'technical_support']) }}">Contact support</a></div>@endif
</div></section>
@endsection
