<?php
namespace App\Http\Controllers;
use App\Http\Requests\UpdateFlipbookSettingsRequest;
use App\Services\Branding\{FlipbookBrandingPresenter,FlipbookLogoStorage};
use App\Services\Team\TeamAuthorization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
class FlipbookSettingsController extends Controller {
 public function __construct(private TeamAuthorization$authz,private FlipbookBrandingPresenter$presenter){}
 private function owner(Request$r){$owner=$this->authz->ownerFor($r->user());abort_unless($owner&&$this->authz->allows($r->user(),$owner,'manage_branding'),403);return$owner;}
 public function edit(Request$r){$owner=$this->owner($r);$owner->load(['flipbookSetting','businessBranding']);return Inertia::render('Settings/Flipbook',['section'=>'flipbook','settings'=>$this->presenter->settings($owner)]);}
 public function update(UpdateFlipbookSettingsRequest$r,FlipbookLogoStorage$logos){$owner=$this->owner($r);$current=$owner->flipbookSetting()->first();$old=$current?->logo_object_key;$new=null;$data=$r->safe()->except(['logo','remove_logo']);try{if($r->hasFile('logo')){$stored=$logos->store($r->file('logo'),$owner->id);$new=$stored['logo_object_key'];$data=array_merge($data,$stored);}elseif($r->boolean('remove_logo'))$data=array_merge($data,['logo_disk'=>null,'logo_object_key'=>null,'logo_mime_type'=>null,'logo_size_bytes'=>null]);DB::transaction(fn()=>$owner->flipbookSetting()->updateOrCreate([],$data));}catch(\Illuminate\Validation\ValidationException$e){throw$e;}catch(\Throwable$e){if($new)try{$logos->delete($new);}catch(\Throwable){}report($e);return back()->withErrors(['logo'=>'We could not upload the logo. Please try again.'])->withInput();}if($old&&($new||$r->boolean('remove_logo')))try{$logos->delete($old);}catch(\Throwable$e){report($e);}return back()->with('success','Flipbook settings updated successfully.');}
}
