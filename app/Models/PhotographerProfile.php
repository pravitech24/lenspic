<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class PhotographerProfile extends Model {
    protected $fillable = ['user_id','first_name','last_name','company_name','company_email','selfie_verification_id'];
    public function user() { return $this->belongsTo(User::class); }
    public function selfieVerification() { return $this->belongsTo(SelfieVerification::class); }
}
