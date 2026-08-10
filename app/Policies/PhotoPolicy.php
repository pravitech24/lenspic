<?php
namespace App\Policies;
use App\Models\{Photo,User};
use App\Services\GroupAccessResolver;
class PhotoPolicy {
 public function __construct(private GroupAccessResolver $access) {}
 public function before(User $user): ?bool { return $user->status!=='active'?false:null; }
 public function view(User $user,Photo $photo): bool { $group=$photo->group;if($this->access->canViewFullGallery($group,$user))return true;if($this->access->role($group,$user)!=='partial_viewer')return false;$highlighted=$photo->folder_id&&$group->folders()->whereKey($photo->folder_id)->where('highlighted',true)->exists();$matched=in_array($photo->id,(array)request()->session()->get('face_matches.'.$group->id,[]),true);return $highlighted||$matched; }
 public function download(User $user,Photo $photo): bool { return $photo->group->downloads_enabled&&$this->view($user,$photo); }
 public function interact(User $user,Photo $photo): bool { return $this->view($user,$photo); }
 public function update(User $user,Photo $photo): bool { return $photo->group->isAdmin($user)||$photo->uploader_id===$user->id; }
 public function delete(User $user,Photo $photo): bool { return $this->update($user,$photo); }
}
