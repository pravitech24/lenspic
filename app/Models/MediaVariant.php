<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class MediaVariant extends Model { protected $fillable=['media_asset_id','variant_type','version','storage_disk','object_key','mime_type','size_bytes','checksum_sha256','width','height','state','error']; public function mediaAsset(){return $this->belongsTo(MediaAsset::class);} }
