<?php

namespace App\Http\Controllers;

use App\Models\Group;
use Illuminate\Http\Request;

class FindMyPhotosController extends Controller
{
    public function __invoke(Request $request, Group $group)
    {
        // This explicit feature entry is distinct from an old auth intended URL.
        if (!$request->user()) {
            $request->session()->put('find_my_photos_intent', [
                'group_id' => $group->id,
                'expires_at' => now()->addMinutes(15)->timestamp,
            ]);
            return redirect()->route('login');
        }
        abort_unless($group->isMember($request->user()) && $group->is_active && $group->face_recognition_enabled, 403);
        $request->session()->forget('find_my_photos_intent');
        return redirect()->route('biometric.page', $group);
    }
}
