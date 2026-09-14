<?php

namespace App\Http\Controllers;

use App\Services\FaceRecognitionService;
use App\Models\Group;
use Illuminate\Http\Request;
use App\Services\GroupAccessResolver;

class GuestController extends Controller
{
    public function viewGroup(Group $group, GroupAccessResolver $access, \App\Services\Branding\BusinessBrandingPresenter $presenter)
    {
        abort_unless($group->is_active && $access->role($group, null) === "anonymous_full", 404);
        $photos = $group->photos()->latest()->paginate(30);
        $group->load('creator.businessBranding');$branding=$presenter->gallery($group);
        return view('guest.group', compact('group', 'photos','branding'));
    }

    public function invite(Request $request, string $code)
    {
        $groupId = $request->query('album');
        $accessType = $request->query('access', 'guest');

        if (!$groupId) {
            abort(404);
        }

        $group = Group::find($groupId);
        if (!$group || !$group->is_active) {
            abort(404);
        }

        if ($accessType === 'viewer') {
            return redirect()->route('groups.join-code');
        }

        return redirect()->route('guest.group', ['group' => $group]);
    }

    public function selfieMatch(Request $request, Group $group, FaceRecognitionService $faceRecognitionService, GroupAccessResolver $access)
    {
        abort_unless($group->is_active && in_array($access->role($group, null), ["anonymous_face_only", "anonymous_full"], true), 404);
        abort(410, 'Join the Group and choose Find My Photos to review biometric consent.');
    }
}
