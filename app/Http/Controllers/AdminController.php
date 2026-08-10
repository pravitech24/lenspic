<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\Photo;
use App\Models\User;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    private function checkAdmin()
    {
        if (!auth()->user()?->isAdmin()) abort(403, 'Admin access required.');
    }

    public function dashboard()
    {
        $this->checkAdmin();
        $stats = [
            'total_users'  => User::count(),
            'total_groups' => Group::count(),
            'total_photos' => Photo::count(),
            'new_users_today'  => User::whereDate('created_at', today())->count(),
            'new_groups_today' => Group::whereDate('created_at', today())->count(),
            'new_photos_today' => Photo::whereDate('created_at', today())->count(),
            'active_subscriptions' => Subscription::where('status', 'active')->count(),
        ];
        $recentUsers  = User::latest()->limit(8)->get();
        $recentGroups = Group::with('creator')->withCount('photos','members')->latest()->limit(8)->get();
        return view('admin.dashboard', compact('stats', 'recentUsers', 'recentGroups'));
    }

    // --- USERS ---
    public function users(Request $request)
    {
        $this->checkAdmin();
        $q = User::query();
        if ($request->search) $q->where('name','like','%'.$request->search.'%')->orWhere('email','like','%'.$request->search.'%');
        $users = $q->latest()->paginate(20);
        return view('admin.users', compact('users'));
    }

    public function editUser(User $user)
    {
        $this->checkAdmin();
        return view('admin.user-edit', compact('user'));
    }

    public function updateUser(Request $request, User $user)
    {
        $this->checkAdmin();
        $request->validate([
            'name'     => 'required|string|max:100',
            'email'    => 'required|email|unique:users,email,'.$user->id,
            'is_admin' => 'boolean',
        ]);
        $data = $request->only('name','email','phone');
        $data['is_admin'] = $request->boolean('is_admin');
        if ($request->filled('password')) {
            $request->validate(['password' => 'min:8']);
            $data['password'] = Hash::make($request->password);
        }
        $user->update($data);
        return redirect()->route('admin.users')->with('success','User updated!');
    }

    public function deleteUser(User $user)
    {
        $this->checkAdmin();
        if ($user->is_admin) return back()->with('error','Cannot delete an admin user.');
        $user->delete();
        return back()->with('success','User deleted.');
    }

    // --- GROUPS ---
    public function groups(Request $request)
    {
        $this->checkAdmin();
        $q = Group::with('creator')->withCount('photos','members');
        if ($request->search) $q->where('name','like','%'.$request->search.'%');
        $groups = $q->latest()->paginate(20);
        return view('admin.groups', compact('groups'));
    }

    public function deleteGroup(Group $group)
    {
        $this->checkAdmin();
        foreach ($group->photos as $photo) {
            \Storage::disk('public')->delete(array_filter([$photo->path, $photo->thumbnail_path]));
        }
        if ($group->cover_photo) \Storage::disk('public')->delete($group->cover_photo);
        $group->delete();
        return back()->with('success','Group deleted.');
    }

    public function toggleGroupStatus(Group $group)
    {
        $this->checkAdmin();
        $group->update(['is_active' => !$group->is_active]);
        return back()->with('success','Group status updated.');
    }

    // --- PHOTOS ---
    public function photos(Request $request)
    {
        $this->checkAdmin();
        $q = Photo::with('group','uploader');
        if ($request->search) $q->where('original_filename','like','%'.$request->search.'%');
        $photos = $q->latest()->paginate(40);
        return view('admin.photos', compact('photos'));
    }

    public function deletePhoto(Photo $photo)
    {
        $this->checkAdmin();
        \Storage::disk('public')->delete(array_filter([$photo->path, $photo->thumbnail_path]));
        $photo->delete();
        return back()->with('success','Photo deleted.');
    }
}
