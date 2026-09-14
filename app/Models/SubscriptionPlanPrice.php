<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class SubscriptionPlanPrice extends Model{protected $guarded=['id'];protected $casts=['base_amount_paise'=>'integer','gst_rate_basis_points'=>'integer','comparison_amount_paise'=>'integer','is_active'=>'boolean','effective_from'=>'datetime','effective_until'=>'datetime'];public function plan(){return$this->belongsTo(SubscriptionPlan::class,'subscription_plan_id');}public function gstAmount():int{return intdiv($this->base_amount_paise*$this->gst_rate_basis_points+5000,10000);}public function totalAmount():int{return$this->base_amount_paise+$this->gstAmount();}}
