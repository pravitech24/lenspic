<?php
namespace App\Models;use Illuminate\Database\Eloquent\Model;class FaceCollection extends Model{protected$fillable=['group_id','provider','provider_collection_ref','state','error'];public function group(){return$this->belongsTo(Group::class);}public function indexedFaces(){return$this->hasMany(IndexedFace::class);}}
