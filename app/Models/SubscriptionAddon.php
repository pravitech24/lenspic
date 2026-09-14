<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class SubscriptionAddon extends Model{protected $guarded=['id'];protected $casts=['base_amount_paise'=>'integer','gst_amount_paise'=>'integer','total_amount_paise'=>'integer','starts_at'=>'datetime','expires_at'=>'datetime'];public function feature(){return$this->belongsTo(SubscriptionFeature::class,'subscription_feature_id');}public function user(){return$this->belongsTo(User::class);}public function active():bool{return$this->status==='active'&&$this->starts_at?->lte(now())&&$this->expires_at?->isFuture();}}
