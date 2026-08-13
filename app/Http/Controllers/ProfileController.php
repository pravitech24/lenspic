<?php

namespace App\Http\Controllers;

use App\Services\ImageOptimizationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function show()
    {
        return redirect()->route('settings.profile');
    }

    public function update(Request $request)
    {
        $user = Auth::user();
        $request->validate([
            'name'  => 'required|string|max:100',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:15',
        ]);
        $user->update($request->only('name', 'email', 'phone'));
        return back()->with('success', 'Profile updated!');
    }

    public function updatePhoto(Request $request, ImageOptimizationService $imageOptimizationService)
    {
        $request->validate(['photo' => 'required|image|max:51200']);
        $user = Auth::user();
        if ($user->profile_photo) Storage::disk('public')->delete($user->profile_photo);
        $optimized = $imageOptimizationService->optimizeAndStore($request->file('photo'), 'avatars');
        $path = $optimized['path'];
        $user->update(['profile_photo' => $path]);
        return back()->with('success', 'Photo updated!');
    }
}
