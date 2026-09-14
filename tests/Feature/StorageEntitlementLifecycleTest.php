<?php

namespace Tests\Feature;

use App\Jobs\PurgeDeletedMediaAsset;
use App\Models\{Group,MediaAsset,MediaQuotaUsageEvent,MediaVariant,Photo,StorageLedgerEntry,User};
use App\Services\Storage\{PlanEntitlements,StorageUsage};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{Hash,Queue};
use Tests\TestCase;

class StorageEntitlementLifecycleTest extends TestCase
{
    use RefreshDatabase;
    private function owner(string$email='owner@test.local'):User{return User::create(['name'=>'Owner','email'=>$email,'password'=>Hash::make('Password!12345'),'account_type'=>'photographer','status'=>'active','plan'=>'standard']);}
    private function asset(User$owner,?\DateTimeInterface$deletedAt=null):array{$token=fake()->uuid();$group=Group::create(['name'=>'Gallery','creator_id'=>$owner->id]);$photo=Photo::create(['group_id'=>$group->id,'uploader_id'=>$owner->id,'filename'=>'a.jpg','original_filename'=>'a.jpg','path'=>"media/$token/a.jpg",'file_size'=>100]);$asset=MediaAsset::create(['uuid'=>$token,'photo_id'=>$photo->id,'group_id'=>$group->id,'owner_id'=>$owner->id,'uploader_id'=>$owner->id,'kind'=>'photo','storage_disk'=>'private_local','original_object_key'=>"media/$token/a.jpg",'original_filename'=>'a.jpg','mime_type'=>'image/jpeg','size_bytes'=>100,'checksum_sha256'=>hash('sha256',$token),'state'=>'completed']);foreach([['original',100],['optimized',60],['thumbnail',20]]as[$type,$bytes])MediaVariant::create(['media_asset_id'=>$asset->id,'variant_type'=>$type,'storage_disk'=>'private_local','object_key'=>"media/$token/$type.jpg",'mime_type'=>'image/jpeg','size_bytes'=>$bytes,'checksum_sha256'=>hash('sha256',$type.$token),'state'=>'ready']);if($deletedAt){$photo->deleted_at=$deletedAt;$photo->save();$asset->deleted_at=$deletedAt;$asset->save();}return[$group,$photo,$asset];}

    public function test_standard_limits_have_one_authoritative_source():void{$u=$this->owner();$limits=app(PlanEntitlements::class)->for($u);$this->assertSame(100000,$limits['photo_limit']);$this->assertSame(5000,$limits['video_storage_limit_mb']);$this->assertSame(200000,$limits['photo_delete_reupload_limit']);$this->assertSame(10000,$limits['video_delete_reupload_limit_mb']);$this->assertSame(24,$limits['deleted_media_usage_release_hours']);}
    public function test_plan_limits_are_database_driven_and_changeable():void{$u=$this->owner();\App\Models\SubscriptionPlan::where('code','standard')->update(['photo_storage_limit'=>123456]);$this->assertSame(123456,app(PlanEntitlements::class)->for($u)['photo_limit']);}
    public function test_one_logical_photo_counts_once_regardless_of_variants_and_groups_are_scoped():void{$owner=$this->owner();$this->asset($owner);$other=$this->owner('other@test.local');$this->asset($other);$summary=app(StorageUsage::class)->summary($owner);$this->assertSame(1.0,$summary['counted_photos']);$this->assertSame(1,$summary['total_groups']);}
    public function test_deleted_photo_remains_counted_for_24_hours_then_is_released():void{$owner=$this->owner();$this->asset($owner,now()->subHours(23));$this->assertSame(1.0,app(StorageUsage::class)->summary($owner)['counted_photos']);MediaAsset::withTrashed()->first()->forceFill(['deleted_at'=>now()->subHours(25)])->save();$this->assertSame(0.0,app(StorageUsage::class)->summary($owner)['counted_photos']);}
    public function test_delete_usage_is_idempotent_and_restore_does_not_add_usage():void{$owner=$this->owner();[$group,$photo,$asset]=$this->asset($owner);$usage=app(StorageUsage::class);$usage->record($owner,'photo_deleted',1,'delete-'.$asset->uuid,$group->id,$asset->id,$owner->id);$usage->record($owner,'photo_deleted',1,'delete-'.$asset->uuid,$group->id,$asset->id,$owner->id);$this->assertSame(1,MediaQuotaUsageEvent::count());$this->assertSame(1.0,$usage->summary($owner)['photo_deletes']);}
    public function test_delete_route_soft_deletes_and_schedules_delayed_purge():void{Queue::fake();$owner=$this->owner();[$group,$photo,$asset]=$this->asset($owner);$this->actingAs($owner)->delete(route('photos.destroy',[$group,$photo]))->assertRedirect();$this->assertSoftDeleted('photos',['id'=>$photo->id]);$this->assertSoftDeleted('media_assets',['id'=>$asset->id]);Queue::assertPushed(PurgeDeletedMediaAsset::class);$this->assertSame(1.0,app(StorageUsage::class)->summary($owner)['counted_photos']);}
    public function test_reconciliation_dry_run_does_not_change_counter():void{$owner=$this->owner();StorageLedgerEntry::create(['owner_id'=>$owner->id,'event_type'=>'fixture','byte_delta'=>500,'idempotency_key'=>'reconcile-fixture']);$this->artisan('lenspic:reconcile-storage-usage',['--dry-run'=>true])->assertSuccessful();$this->assertSame(0,(int)$owner->fresh()->storage_used);}
}
