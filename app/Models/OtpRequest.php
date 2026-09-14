<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class OtpRequest extends Model {
    protected $fillable = ['provider','delivery_status','failure_category','destination_masked','accepted_at','session_hash','request_key','public_reference','authentication_channel','disclosure_version','mobile_e164','email','channel','otp_hash','expires_at','attempts','resend_available_at','verified_at'];
    protected $hidden = ['otp_hash','session_hash','request_key','mobile_e164'];
    protected $casts = ['accepted_at'=>'datetime','expires_at'=>'datetime','resend_available_at'=>'datetime','verified_at'=>'datetime'];
}
