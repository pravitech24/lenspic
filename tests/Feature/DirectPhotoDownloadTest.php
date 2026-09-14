<?php

namespace Tests\Feature;

use App\Models\{Group, GroupAccessInvite, MediaAsset, MediaVariant, Photo, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{Hash, Storage};
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DirectPhotoDownloadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['media.disk' => 'private_local', 'media.delivery_driver' => 'local']);
        Storage::fake('private_local');
    }

    private function user(string $email): User
    {
        return User::create(['name'=>'User','email'=>$email,'password'=>Hash::make('password'),'status'=>'active']);
    }

    private function photo(User $owner, string $filename = '../wedding photo.php'): array
    {
        $group=Group::create(['name'=>'Wedding','creator_id'=>$owner->id,'downloads_enabled'=>true]);
        $group->members()->attach($owner->id,['role'=>'admin','membership_status'=>'active','access_type'=>'full_access']);
        $photo=Photo::create(['group_id'=>$group->id,'uploader_id'=>$owner->id,'filename'=>'opaque','original_filename'=>$filename,'path'=>'private','mime_type'=>'image/jpeg']);
        $asset=MediaAsset::create(['uuid'=>(string)Str::uuid(),'photo_id'=>$photo->id,'group_id'=>$group->id,'owner_id'=>$owner->id,'uploader_id'=>$owner->id,'storage_disk'=>'private_local','original_object_key'=>'private/secret/object','original_filename'=>$filename,'mime_type'=>'image/jpeg','size_bytes'=>14,'checksum_sha256'=>hash('sha256','download-bytes'),'state'=>'completed']);
        $variant=MediaVariant::create(['media_asset_id'=>$asset->id,'variant_type'=>'original','storage_disk'=>'private_local','object_key'=>'private/secret/original-object','mime_type'=>'image/jpeg','size_bytes'=>14,'checksum_sha256'=>hash('sha256','download-bytes'),'state'=>'ready']);
        Storage::disk('private_local')->put($variant->object_key,'download-bytes');
        return [$group,$photo];
    }

    public function test_authorized_download_is_a_safe_attachment_without_private_key(): void
    {
        $owner=$this->user('owner-download@test.local');[$group,$photo]=$this->photo($owner);
        $response=$this->actingAs($owner)->get(route('photos.download',[$group,$photo]))->assertOk()->assertHeader('Content-Type','image/jpeg')->assertHeader('Cache-Control','no-store, private');
        $disposition=(string)$response->headers->get('Content-Disposition');
        $this->assertStringStartsWith('attachment;', $disposition);
        $this->assertStringContainsString('wedding-photo.jpg', $disposition);
        $this->assertStringNotContainsString('private/secret', $disposition);
        $this->assertSame('download-bytes', $response->streamedContent());
        $this->assertSame(1, $photo->fresh()->downloads_count);
    }

    public function test_download_requires_separate_permission_and_matching_group(): void
    {
        $owner=$this->user('owner-permission@test.local');$member=$this->user('member-permission@test.local');$outsider=$this->user('outsider-permission@test.local');[$group,$photo]=$this->photo($owner);
        $group->members()->attach($member->id,['role'=>'member','membership_status'=>'active','access_type'=>'full_access']);
        $this->actingAs($outsider)->get(route('photos.download',[$group,$photo]))->assertForbidden();
        $group->update(['downloads_enabled'=>false]);
        $this->actingAs($member)->get(route('photos.show',[$group,$photo]))->assertOk();
        $this->get(route('photos.download',[$group,$photo]))->assertForbidden();
        $other=Group::create(['name'=>'Other','creator_id'=>$owner->id,'downloads_enabled'=>true]);
        $this->actingAs($owner)->get(route('photos.download',[$other,$photo]))->assertNotFound();
    }

    public function test_grid_and_viewer_use_non_navigating_guarded_downloads(): void
    {
        $grid=file_get_contents(resource_path('js/Pages/Groups/Show.vue'));$viewer=file_get_contents(resource_path('js/Pages/Photos/Show.vue'));
        foreach(['function downloadCurrentPhoto(photo)','anchor.href=photo.download_url','anchor.download=','anchor.click()','Download started']as$expected)$this->assertStringContainsString($expected,$grid);
        foreach(['function downloadCurrentPhoto','const downloadUrl=props.photo.download_url','anchor.download=filename','anchor.click()','anchor.remove()','Download started']as$expected)$this->assertStringContainsString($expected,$viewer);
        $this->assertStringNotContainsString(':href="photo.download_url"',$viewer);
    }

    public function test_viewer_shares_backend_selected_group_invitation_not_photo_media(): void
    {
        $owner=$this->user('owner-share@test.local');[$group,$photo]=$this->photo($owner);$invite=GroupAccessInvite::makeFor($group,GroupAccessInvite::FULL,$owner->id);
        $this->actingAs($owner)->get(route('photos.show',[$group,$photo]))->assertInertia(fn(Assert$page)=>$page
            ->component('Photos/Show')->where('event.can_share_group',true)->where('event.share.url',$invite->url)
            ->where('event.share.access_code',$invite->access_code)->missing('photo.share_url'));
    }

    public function test_viewer_can_generate_missing_share_link_through_authorized_endpoint(): void
    {
        $owner=$this->user('owner-no-share@test.local');[$group,$photo]=$this->photo($owner);
        $this->actingAs($owner)->get(route('photos.show',[$group,$photo]))->assertInertia(fn(Assert$page)=>$page
            ->component('Photos/Show')->where('event.can_share_group',true)->where('event.share.url',null)->where('event.share.endpoint',route('groups.share-link',$group)));
        $response=$this->postJson(route('groups.share-link',$group))->assertOk();$invite=GroupAccessInvite::where('group_id',$group->id)->where('access_type',GroupAccessInvite::FULL)->firstOrFail();
        $response->assertJson(['success'=>true,'invitation'=>['code'=>$invite->access_code,'share_url'=>$invite->url,'access_type'=>'full_access','permissions'=>['find_my_photos'=>false,'view_highlights'=>false,'view_all_photos'=>true,'favourite'=>true,'download'=>true]],'share_url'=>$invite->url,'url'=>$invite->url,'access_code'=>$invite->access_code,'invitation_status'=>'created']);
    }

    public function test_share_endpoint_regenerates_expired_or_revoked_invitation_and_rejects_unauthorized_user(): void
    {
        $owner=$this->user('share-regenerate-owner@test.local');$outsider=$this->user('share-regenerate-outsider@test.local');[$group]=$this->photo($owner);$invite=GroupAccessInvite::makeFor($group,GroupAccessInvite::FULL,$owner->id);$oldToken=$invite->invitation_token;$invite->update(['is_active'=>false,'revoked_at'=>now(),'expires_at'=>now()->subMinute()]);
        $this->actingAs($outsider)->postJson(route('groups.share-link',$group))->assertForbidden();
        $response=$this->actingAs($owner)->postJson(route('groups.share-link',$group))->assertOk()->assertJsonPath('invitation_status','regenerated');$invite->refresh();
        $this->assertTrue($invite->isUsable());$this->assertNotSame($oldToken,$invite->invitation_token);$this->assertSame($invite->url,$response->json('url'));$this->assertStringContainsString('/join/',$response->json('url'));$this->assertStringNotContainsString('/media/',$response->json('url'));
    }

    public function test_share_endpoint_reuses_an_active_invitation(): void
    {
        $owner=$this->user('share-reuse-owner@test.local');[$group]=$this->photo($owner);$invite=GroupAccessInvite::makeFor($group,GroupAccessInvite::FULL,$owner->id);
        $this->actingAs($owner)->postJson(route('groups.share-link',$group))->assertOk()->assertJson(['success'=>true,'share_url'=>$invite->url,'invitation_status'=>'reused']);
        $this->assertDatabaseCount('group_access_invites',1);
    }
}
