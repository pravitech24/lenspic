<?php
namespace App\Http\Controllers;
use App\Models\Group;
use App\Services\Branding\FlipbookBrandingPresenter;
use App\Services\GroupAccessResolver;
class PublicFlipbookController extends Controller {
 public function show(Group$group,GroupAccessResolver$access,FlipbookBrandingPresenter$presenter){abort_unless($group->is_active&&$access->role($group,null)==='anonymous_full',404);$group->load(['creator.flipbookSetting','creator.businessBranding']);$photos=$group->photos()->whereHas('mediaAsset',fn($q)=>$q->where('state','completed'))->with('mediaAsset.variants')->oldest('id')->get()->map(fn($p)=>['name'=>$p->original_filename,'url'=>route('media.show',[$p->mediaAsset->uuid,'optimized']),'thumbnail'=>route('media.show',[$p->mediaAsset->uuid,'thumbnail'])]);return view('guest.flipbook',['group'=>$group,'photos'=>$photos,'branding'=>$presenter->flipbook($group)]);}
}
