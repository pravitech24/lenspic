<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactEnquiry extends Model
{
    protected $fillable = [
        'uuid', 'full_name', 'business_name', 'email', 'phone_country_code',
        'phone_number', 'country', 'enquiry_type', 'message', 'status',
        'source', 'fingerprint', 'consented_at',
    ];

    protected $hidden = ['fingerprint'];
    protected $casts = ['consented_at' => 'datetime'];
}
