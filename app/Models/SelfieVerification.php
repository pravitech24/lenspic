<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class SelfieVerification extends Model {
    protected $fillable = ['user_id','image_path','face_count','blur_score','lighting_score','verification_status','failure_reason','verified_at'];
    protected $casts = ['verified_at'=>'datetime'];
    public function user() { return $this->belongsTo(User::class); }
}
