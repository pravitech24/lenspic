<?php
namespace App\Policies;
use App\Models\{Group,User};
use App\Services\GroupAccessResolver;
class GroupMemberPolicy { public function __construct(private GroupAccessResolver $access) {} public function manage(User $user,Group $group): bool{return $this->access->canManage($group,$user);} }
