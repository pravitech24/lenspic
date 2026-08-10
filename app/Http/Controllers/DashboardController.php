<?php

namespace App\Http\Controllers;

use App\Models\Photo;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user         = Auth::user();
        $myGroups     = $user->createdGroups()->withCount('photos', 'members')->latest()->limit(6)->get();
        $joinedGroups = $user->groups()->withCount('photos', 'members')->latest()->limit(6)->get();
        $recentPhotos = Photo::whereIn('group_id', $user->groups()->pluck('groups.id'))
            ->latest()->limit(12)->get();
        $totalPhotos  = $user->photos()->count();
        $totalGroups  = $myGroups->count() + $joinedGroups->count();

        return view('dashboard', compact('myGroups', 'joinedGroups', 'recentPhotos', 'totalPhotos', 'totalGroups'));
    }
}
