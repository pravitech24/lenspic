<?php

namespace App\Services\Media;

use App\Jobs\DeleteMediaAsset;
use App\Models\MediaAsset;
use LogicException;

class MediaAssetCleanup
{
    public function schedule(MediaAsset $asset): void
    {
        if ($asset->kind !== 'group_cover') {
            throw new LogicException('Only group cover assets may use this cleanup path.');
        }
        $asset->loadMissing('variants');
        $keys = $asset->variants->pluck('object_key')->filter()->unique()->values()->all();
        $bytes = (int) $asset->variants->sum('size_bytes');
        $snapshot = [$asset->id, $asset->uuid, $asset->owner_id, $asset->group_id, $keys, $bytes];
        $asset->delete();
        DeleteMediaAsset::dispatch(...$snapshot)->afterCommit();
    }
}
