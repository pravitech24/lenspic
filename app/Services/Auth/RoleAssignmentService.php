<?php

namespace App\Services\Auth;

use App\Models\{AuditLog, Group, StudioTeamMembership, User};
use App\Services\Team\TeamAuthorization;
use Illuminate\Support\Facades\{DB, Schema};
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RoleAssignmentService
{
    public const ROLES = ['photographer', 'team_member', 'group_member', 'super_admin'];
    public const TEAM_PERMISSIONS = ['view_groups', 'create_groups', 'edit_groups', 'upload_photos', 'delete_photos', 'view_reports', 'analytics.view_assigned', 'analytics.export_assigned'];

    public function currentRole(User $user): string
    {
        if ($user->isSuperAdmin()) return 'super_admin';
        if ($user->studioMemberships()->where('status', 'active')->exists()) return 'team_member';
        if (in_array($user->account_type, ['photographer', 'studio'], true)) return 'photographer';
        return 'group_member';
    }

    public function scope(User $user): array
    {
        return [
            'account_type' => $user->account_type,
            'team' => $user->studioMemberships()->where('status', 'active')->get(['studio_owner_id', 'role', 'permissions', 'assigned_group_ids'])->toArray(),
            'groups' => $user->groups()->wherePivot('membership_status', 'active')->pluck('groups.id')->map(fn ($id) => (int) $id)->all(),
        ];
    }

    public function assign(User $actor, User $target, array $data): void
    {
        if ($data['assignment_role'] === 'super_admin' && $actor->is($target) && ! $target->isSuperAdmin()) {
            throw ValidationException::withMessages(['assignment_role' => 'You cannot assign Super Admin access to yourself.']);
        }
        DB::transaction(function () use ($actor, $target, $data) {
            $target = User::whereKey($target->id)->lockForUpdate()->firstOrFail();
            if ($target->isSuperAdmin() && $data['assignment_role'] !== 'super_admin'
                && User::where('role', 'super_admin')->where('status', 'active')->lockForUpdate()->count() <= 1) {
                throw ValidationException::withMessages(['assignment_role' => 'The last active Super Admin cannot be reassigned.']);
            }
            $before = ['role' => $this->currentRole($target), 'scope' => $this->scope($target)];
            $role = $data['assignment_role'];

            if ($role === 'photographer') {
                $target->update(['role' => 'user', 'is_admin' => false, 'account_type' => 'photographer', 'status' => $data['status'], 'role_assigned_at' => now()]);
                $target->studioMemberships()->where('status', 'active')->update(['status' => 'suspended', 'suspended_at' => now()]);
            } elseif ($role === 'team_member') {
                $owner = User::whereKey($data['studio_owner_id'])->whereIn('account_type', ['photographer', 'studio'])->where('status', 'active')->first();
                if (! $owner || $owner->is($target)) throw ValidationException::withMessages(['studio_owner_id' => 'Select an active parent Photographer account.']);
                $permissions = array_values(array_intersect(self::TEAM_PERMISSIONS, $data['permissions'] ?? []));
                $groupIds = Group::where('creator_id', $owner->id)->whereIn('id', $data['group_ids'] ?? [])->pluck('id')->map(fn ($id) => (int) $id)->all();
                if (array_intersect($permissions, ['analytics.view_assigned', 'analytics.export_assigned']) && ! $groupIds) throw ValidationException::withMessages(['group_ids' => 'Assign at least one Group when granting Analytics access.']);
                $target->update(['role' => 'user', 'is_admin' => false, 'account_type' => 'user', 'status' => $data['status'], 'role_assigned_at' => now()]);
                $target->studioMemberships()->where('status', 'active')->update(['status' => 'suspended', 'suspended_at' => now()]);
                StudioTeamMembership::updateOrCreate(
                    ['studio_owner_id' => $owner->id, 'user_id' => $target->id],
                    ['uuid' => (string) Str::uuid(), 'role' => 'editor', 'status' => 'active', 'permissions' => $permissions ?: ['view_groups'], 'assigned_group_ids' => $groupIds, 'invited_by' => $actor->id, 'joined_at' => now(), 'suspended_at' => null]
                );
            } elseif ($role === 'group_member') {
                $groupIds = Group::whereIn('id', $data['group_ids'] ?? [])->pluck('id');
                if ($groupIds->isEmpty()) throw ValidationException::withMessages(['group_ids' => 'Select at least one Group.']);
                $target->update(['role' => 'user', 'is_admin' => false, 'account_type' => 'user', 'status' => $data['status'], 'role_assigned_at' => now()]);
                $target->studioMemberships()->where('status', 'active')->update(['status' => 'suspended', 'suspended_at' => now()]);
                foreach ($groupIds as $groupId) $target->groups()->syncWithoutDetaching([$groupId => ['role' => 'member', 'joined_at' => now(), 'join_method' => 'admin_assignment', 'membership_status' => 'active', 'access_type' => $data['group_access_type']]]);
            } else {
                $target->update(['role' => 'super_admin', 'is_admin' => true, 'account_type' => 'user', 'status' => 'active', 'role_assigned_at' => now()]);
                $target->studioMemberships()->where('status', 'active')->update(['status' => 'suspended', 'suspended_at' => now()]);
            }

            $after = ['role' => $this->currentRole($target->fresh()), 'scope' => $this->scope($target->fresh())];
            AuditLog::create(['actor_id' => $actor->id, 'actor_type' => 'user', 'action' => 'roles.assignment.changed', 'subject_type' => User::class, 'subject_id' => $target->id, 'request_id' => (string) Str::uuid(), 'ip_address' => request()->ip(), 'user_agent' => Str::limit((string) request()->userAgent(), 1000, ''), 'before' => $before, 'after' => $after, 'metadata' => ['reason' => $data['reason'], 'permissions_added' => array_values(array_diff($after['scope']['team'][0]['permissions'] ?? [], $before['scope']['team'][0]['permissions'] ?? [])), 'permissions_removed' => array_values(array_diff($before['scope']['team'][0]['permissions'] ?? [], $after['scope']['team'][0]['permissions'] ?? []))]]);

            $target->update(['remember_token' => null]);
            if (Schema::hasTable('sessions')) DB::table('sessions')->where('user_id', $target->id)->delete();
        });
    }
}
