<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class TeamInvitation extends Model {
 protected $fillable=['uuid','studio_owner_id','name','email','role','permissions','assigned_group_ids','token_hash','status','invited_by','expires_at','accepted_at','revoked_at','last_sent_at'];
 protected $hidden=['token_hash']; protected $casts=['permissions'=>'array','assigned_group_ids'=>'array','expires_at'=>'datetime','accepted_at'=>'datetime','revoked_at'=>'datetime','last_sent_at'=>'datetime'];
 public function getRouteKeyName():string{return'uuid';} public function owner(){return$this->belongsTo(User::class,'studio_owner_id');} public function inviter(){return$this->belongsTo(User::class,'invited_by');}
 public function effectiveStatus():string{return $this->status==='pending'&&$this->expires_at?->isPast()?'expired':$this->status;}
}
