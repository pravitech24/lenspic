<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class StudioTeamMembership extends Model {
 protected $fillable=['uuid','studio_owner_id','user_id','role','status','permissions','assigned_group_ids','invited_by','joined_at','suspended_at','last_active_at'];
 protected $casts=['permissions'=>'array','assigned_group_ids'=>'array','joined_at'=>'datetime','suspended_at'=>'datetime','last_active_at'=>'datetime'];
 public function getRouteKeyName():string{return'uuid';} public function owner(){return$this->belongsTo(User::class,'studio_owner_id');} public function user(){return$this->belongsTo(User::class);} public function inviter(){return$this->belongsTo(User::class,'invited_by');}
}
