<?php
namespace App\Models;use Illuminate\Database\Eloquent\Model;
class WalletReservation extends Model{protected $fillable=['uuid','wallet_id','credit_units','purpose','reference_type','reference_id','idempotency_key','status','expires_at','captured_at','released_at'];protected $casts=['credit_units'=>'integer','expires_at'=>'datetime','captured_at'=>'datetime','released_at'=>'datetime'];}
