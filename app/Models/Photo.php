<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Photo extends Model
{
    protected $fillable = [
        'group_id', 'uploader_id', 'filename', 'original_filename',
        'path', 'thumbnail_path', 'file_size', 'mime_type',
        'width', 'height', 'taken_at', 'caption', 'folder_id',
        'downloads_count', 'views_count', 'uploader_name', 'uploader_phone',
    ];

    protected $casts = ['taken_at' => 'datetime'];

    public function group()    { return $this->belongsTo(Group::class); }
    public function folder()   { return $this->belongsTo(Folder::class); }
    public function uploader() { return $this->belongsTo(User::class, 'uploader_id'); }
    public function likes()    { return $this->belongsToMany(User::class, 'photo_likes')->withTimestamps(); }
    public function comments() { return $this->hasMany(Comment::class); }

    public function getUrlAttribute()          { return asset('storage/' . $this->path); }
    public function getThumbnailUrlAttribute() { return $this->thumbnail_path ? asset('storage/' . $this->thumbnail_path) : $this->url; }
    public function getLikesCountAttribute()   { return $this->likes()->count(); }

    public function isLikedBy(?User $user): bool
    {
        if (!$user) return false;
        return $this->likes()->where('user_id', $user->id)->exists();
    }

    public function getFileSizeHumanAttribute(): string
    {
        $b = $this->file_size;
        if ($b < 1024) return $b . ' B';
        if ($b < 1048576) return round($b / 1024, 1) . ' KB';
        return round($b / 1048576, 1) . ' MB';
    }

    public function incrementViews()     { $this->increment('views_count'); }
    public function incrementDownloads() { $this->increment('downloads_count'); }
}
