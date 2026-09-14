<?php

namespace Tests\Feature;

use App\Models\{User, Group, GroupAccessInvite, PhotographerProfile, BiometricConsent};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\{Hash, Http, Mail, Queue};
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Fakes\TwilioHttpClient;
use Tests\TestCase;

class OptionalBiometricLoginTest extends TestCase
{
    use RefreshDatabase;

    private function user(array $attributes = []): User
    {
        return User::create(array_replace(['name'=>'Member', 'email'=>Str::uuid().'@test.local', 'password'=>Hash::make('password'), 'role'=>'user', 'account_type'=>'user', 'status'=>'active', 'plan'=>'basic', 'onboarding_completed_at'=>now(), 'mobile_e164'=>'+919876543210'], $attributes));
    }

    private function group(User $member): Group
    {
        $owner=$this->user(['mobile_e164'=>null]);
        $group=Group::create(['name'=>'Private Group', 'creator_id'=>$owner->id, 'is_active'=>true, 'face_recognition_enabled'=>true]);
        $group->members()->attach($member->id, ['role'=>'member', 'membership_status'=>'active', 'access_type'=>'partial_access']);
        return $group;
    }

    private function login(User $user, string $channel, string $destination): void
    {
        auth()->logout();
        $this->travel(2)->minutes();
        if ($channel==='password') {
            $this->post('/login', ['email'=>$user->email, 'password'=>'password'])->assertRedirect($destination);
            return;
        }
        config(['otp.test_mode'=>true, 'otp.test_code'=>'123456', 'otp.whatsapp.phone_number_id'=>'12345', 'otp.whatsapp.access_token'=>'test-meta', 'otp.whatsapp.template'=>'login_code', 'whatsapp.app_secret'=>'test-secret', 'whatsapp.webhook_verify_token'=>'test-verify']);
        Mail::fake();
        Http::fake(['graph.facebook.com/*'=>fn()=>Http::response(['messages'=>[['id'=>'wamid.'.Str::uuid()]]])]);
        if ($channel==='sms') {
            config(['services.twilio.account_sid'=>'AC'.str_repeat('a',32), 'services.twilio.auth_token'=>'test-token', 'services.twilio.verify_service_sid'=>'VA'.str_repeat('b',32), 'services.twilio.channel'=>'sms']);
            $fake=new TwilioHttpClient(); $fake->queue('pending'); $fake->queue('approved');
            $this->app->instance(\Twilio\Http\Client::class, $fake);
        }
        $this->postJson('/send-otp', ['channel'=>$channel, 'email'=>$user->email, 'country_code'=>'+91', 'phone'=>'9876543210'])->assertOk();
        $this->postJson('/verify-otp', ['otp'=>'123456'])->assertOk()->assertJsonPath('redirect', $destination);
    }

    public function test_complete_studio_without_selfie_reaches_dashboard_after_every_login_method(): void
    {
        $user=$this->user(['account_type'=>'photographer', 'onboarding_completed_at'=>null, 'onboarding_step'=>'selfie_pending']);
        PhotographerProfile::create(['user_id'=>$user->id, 'first_name'=>'Studio', 'last_name'=>'Owner', 'company_name'=>'Studio', 'company_email'=>$user->email]);
        foreach (['password','sms','whatsapp','email'] as $channel) {
            $this->withSession(['url.intended'=>route('onboarding.selfie')]);
            $this->login($user, $channel, route('dashboard'));
            $this->get('/dashboard')->assertOk();
            $this->get('/onboarding')->assertRedirect(route('dashboard'));
        }
        $this->assertDatabaseCount('selfie_verifications', 0);
        $this->assertDatabaseCount('biometric_consents', 0);
        $this->assertDatabaseCount('photographer_profiles', 1);
    }

