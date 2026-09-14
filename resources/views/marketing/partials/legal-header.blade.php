@php
    $effectiveDate = \Carbon\CarbonImmutable::parse(config('marketing.legal.effective_date'))->format('j F Y');
    $lastUpdatedDate = \Carbon\CarbonImmutable::parse(config('marketing.legal.last_updated_date'))->format('j F Y');
@endphp
<header class="page-hero legal-hero"><div class="marketing-container"><span class="eyebrow-pill">LensPic policy</span><h1>{{ $title }}</h1><p>{{ $description }}</p><dl class="legal-dates"><div><dt>Effective date</dt><dd>{{ $effectiveDate }}</dd></div><div><dt>Last updated</dt><dd>{{ $lastUpdatedDate }}</dd></div></dl></div></header>
