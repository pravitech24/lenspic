<?php
namespace App\Models;use Illuminate\Database\Eloquent\Model;
class PortfolioImage extends Model{protected $fillable=['portfolio_id','media_asset_id','sort_order'];public function portfolio(){return$this->belongsTo(Portfolio::class);}public function mediaAsset(){return$this->belongsTo(MediaAsset::class);}}
