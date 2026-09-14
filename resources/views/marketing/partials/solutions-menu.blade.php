<div class="solutions-menu" data-solutions-menu>
    <button class="solutions-trigger" type="button" aria-expanded="false" aria-controls="solutions-navigation" data-solutions-trigger>
        <span>Solutions &amp; Use Cases</span><svg aria-hidden="true" viewBox="0 0 20 20" fill="currentColor"><path d="m5.5 7.5 4.5 4.5 4.5-4.5 1.4 1.4-5.9 5.9-5.9-5.9z"/></svg>
    </button>
    <div id="solutions-navigation" class="solutions-panel" hidden data-solutions-panel>
        @foreach(config('marketing.solutions') as $solution)
        <a @class(['active'=>request()->routeIs($solution['route'])]) href="{{ route($solution['route']) }}">{{ $solution['nav'] }}</a>
        @endforeach
    </div>
</div>
