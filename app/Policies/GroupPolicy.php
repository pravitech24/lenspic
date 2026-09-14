<?php
namespace App\Policies;
use App\Models\{Group,User};
use App\Services\GroupAccessResolver;
class GroupPolicy {
 public function __construct(private GroupAccessResolver $access) {}
 public function before(User $user): ?bool { return in_array($user->status, ['suspended','banned'], true) ? false : null; }
 public function view(User $user,Group $group): bool { return $this->access->canViewGroup($group,$user); }
 public function viewFullGallery(User $user,Group $group): bool { return $this->access->canViewFullGallery($group,$user); }
 public function create(User $user): bool { return $user->can('groups.create'); }
 public function update(User $user,Group $group): bool { return $this->access->canManage($group,$user); }
 public function delete(User $user,Group $group): bool { return $user->isSuperAdmin()||$group->creator_id===$user->id; }
 public function upload(User $user,Group $group): bool { $owner=$group->creator;return ($owner&&app(\App\Services\Team\TeamAuthorization::class)->allows($user,$owner,'upload_photos'))||$this->access->canManage($group,$user)||($group->allow_guest_upload&&$group->isMember($user)); }
 public function manageMembers(User $user,Group $group): bool { return ($group->creator&&app(\App\Services\Team\TeamAuthorization::class)->allows($user,$group->creator,'manage_participants'))||$this->access->canManage($group,$user); }
 public function manageInvitations(User $user,Group $group): bool { return $this->manageMembers($user,$group); }
 public function leave(User $user,Group $group): bool { return $group->creator_id!==$user->id&&$group->isMember($user); }
}
