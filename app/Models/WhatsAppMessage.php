<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsAppMessage extends Model
{
    protected $table = 'whatsapp_messages';
    protected $guarded = ['id'];
    protected $hidden = ['recipient_hash', 'idempotency_key'];
    protected $casts = ['metadata'=>'array', 'last_attempted_at'=>'datetime', 'accepted_at'=>'datetime', 'sent_at'=>'datetime', 'delivered_at'=>'datetime', 'read_at'=>'datetime', 'failed_at'=>'datetime'];
}
