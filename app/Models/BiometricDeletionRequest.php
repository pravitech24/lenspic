<?php
namespace App\Models;use Illuminate\Database\Eloquent\Model;class BiometricDeletionRequest extends Model{protected$fillable=['uuid','group_id','user_id','scope','state','attempts','error','requested_at','processing_at','completed_at'];protected$casts=['requested_at'=>'datetime','processing_at'=>'datetime','completed_at'=>'datetime'];}
