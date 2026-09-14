<?php

namespace App\Jobs;

use App\Contracts\ProtectedMediaStorage;
use App\Models\{Group, MediaAsset, StorageLedgerEntry, User};
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\{InteractsWithQueue, SerializesModels};

class DeleteMediaAsset implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public int $tries = 4;

    public function __construct(public int $assetId, public string $assetUuid, public int $ownerId, public int $groupId, public array $objectKeys, public int $bytes)
    { $this->onQueue('maintenance'); }

    public function backoff(): array { return [10, 30, 120]; }

    public function handle(ProtectedMediaStorage $storage): void
    {
        foreach (array_unique($this->objectKeys) as $key) if ($storage->exists($key)) $storage->delete($key);
        $entry = StorageLedgerEntry::firstOrCreate(
            ['idempotency_key'=>'cover-deleted-'.$this->assetUuid],
            ['owner_id'=>$this->ownerId, 'group_id'=>Group::whereKey($this->groupId)->exists() ? $this->groupId : null, 'media_asset_id'=>MediaAsset::withTrashed()->find($this->assetId)?->id, 'event_type'=>'asset_deleted', 'byte_delta'=>-$this->bytes]
        );
        if ($entry->wasRecentlyCreated && $this->bytes > 0) User::whereKey($this->ownerId)->decrement('storage_used', $this->bytes);
        MediaAsset::withTrashed()->find($this->assetId)?->forceDelete();
    }
}
