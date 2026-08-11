<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class MediaMigrationItem extends Model { protected $fillable=['media_migration_batch_id','photo_id','source_path','destination_key','source_size','source_checksum','destination_size','destination_checksum','status','error']; }
