<?php

namespace Tests\Feature;

use App\Models\{User, PhotographerProfile, SelfieVerification, StudioTeamMembership, Group};
use App\Services\Auth\{AuthenticatedLanding, OnboardingState};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{Hash, Http, Mail};
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Fakes\TwilioHttpClient;
use Tests\TestCase;

class AssignedPhotographerOnboardingTest extends TestCase
{
    use RefreshDatabase;

    private function photographer(array $attributes=[]): User
    {
        return User::create(array_replace(['name'=>'Assigned Photographer','email'=>'assigned@test.local','password'=>Hash::make('password'),'role'=>'user','account_type'=>'photographer','onboarding_step'=>'role_pending','status'=>'active','mobile_e164'=>'+919876543210'], $attributes));
    }

    private function selfie(User $user): SelfieVerification
    {
        return SelfieVerification::create(['user_id'=>$user->id,'image_path'=>'test-only.jpg','verification_status'=>'manual_review']);
    }

    private function studio(User $user): void
    {
        PhotographerProfile::create(['user_id'=>$user->id,'first_name'=>'Assigned','last_name'=>'Photographer','company_name'=>'Existing Studio','company_email'=>$user->email]);
    }

    public function test_completed_photographer_skips_all_onboarding_urls_without_loops(): void
    {
        $user=$this->photographer(['onboarding_completed_at'=>now()]);
        foreach (['onboarding.resume','onboarding.role','onboarding.selfie','onboarding.profile','welcome.start'] as $route) {
            $this->actingAs($user)->get(route($route))->assertRedirect(route('dashboard'));
        }
        $this->get(route('dashboard'))->assertOk();
        $this->assertDatabaseCount('photographer_profiles',0);
    }

    public function test_assigned_photographer_skips_role_and_selfie_but_keeps_required_profile(): void
    {
        $user=$this->photographer();
        $this->actingAs($user)->get('/onboarding')->assertRedirect(route('onboarding.profile'));
        $this->get('/onboarding/account-type')->assertRedirect(route('onboarding.profile'));
        $this->get('/onboarding/account-type')->assertRedirect(route('onboarding.profile'));
        $this->get('/onboarding/profile')->assertOk()->assertInertia(fn(Assert $page)=>$page->component('Onboarding')->where('step','profile')->where('accountType','photographer'));
        $this->assertNull($user->fresh()->onboarding_completed_at);
        $this->assertSame('role_pending',$user->fresh()->onboarding_step);
    }

    public function test_studio_account_type_is_also_a_photographer_assignment(): void
    {
        $user=$this->photographer(['account_type'=>'studio']);
        $this->actingAs($user)->get('/onboarding/account-type')->assertRedirect(route('onboarding.profile'));
        $this->get('/onboarding/profile')->assertInertia(fn(Assert $page)=>$page->where('accountType','photographer'));
    }

    public function test_saved_complete_studio_skips_stale_step_without_duplicate_records(): void
    {
        $user=$this->photographer();
        $this->studio($user);
        $this->actingAs($user)->get('/onboarding')->assertRedirect(route('dashboard'));
        $this->post('/onboarding/account-type',['role'=>'user'])->assertRedirect(route('dashboard'));
        $this->post('/onboarding/profile',[])->assertRedirect(route('dashboard'));
        $this->assertDatabaseCount('photographer_profiles',1);
    }

    public function test_new_photographer_assignment_does_not_inherit_old_user_profile_completion(): void
    {
        $user=$this->photographer(['onboarding_completed_at'=>now()->subDay(),'role_assigned_at'=>now()]);
        $this->actingAs($user)->get('/onboarding/account-type')->assertRedirect(route('onboarding.profile'));
        $this->assertFalse(app(OnboardingState::class)->complete($user));
    }

    public function test_stale_submissions_cannot_downgrade_roles_or_change_scoped_permissions(): void
    {
        $user=$this->photographer();
        $owner=User::create(['name'=>'Other Studio','email'=>'other@test.local','password'=>Hash::make('password'),'account_type'=>'photographer']);
        $membership=StudioTeamMembership::create(['uuid'=>(string)Str::uuid(),'studio_owner_id'=>$owner->id,'user_id'=>$user->id,'role'=>'viewer','status'=>'active','permissions'=>['view_groups']]);
        foreach (['user','photographer','super_admin'] as $role) $this->actingAs($user)->post('/onboarding/account-type',['role'=>$role])->assertRedirect(route('onboarding.profile'));
        $this->assertSame('photographer',$user->fresh()->account_type);
        $this->assertSame('user',$user->fresh()->role);
        $this->assertSame(['view_groups'],$membership->fresh()->permissions);
        $this->assertDatabaseCount('studio_team_memberships',1);
        $this->assertDatabaseCount('photographer_profiles',0);
    }

