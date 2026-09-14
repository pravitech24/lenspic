<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class FlipbookSetting extends Model {protected $fillable=['studio_owner_id','business_name','logo_disk','logo_object_key','logo_mime_type','logo_size_bytes','apply_to_portfolio'];protected $hidden=['logo_disk','logo_object_key'];protected $casts=['logo_size_bytes'=>'integer','apply_to_portfolio'=>'boolean'];public function owner(){return$this->belongsTo(User::class,'studio_owner_id');}public function hasLogo():bool{return filled($this->logo_object_key)&&filled($this->logo_mime_type);}}
