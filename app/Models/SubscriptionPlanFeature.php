<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Relations\Pivot;
class SubscriptionPlanFeature extends Pivot{protected $table='subscription_plan_features';public $incrementing=true;protected $guarded=['id'];protected $casts=['is_included'=>'boolean','value_boolean'=>'boolean','value_integer'=>'integer','value_decimal'=>'decimal:2','is_addon'=>'boolean','addon_price_paise'=>'integer'];}
