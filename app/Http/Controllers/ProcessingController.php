<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessMediaAsset;
use App\Models\{Group, UploadBatch};
use App\Support\UploadStatusPresenter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ProcessingController extends Controller
{
    private function allowed(UploadBatch $batch, Request $request): void
    {
        Gate::authorize('view', $batch->group);
        abort_unless($batch->user_id === $request->user()->id || $batch->group->creator_id === $request->user()->id, 403);
    }

    private function present(UploadBatch $batch): array
    {
        $batch->load(['files.asset.photo','files.asset.variants']);
        $lastActivity = $batch->files->max('updated_at') ?? $batch->created_at;
        $stalled = in_array($batch->state, ['pending','queued','processing'], true)
            && $lastActivity?->lt(now()->subSeconds(config('media.processing.stall_seconds')));
        return [
            ...$batch->only('uuid','state','total_files','pending_files','processing_files','completed_files','failed_files','cancelled_files','cancelled_at'),
            'status' => $batch->state,
            'display_status' => UploadStatusPresenter::label($batch->state),
            'display_message' => UploadStatusPresenter::message($batch->state, $batch->completed_files, $batch->total_files, $batch->failed_files),
            'progress_percentage' => $batch->total_files ? (int) round((($batch->completed_files + $batch->failed_files + $batch->cancelled_files) / $batch->total_files) * 100) : 0,
            'stalled' => $stalled,
            'last_activity_at' => $lastActivity?->toIso8601String(),
            'gallery_url' => route('groups.gallery', $batch->group_id),
            'files' => $batch->files->map(function ($file) {
                $asset=$file->asset;$photo=$file->state==='completed'?$asset?->photo:null;$ready=$asset?->variants?->where('state','ready');$variant=$ready?->firstWhere('variant_type','thumbnail')??$ready?->firstWhere('variant_type','optimized');
                return [...$file->only('id','client_filename','error','attempts'),'display_status'=>UploadStatusPresenter::label($file->state),'photo'=>$photo&&$variant?['id'=>$photo->id,'original_filename'=>$photo->original_filename,'url'=>route('media.show',[$asset->uuid,$variant->variant_type]),'viewer_url'=>route('photos.show',[$photo->group_id,$photo->id]),'download_url'=>Gate::allows('download',$photo)?route('photos.download',[$photo->group_id,$photo->id]):null,'width'=>(int)($variant->width?:$asset->width?:$photo->width?:4),'height'=>(int)($variant->height?:$asset->height?:$photo->height?:3),'folder_id'=>$photo->folder_id,'liked'=>false,'favourites_count'=>0]:null];
            }),
        ];
    }

    public function active(Request $request, Group $group)
    {
        Gate::authorize('upload',$group);
        $batch=UploadBatch::where('group_id',$group->id)->where('user_id',$request->user()->id)->whereIn('state',['pending','queued','processing'])->latest()->first();
        if(!$batch)return response()->json(['upload'=>null]);
        $batch->refreshCounts();
        return response()->json(['upload'=>$this->present($batch->fresh())]);
    }

    public function show(Request $request, UploadBatch $batch)
    {
        $this->allowed($batch, $request);
        $batch->refreshCounts();
        return response()->json($this->present($batch->fresh()));
    }

    public function cancel(Request $request, UploadBatch $batch)
    {
        $this->allowed($batch, $request);
        abort_if(in_array($batch->state, ['completed','failed','cancelled'], true), 409);
        $batch->update(['cancelled_at'=>now()]);
        $batch->files()->whereIn('state',['pending','queued'])->update(['state'=>'cancelled','cancelled_at'=>now()]);
        $batch->refreshCounts();
        return $request->header('X-Inertia') ? redirect()->back(303) : response()->json($this->present($batch->fresh()));
    }

    public function retry(Request $request, UploadBatch $batch)
    {
        $this->allowed($batch, $request);
        abort_if($batch->cancelled_at, 409);
        foreach ($batch->files()->where('state','failed')->whereNotNull('media_asset_id')->get() as $file) {
            $file->update(['state'=>'queued','error'=>null,'failed_at'=>null]);
            ProcessMediaAsset::dispatch($file->media_asset_id,$file->id)->afterCommit();
        }
        $batch->refreshCounts();
        return $request->header('X-Inertia') ? redirect()->back(303) : response()->json($this->present($batch->fresh()));
    }
}
