<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class MediaMigrationBatch extends Model { protected $fillable=['uuid','source_disk','destination_disk','status','batch_size','total_items','succeeded_items','failed_items','total_bytes','manifest','started_at','completed_at','rolled_back_at']; protected $casts=['manifest'=>'array','started_at'=>'datetime','completed_at'=>'datetime','rolled_back_at'=>'datetime']; public function items(){return $this->hasMany(MediaMigrationItem::class);} }
