<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RazorpayOrder extends Model
{
    protected $fillable = [
        'user_id', 'provider_order_id', 'plan', 'billing_cycle',
        'amount', 'currency', 'status', 'payment_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