    public function test_group_member_with_missing_or_withdrawn_consent_logs_in_without_capture_using_every_method(): void
    {
        $user=$this->user(); $group=$this->group($user);
        foreach (['missing','withdrawn'] as $state) {
            if ($state==='withdrawn') BiometricConsent::create(['uuid'=>(string)Str::uuid(),'group_id'=>$group->id,'user_id'=>$user->id,'purpose'=>'event_photo_matching','privacy_notice_version'=>config('face.consent_version'),'state'=>'withdrawn','granted_at'=>now()->subDay(),'withdrawn_at'=>now()]);
            foreach (['password','sms','whatsapp','email'] as $channel) {
                $this->withSession(['url.intended'=>route('groups.show',$group)]);
                $this->login($user,$channel,route('groups.show',$group));
                $this->get(route('groups.show',$group))->assertOk();
                $this->get('/onboarding')->assertRedirect(route('groups.index'));
                $this->assertFalse($group->hasFullAccess($user));
            }
        }
        $this->assertDatabaseCount('face_search_subjects',0);
        $this->assertDatabaseHas('biometric_consents',['user_id'=>$user->id,'state'=>'withdrawn']);
    }

    public function test_stale_capture_destinations_and_expired_feature_intents_are_discarded(): void
    {
        $user=$this->user(); $group=$this->group($user);
        foreach ([route('onboarding.selfie'), route('face.show',$group), route('biometric.page',$group), route('biometric.entry',$group)] as $url) {
            $this->withSession(['url.intended'=>$url,'find_my_photos_intent'=>['group_id'=>$group->id,'expires_at'=>now()->subMinute()->timestamp]]);
            $this->login($user,'password',route('groups.index'));
            $this->get('/groups')->assertOk()->assertSessionMissing('url.intended')->assertSessionMissing('find_my_photos_intent');
        }
    }

    public function test_explicit_find_my_photos_survives_every_login_method_and_does_not_grant_consent(): void
    {
        $user=$this->user(); $group=$this->group($user);
        foreach (['password','sms','whatsapp','email'] as $channel) {
            auth()->logout();
            $this->get(route('biometric.entry',$group))->assertRedirect(route('login'))->assertSessionHas('find_my_photos_intent');
            $this->login($user,$channel,route('biometric.page',$group));
            $this->get(route('biometric.page',$group))->assertOk()->assertSessionMissing('find_my_photos_intent')->assertInertia(fn(Assert $page)=>$page->component('Biometric/ConsentSelfie')->where('consent',null));
        }
        $this->assertDatabaseCount('biometric_consents',0);
        $this->assertDatabaseCount('face_search_subjects',0);
    }

    public function test_explicit_intent_is_reauthorized_after_login(): void
    {
        $user=$this->user(); $group=$this->group($user);
        $this->get(route('biometric.entry',$group))->assertRedirect(route('login'));
        $group->members()->updateExistingPivot($user->id,['membership_status'=>'blocked']);
        $this->login($user,'password',route('dashboard'));
        $this->get(route('biometric.entry',$group))->assertForbidden();
        $this->get(route('biometric.page',$group))->assertForbidden();
        $this->get(route('groups.show',$group))->assertForbidden();
    }

    public function test_pending_partial_invitation_lands_in_restricted_group_without_enrollment(): void
    {
        $user=$this->user(); $owner=$this->user(['mobile_e164'=>null]);
        $group=Group::create(['name'=>'Invited Group','creator_id'=>$owner->id,'is_active'=>true,'face_recognition_enabled'=>true]);
        $invite=GroupAccessInvite::makeFor($group,GroupAccessInvite::PARTIAL,$owner->id);
        $this->get(route('invitations.show',$invite->invitation_token))->assertOk();
        foreach (['password','sms','whatsapp','email'] as $channel) {
            $this->login($user,$channel,route('invitations.show',$invite->invitation_token));
        }
        $this->post(route('invitations.accept',$invite->invitation_token))->assertRedirect(route('groups.show',$group));
        $this->get(route('groups.show',$group))->assertOk();
        $this->assertTrue($group->isMember($user));
        $this->assertFalse($group->hasFullAccess($user));
        $this->assertDatabaseCount('biometric_consents',0);
    }

