<?php
namespace App\Http\Controllers;
use App\Contracts\ProtectedMediaStorage;
use App\Models\Group;
use App\Services\GroupAccessResolver;
use App\Services\Team\TeamAuthorization;
use Illuminate\Http\Request;
class FlipbookLogoController extends Controller {
 public function settings(Request$r,TeamAuthorization$authz,ProtectedMediaStorage$storage){$owner=$authz->ownerFor($r->user());abort_unless($owner&&$authz->allows($r->user(),$owner,'manage_branding'),403);return$this->response($owner->flipbookSetting()->first(),$storage,'private, no-store');}
 public function guest(Group$group,GroupAccessResolver$access,ProtectedMediaStorage$storage){abort_unless($group->is_active&&$access->role($group,null)==='anonymous_full',404);return$this->response($group->creator->flipbookSetting,$storage,'public, max-age=300');}
 private function response($setting,ProtectedMediaStorage$storage,string$cache){abort_unless($setting?->hasLogo()&&$storage->exists($setting->logo_object_key),404);$stream=$storage->readStream($setting->logo_object_key);return response()->stream(function()use($stream){fpassthru($stream);fclose($stream);},200,['Content-Type'=>$setting->logo_mime_type,'Content-Length'=>(string)$setting->logo_size_bytes,'Cache-Control'=>$cache,'X-Content-Type-Options'=>'nosniff']);}
}
