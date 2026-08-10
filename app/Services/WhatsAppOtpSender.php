<?php

namespace App\Services;

use App\Contracts\OtpSender;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class WhatsAppOtpSender implements OtpSender
{
    public function send(string $mobileE164, string $code): void
    {
        $settings = config('otp.whatsapp');
        foreach (['phone_number_id', 'access_token', 'template', 'language'] as $key) {
            if (blank($settings[$key] ?? null)) {
                throw new RuntimeException("Meta WhatsApp setting [$key] is missing.");
            }
        }

        $components = [[
            'type' => 'body',
            'parameters' => [['type' => 'text', 'text' => $code]],
        ]];

        if ($settings['copy_code_button']) {
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
                ->timeout(15)
                ->retry(2, 300)
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
            throw new RuntimeException('Could not connect to the Meta WhatsApp API.', previous: $exception);
        }

        if ($response->failed()) {
            $message = $response->json('error.message') ?: 'Meta WhatsApp rejected the OTP message.';
            throw new RuntimeException($message);
        }
    }
}
