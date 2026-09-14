@extends('super-admin.layout')
@section('title','WhatsApp deliveries')
@section('content')
<h1>WhatsApp delivery history</h1>
<p>Authentication messages only. Recipients are masked. Pending may indicate uncertain acceptance; never replay these messages.</p>
<div style="overflow-x:auto"><table class="table"><thead><tr><th>Reference / Created</th><th>Purpose / Recipient</th><th>Provider / Template</th><th>Status / Attempts</th><th>Timeline</th><th>Failure</th></tr></thead><tbody>
@forelse($messages as $message)
<tr><td>{{ $message->public_reference }}<br>{{ $message->created_at }}</td><td>{{ $message->purpose }}<br>{{ $message->recipient_masked }}</td><td>{{ $message->provider }}<br>{{ $message->template }} ({{ $message->language }})</td><td>{{ $message->status }}<br>{{ $message->attempt_count }}</td><td>@foreach(['accepted','sent','delivered','read','failed'] as $status)<div>{{ ucfirst($status) }}: {{ $message->{$status.'_at'} ?? '—' }}</div>@endforeach</td><td>{{ $message->provider_error_code }} {{ $message->provider_error_message }}</td></tr>
@empty<tr><td colspan="6">No WhatsApp messages yet.</td></tr>@endforelse
</tbody></table></div>
{{ $messages->links() }}
@endsection
