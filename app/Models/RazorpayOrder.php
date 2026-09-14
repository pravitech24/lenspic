<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RazorpayOrder extends Model
{
    protected $fillable = [
        'uuid', 'user_id', 'purchase_type', 'subscription_plan_id', 'subscription_plan_price_id', 'subscription_addon_id', 'provider_order_id', 'plan', 'billing_cycle',
        'amount', 'base_amount_paise', 'gst_amount_paise', 'currency', 'status', 'payment_id', 'entitlement_snapshot', 'expires_at', 'paid_at', 'failed_at', 'cancelled_at', 'failure_reason',
    ];
    protected $casts=['entitlement_snapshot'=>'array','expires_at'=>'datetime','paid_at'=>'datetime','failed_at'=>'datetime','cancelled_at'=>'datetime','amount'=>'integer','base_amount_paise'=>'integer','gst_amount_paise'=>'integer'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function subscription(){return $this->hasOne(Subscription::class, 'provider_order_id', 'provider_order_id');}
}
