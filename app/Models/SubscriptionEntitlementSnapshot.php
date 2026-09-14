<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class SubscriptionEntitlementSnapshot extends Model{protected $guarded=['id'];protected $casts=['features'=>'array','addons'=>'array','starts_at'=>'datetime','expires_at'=>'datetime','base_amount_paise'=>'integer','gst_amount_paise'=>'integer','total_amount_paise'=>'integer','photo_limit'=>'integer','photo_reuse_limit'=>'integer','video_limit_mb'=>'integer','team_seat_limit'=>'integer','group_limit'=>'integer','guest_limit'=>'integer'];public function subscription(){return$this->belongsTo(Subscription::class);}public function plan(){return$this->belongsTo(SubscriptionPlan::class,'subscription_plan_id');}}
