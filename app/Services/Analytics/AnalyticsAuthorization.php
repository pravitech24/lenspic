<?php

namespace App\Services\Analytics;

use App\Models\{Group, StudioTeamMembership, User};
use App\Services\Billing\AccountEntitlements;

class AnalyticsAuthorization
{
    public const VIEW_OWN = 'analytics.view_own';
    public const EXPORT_OWN = 'analytics.export_own';
    public const VIEW_ASSIGNED = 'analytics.view_assigned';
    public const EXPORT_ASSIGNED = 'analytics.export_assigned';
    public const VIEW_ALL = 'analytics.view_all';
    public const EXPORT_ALL = 'analytics.export_all';
    public const PERMISSIONS = [self::VIEW_OWN, self::EXPORT_OWN, self::VIEW_ASSIGNED, self::EXPORT_ASSIGNED, self::VIEW_ALL, self::EXPORT_ALL];

    public function context(User $actor, bool $export = false): ?array
    {
        if ($actor->isSuperAdmin()) return ['permission' => $export ? self::EXPORT_ALL : self::VIEW_ALL, 'owner' => null, 'group_ids' => null];
        if (in_array($actor->account_type, ['photographer', 'studio'], true)) return ['permission' => $export ? self::EXPORT_OWN : self::VIEW_OWN, 'owner' => $actor, 'group_ids' => null];

        $permission = $export ? self::EXPORT_ASSIGNED : self::VIEW_ASSIGNED;
        $membership = StudioTeamMembership::with('owner')->where('user_id', $actor->id)->where('status', 'active')
            ->whereJsonContains('permissions', $permission)->oldest()->first();
        if (! $membership) return null;

        return ['permission' => $permission, 'owner' => $membership->owner, 'group_ids' => collect($membership->assigned_group_ids ?: [])->map(fn ($id) => (int) $id)->unique()->values()->all()];
    }

    public function roleEligible(User $actor): bool
    {
        return $actor->isSuperAdmin() || in_array($actor->account_type, ['photographer', 'studio'], true) || $this->context($actor) !== null;
    }

    public function entitled(array $context): bool
    {
        return $context['owner'] === null || app(AccountEntitlements::class)->allows($context['owner'], 'analytics');
    }

    public function groupQuery(array $context)
    {
        $query = Group::query();
        if ($context['owner']) $query->where('creator_id', $context['owner']->id);
        if (is_array($context['group_ids'])) $query->whereIn('id', $context['group_ids']);
        return $query;
    }
}
