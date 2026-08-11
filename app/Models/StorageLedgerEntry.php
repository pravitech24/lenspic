<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class StorageLedgerEntry extends Model { public const UPDATED_AT=null; protected $fillable=['owner_id','group_id','media_asset_id','event_type','byte_delta','idempotency_key','metadata','created_at']; protected $casts=['metadata'=>'array','created_at'=>'datetime']; }
