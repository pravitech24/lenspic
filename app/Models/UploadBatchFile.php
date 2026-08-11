<?php
namespace App\Models; use Illuminate\Database\Eloquent\Model;
class UploadBatchFile extends Model {protected $fillable=['upload_batch_id','media_asset_id','client_filename','state','error','attempts'];public function batch(){return $this->belongsTo(UploadBatch::class,'upload_batch_id');}public function asset(){return $this->belongsTo(MediaAsset::class,'media_asset_id');}}
