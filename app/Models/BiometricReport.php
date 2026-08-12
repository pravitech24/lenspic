<?php
namespace App\Models;use Illuminate\Database\Eloquent\Model;class BiometricReport extends Model{protected$fillable=['uuid','face_match_result_id','reporter_id','reason','details','state','reviewed_by','reviewed_at'];protected$casts=['reviewed_at'=>'datetime'];public function result(){return$this->belongsTo(FaceMatchResult::class,'face_match_result_id');}}
