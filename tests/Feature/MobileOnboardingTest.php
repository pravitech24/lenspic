<?php
namespace Tests\Feature;

use App\Mail\EmailOtpMail;
use App\Models\OtpRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class MobileOnboardingTest extends TestCase
{
    use RefreshDatabase;
    protected function setUp(): void { parent::setUp(); Config::set('otp.test_mode',true); Config::set('otp.test_code','123456'); }

    public function test_valid_mobile_can_request_and_verify_otp(): void {
        $this->post('/send-otp',['country_code'=>'+91','phone'=>'98765 43210'])->assertRedirect('/verify-otp');
        $this->assertDatabaseHas('otp_requests',['mobile_e164'=>'+919876543210']);
        $this->post('/verify-otp',['otp'=>'123456'])->assertRedirect('/onboarding');
        $this->assertAuthenticated(); $this->assertDatabaseHas('users',['mobile_e164'=>'+919876543210','onboarding_step'=>'role_pending']);
    }
    public function test_email_can_request_otp_then_choose_account_type(): void {
        Mail::fake();
        $this->post('/send-otp',['channel'=>'email','email'=>'Person@Example.com'])->assertRedirect('/verify-otp');
        $this->assertDatabaseHas('otp_requests',['channel'=>'email','email'=>'person@example.com']);
        Mail::assertSent(EmailOtpMail::class, fn (EmailOtpMail $mail) => $mail->hasTo('person@example.com'));

        $this->post('/verify-otp',['otp'=>'123456'])->assertRedirect('/onboarding');
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users',['email'=>'person@example.com','onboarding_step'=>'role_pending']);

        $this->post('/onboarding/account-type',['role'=>'user'])->assertRedirect('/onboarding');
        $this->assertDatabaseHas('users',['email'=>'person@example.com','account_type'=>'user','onboarding_step'=>'user_profile_pending']);
    }
    public function test_invalid_indian_mobile_is_rejected(): void { $this->post('/send-otp',['country_code'=>'+91','phone'=>'123'])->assertSessionHasErrors('phone'); }
    public function test_wrong_expired_and_exhausted_otp_are_rejected(): void {
        $this->withSession(['otp.mobile_e164'=>'+919876543210','otp.country_code'=>'+91']);
        OtpRequest::create(['mobile_e164'=>'+919876543210','otp_hash'=>Hash::make('123456'),'expires_at'=>now()->subMinute(),'resend_available_at'=>now()]);
        $this->post('/verify-otp',['otp'=>'123456'])->assertSessionHasErrors('otp');
    }
    public function test_existing_complete_user_logs_in_without_repeating_onboarding(): void {
        User::create(['name'=>'Existing','phone'=>'+919876543210','mobile_e164'=>'+919876543210','password'=>Hash::make('secret-password'),'onboarding_step'=>'completed','onboarding_completed_at'=>now()]);
        $this->withSession(['otp.mobile_e164'=>'+919876543210','otp.country_code'=>'+91']);
        OtpRequest::create(['mobile_e164'=>'+919876543210','otp_hash'=>Hash::make('123456'),'expires_at'=>now()->addMinute(),'resend_available_at'=>now()]);
        $this->post('/verify-otp',['otp'=>'123456'])->assertRedirect('/dashboard');
    }
}
