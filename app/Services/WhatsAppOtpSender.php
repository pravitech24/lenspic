<?php

namespace App\Services;

use App\Contracts\OtpSender;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use App\Models\WhatsAppMessage;
use Illuminate\Support\Str;

class WhatsAppOtpSender implements OtpSender
{
    public function send(string $mobileE164, string $code, ?string $challengeReference = null): void
    {
        if (!$challengeReference) throw new RuntimeException('An OTP challenge reference is required.');
        $message = $this->reserve($mobileE164, $challengeReference);
        try { $this->validateConfiguration(); }
        catch (RuntimeException $exception) {
            if (!$message->accepted_at) $message->update(['status'=>'failed','failed_at'=>now(),'provider_error_message'=>$exception->getMessage()]);
            throw $exception;
        }
        $settings = config('otp.whatsapp');
        if ($message->accepted_at) return;
        // Commit the claim before network I/O. Never replay an ambiguous POST.
        if (!WhatsAppMessage::whereKey($message->id)->where('attempt_count', 0)->update(['attempt_count'=>1, 'last_attempted_at'=>now()])) {
            throw new RuntimeException('Verification delivery was already attempted. Request a new code after the cooldown.');
        }

        $components = [[
            'type' => 'body',
            'parameters' => [['type' => 'text', 'text' => $code]],
        ]];

        if ($settings['copy_code_button'] ?? true) {
            $components[] = [
                'type' => 'button',
                'sub_type' => 'url',
                'index' => '0',
                'parameters' => [['type' => 'text', 'text' => $code]],
            ];
        }

        try {
            $response = Http::asJson()
                ->withToken($settings['access_token'])
                ->connectTimeout(3)
                ->timeout(10)
                ->post(sprintf(
                    'https://graph.facebook.com/%s/%s/messages',
                    $settings['graph_version'],
                    $settings['phone_number_id']
                ), [
                    'messaging_product' => 'whatsapp',
                    'recipient_type' => 'individual',
                    'to' => ltrim($mobileE164, '+'),
                    'type' => 'template',
                    'template' => [
                        'name' => $settings['template'],
                        'language' => ['code' => $settings['language']],
                        'components' => $components,
                    ],
                ]);
        } catch (ConnectionException $exception) {
            $message->update(['metadata'=>['delivery_uncertain'=>true], 'provider_error_message'=>'Delivery acceptance could not be confirmed.']);
            throw new RuntimeException('Verification delivery acceptance could not be confirmed.');
        }

        if (!$response->successful()) {
            $errorCode = $response->json('error.code');
            $message->update(['status'=>'failed', 'failed_at'=>now(), 'provider_error_code'=>is_numeric($errorCode) ? (string)(int)$errorCode : null, 'provider_error_message'=>'Meta rejected the authentication message.']);
            throw new RuntimeException('Verification delivery is temporarily unavailable.');
        }
        $providerId = $response->json('messages.0.id');
        if (!is_string($providerId) || strlen($providerId) > 255 || !preg_match('/^[A-Za-z0-9._=:+\/-]+$/', $providerId)) {
            $message->update(['metadata'=>['delivery_uncertain'=>true], 'provider_error_message'=>'Delivery acceptance could not be confirmed.']);
            throw new RuntimeException('Verification delivery acceptance could not be confirmed.');
        }
        $message->update(['meta_message_id'=>$providerId, 'status'=>'accepted', 'accepted_at'=>now()]);
        app(WhatsAppStatusProcessor::class)->reconcile($providerId);
    }

    public function validateConfiguration(): void
    {
        foreach (['phone_number_id', 'access_token', 'template', 'language', 'graph_version'] as $key) {
            if (blank(config('otp.whatsapp.'.$key))) throw new RuntimeException("Meta WhatsApp setting [$key] is missing.");
        }
        foreach (['webhook_verify_token', 'app_secret'] as $key) {
            if (blank(config('whatsapp.'.$key))) throw new RuntimeException("Meta WhatsApp setting [$key] is missing.");
        }
        if (!preg_match('/^v[0-9]+\.[0-9]+$/', config('otp.whatsapp.graph_version')) || !ctype_digit((string) config('otp.whatsapp.phone_number_id'))) {
            throw new RuntimeException('Meta WhatsApp graph version or phone number ID is invalid.');
        }
    }

    public function reserve(string $mobileE164, string $challengeReference): WhatsAppMessage
    {
        return WhatsAppMessage::firstOrCreate(['idempotency_key'=>hash('sha256', 'authentication_otp:'.$challengeReference)], [
            'public_reference'=>(string) Str::uuid(),
            'recipient_hash'=>hash_hmac('sha256', $mobileE164, config('app.key')),
            'recipient_masked'=>'••••••'.substr($mobileE164, -4),
            'template'=>config('otp.whatsapp.template') ?: 'unconfigured',
            'language'=>config('otp.whatsapp.language') ?: 'unconfigured',
        ]);
    }
}
