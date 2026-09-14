@if($supportUrl=app(\App\Services\WhatsAppLinks::class)->support())
<p class="mt-5"><a class="button button-primary" href="{{ $supportUrl }}" target="_blank" rel="noopener noreferrer">WhatsApp Support</a><span class="mt-2 block text-sm text-slate-600">{{ config('whatsapp.support_availability') }}</span></p>
@else
<p class="mt-5"><a class="font-semibold text-indigo" href="{{ route('marketing.contact') }}">Contact LensPic Support</a></p>
@endif
