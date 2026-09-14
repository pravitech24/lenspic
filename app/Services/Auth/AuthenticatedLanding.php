<?php

namespace App\Services\Auth;

use App\Models\{User, Group};
use Illuminate\Http\Request;

class AuthenticatedLanding
{
    public function routeName(User $user): string
    {
        if ($user->isSuperAdmin()) {
            return 'super-admin.dashboard';
        }

        if (! app(OnboardingState::class)->complete($user)) {
            return 'onboarding.resume';
        }

        // Team and group members share the authorized Groups workspace. Its
        // queries remain scoped by the existing membership/ownership policies.
        return $user->studioMemberships()->where('status', 'active')->exists()
            || $user->groups()->wherePivot('membership_status', 'active')->exists()
            ? 'groups.index'
            : 'dashboard';
    }

    public function url(User $user): string
    {
        return route($this->routeName($user));
    }
    public function afterAuthentication(Request $request): string
    {
        $user = $request->user();
        if ($user->isSuperAdmin()) return $this->url($user);
        $complete = app(OnboardingState::class)->complete($user);
        if ($request->session()->has('validated_group_invitation')) return route($complete ? 'groups.join.complete' : 'onboarding.resume');
        if (!$complete && app(OnboardingState::class)->photographer($user)) return route('onboarding.resume');
        if ($pending = $request->session()->get('pending_team_invitation')) return route('team-invitations.show', [$pending['uuid'], $pending['token']]);
        if ($pending = $request->session()->get('pending_invitation')) return route('invitations.show', $pending);
        if (!$complete) return route('onboarding.resume');

        // Consume only a recent explicit feature request, never a saved capture URL.
        $intent = $request->session()->pull('find_my_photos_intent');
        if (is_array($intent) && ($intent['expires_at'] ?? 0) > now()->timestamp) {
            $group = Group::find($intent['group_id'] ?? null);
            if ($group && $group->is_active && $group->face_recognition_enabled && $group->isMember($user)) {
                $request->session()->forget('url.intended');
                return route('biometric.page', $group);
            }
        }

        // Only use intended destinations whose authorization we can establish here.
        $intended = $request->session()->pull('url.intended');
        if (is_string($intended)) {
            $parts = parse_url($intended);
            $base = parse_url(url('/'));
            if ($parts && !isset($parts['user'], $parts['pass'])
                && (!isset($parts['host']) || ($parts['host'] === $base['host'] && ($parts['scheme'] ?? '') === $base['scheme'] && ($parts['port'] ?? null) === ($base['port'] ?? null)))) {
                $path = $parts['path'] ?? '';
                if (in_array($path, ['/dashboard', '/groups'], true)) return url($path);
                if (preg_match('#^/groups/([0-9]+)$#D', $path, $match)) {
                    $group = Group::find($match[1]);
                    if ($group && $user->can('view', $group)) return route('groups.show', $group);
                }
            }
        }
        return $this->url($user);
    }

}
