<?php

namespace Tests\Feature;

use App\Models\{OtpRequest, User};
use App\Services\Otp\{PhoneNumberNormalizer, PrivateTwilioTransport, SmsOtpChallenges, TwilioVerifyOtpProvider};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{DB, Http, Log, Mail, RateLimiter};
use Illuminate\Support\Str;
use Tests\Fakes\TwilioHttpClient;
use Tests\TestCase;

class TwilioVerifyOtpTest extends TestCase
{
    use RefreshDatabase;
    private TwilioHttpClient $twilio;

    protected function setUp(): void
    {
        parent::setUp();
        config(['otp.default_channel'=>'sms','services.twilio.account_sid'=>'AC'.str_repeat('a',32),
            'services.twilio.auth_token'=>'test-secret-do-not-log','services.twilio.verify_service_sid'=>'VA'.str_repeat('b',32),
            'services.twilio.channel'=>'sms','services.twilio.locale'=>'en','services.twilio.resend_seconds'=>60,
            'services.twilio.expiry_minutes'=>10,'services.twilio.max_attempts'=>5]);
        $this->twilio = new TwilioHttpClient();
        $this->app->instance(\Twilio\Http\Client::class, $this->twilio);
        Http::preventStrayRequests();
        Mail::fake();
    }

    private function payload(array $extra=[]): array
    {
        return array_replace(['channel'=>'sms','country_code'=>'+91','phone'=>'9876543210','request_id'=>(string)Str::uuid()],$extra);
    }

    private function start(array $extra=[]): void
    {
        $this->twilio->queue('pending');
        $this->postJson('/send-otp',$this->payload($extra))->assertOk()->assertJson(['message'=>'Verification code sent successfully.']);
    }

    public function test_sdk_uses_verify_sms_with_e164_and_no_locally_generated_code(): void
    {
        Log::spy();
        $this->start();
        $request=$this->twilio->requests[0];
        $this->assertSame('POST',$request['method']);
        $this->assertSame('https://verify.twilio.com/v2/Services/VA'.str_repeat('b',32).'/Verifications',$request['url']);
        $this->assertSame(['To'=>'+919876543210','Channel'=>'sms','Locale'=>'en'],$request['data']);
        $otp=OtpRequest::sole();
        $this->assertNull($otp->otp_hash);
        $this->assertSame('twilio_verify',$otp->provider);
        $this->assertSame('accepted',$otp->delivery_status);
        $this->assertSame('******3210',$otp->destination_masked);
        $this->assertNull($otp->disclosure_version);
        Log::shouldHaveReceived('info')->with('SMS verification send', \Mockery::on(fn($data)=>$data['destination']==='******3210' && !str_contains(json_encode($data),'9876543210') && !str_contains(json_encode($data),'test-secret')))->once();
        $this->assertStringNotContainsString('test-secret',json_encode(DB::table('otp_requests')->first()));
        $this->assertDatabaseCount('whatsapp_messages',0);
    }

    public function test_indian_and_international_normalization_and_malformed_rejection(): void
    {
        $normalizer=app(PhoneNumberNormalizer::class);
        $this->assertSame('+919876543210',$normalizer->normalize('9876543210'));
        $this->assertSame('+14155552671',$normalizer->normalize('(415) 555-2671','+1'));
        $this->assertSame('+442079460018',$normalizer->normalize('+44 20 7946 0018','+91'));
        $this->start(['phone'=>'+44 20 7946 0018']);
        $this->assertSame('+442079460018',$this->twilio->requests[0]['data']['To']);
        $this->assertSame('+44',session('otp.country_code'));
        foreach (['123','++919876543210','98765abc10','+9999876543210'] as $phone) {
            $this->postJson('/send-otp',$this->payload(['phone'=>$phone]))->assertUnprocessable();
        }
        $this->assertCount(1,$this->twilio->requests);
    }

    public function test_resend_cooldown_and_request_identity(): void
    {
        $payload=$this->payload();
        $this->twilio->queue('pending');
        $this->postJson('/send-otp',$payload)->assertOk();
        $this->postJson('/send-otp',$payload)->assertOk();
        $this->postJson('/send-otp',$this->payload())->assertUnprocessable();
        $first=OtpRequest::sole();
        $this->travel(61)->seconds();
        $this->postJson('/send-otp',$payload)->assertOk();
        $this->assertCount(1,$this->twilio->requests);
        $this->twilio->queue('pending');
        $this->postJson('/send-otp',['resend'=>true,'request_id'=>(string)Str::uuid(),'phone'=>'+14155552671'])->assertOk();
        $this->assertSame('+919876543210',$this->twilio->requests[1]['data']['To']);
        $this->assertTrue($first->fresh()->expires_at->lessThanOrEqualTo(now()));
    }

