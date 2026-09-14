<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Symfony\Component\Process\Process;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('face-api:start', function () {
    $script = base_path('face-api/run.sh');
    $port = (int) config('services.face_recognition.port', 8001);

    $socket = @fsockopen('127.0.0.1', $port, $errorCode, $errorMessage, 0.2);
    if ($socket !== false) {
        fclose($socket);
        $this->info("Face Recognition API is already running on port {$port}.");

        return self::SUCCESS;
    }

    if (!is_file($script)) {
        $this->error('face-api/run.sh was not found.');

        return self::FAILURE;
    }

    $process = new Process(['bash', $script], base_path('face-api'));
    $process->setTimeout(null);

    $this->info('Starting the LensPic Face Recognition API...');
    $process->run(function (string $type, string $buffer) {
        $this->output->write($buffer);
    });

    return $process->isSuccessful() ? self::SUCCESS : self::FAILURE;
})->purpose('Start the FastAPI face recognition service locally');

Schedule::job(new \App\Jobs\PruneExpiredMediaExports)->hourly()->withoutOverlapping();
Schedule::job(new \App\Jobs\PruneBiometricData)->hourly()->withoutOverlapping();
Schedule::job(new \App\Jobs\ReconcileStorageLedger)->dailyAt('02:30')->withoutOverlapping();
Schedule::command('lenspic:reconcile-storage-usage')->dailyAt('02:45')->withoutOverlapping();
Schedule::command('lenspic:prune-notifications')->dailyAt('03:15')->withoutOverlapping();
Schedule::call(function(){\App\Models\MediaAsset::onlyTrashed()->where('kind','photo')->where('deleted_at','<=',now()->subHours(24))->eachById(fn($asset)=>\App\Jobs\PurgeDeletedMediaAsset::dispatch($asset->id));})->name('purge-retained-gallery-media')->hourly()->withoutOverlapping();
Schedule::command('queue:prune-failed --hours=168')->daily();
Schedule::command('horizon:snapshot')->everyFiveMinutes();
