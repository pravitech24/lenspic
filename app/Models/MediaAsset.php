<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class MediaAsset extends Model { use SoftDeletes; protected $fillable=['uuid','photo_id','group_id','owner_id','uploader_id','kind','storage_disk','original_object_key','original_filename','mime_type','size_bytes','checksum_sha256','width','height','captured_at','exif','orientation','state','visibility','legacy_path','migration_state']; protected $casts=['captured_at'=>'datetime','exif'=>'array']; public function photo(){return $this->belongsTo(Photo::class);} public function group(){return $this->belongsTo(Group::class);} public function variants(){return $this->hasMany(MediaVariant::class);} public function variant(string $type){return $this->variants()->where('variant_type',$type)->where('state','ready')->latest('version')->first();} }
