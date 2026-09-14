<?php

namespace App\Http\Controllers;

use App\Services\WhatsAppStatusProcessor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class WhatsAppWebhookController extends Controller
{
    public function verify(Request $request)
    {
        $token = (string) config('whatsapp.webhook_verify_token');
        abort_unless($token !== '' && $request->query('hub_mode') === 'subscribe' && hash_equals($token, (string) $request->query('hub_verify_token')), 403);
        $challenge = $request->query('hub_challenge');
        abort_unless(is_string($challenge) && preg_match('/^\d{1,255}$/', $challenge), 400);
        return response($challenge, 200)->header('Content-Type', 'text/plain');
    }

    public function receive(Request $request, WhatsAppStatusProcessor $processor)
    {
        $raw = $request->getContent();
        abort_if(strlen($raw) > 1048576, 413);
        $secret = (string) config('whatsapp.app_secret');
        abort_unless($secret !== '' && hash_equals('sha256='.hash_hmac('sha256', $raw, $secret), (string) $request->header('X-Hub-Signature-256')), 403);
        $payload = json_decode($raw, true);
        abort_unless(is_array($payload), 400);
        if (($payload['object'] ?? null) !== 'whatsapp_business_account') return response()->noContent();
        $ids = [];
        foreach (($payload['entry'] ?? []) as $entry) foreach (($entry['changes'] ?? []) as $change) {
            if (($change['field'] ?? null) !== 'messages' || (string) data_get($change, 'value.metadata.phone_number_id') !== (string) config('otp.whatsapp.phone_number_id')) continue;
            foreach (($change['value']['statuses'] ?? []) as $event) {
                $id = $event['id'] ?? null;
                $status = $event['status'] ?? null;
                $timestamp = $event['timestamp'] ?? null;
                if (!is_string($id) || strlen($id)>255 || !preg_match('/^[A-Za-z0-9._=:+\/-]+$/', $id) || !in_array($status, ['accepted','sent','delivered','read','failed'], true) || !ctype_digit((string)$timestamp) || (int)$timestamp<1 || (int)$timestamp>now()->addDay()->timestamp) continue;
                $code = data_get($event, 'errors.0.code');
                DB::table('whatsapp_webhook_events')->insertOrIgnore([
                    'event_hash'=>hash('sha256', $id.'|'.$status.'|'.$timestamp),
                    'meta_message_id'=>$id, 'event_type'=>$status,
                    'occurred_at'=>Carbon::createFromTimestampUTC((int)$timestamp),
                    'provider_error_code'=>is_numeric($code) ? (string)(int)$code : null,
                    'received_at'=>now(),
                ]);
                $ids[$id] = true;
            }
        }
        foreach (array_keys($ids) as $id) $processor->reconcile($id);
        return response()->noContent();
    }
}
