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
    public function subscriptionAddons() { return $this->hasMany(SubscriptionAddon::class); }
    public function photographerProfile() { return $this->hasOne(PhotographerProfile::class); }
    public function businessBranding() { return $this->hasOne(BusinessBranding::class); }
    public function studioTeamMembers() { return $this->hasMany(StudioTeamMembership::class, 'studio_owner_id'); }
    public function studioMemberships() { return $this->hasMany(StudioTeamMembership::class); }
    public function teamInvitations() { return $this->hasMany(TeamInvitation::class, 'studio_owner_id'); }
    public function flipbookSetting() { return $this->hasOne(FlipbookSetting::class, 'studio_owner_id'); }
    public function watermarkSetting() { return $this->hasOne(WatermarkSetting::class, 'studio_owner_id'); }
    public function portfolio() { return $this->hasOne(Portfolio::class, 'studio_owner_id'); }
    public function wallet() { return $this->hasOne(Wallet::class, 'studio_owner_id'); }
    public function selfieVerifications() { return $this->hasMany(SelfieVerification::class); }
    public function activeSubscription() { return $this->hasOne(Subscription::class)->where('status', 'active')->latest(); }

    public function isSuperAdmin(): bool { return $this->role === 'super_admin'; }
    public function isAdmin(): bool { return $this->is_admin || $this->role === 'admin' || $this->role === 'super_admin'; }

    public function hasGroupCreatorRole(): bool
    {
        return in_array($this->account_type, ['photographer', 'studio'], true)
            || in_array($this->role, ['photographer', 'studio', 'admin', 'super_admin'], true)
            || $this->is_admin
            || $this->createdGroups()->exists();
    }

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

        if(in_array($feature,['upload','share','settings'],true))return true;
        if($feature==='create_group')return(app(\App\Services\Billing\AccountEntitlements::class)->integerLimit($this,'group_limit')??PHP_INT_MAX)>0;
        $code=['face_recognition'=>'find_my_photos'][$feature]??$feature;
        return app(\App\Services\Billing\AccountEntitlements::class)->allows($this,$code);
    }

    public function canCreateGroup(): bool
    {
        $limit=app(\App\Services\Billing\AccountEntitlements::class)->integerLimit($this,'group_limit');
        return $this->canAccessFeature('create_group')&&($limit===null||$this->createdGroups()->count()<$limit);
    }

    public function getPlanLimitsAttribute(): array {
        if($this->isTrial())return['groups'=>0,'photos_per_group'=>500,'storage_mb'=>0,'storage_label'=>'500-photo trial'];
        $s=app(\App\Services\Billing\AccountEntitlements::class)->snapshot($this);return['groups'=>$s['group_limit']??PHP_INT_MAX,'photos_per_group'=>$s['photo_limit'],'photo_limit'=>$s['photo_limit'],'video_storage_limit_mb'=>$s['video_limit_mb'],'photo_delete_reupload_limit'=>$s['photo_reuse_limit'],'video_delete_reupload_limit_mb'=>$s['video_limit_mb']*2,'deleted_media_usage_release_hours'=>24,'storage_mb'=>0,'storage_label'=>number_format($s['photo_limit']).' photos'];
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
        if ($limitMb <= 0 || $limitMb >= 9999999) return 0;
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
