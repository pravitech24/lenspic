<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class SubscriptionFeature extends Model{protected $guarded=['id'];protected $casts=['is_active'=>'boolean'];public function plans(){return$this->belongsToMany(SubscriptionPlan::class,'subscription_plan_features')->using(SubscriptionPlanFeature::class)->withPivot(['is_included','value_boolean','value_integer','value_decimal','value_text','is_addon','addon_price_paise','display_label','display_order']);}}
