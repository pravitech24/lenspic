<?php

namespace App\Jobs;

use App\Models\MediaAsset;
use App\Services\Media\GroupCoverProcessor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\{InteractsWithQueue, SerializesModels};
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Throwable;

class ProcessGroupCover implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public int $tries=4; public int $timeout=180;
    public function __construct(public int $assetId){$this->onQueue('media-high');}
    public function backoff():array{return[10,30,120];}
    public function middleware():array{return[(new WithoutOverlapping('group-cover-'.$this->assetId))->expireAfter(240)->dontRelease()];}
    public function handle(GroupCoverProcessor $processor):void{$asset=MediaAsset::findOrFail($this->assetId);$asset->update(['state'=>'processing','processing_error'=>null]);try{$processor->process($asset);}catch(Throwable$e){$asset->update(['state'=>'failed','processing_error'=>'Cover processing failed.']);report($e);throw$e;}}
    public function failed(?Throwable$e):void{MediaAsset::find($this->assetId)?->update(['state'=>'failed','processing_error'=>'Cover processing failed after retries.']);}
}
