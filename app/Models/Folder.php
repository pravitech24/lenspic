<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Folder extends Model
{
    protected $fillable = [
        'group_id',
        'cover_photo_id',
        'name',
        'description',
        'color',
        'display_order',
        'highlighted',
    ];

    protected $casts = [
        'highlighted' => 'boolean',
        'display_order' => 'integer',
    ];

    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function coverPhoto()
    {
        return $this->belongsTo(Photo::class, 'cover_photo_id');
    }

    public function photos()
    {
        return $this->hasMany(Photo::class);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('highlighted', 'desc')->orderBy('display_order')->orderBy('created_at');
    }

    public function getPhotoCountAttribute(): int
    {
        return $this->photos()->count();
    }
}
