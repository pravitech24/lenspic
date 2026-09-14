<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Wallet extends Model{protected $fillable=['studio_owner_id','available_credit_units','reserved_credit_units','version'];protected $attributes=['available_credit_units'=>0,'reserved_credit_units'=>0,'version'=>0];protected $casts=['available_credit_units'=>'integer','reserved_credit_units'=>'integer','version'=>'integer'];public function owner(){return$this->belongsTo(User::class,'studio_owner_id');}public function entries(){return$this->hasMany(WalletLedgerEntry::class);}public function topUps(){return$this->hasMany(WalletTopUp::class);}}
