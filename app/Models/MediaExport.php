<?php
namespace App\Models; use Illuminate\Database\Eloquent\Model;
class MediaExport extends Model {protected $fillable=['uuid','group_id','user_id','state','photo_ids','storage_disk','object_key','size_bytes','checksum_sha256','error','expires_at','cancelled_at'];protected $casts=['photo_ids'=>'array','expires_at'=>'datetime','cancelled_at'=>'datetime'];public function group(){return $this->belongsTo(Group::class);}}
