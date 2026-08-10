<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class OtpRequest extends Model {
    protected $fillable = ['mobile_e164','email','channel','otp_hash','expires_at','attempts','resend_available_at','verified_at'];
    protected $hidden = ['otp_hash'];
    protected $casts = ['expires_at'=>'datetime','resend_available_at'=>'datetime','verified_at'=>'datetime'];
}