    public function test_default_user_role_still_requires_explicit_choice_and_user_profile_works(): void
    {
        $user=User::create(['name'=>'New user','email'=>'new@test.local','password'=>Hash::make('password'),'role'=>'user','account_type'=>'user','onboarding_step'=>'role_pending']);
        $this->actingAs($user)->get('/onboarding/account-type')->assertInertia(fn(Assert $page)=>$page->where('step','role'));
        $this->post('/onboarding/account-type',['role'=>'user'])->assertRedirect('/onboarding');
        $this->get('/onboarding/profile')->assertInertia(fn(Assert $page)=>$page->where('accountType','user'));
        $this->post('/onboarding/profile',['name'=>'New Member','email'=>$user->email])->assertRedirect(route('welcome.start'));
        $this->assertNotNull($user->fresh()->onboarding_completed_at);
    }

    public function test_profile_completion_preserves_pending_group_join_and_only_creates_one_studio(): void
    {
        $user=$this->photographer();
        $this->actingAs($user)->withSession(['validated_group_invitation'=>['code'=>'test']]);
        $data=['first_name'=>'Assigned','last_name'=>'Photographer','company_name'=>'New Studio','email'=>$user->email];
        $this->post('/onboarding/profile',$data)->assertRedirect(route('groups.join.complete'));
        $this->post('/onboarding/profile',$data)->assertRedirect(route('groups.join.complete'));
        $this->get(route('groups.join.complete'))->assertOk();
        $this->assertDatabaseCount('photographer_profiles',1);
        $this->assertSame('photographer',$user->fresh()->account_type);
    }

    public function test_password_login_cannot_bypass_remaining_setup_using_stale_intended_role_url(): void
    {
        $user=$this->photographer();
        $this->withSession(['url.intended'=>route('onboarding.role')])->post('/login',['email'=>$user->email,'password'=>'password'])->assertRedirect('/onboarding');
        $this->get('/onboarding')->assertRedirect(route('onboarding.profile'));
    }

    public function test_password_and_all_otp_channels_use_same_assigned_photographer_routing(): void
    {
        $user=$this->photographer(['onboarding_completed_at'=>now()]);
        $this->withSession(['url.intended'=>route('onboarding.role')])->post('/login',['email'=>$user->email,'password'=>'password'])->assertRedirect(route('dashboard'));
        auth()->logout();
        config(['otp.test_mode'=>true,'otp.test_code'=>'123456','otp.whatsapp.phone_number_id'=>'12345','otp.whatsapp.access_token'=>'test-meta','otp.whatsapp.template'=>'login_code','whatsapp.app_secret'=>'test-secret','whatsapp.webhook_verify_token'=>'test-verify']);
        Mail::fake();
        Http::fake(['graph.facebook.com/*'=>Http::response(['messages'=>[['id'=>'wamid.onboarding']]])]);
        foreach (['email','whatsapp','sms'] as $channel) {
            if ($channel==='sms') {
                config(['services.twilio.account_sid'=>'AC'.str_repeat('a',32),'services.twilio.auth_token'=>'test-token','services.twilio.verify_service_sid'=>'VA'.str_repeat('b',32),'services.twilio.channel'=>'sms']);
                $fake=new TwilioHttpClient();$fake->queue('pending');$fake->queue('approved');$this->app->instance(\Twilio\Http\Client::class,$fake);
            }
            $this->withSession(['validated_group_invitation'=>['code'=>'test']])->postJson('/send-otp',['channel'=>$channel,'email'=>$user->email,'country_code'=>'+91','phone'=>'9876543210'])->assertOk();
            $this->postJson('/verify-otp',['otp'=>'123456'])->assertOk()->assertJson(['redirect'=>route('groups.join.complete')]);
            auth()->logout();
        }
    }

    public function test_super_admin_and_completed_multi_role_members_keep_routing(): void
    {
        $super=$this->photographer(['role'=>'super_admin']);
        $this->actingAs($super)->get('/onboarding/account-type')->assertRedirect(route('super-admin.dashboard'));
        $this->post('/onboarding/account-type',['role'=>'user'])->assertRedirect(route('super-admin.dashboard'));
        $this->assertSame('super_admin',$super->fresh()->role);
        $member=$this->photographer(['email'=>'member@test.local','mobile_e164'=>null,'onboarding_completed_at'=>now()]);
        StudioTeamMembership::create(['uuid'=>(string)Str::uuid(),'studio_owner_id'=>$super->id,'user_id'=>$member->id,'role'=>'viewer','status'=>'active']);
        $this->actingAs($member)->get('/onboarding')->assertRedirect(route('groups.index'));
    }

