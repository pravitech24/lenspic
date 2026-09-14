<?php
namespace App\Models;use Illuminate\Database\Eloquent\Model;
class PortfolioService extends Model{protected $fillable=['portfolio_id','system_key','name','price','enabled','is_custom','sort_order','archived_at'];protected $casts=['enabled'=>'boolean','is_custom'=>'boolean','price'=>'decimal:2','archived_at'=>'datetime'];}
