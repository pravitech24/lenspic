<?php
namespace App\Models;use Illuminate\Database\Eloquent\Model;class IndexedFace extends Model{protected$fillable=['face_collection_id','media_asset_id','provider_face_ref','provider_external_ref','confidence','state'];public function asset(){return$this->belongsTo(MediaAsset::class,'media_asset_id');}public function faceCollection(){return$this->belongsTo(FaceCollection::class);}}
