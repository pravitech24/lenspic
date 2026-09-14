<?php

namespace App\Services\Storage;

use App\Models\User;
use App\Services\Billing\AccountEntitlements;

class PlanEntitlements
{
    public function for(User $user): array
    {
        $snapshot=app(AccountEntitlements::class)->snapshot($user);
        return ['photo_limit'=>$snapshot['photo_limit'],'video_storage_limit_mb'=>$snapshot['video_limit_mb'],'photo_delete_reupload_limit'=>$snapshot['photo_reuse_limit'],'video_delete_reupload_limit_mb'=>$snapshot['video_limit_mb']*2,'deleted_media_usage_release_hours'=>24,'usage_reset_period'=>'billing_cycle','team_member_limit'=>$snapshot['team_seat_limit'],'group_limit'=>$snapshot['group_limit'],'guest_limit'=>$snapshot['guest_limit']];
    }
}
