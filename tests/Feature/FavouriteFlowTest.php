<?php

namespace Tests\Feature;

use App\Models\{Group, MediaAsset, Photo, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class FavouriteFlowTest extends TestCase
{
    use RefreshDatabase;
    private function user(string $email): User { return User::create(['name'=>'Viewer','email'=>$email,'password'=>Hash::make('password'),'status'=>'active','account_type'=>'photographer','plan'=>'standard']); }
    private function group(User $owner,string $name='Gallery'): Group { $group=Group::create(['name'=>$name,'creator_id'=>$owner->id,'favourites_enabled'=>true]);$group->members()->attach($owner->id,['role'=>'admin','membership_status'=>'active','access_type'=>'full_access']);return $group; }
    private function photo(Group $group,User $owner): Photo { $photo=Photo::create(['group_id'=>$group->id,'uploader_id'=>$owner->id,'filename'=>Str::uuid().'.jpg','original_filename'=>'moment.jpg','path'=>'private/moment.jpg','file_size'=>10,'mime_type'=>'image/jpeg']);MediaAsset::create(['uuid'=>(string)Str::uuid(),'photo_id'=>$photo->id,'group_id'=>$group->id,'owner_id'=>$owner->id,'uploader_id'=>$owner->id,'storage_disk'=>'private_local','original_object_key'=>'private/'.Str::uuid().'.jpg','original_filename'=>'moment.jpg','mime_type'=>'image/jpeg','size_bytes'=>10,'checksum_sha256'=>str_repeat('a',64),'state'=>'completed']);return $photo; }

    public function test_add_is_idempotent_and_remove_returns_authoritative_state(): void
    {
        $owner=$this->user('owner-favourite@example.com');$group=$this->group($owner);$photo=$this->photo($group,$owner);
        $this->actingAs($owner)->postJson(route('photos.favourite.store',[$group,$photo]))->assertOk()->assertJson(['is_favourite'=>true,'favourites_count'=>1,'message'=>'Added to favourites.']);
        $this->postJson(route('photos.favourite.store',[$group,$photo]))->assertOk()->assertJson(['is_favourite'=>true,'favourites_count'=>1]);
        $this->assertDatabaseCount('photo_likes',1);
        $this->deleteJson(route('photos.favourite.destroy',[$group,$photo]))->assertOk()->assertJson(['is_favourite'=>false,'favourites_count'=>0]);
        $this->deleteJson(route('photos.favourite.destroy',[$group,$photo]))->assertOk()->assertJson(['is_favourite'=>false,'favourites_count'=>0]);
    }

    public function test_favourites_are_user_specific_and_tab_returns_only_current_user_records(): void
    {
        $owner=$this->user('owner-specific@example.com');$viewer=$this->user('viewer-specific@example.com');$group=$this->group($owner);$group->members()->attach($viewer->id,['role'=>'member','membership_status'=>'active','access_type'=>'full_access']);$one=$this->photo($group,$owner);$two=$this->photo($group,$owner);$one->likes()->attach($owner->id);$two->likes()->attach($viewer->id);
        $this->actingAs($owner)->get(route('groups.gallery',[$group,'tab'=>'favorites']))->assertInertia(fn(Assert$page)=>$page->component('Groups/Show')->has('photos.data',1)->where('photos.data.0.id',$one->id));
        $this->actingAs($viewer)->get(route('groups.gallery',[$group,'tab'=>'favorites']))->assertInertia(fn(Assert$page)=>$page->component('Groups/Show')->has('photos.data',1)->where('photos.data.0.id',$two->id));
    }

    public function test_cross_group_and_disabled_favourites_are_rejected(): void
    {
        $owner=$this->user('owner-blocked@example.com');$outsider=$this->user('outsider-blocked@example.com');$group=$this->group($owner);$other=$this->group($owner,'Other');$photo=$this->photo($other,$owner);
        $this->actingAs($owner)->postJson(route('photos.favourite.store',[$group,$photo]))->assertNotFound();
        $this->actingAs($outsider)->postJson(route('photos.favourite.store',[$other,$photo]))->assertForbidden();
        $this->actingAs($owner);
        $other->update(['favourites_enabled'=>false]);
        $this->postJson(route('photos.favourite.store',[$other,$photo]))->assertForbidden()->assertJsonPath('message','Favourites are not available for this group.');
        $this->assertDatabaseCount('photo_likes',0);
    }

    public function test_shared_controls_isolate_clicks_and_use_square_accessible_states(): void
    {
        $gallery=file_get_contents(resource_path('js/Pages/Groups/Show.vue'));$selector=file_get_contents(resource_path('js/Components/PhotoSelector.vue'));
        $this->assertStringContainsString('@click.stop.prevent="favorite(photo)"',$gallery);
        $this->assertStringContainsString(':aria-pressed="photo.liked"',$gallery);
        $this->assertStringContainsString('busyFavourites.has(photo.id)',$gallery);
        $this->assertStringContainsString('fetch(`/groups/${props.group.id}/photos/${photo.id}/favourite`',$gallery);
        $this->assertStringContainsString("photo.liked=previous",$gallery);
        $this->assertStringContainsString('rounded-[7px]',$selector);
        $this->assertStringContainsString('@mousedown.stop',$selector);
        $this->assertStringNotContainsString('rounded-full',$selector);
    }
}
