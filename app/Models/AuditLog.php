<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['actor_id', 'actor_type', 'group_id', 'action', 'subject_type', 'subject_id', 'request_id', 'ip_address', 'user_agent', 'before', 'after', 'metadata', 'created_at'];
    protected $casts = ['before' => 'array', 'after' => 'array', 'metadata' => 'array', 'created_at' => 'datetime'];

    public function actor() { return $this->belongsTo(User::class, 'actor_id'); }
    public function group() { return $this->belongsTo(Group::class); }
}
