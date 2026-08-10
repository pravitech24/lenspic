<?php

namespace App\Services;

use App\Enums\AnonymousAccessMode;
use App\Enums\GroupAccessType;
use App\Enums\MembershipStatus;
use App\Models\Group;
use App\Models\User;

class GroupAccessResolver
{
    public function role(Group $group, ?User $user): string
    {
        if ($user?->isSuperAdmin()) return 'platform_admin';
        if ($user && $group->creator_id === $user->id) return 'owner';
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
        return in_array($this->role($group, $user), ['platform_admin', 'owner', 'admin', 'full_viewer', 'anonymous_full'], true);
    }

    public function canManage(Group $group, ?User $user): bool
    {
        return in_array($this->role($group, $user), ['platform_admin', 'owner', 'admin'], true);
    }
}