    public function test_declined_or_withdrawn_consent_cannot_process_and_does_not_block_group(): void
    {
        Queue::fake(); $user=$this->user(); $group=$this->group($user);
        $this->actingAs($user)->postJson(route('biometric.consent',$group),['accepted'=>false,'privacy_notice_version'=>config('face.consent_version')])->assertUnprocessable();
        $this->postJson(route('biometric.search',$group),['selfie'=>UploadedFile::fake()->image('selfie.jpg')])->assertUnprocessable();
        $consent=BiometricConsent::create(['uuid'=>(string)Str::uuid(),'group_id'=>$group->id,'user_id'=>$user->id,'purpose'=>'event_photo_matching','privacy_notice_version'=>config('face.consent_version'),'state'=>'withdrawn','granted_at'=>now()->subDay(),'withdrawn_at'=>now()]);
        $this->postJson(route('biometric.search',$group),['consent_uuid'=>$consent->uuid,'selfie'=>UploadedFile::fake()->image('selfie.jpg')])->assertNotFound();
        $this->get(route('biometric.page',$group))->assertInertia(fn(Assert $page)=>$page->where('consent.state','withdrawn'));
        $this->get(route('groups.show',$group))->assertOk();
        $this->get('/onboarding')->assertRedirect(route('groups.index'));
        $this->assertDatabaseCount('face_search_subjects',0);
        Queue::assertNothingPushed();
    }

    public function test_retired_onboarding_and_group_selfie_posts_do_not_store_or_mark_enrollment(): void
    {
        $user=$this->user(['account_type'=>'photographer','onboarding_completed_at'=>null]); $group=$this->group($user);
        $this->actingAs($user)->post('/onboarding/selfie',['selfie'=>UploadedFile::fake()->image('selfie.jpg')])->assertRedirect(route('onboarding.profile'));
        $this->postJson(route('face.selfie',$group),['selfie'=>UploadedFile::fake()->image('selfie.jpg')])->assertGone();
        $this->assertDatabaseCount('selfie_verifications',0);
        $this->assertNull($user->fresh()->onboarding_completed_at);
        $this->assertNull($group->membershipFor($user)->pivot->selfie_path);
    }
    public function test_explicit_feature_intent_survives_remaining_studio_setup(): void
    {
        $user=$this->user(['account_type'=>'photographer','onboarding_completed_at'=>null]);
        $group=$this->group($user);
        $this->get(route('biometric.entry',$group))->assertRedirect(route('login'));
        $this->login($user,'password',route('onboarding.resume'));
        $this->get('/onboarding')->assertRedirect(route('onboarding.profile'))->assertSessionHas('find_my_photos_intent');
        $this->post('/onboarding/profile',['first_name'=>'Studio','last_name'=>'Owner','company_name'=>'My Studio','email'=>$user->email])->assertRedirect(route('biometric.page',$group));
        $this->get(route('biometric.page',$group))->assertOk();
        $this->assertDatabaseCount('biometric_consents',0);
        $this->assertDatabaseCount('selfie_verifications',0);
    }

    public function test_non_biometric_profile_save_preserves_existing_selfie_association(): void
    {
        $user=$this->user(['account_type'=>'photographer','onboarding_completed_at'=>null]);
        $selfie=\App\Models\SelfieVerification::create(['user_id'=>$user->id,'image_path'=>'existing-private-selfie.jpg','verification_status'=>'manual_review']);
        $profile=PhotographerProfile::create(['user_id'=>$user->id,'first_name'=>'Studio','last_name'=>'Owner','company_name'=>'','company_email'=>$user->email,'selfie_verification_id'=>$selfie->id]);
        $before=$selfie->fresh()->getAttributes();
        $this->actingAs($user)->post('/onboarding/profile',['first_name'=>'Studio','last_name'=>'Owner','company_name'=>'My Studio','email'=>$user->email])->assertRedirect(route('dashboard'));
        $this->assertSame($selfie->id,$profile->fresh()->selfie_verification_id);
        $this->assertSame($before,$selfie->fresh()->getAttributes());
        $this->assertDatabaseCount('photographer_profiles',1);
    }

}
