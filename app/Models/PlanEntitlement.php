<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlanEntitlement extends Model
{
    protected $fillable = ['plan','photo_limit','video_storage_limit_mb','photo_delete_reupload_limit','video_delete_reupload_limit_mb','deleted_media_usage_release_hours','usage_reset_period','team_member_limit','is_active'];
    protected $casts = [
        'photo_limit'=>'integer','video_storage_limit_mb'=>'integer','photo_delete_reupload_limit'=>'integer',
        'video_delete_reupload_limit_mb'=>'integer','deleted_media_usage_release_hours'=>'integer','team_member_limit'=>'integer','is_active'=>'boolean',
    ];

    public function toQuotaArray(): array
    {
        return $this->only(['photo_limit','video_storage_limit_mb','photo_delete_reupload_limit','video_delete_reupload_limit_mb','deleted_media_usage_release_hours','usage_reset_period','team_member_limit']);
    }
}
