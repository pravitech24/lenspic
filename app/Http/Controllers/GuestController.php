<?php

namespace App\Http\Controllers;

use App\Services\FaceRecognitionService;
use App\Models\Group;
use Illuminate\Http\Request;

class GuestController extends Controller
{
    public function viewGroup(Group $group)
    {
        if (!$group->is_active) abort(404);
        $photos = $group->photos()->latest()->paginate(30);
        return view('guest.group', compact('group', 'photos'));
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
            return redirect()->route('guest.selfie', ['group' => $group]);
        }

        return redirect()->route('guest.group', ['group' => $group]);
    }

    public function selfieMatch(Request $request, Group $group, FaceRecognitionService $faceRecognitionService)
    {
        $request->validate([
            'name'   => 'required|string',
            'phone'  => 'required|string',
            'selfie' => 'required|image|max:5120',
        ]);

        $photoLimit = (int) config('services.face_recognition.photo_limit', 0);
        $photosQuery = $group->photos()->latest();
        if ($photoLimit > 0) {
            $photosQuery->limit($photoLimit);
        }
        $photos = $photosQuery->get();

        if ($photos->isEmpty()) {
            return view('guest.results', [
                'group' => $group,
                'photos' => collect(),
                'name' => $request->name,
            ]);
        }

        try {
            $matches = $faceRecognitionService->matchGroupPhotos($group, $request->file('selfie'), $photos);
        } catch (\RuntimeException $exception) {
            report($exception);

            return back()
                ->withInput($request->only(['name', 'phone']))
                ->withErrors(['selfie' => 'Face recognition is temporarily unavailable. Please try again later.']);
        }

        $photosById = $photos->keyBy('id');
        $matchedPhotos = $matches
            ->map(fn (array $match) => $photosById->get($match['photo_id']))
            ->filter()
            ->values()
            ->take((int) config('services.face_recognition.result_limit', 3));

        return view('guest.results', [
            'group' => $group,
            'photos' => $matchedPhotos,
            'name' => $request->name,
        ]);
    }
}
