<?php

namespace App\Http\Controllers;

use App\Models\{AuditLog, Group, User};
use App\Services\Auth\RoleAssignmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Hash, Password};
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SuperAdminRoleAssignmentController extends Controller
{
    public function roles()
    {
        return view('super-admin.roles.governance', ['activeTab' => 'roles', 'roles' => [['name' => 'Photographer', 'scope' => 'Own photography workspace and Groups'], ['name' => 'Team Member', 'scope' => 'One parent Photographer account'], ['name' => 'Group Member', 'scope' => 'Explicitly assigned Groups only'], ['name' => 'Super Admin', 'scope' => 'Platform-wide administration']]]);
    }

    public function permissions()
    {
        return view('super-admin.roles.governance', ['activeTab' => 'permissions', 'permissions' => RoleAssignmentService::TEAM_PERMISSIONS, 'analyticsPermissions' => \App\Services\Analytics\AnalyticsAuthorization::PERMISSIONS]);
    }

    public function index(Request $request, RoleAssignmentService $roles)
    {
        $query = User::query()->with(['studioMemberships.owner:id,name,email'])->latest();
        if ($search = trim((string) $request->search)) $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%")->orWhere('mobile_e164', 'like', "%{$search}%"));
        if ($request->status) $query->where('status', $request->status);
        if ($request->role === 'super_admin') $query->where('role', 'super_admin');
        elseif ($request->role === 'photographer') $query->whereIn('account_type', ['photographer', 'studio'])->where('role', '!=', 'super_admin');
        elseif ($request->role === 'team_member') $query->whereHas('studioMemberships', fn ($q) => $q->where('status', 'active'));
        elseif ($request->role === 'group_member') $query->whereHas('groups', fn ($q) => $q->where('group_members.membership_status', 'active'))->whereDoesntHave('studioMemberships', fn ($q) => $q->where('status', 'active'))->whereNotIn('account_type', ['photographer', 'studio'])->where('role', '!=', 'super_admin');
        $users = $query->paginate(30)->withQueryString();
        $users->getCollection()->transform(fn ($user) => tap($user, fn ($u) => $u->assignment_role = $roles->currentRole($u)));
        return view('super-admin.roles.assignments-index', compact('users'));
    }

    public function edit(User $user, RoleAssignmentService $roles)
    {
        return view('super-admin.roles.assignment-edit', ['target' => $user, 'currentRole' => $roles->currentRole($user), 'currentScope' => $roles->scope($user), 'photographers' => User::whereIn('account_type', ['photographer', 'studio'])->where('status', 'active')->whereKeyNot($user->id)->orderBy('name')->get(['id', 'name', 'email']), 'groups' => Group::with('creator:id,name')->orderBy('name')->get(['id', 'name', 'creator_id']), 'permissions' => RoleAssignmentService::TEAM_PERMISSIONS]);
    }

    public function create()
    {
        return view('super-admin.roles.assignment-create', ['photographers' => User::whereIn('account_type', ['photographer', 'studio'])->where('status', 'active')->orderBy('name')->get(['id', 'name', 'email']), 'groups' => Group::with('creator:id,name')->orderBy('name')->get(['id', 'name', 'creator_id']), 'permissions' => RoleAssignmentService::TEAM_PERMISSIONS]);
    }

    public function store(Request $request, RoleAssignmentService $roles)
    {
        $data = $this->validatedAssignment($request) + $request->validate(['name' => ['required', 'string', 'max:100'], 'email' => ['required', 'email:rfc', 'max:255', 'unique:users,email'], 'phone' => ['nullable', 'string', 'max:30'], 'send_invitation' => ['nullable', 'boolean']]);
        $this->authorizePrivilegedAssignment($request, $data);
        $user = DB::transaction(function () use ($request, $roles, $data) {
            $user = User::create(['name' => $data['name'], 'email' => strtolower($data['email']), 'phone' => $data['phone'] ?? null, 'password' => Hash::make(Str::random(64)), 'role' => 'user', 'account_type' => 'user', 'status' => 'active', 'onboarding_completed_at' => now(), 'plan' => 'free']);
            $roles->assign($request->user(), $user, $data);
            return $user;
        });
        $message = 'User created and role assigned. No predictable password was generated.';
        if ($request->boolean('send_invitation')) $message .= Password::sendResetLink(['email' => $user->email]) === Password::RESET_LINK_SENT ? ' A secure password setup email was sent.' : ' The password setup email could not be sent; retry through the password-reset flow.';
        return redirect()->route('super-admin.role-assignments.edit', $user)->with('success', $message);
    }

    public function update(Request $request, User $user, RoleAssignmentService $roles)
    {
        $data = $this->validatedAssignment($request);
        $this->authorizePrivilegedAssignment($request, $data);
        $roles->assign($request->user(), $user, $data);
        return redirect()->route('super-admin.role-assignments.edit', $user)->with('success', 'Role and scoped access updated. The user must sign in again.');
    }

    public function history()
    {
        return view('super-admin.roles.history', ['audits' => AuditLog::with('actor')->where('action', 'roles.assignment.changed')->latest()->paginate(50)]);
    }

    private function validatedAssignment(Request $request): array
    {
        $request->merge(['group_access_type' => $request->input('group_access_type', 'full_access')]);
        return $request->validate(['assignment_role' => ['required', Rule::in(RoleAssignmentService::ROLES)], 'studio_owner_id' => ['required_if:assignment_role,team_member', 'nullable', 'integer', 'exists:users,id'], 'group_ids' => ['required_if:assignment_role,group_member', 'nullable', 'array'], 'group_ids.*' => ['integer', 'exists:groups,id'], 'group_access_type' => ['required', Rule::in(['full_access', 'partial_access'])], 'permissions' => ['nullable', 'array'], 'permissions.*' => [Rule::in(RoleAssignmentService::TEAM_PERMISSIONS)], 'status' => ['required', Rule::in(['active', 'suspended'])], 'reason' => ['required', 'string', 'min:8', 'max:500'], 'confirm_role_change' => ['accepted'], 'password' => ['nullable', 'string']]);
    }

    private function authorizePrivilegedAssignment(Request $request, array $data): void
    {
        if (! empty($data['permissions'])) $request->user()->can('permissions.assign') || abort(403);
        if ($data['assignment_role'] !== 'super_admin') return;
        $request->user()->can('roles.assign_super_admin') || abort(403);
        if (! $request->boolean('confirm_super_admin')) throw ValidationException::withMessages(['confirm_super_admin' => 'Confirm the Super Admin warning.']);
        if (! Hash::check((string) $request->password, $request->user()->password)) throw ValidationException::withMessages(['password' => 'Enter your current password to assign Super Admin access.']);
    }
}
