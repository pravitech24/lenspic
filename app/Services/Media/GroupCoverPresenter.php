<?php

namespace App\Services\Media;

use App\Models\Group;

class GroupCoverPresenter
{
    public function present(Group $group, string $variant): array
    {
        $current = $group->coverMediaAsset;
        $pending = $group->pendingCoverMediaAsset;
        $record = $current?->state === 'completed' ? $current->variants->first(fn($item)=>$item->variant_type===$variant&&$item->state==='ready') : null;
        return [
            'url' => $record ? route('media.show',[$current->uuid,$variant]) : null,
            'state' => $pending?->state ?? ($record ? 'ready' : 'empty'),
            'error' => $pending?->state === 'failed' ? 'Cover processing failed. Retry or choose another image.' : null,
            'asset_uuid' => $current?->uuid,
        ];
    }
}
