<?php

namespace Tests\Feature;

use App\Services\WhatsAppOtpSender;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WhatsAppOtpSenderTest extends TestCase
{
    use \Illuminate\Foundation\Testing\RefreshDatabase;
    public function test_it_sends_a_meta_whatsapp_authentication_template(): void
    {
        config(['whatsapp.app_secret'=>'test-secret','whatsapp.webhook_verify_token'=>'test-verify']);
        config()->set('otp.whatsapp', [
            'graph_version' => 'v23.0',
            'phone_number_id' => '12345',
            'access_token' => 'test-token',
            'template' => 'login_code',
            'language' => 'en_US',
            'copy_code_button' => true,
        ]);
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'message-id']]])]);

        app(WhatsAppOtpSender::class)->send('+919876543210', '123456', 'challenge-reference');

        Http::assertSent(fn ($request) =>
            $request->url() === 'https://graph.facebook.com/v23.0/12345/messages'
            && $request->hasHeader('Authorization', 'Bearer test-token')
            && $request['to'] === '919876543210'
            && $request['template']['name'] === 'login_code'
            && $request['template']['components'][0]['parameters'][0]['text'] === '123456'
        );
    }
}
