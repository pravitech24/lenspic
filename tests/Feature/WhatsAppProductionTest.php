<?php

namespace Tests\Feature;

use App\Models\{OtpRequest, User, WhatsAppMessage};
use App\Services\{WhatsAppLinks, WhatsAppOtpSender};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{DB, Http};
use Tests\TestCase;

class WhatsAppProductionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['otp.driver'=>'whatsapp', 'otp.test_mode'=>true, 'otp.test_code'=>'654321',
            'otp.whatsapp'=>['graph_version'=>'v23.0','phone_number_id'=>'12345','access_token'=>'SECRET-TOKEN','template'=>'login_code','language'=>'en_US','copy_code_button'=>true],
            'whatsapp.app_secret'=>'test-secret','whatsapp.webhook_verify_token'=>'verify-token']);
        $this->app->bind(\App\Contracts\OtpSender::class, WhatsAppOtpSender::class);
        Http::preventStrayRequests();
    }

    public function test_acceptance_is_stored_and_repeated_delivery_is_a_noop(): void
    {
        Http::fake(['*'=>Http::response(['messages'=>[['id'=>'wamid.test']]])]);
        $sender=app(WhatsAppOtpSender::class);
        $sender->send('+919876543210','654321','challenge-1');
        $sender->send('+919876543210','654321','challenge-1');
        Http::assertSentCount(1);
        $message=WhatsAppMessage::sole();
        $this->assertSame('accepted',$message->status);
        $this->assertSame('wamid.test',$message->meta_message_id);
        $this->assertSame(1,$message->attempt_count);
        $raw=json_encode(DB::table('whatsapp_messages')->first());
        foreach (['654321','SECRET-TOKEN','919876543210'] as $secret) $this->assertStringNotContainsString($secret,$raw);
    }

    public function test_rejections_are_sanitized_and_never_retried(): void
    {
        foreach ([400,401,403,429,500,503] as $status) {
            Http::fake(['*'=>Http::response(['error'=>['code'=>190,'message'=>'SECRET-TOKEN 654321 +919876543210']],$status)]);
            for ($i=0;$i<2;$i++) {
                try { app(WhatsAppOtpSender::class)->send('+919876543210','654321','failure-'.$status); $this->fail('Expected rejection'); }
                catch (\RuntimeException $e) { $this->assertStringNotContainsString('SECRET-TOKEN',$e->getMessage()); }
            }
            Http::assertSentCount(1);
            $record=WhatsAppMessage::latest('id')->first();
            $this->assertSame('failed',$record->status);
            $this->assertSame('190',$record->provider_error_code);
            $this->assertSame('Meta rejected the authentication message.',$record->provider_error_message);
        }
    }

    public function test_timeout_remains_uncertain_and_cannot_be_replayed(): void
    {
        Http::fake(fn()=>throw new \Illuminate\Http\Client\ConnectionException('sensitive provider context'));
        for ($i=0;$i<2;$i++) {
            try { app(WhatsAppOtpSender::class)->send('+919876543210','654321','timeout'); $this->fail(); }
            catch (\RuntimeException $e) { $this->assertNull($e->getPrevious()); }
        }
        $record=WhatsAppMessage::sole();
        $this->assertSame('pending',$record->status);
        $this->assertSame(1,$record->attempt_count);
        $this->assertTrue($record->metadata['delivery_uncertain']);
    }

    public function test_missing_configuration_is_safe(): void
    {
        config(['otp.whatsapp.access_token'=>null]);
        $this->postJson('/send-otp',['channel'=>'mobile','country_code'=>'+91','phone'=>'9876543210'])->assertUnprocessable()->assertJsonValidationErrors('phone');
        Http::assertNothingSent();
        $this->assertSame('failed',WhatsAppMessage::sole()->status);
        $this->assertTrue(OtpRequest::sole()->expires_at->lessThanOrEqualTo(now()));
    }

    public function test_submission_cooldown_resend_and_consent(): void
    {
        Http::fakeSequence()->push(['messages'=>[['id'=>'wamid.first']]])->push(['messages'=>[['id'=>'wamid.second']]]);
        $payload=['channel'=>'mobile','country_code'=>'+91','phone'=>'9876543210'];
        $this->postJson('/send-otp',$payload)->assertOk();
        $first=OtpRequest::sole();
        $this->assertSame('whatsapp',$first->authentication_channel);
        $this->assertSame('whatsapp-auth-v1',$first->disclosure_version);
        $this->postJson('/send-otp',$payload)->assertUnprocessable();
        Http::assertSentCount(1);
        $this->travel(61)->seconds();
        $this->postJson('/send-otp',$payload)->assertOk();
        Http::assertSentCount(2);
        $this->assertTrue($first->fresh()->expires_at->lessThanOrEqualTo(now()));
        $this->assertNotSame($first->public_reference,OtpRequest::latest('id')->first()->public_reference);
        $this->postJson('/verify-otp',['otp'=>'654321'])->assertOk();
    }

    public function test_verification_attempts_are_limited(): void
    {
        Http::fake(['*'=>Http::response(['messages'=>[['id'=>'wamid.verify']]])]);
        $this->postJson('/send-otp',['country_code'=>'+91','phone'=>'9876543210'])->assertOk();
        for ($i=0;$i<5;$i++) $this->postJson('/verify-otp',['otp'=>'000000'])->assertUnprocessable();
        $this->postJson('/verify-otp',['otp'=>'654321'])->assertUnprocessable();
        $this->assertGuest();
    }

    public function test_phone_normalization(): void
    {
        $links=app(WhatsAppLinks::class);
        $this->assertSame('+919876543210',$links->normalize('98765 43210','+91'));
        $this->assertSame('+14155552671',$links->normalize('(415) 555-2671','+1'));
        $this->assertSame('+442079460018',$links->normalize('+44 20 7946 0018'));
        $this->assertNull($links->contact('123','+91','Hello'));
        config(['otp.supported_regions'=>['IN']]);
        $this->assertNull($links->contact('4155552671','+1','Hello'));
    }

    private function webhook(string $status, string $id='wamid.test', ?int $timestamp=null, bool $valid=true)
    {
        $raw=json_encode(['object'=>'whatsapp_business_account','entry'=>[['changes'=>[['field'=>'messages','value'=>['metadata'=>['phone_number_id'=>'12345'],'statuses'=>[['id'=>$id,'status'=>$status,'timestamp'=>(string)($timestamp??now()->timestamp),'recipient_id'=>'919876543210','errors'=>[['code'=>131026,'message'=>'sensitive']]]]]]]]]]);
        return $this->call('POST','/webhooks/whatsapp',[],[],[],['CONTENT_TYPE'=>'application/json','HTTP_X_HUB_SIGNATURE_256'=>$valid?'sha256='.hash_hmac('sha256',$raw,'test-secret'):'invalid'],$raw);
    }

    public function test_webhook_verification_and_signature(): void
    {
        $this->get('/webhooks/whatsapp?hub.mode=subscribe&hub.verify_token=verify-token&hub.challenge=123456')->assertOk()->assertSee('123456');
        $this->get('/webhooks/whatsapp?hub.mode=subscribe&hub.verify_token=wrong&hub.challenge=123456')->assertForbidden();
        $this->webhook('sent',valid:false)->assertForbidden();
        $this->assertDatabaseCount('whatsapp_webhook_events',0);
    }

    public function test_webhooks_are_idempotent_and_cannot_downgrade_delivery(): void
    {
        Http::fake(['*'=>Http::response(['messages'=>[['id'=>'wamid.test']]])]);
        app(WhatsAppOtpSender::class)->send('+919876543210','654321','webhook');
        foreach (['delivered','sent','delivered','read','failed','sent'] as $status) $this->webhook($status)->assertNoContent();
        $this->assertSame('read',WhatsAppMessage::sole()->status);
        $this->assertDatabaseCount('whatsapp_webhook_events',4);
        $this->assertSame(0,DB::table('whatsapp_webhook_events')->whereNull('processed_at')->count());
        $raw=json_encode(DB::table('whatsapp_webhook_events')->get());
        $this->assertStringNotContainsString('919876543210',$raw);
        $this->assertStringNotContainsString('sensitive',$raw);
    }

    public function test_status_arriving_before_acceptance_is_reconciled(): void
    {
        $this->webhook('delivered')->assertNoContent();
        Http::fake(['*'=>Http::response(['messages'=>[['id'=>'wamid.test']]])]);
        app(WhatsAppOtpSender::class)->send('+919876543210','654321','race');
        $this->assertSame('delivered',WhatsAppMessage::sole()->status);
    }

    public function test_support_is_only_rendered_with_valid_configuration(): void
    {
        $this->get('/pricing')->assertOk()->assertDontSee('24x7 WhatsApp')->assertDontSee('https://wa.me/');
        config(['whatsapp.support_enabled'=>true,'whatsapp.support_number'=>'9876543210']);
        $this->get('/pricing')->assertOk()->assertSee('https://wa.me/919876543210',false);
        config(['whatsapp.support_number'=>'123']);
        $this->get('/pricing')->assertDontSee('https://wa.me/');
    }

    public function test_delivery_administration_is_masked_and_role_protected(): void
    {
        app(WhatsAppOtpSender::class)->reserve('+919876543210','admin');
        foreach (['photographer','user','super_admin'] as $role) {
            $user=User::create(['name'=>'Test','email'=>$role.'@test.local','password'=>bcrypt('password'),'role'=>$role==='super_admin'?'super_admin':'user','account_type'=>$role==='photographer'?'photographer':'user','status'=>'active']);
            $response=$this->actingAs($user)->get('/super-admin/whatsapp-deliveries');
            if ($role==='super_admin') $response->assertOk()->assertSee('••••••3210')->assertDontSee('919876543210')->assertDontSee('SECRET-TOKEN');
            else $response->assertForbidden();
        }
    }
    public function test_request_identity_prevents_resending_even_after_cooldown(): void
    {
        Http::fake(['*'=>Http::response(['messages'=>[['id'=>'wamid.identity']]])]);
        $payload=['request_id'=>(string) \Illuminate\Support\Str::uuid(),'country_code'=>'+91','phone'=>'9876543210'];
        $this->postJson('/send-otp',$payload)->assertOk();
        $this->travel(61)->seconds();
        $this->postJson('/send-otp',$payload)->assertOk();
        Http::assertSentCount(1);
        $this->assertDatabaseCount('otp_requests',1);
    }

    public function test_public_portfolio_contact_requires_visibility_and_valid_number(): void
    {
        $owner=User::create(['name'=>'Studio','email'=>'portfolio-wa@test.local','password'=>bcrypt('password'),'account_type'=>'photographer']);
        $portfolio=\App\Models\Portfolio::create(['studio_owner_id'=>$owner->id,'slug'=>'wa-studio','name'=>'Studio','status'=>'published','currency_code'=>'INR']);
        $branding=$owner->businessBranding()->create(['whatsapp_country_code'=>'+91','whatsapp_phone_number'=>'9876543210','show_whatsapp_in_portfolio'=>true]);
        $this->get(route('portfolio.show',$portfolio))->assertOk()->assertSee('Chat on WhatsApp')->assertSee('https://wa.me/919876543210',false);
        $branding->update(['show_whatsapp_in_portfolio'=>false]);
        $this->get(route('portfolio.show',$portfolio))->assertDontSee('Chat on WhatsApp')->assertDontSee('9876543210');
        $branding->update(['show_whatsapp_in_portfolio'=>true,'whatsapp_phone_number'=>'123']);
        $this->get(route('portfolio.show',$portfolio))->assertDontSee('Chat on WhatsApp');
    }

    public function test_group_share_endpoint_enforces_owner_permissions_and_opaque_url(): void
    {
        $owner=User::create(['name'=>'Owner','email'=>'share-owner@test.local','password'=>bcrypt('password'),'account_type'=>'photographer','status'=>'active','plan'=>'standard']);
        $member=User::create(['name'=>'Member','email'=>'share-member@test.local','password'=>bcrypt('password'),'account_type'=>'user','status'=>'active']);
        $group=\App\Models\Group::create(['name'=>'Private Wedding','creator_id'=>$owner->id,'is_active'=>true]);
        $group->members()->attach($member->id,['role'=>'member','membership_status'=>'active','access_type'=>'full_access']);
        $this->actingAs($member)->postJson(route('groups.share-link',$group))->assertForbidden();
        $response=$this->actingAs($owner)->postJson(route('groups.share-link',$group))->assertOk();
        $url=$response->json('invitation.share_url');
        $this->assertNotEmpty($url);
        $this->assertStringNotContainsString('/groups/'.$group->id,$url);
        $this->assertStringNotContainsString('Signature=',$url);
        $this->assertSame($group->accessInvites()->first()->url,$url);
    }

    public function test_notification_placeholder_cannot_be_enabled(): void
    {
        $owner=User::create(['name'=>'Owner','email'=>'preferences-wa@test.local','password'=>bcrypt('password'),'account_type'=>'photographer','status'=>'active','plan'=>'standard']);
        $this->actingAs($owner)->put('/settings/account-preferences',['upload_quality_preference'=>'standard','post_transfer_action'=>'none','whatsapp_notifications'=>true])->assertSessionHasNoErrors();
        $this->assertFalse($owner->fresh()->meta['preferences']['whatsapp_notifications']);
        $this->assertDatabaseCount('wallet_ledger_entries',0);
    }

    public function test_wallet_cannot_reserve_whatsapp_credits(): void
    {
        $owner=User::create(['name'=>'Owner','email'=>'wallet-wa@test.local','password'=>bcrypt('password')]);
        $wallet=app(\App\Services\Wallet\WalletService::class)->for($owner);
        $wallet->update(['available_credit_units'=>100]);
        try { app(\App\Services\Wallet\WalletService::class)->reserve($wallet,10,'whatsapp_notification','test','test','wa-test'); $this->fail(); }
        catch (\Illuminate\Validation\ValidationException) { $this->assertSame(100,$wallet->fresh()->available_credit_units); }
        $this->assertDatabaseCount('wallet_reservations',0);
    }

    public function test_failed_request_retains_cooldown_and_invalidates_challenge(): void
    {
        Http::fake(['*'=>Http::response(['error'=>['code'=>190]],401)]);
        $payload=['country_code'=>'+91','phone'=>'9876543210'];
        $this->postJson('/send-otp',$payload)->assertUnprocessable();
        $this->postJson('/send-otp',$payload)->assertUnprocessable();
        Http::assertSentCount(1);
        $this->assertTrue(OtpRequest::sole()->expires_at->lessThanOrEqualTo(now()));
        $this->postJson('/verify-otp',['otp'=>'654321'])->assertUnprocessable();
    }

    public function test_missing_message_id_is_uncertain_and_is_not_retried(): void
    {
        Http::fake(['*'=>Http::response(['success'=>true])]);
        for ($i=0;$i<2;$i++) {
            try { app(WhatsAppOtpSender::class)->send('+919876543210','654321','missing-id'); $this->fail(); }
            catch (\RuntimeException) {}
        }
        Http::assertSentCount(1);
        $this->assertTrue(WhatsAppMessage::sole()->metadata['delivery_uncertain']);
    }

    public function test_duplicate_while_delivery_is_in_flight_does_not_invalidate_or_send(): void
    {
        $payload=['request_id'=>(string) \Illuminate\Support\Str::uuid(),'country_code'=>'+91','phone'=>'9876543210'];
        Http::fake(function () use ($payload) {
            $this->postJson('/send-otp',$payload)->assertUnprocessable();
            $this->assertTrue(OtpRequest::sole()->expires_at->isFuture());
            return Http::response(['messages'=>[['id'=>'wamid.in-flight']]]);
        });
        $this->postJson('/send-otp',$payload)->assertOk();
        Http::assertSentCount(1);
        $this->assertSame('accepted',WhatsAppMessage::sole()->status);
        $this->assertTrue(OtpRequest::sole()->expires_at->isFuture());
    }

}
