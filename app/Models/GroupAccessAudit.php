<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class GroupAccessAudit extends Model { public $timestamps=false; protected $fillable=['group_id','access_invite_id','user_id','action','metadata','created_at']; protected $casts=['metadata'=>'array','created_at'=>'datetime']; }
