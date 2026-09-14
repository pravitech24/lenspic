<?php

namespace App\Http\Controllers;

use App\Models\{Group, UploadBatch, UploadBatchFile};
use App\Services\Media\QueuedMediaIngestor;
use Illuminate\Http\{Request, UploadedFile};
use Illuminate\Support\Facades\{Gate, Validator};
use Illuminate\Support\Str;
use App\Support\UploadStatusPresenter;

class QueuedUploadController extends Controller
{
    public function store(Request $request, Group $group, QueuedMediaIngestor $ingestor)
    {
        Gate::authorize('upload', $group);
        $files = $this->files($request->allFiles());
        $maxFiles = config('media.photo_upload.max_batch_files');
        $maxFileMb = config('media.photo_upload.max_file_mb');
        $validator = Validator::make(['photos' => $files], [
            'photos' => "required|array|min:1|max:{$maxFiles}",
            'photos.*' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.($maxFileMb * 1024)],
        ], [
            'photos.max' => "You can upload up to {$maxFiles} photos at once.",
            'photos.*.max' => "A photo exceeds the maximum size of {$maxFileMb} MB.",
            'photos.*.image' => 'Only valid JPG, JPEG, PNG and WebP images can be uploaded.',
            'photos.*.mimes' => 'Only JPG, JPEG, PNG and WebP images can be uploaded.',
        ]);
        if ($validator->fails()) return $this->invalid($request, $validator->errors()->first(), $validator->errors()->toArray());

        $folderId = null;

        $user = $request->user();
        $owner = $group->creator;
        $physicalLimit = (int) ($owner->plan_limits['storage_mb'] ?? 0) * 1048576;
        $incoming = array_sum(array_map(fn ($file) => (int) $file->getSize(), $files));
        if ($physicalLimit > 0 && (int) $owner->storage_used + $incoming > $physicalLimit) {
            $message='You have reached your storage limit for the current plan.';
            if (!$request->header('X-Inertia') && !$request->wantsJson()) return back()->with('error',$message);
            return $this->invalid($request,$message,['photos'=>[$message]],507);
        }
        $batch = UploadBatch::create(['uuid' => (string) Str::uuid(), 'group_id' => $group->id, 'user_id' => $user->id, 'state' => 'pending', 'total_files' => count($files), 'pending_files' => count($files)]);
        $uploaded = 0;
        foreach ($files as $file) {
            $item = UploadBatchFile::create(['upload_batch_id' => $batch->id, 'client_filename' => basename($file->getClientOriginalName()), 'state' => 'pending']);
            $fileError=$this->uploadError($file);
            $fileValidator=Validator::make(['photo'=>$file],['photo'=>['required','file','image','mimes:jpg,jpeg,png,webp','max:'.($maxFileMb * 1024)]]);
            if($fileError||$fileValidator->fails()){$item->update(['state'=>'failed','error'=>$fileError?:$this->validationMessage($file,$fileValidator->errors()->first())]);continue;}
            try { $ingestor->ingest($file, $group, $user, $item); $uploaded++; }
            catch (\Throwable $exception) { $requestId=(string)Str::uuid();logger()->error('Photo ingestion failed',['request_id'=>$requestId,'batch'=>$batch->uuid,'exception'=>$exception]);$item->update(['state'=>'failed','error'=>$exception instanceof \Illuminate\Database\QueryException?'Photo upload is temporarily unavailable. Please try again shortly.':'We couldn’t save this photo. Please try again.']); }
        }
        $batch->refreshCounts();
        $state = $batch->fresh()->state;
        if ($uploaded === 0) {
            $message = $batch->files()->where('state', 'failed')->value('error') ?: 'The photo could not be uploaded.';
            return $this->invalid($request, $message, ['photos' => [$message]]);
        }
        $message = $uploaded === 1 ? "Photo uploaded successfully. We're preparing it for your gallery." : "$uploaded photos uploaded. We're preparing them for your gallery.";
        $payload = ['uploaded' => $uploaded, 'upload_reference' => $batch->uuid, 'status' => $state, 'display_status' => UploadStatusPresenter::label($state), 'message' => $message, 'folder_id' => $folderId];
        if ($request->header('X-Inertia')) return redirect()->route('groups.gallery', $group, 303)->with('success', $message);
        return $request->wantsJson() ? response()->json($payload) : back()->with('success', $message);
    }

    private function invalid(Request $request, string $message, array $errors, int $status=422)
    {
        if ($request->header('X-Inertia')) return back()->withErrors($errors);
        return $request->wantsJson() ? response()->json(['message' => $message, 'errors' => $errors], $status) : back()->withErrors($errors);
    }

    private function uploadError(UploadedFile $file): ?string
    {
        if($file->isValid())return null;
        $maxFileMb = config('media.photo_upload.max_file_mb');
        return match($file->getError()){UPLOAD_ERR_INI_SIZE=>'This photo exceeds the server upload limit.',UPLOAD_ERR_FORM_SIZE=>"This photo is larger than {$maxFileMb} MB.",UPLOAD_ERR_PARTIAL=>'This photo wasn’t fully uploaded. Please try again.',UPLOAD_ERR_NO_FILE=>'No photo was received.',UPLOAD_ERR_NO_TMP_DIR,UPLOAD_ERR_EXTENSION=>'Photo upload is temporarily unavailable. Please try again shortly.',UPLOAD_ERR_CANT_WRITE=>'The server couldn’t save this photo.',default=>'We couldn’t receive this photo. Please try again.'};
    }

    private function validationMessage(UploadedFile $file, string $fallback): string
    {
        $maxFileMb = config('media.photo_upload.max_file_mb');
        if((int)$file->getSize()>$maxFileMb*1024*1024)return "This photo is larger than {$maxFileMb} MB.";
        return 'This photo isn’t supported. Please choose a JPG, JPEG, PNG or WebP image.';
    }

    private function files(array $values): array
    {
        $files = [];
        array_walk_recursive($values, function ($value) use (&$files) { if ($value instanceof UploadedFile) $files[] = $value; });
        return $files;
    }
}
