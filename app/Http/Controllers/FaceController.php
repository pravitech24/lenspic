<?php

namespace App\Http\Controllers;

use App\Models\Group;
use Illuminate\Http\Request;

class FaceController extends Controller
{
    public function showSelfie(Request $request, Group $group)
    {
        abort_unless($group->isMember($request->user()) && $group->is_active && $group->face_recognition_enabled, 403);
        return redirect()->route('biometric.page', $group);
    }

    public function uploadSelfie(Request $request, Group $group)
    {
        abort_unless($group->isMember($request->user()), 403);
        abort(410, 'Choose Find My Photos and grant consent before submitting a selfie.');
    }

    public function recognize(Request $request, Group $group)
    {
        abort_unless($group->isMember($request->user()), 403);
        abort(410, 'Use the consent-gated Find My Photos search.');
    }
}
