<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Group extends Model
{
    protected $fillable = [
        'name', 'description', 'event_date', 'event_type',
        'cover_photo', 'share_token', 'creator_id', 'is_active',
        'allow_guest_upload', 'watermark_enabled', 'watermark_text',
        'face_recognition_enabled', 'privacy',
        'event_code','invitation_token','invitation_expires_at','event_code_expires_at',
        'membership_status','membership_limit','location',
    ];

    protected $casts = [
        'event_date'               => 'date',
        'created_at'               => 'datetime',
        'updated_at'               => 'datetime',
        'is_active'                => 'boolean',
        'allow_guest_upload'       => 'boolean',
        'watermark_enabled'        => 'boolean',
        'face_recognition_enabled' => 'boolean',
        'invitation_expires_at' => 'datetime',
        'event_code_expires_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($g) {
            $g->share_token ??= Str::random(12);
            $g->invitation_token ??= Str::random(48);
            if (!$g->event_code) do { $code = (string) random_int(100000, 999999); } while (static::where('event_code', $code)->exists());
            $g->event_code ??= $code;
        });
    }

    public function creator()  { return $this->belongsTo(User::class, 'creator_id'); }
    public function photos()   { return $this->hasMany(Photo::class); }
    public function folders()  { return $this->hasMany(Folder::class)->ordered(); }
    public function accessInvites() { return $this->hasMany(GroupAccessInvite::class); }

    public function members()
    {
        return $this->belongsToMany(User::class, 'group_members')
            ->withPivot('role','joined_at','selfie_path','join_method','membership_status','access_type','access_invite_id','requested_at','approved_at','approved_by','access_upgraded_at')->withTimestamps();
    }

    public function getShareUrlAttribute()    { return route('guest.group', $this->share_token); }
    public function getPhotosCountAttribute() { return $this->photos()->count(); }
    public function getMembersCountAttribute(){ return $this->members()->count(); }

    public function getCoverPhotoUrlAttribute()
    {
        return $this->cover_photo ? asset('storage/' . $this->cover_photo) : null;
    }

    public function isAdmin(User $user): bool
    {
        return $this->creator_id === $user->id ||
            $this->members()->where('user_id', $user->id)->wherePivot('role', 'admin')->exists();
    }

    public function isMember(User $user): bool
    {
        return $this->members()->where('user_id', $user->id)->wherePivot('membership_status','active')->exists();
    }

    public function membershipFor(User $user) { return $this->members()->where('user_id',$user->id)->first(); }
    public function hasFullAccess(User $user): bool { return $this->isAdmin($user) || $this->members()->where('user_id',$user->id)->wherePivot('membership_status','active')->wherePivot('access_type',GroupAccessInvite::FULL)->exists(); }

    public function regenerateToken(): void { $this->update(['share_token' => Str::random(12)]); }
    public function getInvitationUrlAttribute(): string { return route('invitations.show', $this->invitation_token); }

    public function getEventTypeLabel(): string
    {
        return match($this->event_type) {
            'wedding'    => '💍 Wedding',
            'birthday'   => '🎂 Birthday',
            'corporate'  => '🏢 Corporate',
            'graduation' => '🎓 Graduation',
            'travel'     => '✈️ Travel',
            'family'     => '👨‍👩‍👧 Family',
            'festival'   => '🎉 Festival',
            'sports'     => '⚽ Sports',
            default      => '📸 Event',
        };
    }
}
