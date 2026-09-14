<?php

namespace App\Services\Auth;

use App\Models\User;

class OnboardingState
{
    public function photographer(User $user): bool
    {
        // These are the assignment fields used by role governance and authorization.
        // Group ownership/permissions alone do not constitute a Photographer assignment.
        return in_array($user->account_type, ['photographer', 'studio'], true)
            || in_array($user->role, ['photographer', 'studio'], true);
    }

    public function complete(User $user): bool
    {
        if ($user->isSuperAdmin()) return true;
        if (!$this->photographer($user)) return (bool)$user->onboarding_completed_at;
        $profile = $user->photographerProfile;
        if ($profile) return filled($profile->first_name) && filled($profile->last_name)
            && filled($profile->company_name) && filled($profile->company_email);
        // The existing migration explicitly completed legacy accounts without a
        // studio record. A later assignment cannot inherit ordinary-user setup.
        return $user->onboarding_completed_at
            && (!$user->role_assigned_at || $user->role_assigned_at->lt($user->onboarding_completed_at));
    }

    public function step(User $user): ?string
    {
        if ($this->complete($user)) return null;
        if ($this->photographer($user)) return 'profile';
        // Default role=user/account_type=user is not evidence of an explicit choice.
        if ($user->role_assigned_at || $user->onboarding_step === 'user_profile_pending'
            || $user->studioMemberships()->where('status', 'active')->exists()) return 'profile';
        return 'role';
    }
}