    public function test_only_twilio_approval_completes_phone_authentication_and_cannot_be_reused(): void
    {
        $this->start();
        $this->twilio->queue('approved');
        $this->postJson('/verify-otp',['otp'=>'741852'])->assertOk()->assertJsonStructure(['redirect']);
        $this->assertAuthenticated();
        $this->assertSame('+919876543210',auth()->user()->mobile_e164);
        $this->assertNotNull(auth()->user()->phone_verified_at);
        $this->assertNotNull(OtpRequest::sole()->verified_at);
        $this->assertEquals(['To'=>'+919876543210','Code'=>'741852'],$this->twilio->requests[1]['data']);
        $this->assertStringEndsWith('/VerificationCheck',$this->twilio->requests[1]['url']);
        $this->assertStringNotContainsString('741852',json_encode(DB::table('otp_requests')->first()));
        auth()->logout();
        $this->postJson('/verify-otp',['otp'=>'741852'])->assertUnprocessable();
        $this->assertCount(2,$this->twilio->requests);
    }

    public function test_invalid_expired_and_non_approved_statuses_never_authenticate(): void
    {
        $this->start();
        foreach (['pending','expired','failed'] as $status) {
            $this->twilio->queue($status);
            $this->postJson('/verify-otp',['otp'=>'741852'])->assertUnprocessable()->assertJsonValidationErrors('otp')->assertJsonFragment([SmsOtpChallenges::INVALID]);
            $this->assertGuest();
        }
        $this->twilio->responses[]=[404,['code'=>20404,'message'=>'sensitive +919876543210 test-secret-do-not-log']];
        $this->postJson('/verify-otp',['otp'=>'741852'])->assertUnprocessable()->assertDontSee('test-secret');
        $this->assertNull(OtpRequest::sole()->verified_at);
    }

    public function test_attempt_limit_and_local_expiry_prevent_provider_calls(): void
    {
        $this->start();
        for ($i=0;$i<5;$i++) {
            $this->twilio->queue('pending');
            $this->postJson('/verify-otp',['otp'=>'741852'])->assertUnprocessable();
        }
        $this->postJson('/verify-otp',['otp'=>'741852'])->assertUnprocessable();
        $this->assertCount(6,$this->twilio->requests);
        $this->assertSame(5,OtpRequest::sole()->attempts);
        $this->travel(11)->minutes();
        $this->postJson('/verify-otp',['otp'=>'741852'])->assertUnprocessable();
        $this->assertCount(6,$this->twilio->requests);
    }

    public function test_missing_configuration_has_safe_alternative_message(): void
    {
        config(['services.twilio.auth_token'=>null]);
        $this->postJson('/send-otp',$this->payload())->assertUnprocessable()->assertJsonFragment([SmsOtpChallenges::UNAVAILABLE]);
        $this->assertCount(0,$this->twilio->requests);
        $this->assertSame('configuration',OtpRequest::sole()->failure_category);
    }

    public function test_sdk_errors_are_allowlisted_and_sanitized(): void
    {
        $cases=[[401,20003,'authentication'],[400,21608,'trial_recipient'],[400,21408,'geographic_or_policy'],[429,60203,'rate_limited'],[400,20005,'billing_or_account'],[503,60613,'provider_unavailable']];
        foreach ($cases as [$http,$code,$category]) {
            $this->twilio->responses[]=[$http,['code'=>$code,'message'=>'test-secret-do-not-log +919876543210 741852']];
            $result=app(TwilioVerifyOtpProvider::class)->send('+919876543210');
            $this->assertFalse($result->accepted);
            $this->assertSame($category,$result->failureCategory);
            $this->assertStringNotContainsString('test-secret',json_encode($result));
        }
    }

    public function test_timeout_does_not_retry_and_keeps_cooldown(): void
    {
        $this->twilio->responses[]=new \Twilio\Exceptions\EnvironmentException('timeout with test-secret-do-not-log');
        $this->postJson('/send-otp',$this->payload())->assertUnprocessable()->assertDontSee('test-secret');
        $this->postJson('/send-otp',$this->payload())->assertUnprocessable();
        $this->assertCount(1,$this->twilio->requests);
        $this->assertSame('connection_uncertain',OtpRequest::sole()->failure_category);
    }

    public function test_hourly_phone_limit_and_ip_route_limit(): void
    {
        for ($i=0;$i<5;$i++) { $this->start(); $this->travel(61)->seconds(); }
        $this->postJson('/send-otp',$this->payload())->assertUnprocessable();
        $this->assertCount(5,$this->twilio->requests);
        for ($i=0;$i<9;$i++) $this->postJson('/send-otp',$this->payload());
        $this->postJson('/send-otp',$this->payload())->assertTooManyRequests();
        $this->assertCount(5,$this->twilio->requests);
    }

    public function test_session_hourly_limit(): void
    {
        // Use the active browser session established by a successful request.
        $this->start();
        $hash=hash_hmac('sha256',session('otp.browser_key'),config('app.key'));
        for ($i=0;$i<10;$i++) RateLimiter::hit('sms-hour:session:'.$hash,3600);
        $this->travel(61)->seconds();
        $this->postJson('/send-otp',$this->payload())->assertUnprocessable();
        $this->assertCount(1,$this->twilio->requests);
    }

