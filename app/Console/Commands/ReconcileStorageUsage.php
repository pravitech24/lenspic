<?php

namespace App\Console\Commands;

use App\Models\{StorageLedgerEntry,User};
use App\Services\Storage\StorageUsage;
use Illuminate\Console\Command;

class ReconcileStorageUsage extends Command
{
    protected $signature='lenspic:reconcile-storage-usage {--dry-run : Report drift without changing counters}';
    protected $description='Compare account storage counters with the durable ledger and safely correct counter drift';
    public function handle(StorageUsage $usage):int
    {
        $rows=[];User::eachById(function(User$user)use(&$rows,$usage){$ledger=max(0,(int)StorageLedgerEntry::where('owner_id',$user->id)->sum('byte_delta'));$summary=$usage->summary($user);if((int)$user->storage_used!==$ledger){$rows[]=[$user->id,$user->storage_used,$ledger,$summary['counted_photos']];if(!$this->option('dry-run'))$user->forceFill(['storage_used'=>$ledger])->save();}});
        $this->table(['Owner','Counter bytes','Ledger bytes','Counted photos'],$rows);$this->info($this->option('dry-run')?'Dry run only; no records changed.':'Safe storage counter drift corrected.');return self::SUCCESS;
    }
}
