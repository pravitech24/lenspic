<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class GroupAccessInvite extends Model {
    public const PARTIAL='partial_access'; public const FULL='full_access';
    protected $fillable=['group_id','access_type','access_code','invitation_token','is_active','expires_at','max_uses','used_count','created_by','revoked_at'];
    protected $casts=['is_active'=>'boolean','expires_at'=>'datetime','revoked_at'=>'datetime'];
    public function group(){return $this->belongsTo(Group::class);} public function creator(){return $this->belongsTo(User::class,'created_by');}
    public function getUrlAttribute(): string { return route('invitations.show',$this->invitation_token); }
    public function getLabelAttribute(): string { return $this->access_type===self::FULL?'Full Access':'Partial Access'; }
    public function isUsable(): bool { return $this->is_active && !$this->revoked_at && !$this->expires_at?->isPast() && (!$this->max_uses || $this->used_count<$this->max_uses); }
    public static function generateCode(): string
    {
        $letters = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
        $numbers = '23456789';
        $characters = $letters.$numbers;

        do {
            // Always include at least one letter and one number.
            $parts = [
                $letters[random_int(0, strlen($letters) - 1)],
                $numbers[random_int(0, strlen($numbers) - 1)],
            ];
            while (count($parts) < 6) {
                $parts[] = $characters[random_int(0, strlen($characters) - 1)];
            }
            for ($i = count($parts) - 1; $i > 0; $i--) {
                $j = random_int(0, $i);
                [$parts[$i], $parts[$j]] = [$parts[$j], $parts[$i]];
            }
            $code = implode('', $parts);
        } while (static::where('access_code', $code)->exists());

        return $code;
    }
    public static function makeFor(Group $group,string $type,int $creatorId): self { return static::create(['group_id'=>$group->id,'access_type'=>$type,'access_code'=>static::generateCode(),'invitation_token'=>Str::random(48),'created_by'=>$creatorId]); }
}
