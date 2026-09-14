<?php

namespace App\Services;

use App\Enums\AnonymousAccessMode;
use App\Enums\GroupAccessType;
use App\Enums\MembershipStatus;
use App\Models\Group;
use App\Models\User;

class GroupAccessResolver
{
    public function __construct(private \App\Services\Team\TeamAuthorization $team) {}
    public function role(Group $group, ?User $user): string
    {
        if ($user?->isSuperAdmin()) return 'platform_admin';
        if ($user && $group->creator_id === $user->id) return 'owner';
        if ($user && ($permission=$this->teamPermission($group,$user))) return $permission;
        if ($user) {
            $member = $group->membershipFor($user);
            if ($member && $member->pivot->membership_status === MembershipStatus::Active->value) {
                if ($member->pivot->role === 'admin') return 'admin';
                return $member->pivot->access_type === GroupAccessType::Full->value ? 'full_viewer' : 'partial_viewer';
            }
        }
        return match ($group->anonymous_access_mode ?? AnonymousAccessMode::Disabled->value) {
            AnonymousAccessMode::Full->value => 'anonymous_full',
            AnonymousAccessMode::FaceOnly->value => 'anonymous_face_only',
            default => 'denied',
        };
    }

    public function canViewGroup(Group $group, ?User $user): bool
    {
        return $group->is_active && $this->role($group, $user) !== 'denied';
    }

    public function canViewFullGallery(Group $group, ?User $user): bool
    {
        return in_array($this->role($group, $user), ['platform_admin', 'owner', 'admin', 'studio_admin', 'studio_photographer', 'studio_editor', 'studio_viewer', 'full_viewer', 'anonymous_full'], true);
    }

    public function canManage(Group $group, ?User $user): bool
    {
        $role=$this->role($group,$user);if(in_array($role,['platform_admin','owner','admin'],true))return true;if(str_starts_with($role,'studio_')&&$user&&$group->creator)return$this->team->allows($user,$group->creator,'edit_groups');return false;
    }
    private function teamPermission(Group$group,User$user):?string{$owner=$group->creator;if(!$owner)return null;foreach(['admin','photographer','editor','viewer']as$role){$m=$this->team->activeMembership($user,$owner);if($m?->role===$role&&$this->team->allows($user,$owner,'view_groups'))return'studio_'.$role;}return null;}
}
