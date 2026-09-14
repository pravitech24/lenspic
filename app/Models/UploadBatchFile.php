<?php
namespace App\Models; use Illuminate\Database\Eloquent\Model;
class UploadBatchFile extends Model {protected $fillable=['upload_batch_id','media_asset_id','client_filename','state','error','attempts','started_at','completed_at','failed_at','cancelled_at'];protected $casts=['started_at'=>'datetime','completed_at'=>'datetime','failed_at'=>'datetime','cancelled_at'=>'datetime'];public function batch(){return $this->belongsTo(UploadBatch::class,'upload_batch_id');}public function asset(){return $this->belongsTo(MediaAsset::class,'media_asset_id');}}
