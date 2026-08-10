<?php
namespace App\Policies;
use App\Models\{Group,GroupAccessInvite,User};
use App\Services\GroupAccessResolver;
class GroupAccessInvitePolicy { public function __construct(private GroupAccessResolver $access) {} public function manage(User $user,Group $group): bool{return $this->access->canManage($group,$user);} public function update(User $user,GroupAccessInvite $invite): bool{return $this->access->canManage($invite->group,$user);} }