    public function test_only_authorized_intended_group_is_resumed(): void
    {
        $user=$this->photographer(['onboarding_completed_at'=>now()]);
        $own=Group::create(['name'=>'Own Group','creator_id'=>$user->id]);
        $other=User::create(['name'=>'Other','email'=>'unauthorized@test.local','password'=>Hash::make('password')]);
        $private=Group::create(['name'=>'Other Group','creator_id'=>$other->id]);
        $this->actingAs($user)->withSession(['url.intended'=>route('groups.show',$own)])->get('/onboarding')->assertRedirect(route('groups.show',$own));
        foreach ([route('groups.show',$private),'https://example.com/elsewhere',route('onboarding.role')] as $url) {
            $this->withSession(['url.intended'=>$url])->get('/onboarding')->assertRedirect(route('dashboard'));
        }
    }
    public function test_incomplete_photographer_after_every_otp_method_keeps_join_and_skips_role(): void
    {
        $user=$this->photographer();
        config(['otp.test_mode'=>true,'otp.test_code'=>'123456','otp.whatsapp.phone_number_id'=>'12345','otp.whatsapp.access_token'=>'test-meta','otp.whatsapp.template'=>'login_code','whatsapp.app_secret'=>'test-secret','whatsapp.webhook_verify_token'=>'test-verify']);
        Mail::fake();Http::fake(['graph.facebook.com/*'=>Http::response(['messages'=>[['id'=>'wamid.incomplete']]])]);
        foreach (['email','whatsapp','sms'] as $channel) {
            if ($channel==='sms') {
                config(['services.twilio.account_sid'=>'AC'.str_repeat('a',32),'services.twilio.auth_token'=>'test-token','services.twilio.verify_service_sid'=>'VA'.str_repeat('b',32),'services.twilio.channel'=>'sms']);
                $fake=new TwilioHttpClient();$fake->queue('pending');$fake->queue('approved');$this->app->instance(\Twilio\Http\Client::class,$fake);
            }
            $this->withSession(['validated_group_invitation'=>['code'=>'test']])->postJson('/send-otp',['channel'=>$channel,'email'=>$user->email,'country_code'=>'+91','phone'=>'9876543210'])->assertOk();
            $this->postJson('/verify-otp',['otp'=>'123456'])->assertJson(['redirect'=>route('onboarding.resume')])->assertSessionHas('validated_group_invitation');
            $this->get('/onboarding')->assertRedirect(route('onboarding.profile'));
            $this->get('/onboarding/account-type')->assertRedirect(route('onboarding.profile'));
            $this->assertNull($user->fresh()->onboarding_completed_at);
            auth()->logout();
        }
    }

    public function test_new_user_can_choose_photographer_without_overwriting_platform_role(): void
    {
        $user=User::create(['name'=>'New user','email'=>'new-photo@test.local','password'=>Hash::make('password'),'role'=>'user','onboarding_step'=>'role_pending']);
        $this->actingAs($user)->post('/onboarding/account-type',['role'=>'photographer'])->assertRedirect('/onboarding');
        $this->get('/onboarding')->assertRedirect(route('onboarding.profile'));
        $this->assertSame('photographer',$user->fresh()->account_type);
        $this->assertSame('user',$user->fresh()->role);
        $this->assertNull($user->fresh()->onboarding_completed_at);
        $this->assertDatabaseCount('photographer_profiles',0);
    }

    public function test_pending_invitation_destinations_survive_onboarding_redirects(): void
    {
        $user=$this->photographer(['onboarding_completed_at'=>now()]);
        $this->actingAs($user)->withSession(['pending_invitation'=>'opaque-invitation'])->get('/onboarding/account-type')->assertRedirect(route('invitations.show','opaque-invitation'));
        session()->forget('pending_invitation');
        $pending=['uuid'=>(string)Str::uuid(),'token'=>'opaque-team-token'];
        $this->withSession(['pending_team_invitation'=>$pending])->get('/onboarding/account-type')->assertRedirect(route('team-invitations.show',array_values($pending)));
    }

    public function test_incomplete_saved_studio_is_not_completed_by_an_old_timestamp(): void
    {
        $user=$this->photographer(['onboarding_completed_at'=>now()]);
        $this->studio($user);
        $user->photographerProfile()->update(['company_name'=>'']);
        $this->actingAs($user)->get('/onboarding/account-type')->assertRedirect(route('onboarding.profile'));
        $this->assertFalse(app(OnboardingState::class)->complete($user->fresh()));
    }

}
