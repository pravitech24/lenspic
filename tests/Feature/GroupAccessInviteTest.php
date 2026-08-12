<?php
namespace Tests\Feature;

use App\Models\Group;
use App\Models\GroupAccessInvite;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GroupAccessInviteTest extends TestCase {
    use RefreshDatabase;
    protected function setUp(): void { parent::setUp(); $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class); }
    private function user(array $extra=[]): User { return User::create(array_merge(['name'=>'User','email'=>uniqid().'@example.com','password'=>bcrypt('password'),'onboarding_step'=>'completed','onboarding_completed_at'=>now(),'plan'=>'free'],$extra)); }
    private function group(User $owner): Group { return Group::create(['name'=>'Wedding','creator_id'=>$owner->id,'privacy'=>'link_only','face_recognition_enabled'=>true]); }
    public function test_creation_generates_distinct_allowed_codes(): void {
        $owner=$this->user(['account_type'=>'photographer']); $group=$this->group($owner);
        $partial=GroupAccessInvite::makeFor($group,'partial_access',$owner->id); $full=GroupAccessInvite::makeFor($group,'full_access',$owner->id);
        $this->assertMatchesRegularExpression('/^(?=.*[A-Z])(?=.*\d)[A-Z0-9]{6}$/',$partial->access_code); $this->assertNotSame($partial->access_code,$full->access_code); $this->assertNotSame($partial->invitation_token,$full->invitation_token);
    }
    public function test_lowercase_code_is_normalized_and_previewed(): void {
        $owner=$this->user();$member=$this->user();$invite=GroupAccessInvite::makeFor($this->group($owner),'partial_access',$owner->id);
        $this->actingAs($member)->postJson('/api/groups/validate-code',['code'=>strtolower($invite->access_code)])->assertOk()->assertJsonPath('data.group_reference',$invite->invitation_token);
    }
    public function test_public_join_code_requires_six_mixed_characters(): void {
        $this->postJson('/api/groups/validate-code',['code'=>'12345'])->assertUnprocessable()->assertJsonValidationErrors('code');
        $this->postJson('/api/groups/validate-code',['code'=>'1234567'])->assertUnprocessable()->assertJsonValidationErrors('code');
        $this->postJson('/api/groups/validate-code',['code'=>'123456'])->assertUnprocessable()->assertJsonValidationErrors('code');
        $this->postJson('/api/groups/validate-code',['code'=>'ABCDEF'])->assertUnprocessable()->assertJsonValidationErrors('code');
    }
    public function test_album_share_codes_are_persisted_and_validate_successfully(): void {
        $owner=$this->user();$group=$this->group($owner);
        $this->actingAs($owner)->get('/groups/'.$group->id)->assertOk();
        $invite=$group->accessInvites()->where('access_type','partial_access')->firstOrFail();
        $this->postJson('/api/groups/validate-code',['code'=>strtolower($invite->access_code)])->assertOk()->assertJsonPath('data.group_name',$group->name);
    }
    public function test_partial_join_and_full_upgrade_never_duplicate_membership(): void {
        $owner=$this->user();$member=$this->user();$group=$this->group($owner);$partial=GroupAccessInvite::makeFor($group,'partial_access',$owner->id);$full=GroupAccessInvite::makeFor($group,'full_access',$owner->id);
        $this->actingAs($member)->get('/join/'.$partial->invitation_token)->assertOk();$this->post('/join/'.$partial->invitation_token)->assertRedirect('/groups/'.$group->id.'/discover');
        $this->get('/join/'.$full->invitation_token)->assertOk();$this->post('/join/'.$full->invitation_token)->assertRedirect('/groups/'.$group->id);
        $this->assertSame(1,$group->members()->where('user_id',$member->id)->count());$this->assertSame('full_access',$group->membershipFor($member)->pivot->access_type);
        $this->actingAs($member)->get('/join/'.$partial->invitation_token);$this->post('/join/'.$partial->invitation_token);$this->assertSame('full_access',$group->membershipFor($member)->pivot->access_type);
    }
    public function test_revoked_expired_and_exhausted_invites_fail(): void {
        $owner=$this->user();$member=$this->user();$group=$this->group($owner);$invite=GroupAccessInvite::makeFor($group,'partial_access',$owner->id);
        $invite->update(['is_active'=>false,'revoked_at'=>now()]);$this->actingAs($member)->get('/join/'.$invite->invitation_token)->assertOk()->assertSee('revoked');
        $invite->update(['is_active'=>true,'revoked_at'=>null,'expires_at'=>now()->subMinute()]);$this->actingAs($member)->get('/join/'.$invite->invitation_token)->assertOk()->assertSee('expired');
        $invite->update(['expires_at'=>null,'max_uses'=>1,'used_count'=>1]);$this->actingAs($member)->get('/join/'.$invite->invitation_token)->assertOk()->assertSee('unavailable');
    }
    public function test_partial_cannot_browse_gallery_but_full_cannot_manage_settings(): void {
        $owner=$this->user();$partial=$this->user();$full=$this->user();$group=$this->group($owner);
        $group->members()->attach($partial->id,['role'=>'member','membership_status'=>'active','access_type'=>'partial_access']);$group->members()->attach($full->id,['role'=>'member','membership_status'=>'active','access_type'=>'full_access']);
        $this->actingAs($partial)->get('/groups/'.$group->id.'/photos')->assertForbidden();$this->actingAs($full)->get('/groups/'.$group->id.'/photos')->assertOk();$this->actingAs($full)->get('/groups/'.$group->id.'/settings')->assertForbidden();
    }
    public function test_non_admin_cannot_manage_invites(): void { $owner=$this->user();$other=$this->user();$group=$this->group($owner);$this->actingAs($other)->get('/groups/'.$group->id.'/access-invites')->assertForbidden(); }
}
