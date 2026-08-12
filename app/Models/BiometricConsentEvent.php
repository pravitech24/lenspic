<?php
namespace App\Models;use Illuminate\Database\Eloquent\Model;class BiometricConsentEvent extends Model{public$timestamps=false;protected$fillable=['biometric_consent_id','action','purpose','consent_version','actor_id','metadata','created_at'];protected$casts=['metadata'=>'array','created_at'=>'datetime'];}
