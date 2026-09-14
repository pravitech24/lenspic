<?php

namespace App\Console\Commands;

use App\Contracts\ProtectedMediaStorage;
use App\Jobs\ProcessMediaAsset;
use App\Models\UploadBatchFile;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ReconcileUploadQueues extends Command
{
    protected $signature = 'lenspic:reconcile-upload-queues {--dry-run : Report changes without dispatching jobs or updating records}';
    protected $description = 'Safely reconcile queued upload items that no longer have a database processing job';

    public function handle(ProtectedMediaStorage $storage): int
    {
        $queuedAssets = $this->queuedAssetIds();
        $summary = ['healthy' => 0, 'redispatch' => 0, 'failed' => 0];
        UploadBatchFile::query()->whereIn('state', ['pending','queued','processing'])->with('asset.variants')->eachById(function (UploadBatchFile $file) use ($storage, $queuedAssets, &$summary) {
            $asset = $file->asset;
            if ($asset && isset($queuedAssets[$asset->id])) { $summary['healthy']++; return; }
            $original = $asset?->variant('original');
            if (!$asset || !$original || !$storage->exists($original->object_key)) {
                $summary['failed']++;
                $this->warn("Item {$file->id}: mark failed (private original unavailable)");
                if (!$this->option('dry-run')) { $file->update(['state'=>'failed','failed_at'=>now(),'error'=>'The private original is unavailable for processing.']); $file->batch->refreshCounts(); }
                return;
            }
            $summary['redispatch']++;
            $this->line("Item {$file->id}: redispatch media asset {$asset->id}");
            if (!$this->option('dry-run')) { $file->update(['state'=>'queued','error'=>null,'failed_at'=>null]); ProcessMediaAsset::dispatch($asset->id,$file->id)->afterCommit(); $file->batch->refreshCounts(); }
        });
        $this->table(['Healthy queued','Would redispatch','Would fail'], [array_values($summary)]);
        if ($this->option('dry-run')) $this->info('Dry run only; no records or jobs were changed.');
        return self::SUCCESS;
    }

    private function queuedAssetIds(): array
    {
        $ids=[];
        DB::table('jobs')->whereIn('queue',['media-high'])->orderBy('id')->each(function($job)use(&$ids){
            try{$payload=json_decode($job->payload,true,flags:JSON_THROW_ON_ERROR);$command=unserialize($payload['data']['command'],['allowed_classes'=>true]);if($command instanceof ProcessMediaAsset)$ids[$command->assetId]=true;}catch(\Throwable){}
        });
        return $ids;
    }
}
