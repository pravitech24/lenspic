<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\Photo;
use App\Services\ImageOptimizationService;
use App\Services\Media\PrivateMediaIngestor;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class PhotoController extends Controller
{
    public function index(Group $group)
    {
        $this->requireFullAccess($group);
        return app(LensPicUiController::class)->show(request(), $group);
    }

    public function store(Request $request, Group $group, ImageOptimizationService $imageOptimizationService, PrivateMediaIngestor $mediaIngestor)
    {
        if (Auth::guest()) {
            if ($request->wantsJson()) {
                return response()->json(['message' => 'Please sign in to upload photos.'], 401);
            }

            return redirect()->route('login')->with('error', 'Please sign in to upload photos.');
        }

        $files = $this->extractUploadFiles($request);
        if (empty($files)) {
            if ($request->wantsJson()) {
                return response()->json(['message' => 'Please choose at least one photo to upload.'], 422);
            }

            return back()->with('error', 'Please choose at least one photo to upload.');
        }

        $validator = Validator::make(
            ['photos' => $files],
            ['photos' => 'required|array|max:20', 'photos.*' => 'image|mimes:jpeg,jpg,png,webp|max:51200']
        );

        if ($validator->fails()) {
            $message = $validator->errors()->first('photos') ?: $validator->errors()->first('photos.*');
            if ($request->wantsJson()) {
                return response()->json(['message' => $message ?: 'The uploaded files were invalid.'], 422);
            }

            return back()->with('error', $message ?: 'The uploaded files were invalid.');
        }

        $user = Auth::user();
        \Illuminate\Support\Facades\Gate::authorize('upload', $group);
        $uploaded = 0;

        foreach ($files as $file) {
            $fileSize = $file->getSize();
            $photoCount = $group->photos()->count();
            $storageLimitBytes = $user->plan_limits['storage_mb'] * 1024 * 1024;
            $storageRemaining = max(0, $storageLimitBytes - (int) $user->storage_used);

            if ($photoCount >= $user->plan_limits['photos_per_group'] || $fileSize > $storageRemaining) {
                $message = $photoCount >= $user->plan_limits['photos_per_group']
                    ? 'You have reached your plan limit for photos in this group.'
                    : 'You have reached your storage limit for the current plan.';

                if ($request->wantsJson()) {
                    return response()->json(['message' => $message], 422);
                }

                return back()->with('error', $message);
            }

            try {
                $mediaIngestor->ingest($file, $group, $user);
                $uploaded++;
            } catch (\Throwable $exception) {
                report($exception);

                if ($request->wantsJson()) {
                    return response()->json(['message' => 'Image processing failed: ' . $exception->getMessage()], 500);
                }

                return back()->with('error', 'Image processing failed: ' . $exception->getMessage());
            }
        }

        if ($request->wantsJson()) {
            return response()->json(['uploaded' => $uploaded]);
        }
        return back()->with('success', "$uploaded photo(s) uploaded!");
    }

    private function extractUploadFiles(Request $request): array
    {
        $files = $request->allFiles();

        if (empty($files)) {
            return [];
        }

        $input = $files['photos'] ?? $files['photos[]'] ?? null;
        if ($input instanceof UploadedFile) {
            return [$input];
        }

        $flattened = [];
        if (is_array($input)) {
            foreach ($input as $value) {
                if ($value instanceof UploadedFile) {
                    $flattened[] = $value;
                } elseif (is_array($value)) {
                    $flattened = array_merge($flattened, $this->extractNestedFiles($value));
                }
            }
        }

        if (!empty($flattened)) {
            return array_values($flattened);
        }

        return array_values($this->extractNestedFiles($files));
    }

    private function extractNestedFiles(array $values): array
    {
        $files = [];

        foreach ($values as $value) {
            if ($value instanceof UploadedFile) {
                $files[] = $value;
            } elseif (is_array($value)) {
                $files = array_merge($files, $this->extractNestedFiles($value));
            }
        }

        return $files;
    }

    private function storePhoto($file, Group $group, ImageOptimizationService $imageOptimizationService): Photo
    {
        $filename = Str::uuid() . '.' . strtolower($file->getClientOriginalExtension());
        $dir      = 'photos/' . $group->id;
        $optimized = $imageOptimizationService->optimizeAndStore($file, $dir, $filename);
        $path     = $optimized['path'];

        $info   = @getimagesize(Storage::disk('public')->path($path));
        $width  = $info[0] ?? null;
        $height = $info[1] ?? null;

        $photo = Photo::create([
            'group_id'          => $group->id,
            'uploader_id'       => Auth::id(),
            'filename'          => $filename,
            'original_filename' => $file->getClientOriginalName(),
            'path'              => $path,
            'thumbnail_path'    => $path,
            'file_size'         => $optimized['size'],
            'mime_type'         => $file->getMimeType(),
            'width'             => $width,
            'height'            => $height,
        ]);

        $user = Auth::user();
        $user->forceFill([
            'storage_used' => (int) $user->storage_used + (int) $optimized['size'],
        ])->save();

        return $photo;
    }

    public function show(Group $group, Photo $photo)
    {
        $this->requirePhotoAccess($group,$photo);
        $photo->incrementViews();
        $photo->load('uploader', 'likes', 'comments.user');
        $isAdmin = $group->isAdmin(Auth::user());
        $isLiked = $photo->isLikedBy(Auth::user());
        return view('photos.show', compact('group', 'photo', 'isAdmin', 'isLiked'));
    }

    public function destroy(Group $group, Photo $photo, \App\Contracts\ProtectedMediaStorage $mediaStorage)
    {
        abort_unless($photo->group_id === $group->id, 404);
        $user = Auth::user();
        \Illuminate\Support\Facades\Gate::authorize('delete', $photo);
        if ($asset = $photo->mediaAsset) { foreach ($asset->variants as $variant) { $mediaStorage->delete($variant->object_key); } $asset->delete(); } else { Storage::disk('public')->delete(array_filter([$photo->path, $photo->thumbnail_path])); }
        $photo->delete();
        if (request()->wantsJson()) return response()->json(['deleted' => true]);
        return back()->with('success', 'Photo deleted.');
    }

    public function toggleLike(Group $group, Photo $photo)
    {
        $this->requirePhotoAccess($group,$photo);
        $user = Auth::user();
        if ($photo->likes()->where('user_id', $user->id)->exists()) {
            $photo->likes()->detach($user->id);
            $liked = false;
        } else {
            $photo->likes()->attach($user->id);
            $liked = true;
        }
        return response()->json(['liked' => $liked, 'count' => $photo->likes()->count()]);
    }

    public function download(Group $group, Photo $photo)
    {
        $this->requirePhotoAccess($group,$photo);
        $photo->incrementDownloads();
        if ($asset = $photo->mediaAsset) return redirect()->route('media.show', [$asset, 'original', 'download' => 1]);
        $path = Storage::disk('public')->path($photo->path);
        $watermark = $this->resolveWatermarkConfig($group);

        if ($watermark === null) {
            return response()->download($path, $photo->original_filename);
        }

        $tempPath = $this->createWatermarkedCopy($path, $watermark);
        return response()->download($tempPath, $photo->original_filename)->deleteFileAfterSend(true);
    }

    private function resolveWatermarkConfig(Group $group): ?array
    {
        if (!$group->watermark_enabled) {
            return null;
        }

        $user = Auth::user();
        $text = trim((string) ($group->watermark_text ?? ''));
        if ($text === '') {
            $text = trim((string) ($user->meta['watermark_text'] ?? ''));
        }

        if ($text === '') {
            return null;
        }

        return [
            'text' => $text,
            'position' => $user->meta['watermark_position'] ?? 'bottom-right',
            'opacity' => max(20, min(90, (int) ($user->meta['watermark_opacity'] ?? 70))),
        ];
    }

    private function createWatermarkedCopy(string $sourcePath, array $watermark): string
    {
        $info = @getimagesize($sourcePath);
        if ($info === false) {
            return $sourcePath;
        }

        [$width, $height, $type] = $info;
        $image = match ($type) {
            IMAGETYPE_JPEG => imagecreatefromjpeg($sourcePath),
            IMAGETYPE_PNG => imagecreatefrompng($sourcePath),
            IMAGETYPE_WEBP => imagecreatefromwebp($sourcePath),
            default => false,
        };

        if ($image === false) {
            return $sourcePath;
        }

        imagealphablending($image, true);
        imagesavealpha($image, true);

        $font = 5;
        $margin = (int) max(12, min($width, $height) * 0.02);
        $lines = explode("\n", wordwrap($watermark['text'], max(30, (int) floor($width / 20)), "\n"));
        $lineHeight = imagefontheight($font);
        $textHeight = count($lines) * $lineHeight;
        $textWidth = 0;

        foreach ($lines as $line) {
            $textWidth = max($textWidth, imagefontwidth($font) * strlen($line));
        }

        switch ($watermark['position']) {
            case 'top-left':
                $x = $margin;
                $y = $margin;
                break;
            case 'top-right':
                $x = max($margin, $width - $textWidth - $margin);
                $y = $margin;
                break;
            case 'bottom-left':
                $x = $margin;
                $y = max($margin, $height - $textHeight - $margin);
                break;
            default:
                $x = max($margin, $width - $textWidth - $margin);
                $y = max($margin, $height - $textHeight - $margin);
                break;
        }

        $alpha = max(0, min(127, 127 - (int) round($watermark['opacity'] * 1.27)));
        $shadowColor = imagecolorallocatealpha($image, 0, 0, 0, max(0, min(127, $alpha + 20)));
        $textColor = imagecolorallocatealpha($image, 255, 255, 255, $alpha);

        foreach ($lines as $index => $line) {
            $lineY = $y + ($index * $lineHeight);
            imagestring($image, $font, $x + 1, $lineY + 1, $line, $shadowColor);
            imagestring($image, $font, $x, $lineY, $line, $textColor);
        }

        $extension = match ($type) {
            IMAGETYPE_JPEG => 'jpg',
            IMAGETYPE_PNG => 'png',
            IMAGETYPE_WEBP => 'webp',
            default => 'jpg',
        };

        $tempPath = sys_get_temp_dir() . '/kwikpic_wm_' . uniqid() . '.' . $extension;

        switch ($type) {
            case IMAGETYPE_JPEG:
                imagejpeg($image, $tempPath, 90);
                break;
            case IMAGETYPE_PNG:
                imagepng($image, $tempPath, 8);
                break;
            case IMAGETYPE_WEBP:
                imagewebp($image, $tempPath, 90);
                break;
            default:
                imagejpeg($image, $tempPath, 90);
                break;
        }

        imagedestroy($image);
        return $tempPath;
    }

    public function myPhotos(Group $group)
    {
        abort_unless($group->isMember(Auth::user()),403); return redirect()->route('biometric.page',$group);
    }

    public function bulkDownload(Request $request, Group $group)
    {
        $this->requireFullAccess($group);
        $request->validate(['photo_ids' => 'required|array']);
        $photos = $group->photos()->whereIn('id', $request->photo_ids)->get();

        $tmpDir = storage_path('app/temp');
        if (!is_dir($tmpDir)) mkdir($tmpDir, 0755, true);

        $zipName = 'kwikpic-' . $group->id . '-' . time() . '.zip';
        $zipPath = $tmpDir . '/' . $zipName;

        $zip = new \ZipArchive();
        if ($zip->open($zipPath, \ZipArchive::CREATE) === true) {
            $watermark = $this->resolveWatermarkConfig($group);
            foreach ($photos as $photo) {
                $fp = Storage::disk('public')->path($photo->path);
                if (!file_exists($fp)) {
                    continue;
                }

                $filePath = $fp;
                if ($watermark !== null) {
                    $filePath = $this->createWatermarkedCopy($fp, $watermark);
                }

                $zip->addFile($filePath, $photo->original_filename);
                $photo->incrementDownloads();
            }
            $zip->close();
        }

        return response()->download($zipPath, $zipName)->deleteFileAfterSend(true);
    }

    public function assignToFolder(Request $request, Group $group, Photo $photo)
    {
        abort_unless($group->isAdmin(Auth::user()),403);
        if ($photo->group_id !== $group->id) abort(404);

        $user = Auth::user();
        if ($photo->uploader_id !== $user->id && !$group->isAdmin($user)) abort(403);

        $validated = $request->validate(['folder_id' => ['nullable', 'integer', 'exists:folders,id']]);

        if ($validated['folder_id']) {
            $folder = $group->folders()->findOrFail($validated['folder_id']);
        }

        $photo->update(['folder_id' => $validated['folder_id'] ?? null]);

        if ($request->wantsJson()) {
            return response()->json(['assigned' => true, 'folder_id' => $validated['folder_id'] ?? null]);
        }

        return back()->with('success', 'Photo assigned to folder.');
    }

    public function bulkAssignFolder(Request $request, Group $group)
    {
        abort_unless($group->isAdmin(Auth::user()),403);
        $user = Auth::user();
        if (!$user) abort(403, 'Unauthorized');

        $validated = $request->validate([
            'photo_ids' => ['required', 'array', 'min:1'],
            'photo_ids.*' => ['integer', 'exists:photos,id'],
            'folder_id' => ['nullable', 'integer', 'exists:folders,id'],
        ]);

        $photos = $group->photos()->whereIn('id', $validated['photo_ids'])->get();
        $moved = 0;

        foreach ($photos as $photo) {
            if ($photo->uploader_id === $user->id || $group->isAdmin($user)) {
                $photo->update(['folder_id' => $validated['folder_id'] ?? null]);
                $moved++;
            }
        }

        if ($request->wantsJson()) {
            return response()->json(['moved' => $moved, 'total' => count($validated['photo_ids'])]);
        }

        return back()->with('success', "$moved photo(s) moved successfully.");
    }
    private function requireFullAccess(Group $group): void { \Illuminate\Support\Facades\Gate::authorize("viewFullGallery", $group); }
    private function requirePhotoAccess(Group $group,Photo $photo): void { abort_unless($photo->group_id===$group->id,404); \Illuminate\Support\Facades\Gate::authorize("view", $photo); }
}
