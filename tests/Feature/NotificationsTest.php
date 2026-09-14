<?php

namespace Tests\Feature;

use App\Models\{StudioTeamMembership, User};
use App\Services\Notifications\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class NotificationsTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $email): User{return User::create(['name'=>'Owner','email'=>$email,'password'=>Hash::make('password'),'status'=>'active','account_type'=>'photographer']);}
    private function send(User $user,string $key='event:1',array $extra=[]){return app(NotificationService::class)->send($user,$key,array_merge(['category'=>'groups','title'=>'Group created','message'=>'Wedding is ready.','studio_id'=>$user->id,'subject_type'=>'group','subject_id'=>'1','severity'=>'success'],$extra));}

    public function test_page_is_full_inertia_page_with_real_empty_state_contract():void
    {
        $user=$this->user('empty@example.com');
        $this->actingAs($user)->get(route('notifications.index'))->assertOk()->assertInertia(fn(Assert$page)=>$page->component('Notifications')->where('notifications.data',[])->where('filters.status','all'));
    }

    public function test_notification_persists_with_safe_schema_and_is_idempotent():void
    {
        $user=$this->user('persist@example.com');$first=$this->send($user);$second=$this->send($user);
        $this->assertSame($first->id,$second->id);$this->assertDatabaseCount('notifications',1);$this->assertDatabaseCount('notification_deliveries',1);
        $this->assertSame(1,$first->data['schema_version']);$this->assertArrayNotHasKey('url',$first->data);
    }

    public function test_read_unread_read_all_and_count_are_scoped():void
    {
        $user=$this->user('one@example.com');$other=$this->user('two@example.com');$notification=$this->send($user);$otherNotification=$this->send($other,'event:2');
        $this->actingAs($user)->getJson(route('notifications.unread-count'))->assertJson(['count'=>1]);
        $this->patch(route('notifications.read',$notification->id))->assertRedirect();$this->assertNotNull($notification->fresh()->read_at);
        $this->patch(route('notifications.unread',$notification->id))->assertRedirect();$this->assertNull($notification->fresh()->read_at);
        $this->patch(route('notifications.read',$otherNotification->id))->assertNotFound();
        $this->patch(route('notifications.read-all'))->assertRedirect();$this->assertNotNull($notification->fresh()->read_at);$this->assertNull($otherNotification->fresh()->read_at);
    }

    public function test_suspended_member_cannot_see_studio_sensitive_notification():void
    {
        $owner=$this->user('studio@example.com');$member=$this->user('member@example.com');
        StudioTeamMembership::create(['uuid'=>fake()->uuid(),'studio_owner_id'=>$owner->id,'user_id'=>$member->id,'role'=>'admin','status'=>'active','permissions'=>[]]);
        $notification=$this->send($member,'studio:event',['studio_id'=>$owner->id]);
        $this->actingAs($member)->getJson(route('notifications.unread-count'))->assertJson(['count'=>1]);
        StudioTeamMembership::where('user_id',$member->id)->update(['status'=>'suspended']);
        $this->getJson(route('notifications.unread-count'))->assertJson(['count'=>0]);$this->patch(route('notifications.read',$notification->id))->assertNotFound();
    }

    public function test_preferences_are_consumed_but_mandatory_notice_remains():void
    {
        $user=$this->user('prefs@example.com');$user->update(['meta'=>['preferences'=>['in_app_notifications'=>false]]]);
        $this->assertNull($this->send($user));
        $this->assertNotNull($this->send($user,'mandatory:1',['mandatory'=>true,'category'=>'billing']));
        $this->assertDatabaseCount('notifications',1);
    }

    public function test_arbitrary_action_route_is_rejected():void
    {
        $this->expectException(\InvalidArgumentException::class);$this->send($this->user('unsafe@example.com'),'unsafe:1',['action_route'=>'evil.redirect','action_parameters'=>['url'=>'https://evil.example']]);
    }
}
