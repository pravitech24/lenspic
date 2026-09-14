<?php

namespace App\Http\Controllers;

use App\Contracts\ProtectedMediaStorage;
use App\Models\Group;
use Illuminate\Http\Request;

class BusinessBrandingLogoController extends Controller
{
    public function settings(Request$request,ProtectedMediaStorage$storage){$branding=$request->user()->businessBranding()->first();abort_unless($branding?->hasLogo(),404);return$this->response($branding,$storage);}
    public function gallery(Group$group,ProtectedMediaStorage$storage){abort_unless($group->is_active,404);$branding=$group->creator->businessBranding;abort_unless($branding?->hasLogo(),404);return$this->response($branding,$storage);}
    private function response($branding,ProtectedMediaStorage$storage){abort_unless($storage->exists($branding->logo_object_key),404);$stream=$storage->readStream($branding->logo_object_key);return response()->stream(function()use($stream){fpassthru($stream);fclose($stream);},200,['Content-Type'=>$branding->logo_mime_type,'Content-Length'=>(string)$branding->logo_size_bytes,'Cache-Control'=>'private, max-age=300','X-Content-Type-Options'=>'nosniff']);}
}
