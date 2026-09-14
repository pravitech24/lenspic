<?php

namespace App\Services\Notifications;

use App\Models\{FaceSearchSubject, Group, MediaExport, MediaQuotaUsageEvent, TeamInvitation, User, WalletTopUp};
use App\Services\Storage\StorageUsage;

class NotificationDomainSubscriber
{
    public static function register(): void
    {
        MediaExport::updated(function (MediaExport $export) {
            if (! $export->wasChanged('state') || $export->state !== 'completed') return;
            $user=User::find($export->user_id);$group=$export->group;if(!$user||!$group)return;
            app(NotificationService::class)->send($user,'export:'.$export->uuid.':ready',['category'=>'uploads','title'=>'Export ready','message'=>'Your photo export for '.$group->name.' is ready to download.','studio_id'=>$group->creator_id,'subject_type'=>'media_export','subject_id'=>$export->uuid,'action_route'=>'exports.download','action_parameters'=>['export'=>$export->uuid],'action_label'=>'Download Export','severity'=>'success']);
        });
        FaceSearchSubject::updated(function (FaceSearchSubject $subject) {
            if (! $subject->wasChanged('state') || $subject->state !== 'completed') return;
            $user=User::find($subject->user_id);$group=Group::find($subject->group_id);if(!$user||!$group)return;
            app(NotificationService::class)->send($user,'face-search:'.$subject->uuid.':completed',['category'=>'face_recognition','title'=>'Your matched photos are ready','message'=>'Your authorized matches for '.$group->name.' are ready to review.','studio_id'=>$group->creator_id,'subject_type'=>'face_search','subject_id'=>$subject->uuid,'action_route'=>'biometric.results-page','action_parameters'=>['s'=>$subject->uuid],'action_label'=>'View Photos','severity'=>'success']);
        });
        TeamInvitation::created(function (TeamInvitation $invitation) {
            $recipient=User::where('email',$invitation->email)->first();if(!$recipient)return;
            app(NotificationService::class)->send($recipient,'team-invitation:'.$invitation->uuid.':created',['category'=>'team','title'=>'Team invitation received','message'=>'You were invited to join '.($invitation->owner?->studio_name?:$invitation->owner?->name?:'a LensPic studio').' as '.ucfirst($invitation->role).'.','studio_id'=>null,'actor_id'=>$invitation->invited_by,'subject_type'=>'team_invitation','subject_id'=>$invitation->uuid,'action_route'=>'settings.team-login','action_label'=>'Review Invitation','severity'=>'info']);
        });
        TeamInvitation::updated(function (TeamInvitation $invitation) {
            if (!$invitation->wasChanged('status')||!in_array($invitation->status,['accepted','revoked'],true))return;
            $owner=$invitation->owner;if(!$owner)return;
            app(NotificationService::class)->send($owner,'team-invitation:'.$invitation->uuid.':'.$invitation->status,['category'=>'team','title'=>$invitation->status==='accepted'?'Team invitation accepted':'Team invitation revoked','message'=>$invitation->name.' — '.$invitation->email.' '.($invitation->status==='accepted'?'joined your studio.':'invitation was revoked.'),'studio_id'=>$owner->id,'subject_type'=>'team_invitation','subject_id'=>$invitation->uuid,'action_route'=>'settings.team-login','action_label'=>'View Team','severity'=>$invitation->status==='accepted'?'success':'info']);
        });
        WalletTopUp::updated(function (WalletTopUp $topUp) {
            if (!$topUp->wasChanged('status')||!in_array($topUp->status,['paid','failed','refunded'],true))return;
            $owner=User::find($topUp->studio_owner_id);if(!$owner)return;$status=$topUp->status;
            app(NotificationService::class)->send($owner,'wallet-top-up:'.$topUp->uuid.':'.$status,['category'=>'billing','title'=>match($status){'paid'=>'Wallet top-up successful','failed'=>'Payment failed',default=>'Refund completed'},'message'=>match($status){'paid'=>'Credits were added to your LensPic Wallet.','failed'=>'The Wallet payment was not completed. No credits were added.',default=>'Your Wallet refund was completed.'},'studio_id'=>$owner->id,'subject_type'=>'wallet_top_up','subject_id'=>$topUp->uuid,'action_route'=>'settings.wallet','action_label'=>'View Wallet','severity'=>$status==='failed'?'error':'success','mandatory'=>true]);
        });
        MediaQuotaUsageEvent::created(function (MediaQuotaUsageEvent $event) {
            $owner=User::find($event->owner_id);if(!$owner)return;$summary=app(StorageUsage::class)->summary($owner);$percent=(int)$summary['photo_percent'];$threshold=$percent>=100?100:($percent>=90?90:($percent>=80?80:null));if(!$threshold)return;
            app(NotificationService::class)->send($owner,'storage:'.$owner->id.':'.$summary['period_starts_at']->toDateString().':'.$threshold,['category'=>'storage','title'=>$threshold>=100?'Storage limit reached':'Storage almost full','message'=>'You have used '.$percent.'% of your photo storage.','studio_id'=>$owner->id,'subject_type'=>'storage_period','subject_id'=>$summary['period_starts_at']->toDateString(),'action_route'=>'settings.profile','action_label'=>'View Storage','severity'=>$threshold>=100?'error':'warning','mandatory'=>$threshold>=100]);
        });
    }
}
