<?php

namespace App\Services\Storage;

use App\Models\{MediaAsset, MediaQuotaUsageEvent, StorageLedgerEntry, User};
use Illuminate\Support\Carbon;
use App\Support\MediaQuality;

class StorageUsage
{
    public function __construct(private PlanEntitlements $plans) {}

    public function summary(User $owner): array
    {
        $limits = $this->plans->for($owner);
        $release = now()->subHours($limits['deleted_media_usage_release_hours']);
        $base = MediaAsset::withTrashed()->where('owner_id', $owner->id)->where('kind', 'photo')->whereNotNull('photo_id')->whereIn('state', ['queued','processing','ready','completed']);
        $active = (clone $base)->whereNull('deleted_at')->count();
        $deleted = (clone $base)->whereNotNull('deleted_at')->where('deleted_at', '>', $release)->count();
        $countedAssets=(clone $base)->where(fn($q)=>$q->whereNull('deleted_at')->orWhere('deleted_at','>',$release));
        $standard=(clone $countedAssets)->where('quality_mode',MediaQuality::STANDARD)->count();
        $high=(clone $countedAssets)->where('quality_mode',MediaQuality::HIGH_RESOLUTION)->count();
        $countedPhotos=$standard+($high*2.5);
        $video = MediaAsset::withTrashed()->where('owner_id', $owner->id)->where('kind', 'video')->where(fn ($q) => $q->whereNull('deleted_at')->orWhere('deleted_at', '>', $release));
        $videoBytes = (int) (clone $video)->sum('size_bytes');
        $deletedVideoBytes = (int) (clone $video)->whereNotNull('deleted_at')->sum('size_bytes');
        [$periodStart, $periodEnd] = $this->period($owner);
        $events = MediaQuotaUsageEvent::where('owner_id', $owner->id)->where('period_starts_at', $periodStart);
        $photoUploads = (float) (clone $events)->where('event_type', MediaQuotaUsageEvent::PHOTO_UPLOADED)->sum('quantity');
        $photoDeletes = (float) (clone $events)->where('event_type', MediaQuotaUsageEvent::PHOTO_DELETED)->sum('quantity');
        $videoUploads = (int) (clone $events)->where('event_type', 'video_uploaded')->sum('quantity');
        $videoDeletes = (int) (clone $events)->where('event_type', 'video_deleted')->sum('quantity');
        $physical = max(0, (int) StorageLedgerEntry::where('owner_id', $owner->id)->sum('byte_delta'));
        $videoLimitBytes = $limits['video_storage_limit_mb'] * 1048576;
        $deleteVideoLimitBytes = $limits['video_delete_reupload_limit_mb'] * 1048576;
        return [
            'limits'=>$limits, 'active_photos'=>$active, 'retained_deleted_photos'=>$deleted, 'counted_photos'=>$countedPhotos, 'used_quota_units'=>$countedPhotos,
            'standard_count'=>$standard,'high_resolution_count'=>$high,
            'remaining_photos'=>max(0,$limits['photo_limit']-$countedPhotos), 'photo_percent'=>$this->percent($countedPhotos,$limits['photo_limit']),
            'video_bytes'=>$videoBytes, 'video_storage_used_mb'=>round($videoBytes/1048576, 2), 'retained_deleted_video_bytes'=>$deletedVideoBytes, 'remaining_video_bytes'=>max(0,$videoLimitBytes-$videoBytes), 'video_percent'=>$this->percent($videoBytes,$videoLimitBytes),
            'physical_storage_bytes'=>$physical, 'total_groups'=>$owner->createdGroups()->count(), 'standard_active_count'=>$standard,
            'photo_uploads'=>$photoUploads, 'photo_deletes'=>$photoDeletes, 'photo_delete_reupload_used'=>$photoUploads+$photoDeletes,
            'photo_delete_reupload_remaining'=>max(0,$limits['photo_delete_reupload_limit']-$photoUploads-$photoDeletes),
            'video_uploaded_bytes'=>$videoUploads, 'video_deleted_bytes'=>$videoDeletes,
            'video_delete_reupload_remaining_bytes'=>max(0,$deleteVideoLimitBytes-$videoUploads-$videoDeletes),
            'period_starts_at'=>$periodStart, 'period_ends_at'=>$periodEnd,
        ];
    }

    public function assertPhotoCapacity(User $owner, string $quality=MediaQuality::STANDARD): void
    {
        $summary=$this->summary($owner);
        abort_if($summary['used_quota_units']+(float)MediaQuality::quotaUnits($quality)>$summary['limits']['photo_limit'], 422, 'Your photo storage limit has been reached. Upgrade your plan to upload more.');
    }

    public function record(User $owner, string $type, string|int|float $quantity, string $key, ?int $groupId=null, ?int $assetId=null, ?int $actorId=null): void
    {
        if(!in_array($type,MediaQuotaUsageEvent::TYPES,true))throw new \InvalidArgumentException('Unsupported media quota event type.');
        [$start,$end]=$this->period($owner);
        MediaQuotaUsageEvent::firstOrCreate(['idempotency_key'=>$key],['owner_id'=>$owner->id,'group_id'=>$groupId,'media_asset_id'=>$assetId,'actor_id'=>$actorId,'event_type'=>$type,'quantity'=>$quantity,'period_starts_at'=>$start,'period_ends_at'=>$end]);
    }

    private function period(User $owner): array
    {
        $subscription=$owner->subscriptions()->where('status','active')->latest()->first();
        $start=$subscription?->starts_at?->copy()->startOfDay() ?? now()->startOfMonth();
        $end=$subscription?->expires_at?->copy()->endOfDay() ?? now()->endOfMonth();
        return [$start,$end];
    }
    private function percent(int $used,int $limit): int { return $limit>0?min(100,(int)round($used/$limit*100)):0; }
}
