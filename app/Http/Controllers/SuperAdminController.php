<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Group;
use App\Models\Photo;
use App\Models\Subscription;
use App\Models\Report;
use App\Models\{MediaAsset, RazorpayOrder, SubscriptionPlan, SubscriptionPlanAudit};
use App\Services\Media\MediaAssetCleanup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class SuperAdminController extends Controller
{
    private function checkSuperAdmin()
    {
        if (!auth()->user()?->isSuperAdmin()) abort(403, 'Super admin access required.');
    }

    // DASHBOARD
    public function dashboard()
    {
        $this->checkSuperAdmin();

        $plans = SubscriptionPlan::withCount('features')->get();
        $stats = [
            'total_users' => User::count(),
            'photographers' => User::whereIn('account_type', ['photographer', 'studio'])->count(),
            'active_photographers' => User::whereIn('account_type', ['photographer', 'studio'])->where('status', 'active')->count(),
            'total_groups' => Group::count(),
            'total_media' => MediaAsset::count(),
            'storage_bytes' => (int) MediaAsset::whereNotIn('state', ['deleted', 'purged'])->sum('size_bytes'),
            'active_subscriptions' => Subscription::where('status', 'active')->count(),
            'expiring_subscriptions' => Subscription::where('status', 'active')->whereBetween('expires_at', [now(), now()->addDays(30)])->count(),
            'monthly_payments' => RazorpayOrder::where('status', 'paid')->where('created_at', '>=', now()->startOfMonth())->count(),
            'failed_payments' => RazorpayOrder::whereIn('status', ['failed', 'expired'])->count(),
            'pending_payments' => RazorpayOrder::whereIn('status', ['created', 'pending'])->count(),
            'failed_jobs' => Schema::hasTable('failed_jobs') ? \DB::table('failed_jobs')->count() : 0,
            'queue_backlog' => Schema::hasTable('jobs') ? \DB::table('jobs')->count() : 0,
            'active_public_plans' => $plans->where('is_active', true)->where('is_public', true)->count(),
            'inactive_plans' => $plans->where('is_active', false)->count(),
            'configured_prices' => \App\Models\SubscriptionPlanPrice::where('is_active', true)->count(),
            'plans_missing_prices' => $plans->filter(fn ($plan) => ! $plan->currentPrice('quarterly') || ! $plan->currentPrice('yearly'))->count(),
            'plans_missing_features' => $plans->where('features_count', 0)->count(),
            'last_plan_update' => $plans->max('updated_at'),
        ];

        $recentUsers = User::latest()->limit(10)->get();
        $recentGroups = Group::with('creator')->withCount('photos', 'members')->latest()->limit(10)->get();
        $activeSubscriptions = Subscription::with('user')->where('status', 'active')->latest()->limit(10)->get();

        $recentActivity = SubscriptionPlanAudit::with('actor')->latest()->limit(8)->get();

        return view('super-admin.dashboard', compact('stats', 'recentUsers', 'recentGroups', 'activeSubscriptions', 'recentActivity'));
    }

    // --- USERS MANAGEMENT ---
    public function users(Request $request)
    {
        $this->checkSuperAdmin();

        $query = User::query();

        if ($request->search) {
            $query->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%');
        }

        if ($request->type === 'photographers') {
            $query->whereIn('account_type', ['photographer', 'studio']);
        } elseif ($request->type === 'team-members') {
            $query->whereHas('studioMemberships', fn ($membership) => $membership->where('status', 'active'));
        } elseif ($request->type === 'group-members') {
            $query->whereHas('groups', fn ($group) => $group->where('group_members.membership_status', 'active'));
        } elseif ($request->role) {
            $query->where('role', $request->role);
        }

        if ($request->status) {
            if ($request->status === 'inactive') {
                $query->where('created_at', '<', now()->subDays(30));
            }
        }

        $users = $query->latest()->paginate(50);

        return view('super-admin.users.index', compact('users'));
    }

    public function showUser(User $user)
    {
        $this->checkSuperAdmin();
        $subscriptions = $user->subscriptions()->latest()->get();
        $groups = $user->createdGroups()->with('members')->get();
        $storage = $user->storage_used_human;

        return view('super-admin.users.show', compact('user', 'subscriptions', 'groups', 'storage'));
    }

    public function editUser(User $user)
    {
        $this->checkSuperAdmin();
        return view('super-admin.users.edit', compact('user'));
    }

    public function updateUser(Request $request, User $user)
    {
        $this->checkSuperAdmin();

        $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20',
            'role' => 'required|in:user,admin,super_admin',
            'status' => 'required|in:active,suspended,banned',
        ]);

        $data = $request->only('name', 'email', 'phone', 'role');

        if ($user->role !== $request->role) {
            $data['role_assigned_at'] = now();
        }

        if ($request->filled('password')) {
            $request->validate(['password' => 'min:8']);
            $data['password'] = Hash::make($request->password);
        }

        // Handle user status (suspend/ban logic would go in a separate column or field)

        $user->update($data);

        return redirect()->route('super-admin.users.show', $user)->with('success', 'User updated successfully!');
    }

    public function promoteToAdmin(User $user)
    {
        $this->checkSuperAdmin();

        if ($user->isSuperAdmin()) {
            return back()->with('error', 'User is already a super admin.');
        }

        $user->update([
            'role' => 'admin',
            'role_assigned_at' => now(),
        ]);

        return back()->with('success', 'User promoted to admin.');
    }

    public function promoteToSuperAdmin(User $user)
    {
        $this->checkSuperAdmin();

        $user->update([
            'role' => 'super_admin',
            'role_assigned_at' => now(),
        ]);

        return back()->with('success', 'User promoted to super admin.');
    }

    public function demoteToUser(User $user)
    {
        $this->checkSuperAdmin();

        if (User::where('role', 'super_admin')->count() === 1 && $user->isSuperAdmin()) {
            return back()->with('error', 'Cannot demote the last super admin.');
        }

        $user->update([
            'role' => 'user',
            'role_assigned_at' => now(),
        ]);

        return back()->with('success', 'User demoted to regular user.');
    }

    public function deleteUser(User $user)
    {
        $this->checkSuperAdmin();

        if ($user->isSuperAdmin()) {
            return back()->with('error', 'Cannot delete super admins.');
        }

        $user->delete();

        return redirect()->route('super-admin.users')->with('success', 'User deleted.');
    }

    // --- SUBSCRIPTIONS MANAGEMENT ---
    public function subscriptions(Request $request)
    {
        $this->checkSuperAdmin();

        $query = Subscription::with('user');

        if ($request->plan) {
            $query->where('plan', $request->plan);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $subscriptions = $query->latest()->paginate(50);

        return view('super-admin.subscriptions.index', compact('subscriptions'));
    }

    public function showSubscription(Subscription $subscription)
    {
        $this->checkSuperAdmin();
        return view('super-admin.subscriptions.show', compact('subscription'));
    }

    public function updateSubscription(Request $request, Subscription $subscription)
    {
        $this->checkSuperAdmin();

        $request->validate([
            'plan' => 'required|in:free,standard,essential,premium,pro,business,enterprise',
            'status' => 'required|in:active,inactive,cancelled,suspended',
            'amount' => 'nullable|numeric',
            'expires_at' => 'nullable|date',
        ]);

        $subscription->update($request->only('plan', 'status', 'amount', 'expires_at'));

        return back()->with('success', 'Subscription updated.');
    }

    public function suspendSubscription(Subscription $subscription)
    {
        $this->checkSuperAdmin();

        $subscription->update(['status' => 'suspended']);

        return back()->with('success', 'Subscription suspended.');
    }

    public function activateSubscription(Subscription $subscription)
    {
        $this->checkSuperAdmin();

        $subscription->update(['status' => 'active']);

        return back()->with('success', 'Subscription activated.');
    }

    public function cancelSubscription(Subscription $subscription)
    {
        $this->checkSuperAdmin();

        $subscription->update(['status' => 'cancelled']);

        return back()->with('success', 'Subscription cancelled.');
    }

    // --- GROUPS MANAGEMENT ---
    public function groups(Request $request)
    {
        $this->checkSuperAdmin();

        $query = Group::with('creator')->withCount('photos', 'members');

        if ($request->search) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        if ($request->status) {
            $query->where('is_active', $request->status === 'active');
        }

        $groups = $query->latest()->paginate(50);

        return view('super-admin.groups.index', compact('groups'));
    }

    public function showGroup(Group $group)
    {
        $this->checkSuperAdmin();

        $members = $group->members()->paginate(20);
        $photos = $group->photos()->latest()->paginate(20);

        return view('super-admin.groups.show', compact('group', 'members', 'photos'));
    }

    public function deleteGroup(Group $group, MediaAssetCleanup $cleanup)
    {
        $this->checkSuperAdmin();

        $group->load('coverMediaAsset.variants', 'pendingCoverMediaAsset.variants');
        $covers = collect([$group->coverMediaAsset, $group->pendingCoverMediaAsset])->filter()->unique('id');
        $group->update(['cover_media_asset_id' => null, 'pending_cover_media_asset_id' => null]);
        foreach ($covers as $cover) $cleanup->schedule($cover);

        foreach ($group->photos as $photo) {
            \Storage::disk('public')->delete(array_filter([$photo->path, $photo->thumbnail_path]));
        }

        if ($group->cover_photo) {
            \Storage::disk('public')->delete($group->cover_photo);
        }

        $group->delete();

        return back()->with('success', 'Group deleted.');
    }

    public function toggleGroupStatus(Group $group)
    {
        $this->checkSuperAdmin();

        $group->update(['is_active' => !$group->is_active]);

        return back()->with('success', 'Group status updated.');
    }

    // --- REPORTS ---
    public function reports()
    {
        $this->checkSuperAdmin();

        $userActivityReport = Report::generateUserActivityReport();
        $groupActivityReport = Report::generateGroupActivityReport();
        $storageUsageReport = Report::generateStorageUsageReport();
        $subscriptionReport = Report::generateSubscriptionReport();

        return view('super-admin.reports', compact(
            'userActivityReport',
            'groupActivityReport',
            'storageUsageReport',
            'subscriptionReport'
        ));
    }

    public function exportReport(Request $request)
    {
        $this->checkSuperAdmin();

        $type = $request->type ?? 'user_activity';

        $report = match($type) {
            'user_activity' => Report::generateUserActivityReport(),
            'group_activity' => Report::generateGroupActivityReport(),
            'storage_usage' => Report::generateStorageUsageReport(),
            'subscription' => Report::generateSubscriptionReport(),
            default => []
        };

        return response()->json($report);
    }

    // --- ACTIONS ON USERS ---
    public function suspendUser(User $user)
    {
        $this->checkSuperAdmin();

        if ($user->isSuperAdmin()) {
            return back()->with('error', 'Cannot suspend super admins.');
        }

        $user->subscriptions()->update(['status' => 'suspended']);

        return back()->with('success', 'User suspended (subscriptions paused).');
    }

    public function banUser(User $user)
    {
        $this->checkSuperAdmin();

        if ($user->isSuperAdmin()) {
            return back()->with('error', 'Cannot ban super admins.');
        }

        // Mark user as inactive and suspend subscriptions
        $user->subscriptions()->update(['status' => 'suspended']);
        $user->createdGroups()->update(['is_active' => false]);

        return back()->with('success', 'User banned (all groups deactivated).');
    }

    public function reactivateUser(User $user)
    {
        $this->checkSuperAdmin();

        $user->createdGroups()->update(['is_active' => true]);

        return back()->with('success', 'User reactivated.');
    }
}
