<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class SubscriptionPlanAudit extends Model{protected $guarded=['id'];protected $casts=['previous_values'=>'array','new_values'=>'array'];public function plan(){return$this->belongsTo(SubscriptionPlan::class,'subscription_plan_id');}public function actor(){return$this->belongsTo(User::class,'actor_id');}}
