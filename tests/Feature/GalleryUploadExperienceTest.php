<?php

namespace Tests\Feature;

use App\Models\{Group, UploadBatch, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class GalleryUploadExperienceTest extends TestCase
{
    use RefreshDatabase;

    private function owner(string $email): array
    {
        $user=User::create(['name'=>'Owner','email'=>$email,'password'=>Hash::make('x'),'status'=>'active']);
        $group=Group::create(['name'=>'Gallery','creator_id'=>$user->id]);
        $group->members()->attach($user->id,['role'=>'admin','membership_status'=>'active','access_type'=>'full_access']);
        return [$user,$group];
    }

    public function test_active_upload_recovery_is_scoped_to_user_and_group(): void
    {
        [$owner,$group]=$this->owner('upload-owner@test.local');[$other]=$this->owner('upload-other@test.local');
        $batch=UploadBatch::create(['uuid'=>(string)Str::uuid(),'group_id'=>$group->id,'user_id'=>$owner->id,'state'=>'queued','total_files'=>1,'pending_files'=>1]);
        $batch->files()->create(['client_filename'=>'one.jpg','state'=>'queued']);
        $this->actingAs($owner)->getJson(route('processing.active',$group))->assertOk()->assertJsonPath('upload.uuid',$batch->uuid)->assertJsonPath('upload.display_status','Preparing');
        $this->actingAs($other)->getJson(route('processing.active',$group))->assertForbidden();
    }

    public function test_gallery_contains_same_page_real_progress_and_progressive_insertion(): void
    {
        $source=file_get_contents(resource_path('js/Pages/Groups/Show.vue'));
        foreach (['new XMLHttpRequest()','request.upload.onprogress','Preparing your gallery','addReadyPhotos','schedulePoll','restoreUpload','clearTimeout(pollTimer.value)','@drop.prevent','UploadProgress'] as $token) {
            $this->assertStringContainsString($token,$source);
        }
        $this->assertStringNotContainsString('uploadForm.post', $source);
        $this->assertStringNotContainsString('Upload batches', $source);
        $progress=file_get_contents(resource_path('js/Components/UploadProgress.vue'));
        $this->assertStringContainsString('role="progressbar"',$progress);
        $this->assertStringContainsString('aria-valuenow',$progress);
        $this->assertStringContainsString('<UploadModal', $source);
        $this->assertStringNotContainsString('Upload quality', $source);
        $this->assertStringNotContainsString('Original Quality', $source);
        $this->assertStringNotContainsString('Storage Saver', $source);
        $this->assertStringNotContainsString('Destination folder', $source);
        $this->assertStringNotContainsString('quality_mode', $source);
        $this->assertStringNotContainsString("data.append('folder_id'", $source);
        $this->assertStringNotContainsString('<iframe', file_get_contents(resource_path('js/Components/UploadModal.vue')));
        $this->assertStringContainsString('Choose Photos', $source);
        $this->assertStringContainsString('ref="fileInput" type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" multiple', $source);
        $this->assertStringContainsString('@click.stop="openFilePicker"', $source);
        $this->assertStringContainsString('@change="handleFiles"', $source);
        $this->assertStringContainsString('item.file.lastModified===file.lastModified', $source);
        $this->assertStringContainsString("data.append('photos[]',item.file)", $source);
        $this->assertStringContainsString('uploadConcurrency', $source);
        $this->assertStringContainsString('retryTransport(index)', $source);
        $this->assertStringNotContainsString("uploadFiles.value.forEach(item=>data.append('photos[]',item.file))", $source);
    }

    public function test_upload_visibility_matches_participant_upload_policy(): void
    {
        [$owner,$group]=$this->owner('visibility-owner@test.local');
        [$participant]=$this->owner('visibility-participant@test.local');
        $group->members()->attach($participant->id,['role'=>'member','membership_status'=>'active','access_type'=>'full_access']);
        $this->actingAs($participant)->get(route('groups.gallery',$group))->assertInertia(fn($page)=>$page->where('canManage',false)->where('canUpload',false));
        $group->update(['allow_guest_upload'=>true]);
        $this->get(route('groups.gallery',$group))->assertInertia(fn($page)=>$page->where('canManage',false)->where('canUpload',true));
    }
}
