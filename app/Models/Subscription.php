<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'subscription_plan_id', 'plan', 'plan_version', 'starts_at', 'expires_at',
        'amount', 'currency', 'payment_id', 'provider_order_id', 'billing_cycle', 'status', 'scheduled_plan', 'scheduled_change_at', 'notes',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'scheduled_change_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function catalogPlan(){return $this->belongsTo(SubscriptionPlan::class,'subscription_plan_id');}
    public function entitlementSnapshot(){return $this->hasOne(SubscriptionEntitlementSnapshot::class);}

    public function isActive(): bool
    {
        return $this->status === 'active' && ($this->expires_at === null || $this->expires_at->isFuture());
    }

    public function getPlanLabelAttribute(): string
    {
        return match($this->plan) {
            'standard'   => 'Standard',
            'essential'  => 'Essential',
            'premium'    => 'Premium',
            'pro'        => 'Essential',
            'business'   => 'Premium',
            'enterprise' => 'Premium',
            default      => 'Free',
        };
    }
}
