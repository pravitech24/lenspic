<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name','email','phone','password','profile_photo',
        'otp','otp_expires_at','phone_verified_at','email_verified_at','mobile_country_code','mobile_e164',
        'account_type','onboarding_step','onboarding_completed_at','status',
        'is_admin','role','role_assigned_at','meta','plan','plan_expires_at','storage_used',
    ];

    protected $hidden = ['password','remember_token','otp'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'phone_verified_at' => 'datetime',
        'otp_expires_at'    => 'datetime',
        'plan_expires_at'   => 'datetime',
        'role_assigned_at'  => 'datetime',
        'onboarding_completed_at' => 'datetime',
        'is_admin'          => 'boolean',
        'meta'              => 'array',
    ];

    public function createdGroups() { return $this->hasMany(Group::class, 'creator_id'); }

    public function groups() {
        return $this->belongsToMany(Group::class, 'group_members')
            ->withPivot('role','joined_at','selfie_path','join_method','membership_status','access_type','access_invite_id')->withTimestamps();
    }

    public function photos() { return $this->hasMany(Photo::class, 'uploader_id'); }
    public function likes()  { return $this->belongsToMany(Photo::class, 'photo_likes')->withTimestamps(); }

    public function subscriptions() { return $this->hasMany(Subscription::class); }
    public function photographerProfile() { return $this->hasOne(PhotographerProfile::class); }
    public function selfieVerifications() { return $this->hasMany(SelfieVerification::class); }
    public function activeSubscription() { return $this->hasOne(Subscription::class)->where('status', 'active')->latest(); }

    public function isSuperAdmin(): bool { return $this->role === 'super_admin'; }
    public function isAdmin(): bool { return $this->role === 'admin' || $this->role === 'super_admin'; }

    public function getProfilePhotoUrlAttribute(): string {
        return $this->profile_photo
            ? asset('storage/'.$this->profile_photo)
            : 'https://ui-avatars.com/api/?name='.urlencode($this->name).'&background=6366f1&color=fff&size=128';
    }

    public function getFirstNameAttribute(): string { return explode(' ', $this->name)[0]; }
    public function getLastNameAttribute(): string  { $p = explode(' ', $this->name, 2); return $p[1] ?? ''; }

    public function getPlanLabelAttribute(): string {
        return match($this->plan) {
            'trial'      => 'Trial',
            'basic'      => 'Basic',
            'standard'   => 'Standard',
            'essential'  => 'Essential',
            'premium'    => 'Premium',
            'pro'        => 'Essential',
            'business'   => 'Premium',
            'enterprise' => 'Premium',
            default      => 'Free',
        };
    }

    public function isTrial(): bool { return $this->plan === 'trial'; }

    public function canAccessFeature(string $feature): bool
    {
        if ($this->isTrial()) {
            return match ($feature) {
                'upload', 'share' => true,
                default => false,
            };
        }

        // Face recognition is only available on paid plans.
        $paidPlans = ['basic','standard','essential','premium','pro','business','enterprise'];

        return match ($feature) {
            'create_group' => $this->plan_limits['groups'] > 0,
            'upload' => true,
            'share' => true,
            'settings' => true,
            'face_recognition' => in_array($this->plan, $paidPlans, true),
            default => true,
        };
    }

    public function canCreateGroup(): bool
    {
        return $this->canAccessFeature('create_group')
            && $this->createdGroups()->count() < $this->plan_limits['groups'];
    }

    public function getPlanLimitsAttribute(): array {
        return match($this->plan) {
            'trial'      => ['groups' => 0, 'photos_per_group' => 500, 'storage_mb' => 512, 'storage_label' => '500-photo trial'],
            'basic'      => ['groups' => 10,  'photos_per_group' => 1000, 'storage_mb' => 20480, 'storage_label' => '20 GB'],
            'standard'   => ['groups' => 999, 'photos_per_group' => 3000,  'storage_mb' => 102400,  'storage_label' => '100 GB'],
            'essential'  => ['groups' => 999, 'photos_per_group' => 6000,  'storage_mb' => 256000,  'storage_label' => '250 GB'],
            'premium'    => ['groups' => 999, 'photos_per_group' => 12500, 'storage_mb' => 614400,  'storage_label' => '600 GB'],
            'pro'        => ['groups' => 999, 'photos_per_group' => 6000,  'storage_mb' => 256000,  'storage_label' => '250 GB'],
            'business'   => ['groups' => 999, 'photos_per_group' => 12500, 'storage_mb' => 614400,  'storage_label' => '600 GB'],
            'enterprise' => ['groups' => 999, 'photos_per_group' => 12500, 'storage_mb' => 9999999, 'storage_label' => 'Unlimited'],
            default      => ['groups' => 3,   'photos_per_group' => 100,   'storage_mb' => 1024,    'storage_label' => '1 GB'],
        };
    }

    public function getStorageUsedMbAttribute(): float { return round($this->storage_used / 1048576, 2); }
    public function getStorageUsedHumanAttribute(): string {
        $b = $this->storage_used;
        if ($b < 1048576) return round($b/1024,1).' KB';
        if ($b < 1073741824) return round($b/1048576,1).' MB';
        return round($b/1073741824,2).' GB';
    }
    public function getStoragePercentAttribute(): int {
        $limitMb = $this->plan_limits['storage_mb'];
        if ($limitMb >= 9999999) return 0;
        return min(100, (int)(($this->storage_used_mb / $limitMb) * 100));
    }

    public function getStudioNameAttribute(): string {
        return $this->meta['studio_name'] ?? $this->name;
    }

    public function generateOtp(): string {
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $this->update(['otp' => bcrypt($otp), 'otp_expires_at' => now()->addMinutes(10)]);
        return $otp;
    }

    public function verifyOtp(string $otp): bool {
        if ($this->otp_expires_at?->isPast()) return false;
        return \Hash::check($otp, $this->otp);
    }
}
