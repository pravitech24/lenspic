<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;use Illuminate\Notifications\DatabaseNotification;
class PruneNotifications extends Command{protected $signature='lenspic:prune-notifications {--dry-run}';protected $description='Prune old read, non-mandatory notifications while preserving domain records';public function handle():int{$cutoff=now()->subDays((int)config('notifications.retention_days',90));$query=DatabaseNotification::query()->whereNotNull('read_at')->where('created_at','<',$cutoff)->where(fn($q)=>$q->whereNull('data->mandatory')->orWhere('data->mandatory',false));$count=(clone$query)->count();if(!$this->option('dry-run'))$query->delete();$this->info(($this->option('dry-run')?'Would prune ':'Pruned ').$count.' notifications.');return self::SUCCESS;}}
