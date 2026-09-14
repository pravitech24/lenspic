<?php

namespace App\Http\Controllers;

use App\Http\Requests\UploadGroupCoverRequest;
use App\Jobs\ProcessGroupCover;
use App\Models\Group;
use App\Services\Media\{GroupCoverIngestor, MediaAssetCleanup};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GroupCoverController extends Controller
{
    public function store(UploadGroupCoverRequest $request, Group $group, GroupCoverIngestor $ingestor)
    {
        $this->authorize('update',$group);
        $ingestor->ingest($request->file('cover_photo'),$group,$request->user());
        return back()->with('success','Cover uploaded. We’re preparing it now.');
    }

    public function destroy(Request $request, Group $group, MediaAssetCleanup $cleanup)
    {
        $this->authorize('update',$group);
        [$current,$pending]=DB::transaction(function()use($group){$locked=Group::query()->lockForUpdate()->findOrFail($group->id);$assets=[$locked->coverMediaAsset,$locked->pendingCoverMediaAsset];$locked->update(['cover_media_asset_id'=>null,'pending_cover_media_asset_id'=>null]);return$assets;});
        foreach(collect([$current,$pending])->filter()->unique('id')as$asset)$cleanup->schedule($asset);
        return back()->with('success','Group cover removed.');
    }

    public function retry(Request $request, Group $group)
    {
        $this->authorize('update',$group);$asset=$group->pendingCoverMediaAsset;
        abort_unless($asset&&$asset->kind==='group_cover'&&$asset->state==='failed',409);
        $asset->update(['state'=>'queued','processing_error'=>null]);ProcessGroupCover::dispatch($asset->id)->afterCommit();
        return back()->with('success','We’re preparing your cover again.');
    }
}
