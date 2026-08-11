<?php
namespace App\Policies;
use App\Models\{MediaAsset,User};
use Illuminate\Support\Facades\Gate;
class MediaAssetPolicy { public function view(User $user,MediaAsset $asset): bool{return $asset->photo?Gate::forUser($user)->allows('view',$asset->photo):false;} public function download(User $user,MediaAsset $asset): bool{return $asset->photo?Gate::forUser($user)->allows('download',$asset->photo):false;} }
