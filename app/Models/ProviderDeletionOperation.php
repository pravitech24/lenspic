<?php
namespace App\Models;use Illuminate\Database\Eloquent\Model;class ProviderDeletionOperation extends Model{protected$fillable=['uuid','provider','operation','provider_references','state','attempts','error','completed_at'];protected$casts=['provider_references'=>'array','completed_at'=>'datetime'];}