    public function test_no_session_and_foreign_session_cannot_verify_or_resend(): void
    {
        $this->get('/verify-otp')->assertForbidden();
        $this->postJson('/send-otp',['resend'=>true])->assertForbidden();
        $this->postJson('/verify-otp',['otp'=>'741852'])->assertUnprocessable();
        $this->start();
        OtpRequest::sole()->update(['session_hash'=>str_repeat('f',64)]);
        $this->get('/verify-otp')->assertForbidden();
        $this->postJson('/verify-otp',['otp'=>'741852'])->assertUnprocessable();
        $this->assertCount(1,$this->twilio->requests);
    }

    public function test_method_allowlist_code_validation_and_masked_ui(): void
    {
        $this->postJson('/send-otp',$this->payload(['channel'=>'call']))->assertUnprocessable();
        $this->start();
        $this->get('/verify-otp')->assertOk()->assertSee('******3210')->assertDontSee('9876543210')->assertSee('Resend in');
        foreach (['abc123','12345','1234567'] as $code) $this->postJson('/verify-otp',['otp'=>$code])->assertUnprocessable();
        $this->assertCount(1,$this->twilio->requests);
    }

    public function test_email_remains_local_and_never_uses_twilio(): void
    {
        config(['otp.test_mode'=>true,'otp.test_code'=>'741852']);
        $this->postJson('/send-otp',['channel'=>'email','email'=>'email-otp@test.local'])->assertOk();
        Mail::assertSent(\App\Mail\EmailOtpMail::class);
        $this->postJson('/verify-otp',['otp'=>'741852'])->assertOk();
        $this->assertAuthenticated();
        $this->assertCount(0,$this->twilio->requests);
    }

    public function test_whatsapp_remains_meta_and_never_uses_twilio(): void
    {
        config(['otp.whatsapp.phone_number_id'=>'12345','otp.whatsapp.access_token'=>'fake-meta-token','otp.whatsapp.template'=>'login_code','whatsapp.app_secret'=>'fake-secret','whatsapp.webhook_verify_token'=>'fake-verify','otp.test_mode'=>true,'otp.test_code'=>'741852']);
        Http::fake(['graph.facebook.com/*'=>Http::response(['messages'=>[['id'=>'wamid.sms-regression']]])]);
        $this->postJson('/send-otp',$this->payload(['channel'=>'whatsapp']))->assertOk();
        $this->postJson('/verify-otp',['otp'=>'741852'])->assertOk();
        Http::assertSentCount(1);
        $this->assertCount(0,$this->twilio->requests);
    }

    public function test_real_sms_transport_is_disabled_in_tests(): void
    {
        $this->expectException(\Twilio\Exceptions\EnvironmentException::class);
        app(PrivateTwilioTransport::class)->request('POST','https://verify.twilio.com');
    }
    public function test_duplicate_send_in_flight_cannot_contact_twilio_twice(): void
    {
        $payload=$this->payload();
        $this->twilio->responses[]=function () use ($payload) {
            $this->postJson('/send-otp',$payload)->assertUnprocessable();
            return [200,['status'=>'pending']];
        };
        $this->postJson('/send-otp',$payload)->assertOk();
        $this->assertCount(1,$this->twilio->requests);
        $this->assertDatabaseCount('otp_requests',1);
    }

    public function test_concurrent_verification_can_only_claim_one_check(): void
    {
        $this->start();
        $this->twilio->responses[]=function () {
            $this->postJson('/verify-otp',['otp'=>'741852'])->assertUnprocessable();
            return [200,['status'=>'approved']];
        };
        $this->postJson('/verify-otp',['otp'=>'741852'])->assertOk();
        $this->assertCount(2,$this->twilio->requests);
        $this->assertAuthenticated();
    }

    public function test_existing_group_member_keeps_the_group_join_flow_after_sms(): void
    {
        User::create(['name'=>'Member','mobile_e164'=>'+919876543210','phone'=>'+919876543210','password'=>bcrypt('password'),'account_type'=>'user','status'=>'active','onboarding_completed_at'=>now()]);
        $this->withSession(['validated_group_invitation'=>['code'=>'test-only']]);
        $this->start();
        $this->twilio->queue('approved');
        $this->postJson('/verify-otp',['otp'=>'741852'])->assertOk()->assertJson(['redirect'=>route('groups.join.complete')]);
    }

    public function test_production_http_is_rejected_before_contacting_twilio(): void
    {
        $this->app->instance('env','production');
        $this->withSession(['_token'=>'test-csrf-token'])->postJson('/send-otp',$this->payload(['_token'=>'test-csrf-token']))->assertStatus(400);
        $this->assertCount(0,$this->twilio->requests);
    }

    public function test_otp_is_not_flashed_to_session_on_validation_failure(): void
    {
        $this->start();
        $this->from('/verify-otp')->post('/verify-otp',['otp'=>'secret-not-a-code'])->assertRedirect('/verify-otp');
        $this->assertNull(session('_old_input.otp'));
        $this->assertCount(1,$this->twilio->requests);
    }

    public function test_browser_sms_requests_require_csrf(): void
    {
        $this->app->instance('env','local');
        $this->postJson('/send-otp',$this->payload())->assertStatus(419);
        $this->assertCount(0,$this->twilio->requests);
    }

}
