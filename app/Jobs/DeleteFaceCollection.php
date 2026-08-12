<?php
namespace App\Jobs;use App\Contracts\FaceProvider;use App\Models\FaceCollection;use Illuminate\Bus\Queueable;use Illuminate\Contracts\Queue\ShouldQueue;use Illuminate\Foundation\Bus\Dispatchable;use Illuminate\Queue\{InteractsWithQueue,SerializesModels};
class DeleteFaceCollection implements ShouldQueue{use Dispatchable,InteractsWithQueue,Queueable,SerializesModels;public function __construct(public string$providerCollectionRef){$this->onQueue('maintenance');}public function handle(FaceProvider$p):void{$p->deleteCollection($this->providerCollectionRef);}}
