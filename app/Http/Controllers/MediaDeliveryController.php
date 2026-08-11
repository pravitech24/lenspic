<?php
namespace App\Http\Controllers;
use App\Models\MediaAsset;
use App\Services\GroupAccessResolver;
use App\Services\Media\MediaDeliveryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
class MediaDeliveryController extends Controller {
 public function show(Request $request,MediaAsset $mediaAsset,string $variant,GroupAccessResolver $access,MediaDeliveryService $delivery){abort_unless(in_array($variant,['original','optimized','thumbnail','watermarked'],true),404);$photo=$mediaAsset->photo;abort_unless($photo&&$photo->group_id===$mediaAsset->group_id,404);if($request->user()){Gate::forUser($request->user())->authorize($request->boolean('download')?'download':'view',$mediaAsset);}else{$role=$access->role($mediaAsset->group,null);$matched=in_array($photo->id,(array)$request->session()->get('guest_face_matches.'.$mediaAsset->group_id,[]),true);abort_unless($role==='anonymous_full'||($role==='anonymous_face_only'&&$matched),403);} $record=$variant==='original'?$mediaAsset->variant('original'):($mediaAsset->variant($variant)??$mediaAsset->variant('optimized'));abort_unless($record&&$record->state==='ready',404);return $delivery->deliver($record,$request->boolean('download')); }
}
