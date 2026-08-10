<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Services\FaceRecognitionService;
use App\Services\ImageOptimizationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Collection;

class FaceController extends Controller
{
    public function showSelfie(Group $group)
    {
        abort_unless($group->isMember(auth()->user()),403); if (!$group->face_recognition_enabled) return redirect()->route('groups.index')->withErrors(['face'=>'Face recognition is not enabled for this group.']);

        $member = $group->members()->where('user_id', Auth::id())->first();
        $selfie = $member?->pivot?->selfie_path;
        return view('face.selfie', compact('group', 'selfie'));
    }

    public function uploadSelfie(Request $request, Group $group, ImageOptimizationService $imageOptimizationService)
    {
        abort_unless($group->isMember(auth()->user()),403); if(!$group->face_recognition_enabled)return back()->withErrors(['face'=>'Face recognition is not enabled for this group.']);

        $request->validate(['selfie' => 'required|image|max:51200']);
        $optimized = $imageOptimizationService->optimizeAndStore($request->file('selfie'), 'selfies/' . $group->id);
        $path = $optimized['path'];
        $group->members()->updateExistingPivot(Auth::id(), ['selfie_path' => $path]);
        return back()->with('success', 'Selfie saved! We\'ll find your photos.');
    }

    public function recognize(Request $request, Group $group, FaceRecognitionService $faceRecognitionService)
    {
        if (!$group->isMember(auth()->user()) || !$group->face_recognition_enabled) {
            return response()->json(['message' => 'Face recognition is not available on your plan.'], 403);
        }

        $member = $group->members()->where('user_id', Auth::id())->first();
        $selfiePath = $member?->pivot?->selfie_path;

        if (!$selfiePath) {
            return response()->json([
                'message' => 'Upload a selfie before searching for matches.',
            ], 422);
        }

        $photoLimit = (int) config('services.face_recognition.photo_limit', 0);
        $photosQuery = $group->photos()->latest();
        if ($photoLimit > 0) {
            $photosQuery->limit($photoLimit);
        }
        $photos = $photosQuery->get();

        if ($photos->isEmpty()) {
            return response()->json(['photos' => []]);
        }

        try {
            $matches = $faceRecognitionService->matchGroupPhotos($group, $selfiePath, $photos);
        } catch (\RuntimeException $exception) {
            report($exception);

            return response()->json([
                'message' => 'Face recognition is temporarily unavailable. Please try again later.',
            ], 502);
        }

        $matchedPhotos = $this->mapMatchesToPhotos($photos, $matches)->take((int) config('services.face_recognition.result_limit', 3));
        $request->session()->put('face_matches.'.$group->id, $matchedPhotos->pluck('id')->all());

        return response()->json([
            'photos' => $matchedPhotos->map(fn ($photo) => [
                'id' => $photo->id,
                'url' => $photo->url,
                'thumbnail_url' => $photo->thumbnail_url,
            ])->values(),
        ]);
    }

    private function mapMatchesToPhotos(Collection $photos, Collection $matches): Collection
    {
        if ($matches->isEmpty()) {
            return collect();
        }

        $photosById = $photos->keyBy('id');

        return $matches
            ->map(fn (array $match) => $photosById->get($match['photo_id']))
            ->filter()
            ->values();
    }
}
