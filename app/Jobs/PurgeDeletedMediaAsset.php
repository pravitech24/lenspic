<?php

namespace App\Jobs;

use App\Contracts\ProtectedMediaStorage;
use App\Models\{MediaAsset, Photo, StorageLedgerEntry, User};
use App\Services\Storage\PlanEntitlements;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\{InteractsWithQueue, SerializesModels};
use Illuminate\Support\Facades\DB;

class PurgeDeletedMediaAsset implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public int $tries=4;
    public function __construct(public int $assetId) { $this->onQueue('maintenance'); }

    public function handle(ProtectedMediaStorage $storage, PlanEntitlements $plans): void
    {
        $asset=MediaAsset::withTrashed()->with('variants')->find($this->assetId);
        if(!$asset || !$asset->trashed()) return;
        $owner=User::find($asset->owner_id); if(!$owner) return;
        $hours=$plans->for($owner)['deleted_media_usage_release_hours'];
        if($asset->deleted_at->gt(now()->subHours($hours))){self::dispatch($asset->id)->delay($asset->deleted_at->copy()->addHours($hours));return;}
        foreach($asset->variants->pluck('object_key')->filter()->unique() as$key) if($storage->exists($key))$storage->delete($key);
        $bytes=(int)$asset->variants->sum('size_bytes');
        DB::transaction(function()use($asset,$bytes,$owner){
            $entry=StorageLedgerEntry::firstOrCreate(['idempotency_key'=>'retention-purge-'.$asset->uuid],['owner_id'=>$owner->id,'group_id'=>$asset->group_id,'media_asset_id'=>$asset->id,'event_type'=>'retention_purged','byte_delta'=>-$bytes]);
            if($entry->wasRecentlyCreated&&$bytes>0)User::whereKey($owner->id)->decrement('storage_used',$bytes);
            $photoId=$asset->photo_id;$asset->forceDelete();if($photoId)Photo::withTrashed()->find($photoId)?->forceDelete();
        });
    }
}
