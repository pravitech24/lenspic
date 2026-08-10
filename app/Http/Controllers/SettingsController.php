<?php

namespace App\Http\Controllers;

use App\Services\ImageOptimizationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class SettingsController extends Controller
{
    public function index()    { return redirect()->route('settings.profile'); }
    public function profile()  { return view('settings.profile',  ['user' => Auth::user()]); }
    public function branding() { return view('settings.branding', ['user' => Auth::user()]); }
    public function watermark(){ return view('settings.watermark',['user' => Auth::user()]); }
    public function team()     { return view('settings.team',     ['user' => Auth::user()]); }
    public function portfolio(){ return view('settings.portfolio',['user' => Auth::user()]); }
    public function wallet()   { return view('settings.wallet',   ['user' => Auth::user()]); }
    public function transactions(){ return view('settings.transactions', ['user' => Auth::user()]); }
    public function subscription(){ return view('settings.subscription',['user' => Auth::user()]); }

    public function updateProfile(Request $request, ImageOptimizationService $imageOptimizationService)
    {
        $user = Auth::user();
        $request->validate([
            'first_name' => 'required|string|max:60',
            'last_name'  => 'nullable|string|max:60',
            'email'      => 'required|email|unique:users,email,'.$user->id,
            'phone'      => 'nullable|string|max:15',
        ]);

        $name = trim($request->first_name . ' ' . $request->last_name);
        $data = ['name' => $name, 'email' => $request->email, 'phone' => $request->phone];

        if ($request->filled('password')) {
            $request->validate(['password' => 'min:8|confirmed']);
            $data['password'] = Hash::make($request->password);
        }

        if ($request->hasFile('profile_photo')) {
            if ($user->profile_photo) Storage::disk('public')->delete($user->profile_photo);
            $optimized = $imageOptimizationService->optimizeAndStore($request->file('profile_photo'), 'avatars');
            $data['profile_photo'] = $optimized['path'];
        }

        $user->update($data);
        return back()->with('success', 'Profile updated successfully!');
    }

    public function updateBranding(Request $request, ImageOptimizationService $imageOptimizationService)
    {
        $user = Auth::user();
        $request->validate(['studio_name' => 'nullable|string|max:100', 'logo' => 'nullable|image|max:51200']);
        $meta = $user->meta ?? [];
        $meta['studio_name']   = $request->studio_name;
        $meta['studio_tagline']= $request->studio_tagline;
        $meta['brand_color']   = $request->brand_color ?? '#6366f1';
        if ($request->hasFile('logo')) {
            if (!empty($meta['logo'])) Storage::disk('public')->delete($meta['logo']);
            $optimized = $imageOptimizationService->optimizeAndStore($request->file('logo'), 'logos');
            $meta['logo'] = $optimized['path'];
        }
        $user->update(['meta' => $meta]);
        return back()->with('success', 'Branding updated!');
    }

    public function updateWatermark(Request $request)
    {
        $user = Auth::user();
        $meta = $user->meta ?? [];
        $meta['watermark_text']     = $request->watermark_text;
        $meta['watermark_position'] = $request->watermark_position ?? 'bottom-right';
        $meta['watermark_opacity']  = $request->watermark_opacity ?? 70;
        $user->update(['meta' => $meta]);
        return back()->with('success', 'Watermark settings saved!');
    }
}
