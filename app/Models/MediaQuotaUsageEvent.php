<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MediaQuotaUsageEvent extends Model
{
    public const UPDATED_AT = null;
    protected $fillable = ['owner_id','group_id','media_asset_id','actor_id','event_type','quantity','period_starts_at','period_ends_at','idempotency_key','metadata','created_at'];
    protected $casts = ['quantity'=>'decimal:2','period_starts_at'=>'datetime','period_ends_at'=>'datetime','created_at'=>'datetime','metadata'=>'array'];

    public const PHOTO_UPLOADED='photo_uploaded';
    public const PHOTO_DELETED='photo_deleted';
    public const VIDEO_UPLOADED='video_uploaded';
    public const VIDEO_DELETED='video_deleted';
    public const TYPES=[self::PHOTO_UPLOADED,self::PHOTO_DELETED,self::VIDEO_UPLOADED,self::VIDEO_DELETED];
}
