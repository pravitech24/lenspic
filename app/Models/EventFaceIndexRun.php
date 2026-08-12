<?php
namespace App\Models;use Illuminate\Database\Eloquent\Model;class EventFaceIndexRun extends Model{protected$fillable=['uuid','group_id','requested_by','state','total','completed','failed','cursor','error'];}
