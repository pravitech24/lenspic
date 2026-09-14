<?php

namespace App\Services;

use App\Models\WhatsAppMessage;
use Illuminate\Support\Facades\DB;

class WhatsAppStatusProcessor
{
    public function reconcile(string $providerId): void
    {
        DB::transaction(function () use ($providerId) {
            $message = WhatsAppMessage::where('meta_message_id', $providerId)->lockForUpdate()->first();
            if (!$message) return; // Retained for the acceptance/write race; sender reconciles again.
            $events = DB::table('whatsapp_webhook_events')->where('meta_message_id', $providerId)->whereNull('processed_at')->orderBy('occurred_at')->orderBy('id')->get();
            $rank = ['pending'=>0, 'accepted'=>1, 'sent'=>2, 'failed'=>3, 'delivered'=>4, 'read'=>5];
            foreach ($events as $event) {
                $status = $event->event_type;
                $changes = [];
                if (!$message->{$status.'_at'}) $changes[$status.'_at'] = $event->occurred_at;
                if ($rank[$status] > $rank[$message->status]) $changes['status'] = $status;
                if ($status === 'failed') {
                    $changes['provider_error_code'] = $event->provider_error_code;
                    $changes['provider_error_message'] = 'Meta reported delivery failure.';
                }
                $message->update($changes);
                DB::table('whatsapp_webhook_events')->where('id', $event->id)->update(['processed_at'=>now()]);
            }
        });
    }
}
