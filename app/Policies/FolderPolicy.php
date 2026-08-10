<?php
namespace App\Policies;
use App\Models\{Folder,Group,User};
use App\Services\GroupAccessResolver;
class FolderPolicy {
 public function __construct(private GroupAccessResolver $access) {}
 public function viewAny(User $user,Group $group): bool { return $this->access->canViewGroup($group,$user); }
 public function view(User $user,Folder $folder): bool { return $this->access->canViewFullGallery($folder->group,$user)||($folder->highlighted&&$folder->group->isMember($user)); }
 public function create(User $user,Group $group): bool { return $this->access->canManage($group,$user); }
 public function update(User $user,Folder $folder): bool { return $this->access->canManage($folder->group,$user); }
 public function delete(User $user,Folder $folder): bool { return $this->update($user,$folder); }
 public function transfer(User $user,Folder $folder): bool { return $this->update($user,$folder); }
}
