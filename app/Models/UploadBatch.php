<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class UploadBatch extends Model
{
    protected $fillable = ['uuid','group_id','user_id','state','total_files','pending_files','processing_files','completed_files','failed_files','cancelled_files','cancelled_at'];
    protected $casts = ['cancelled_at' => 'datetime'];
    public function files(){return $this->hasMany(UploadBatchFile::class);}
    public function group(){return $this->belongsTo(Group::class);}
    public function user(){return $this->belongsTo(User::class);}

    public function refreshCounts(): void
    {
        DB::transaction(function () {
            $batch = self::query()->lockForUpdate()->findOrFail($this->id);
            $counts = $batch->files()->selectRaw('state,count(*) aggregate')->groupBy('state')->pluck('aggregate','state');
            $failed = (int)($counts['failed']??0);
            $completed = (int)($counts['completed']??0);
            $processing = (int)($counts['processing']??0);
            $cancelled = (int)($counts['cancelled']??0);
            $pending = (int)($counts['pending']??0)+(int)($counts['queued']??0);
            $active = $pending + $processing;
            $state = match (true) {
                $batch->cancelled_at !== null && $active === 0 && $completed === 0 && $failed === 0 => 'cancelled',
                $active > 0 && ($processing > 0 || $completed > 0 || $failed > 0) => 'processing',
                $active > 0 => 'queued',
                $completed > 0 && $failed > 0 => 'completed_with_errors',
                $failed > 0 && $completed === 0 => 'failed',
                $completed > 0 => 'completed',
                $cancelled > 0 => 'cancelled',
                default => 'pending',
            };
            $batch->update(['state'=>$state,'pending_files'=>$pending,'processing_files'=>$processing,'completed_files'=>$completed,'failed_files'=>$failed,'cancelled_files'=>$cancelled]);
            $this->setRawAttributes($batch->fresh()->getAttributes(), true);
        });
        if (in_array($this->state, ['completed','completed_with_errors','failed'], true) && $this->user && $this->group) {
            $failed=(int)$this->failed_files;$completed=(int)$this->completed_files;
            app(\App\Services\Notifications\NotificationService::class)->send($this->user, 'upload-batch:'.$this->uuid.':terminal', ['category'=>'uploads','title'=>$this->state==='failed'?'Photo upload failed':($failed?'Photo upload partially completed':'Photo upload completed'),'message'=>$completed.' '.str('photo')->plural($completed).' were added to '.$this->group->name.'.'.($failed?' '.$failed.' could not be uploaded.':''),'studio_id'=>$this->group->creator_id,'subject_type'=>'upload_batch','subject_id'=>$this->uuid,'action_route'=>'groups.operations','action_parameters'=>['group'=>$this->group_id],'action_label'=>'View Upload','severity'=>$this->state==='failed'?'error':($failed?'warning':'success')]);
        }
    }
}
