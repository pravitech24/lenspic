# Milestone 1C operations

Use `QUEUE_CONNECTION=sync` locally or `redis` in production. Start Redis, run `php artisan migrate`, then `php artisan horizon`. Horizon is authorized only for authenticated platform administrators.

After deployment run `php artisan horizon:terminate` so the process manager restarts workers on the new code. Inspect and retry failures with `php artisan queue:failed` and `php artisan queue:retry <uuid>`. Prune with `php artisan queue:prune-failed --hours=168`; Horizon retains recent jobs according to `config/horizon.php`.

```ini
[program:lenspic-horizon]
command=/usr/bin/php /var/www/lenspic/artisan horizon
directory=/var/www/lenspic
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/log/supervisor/lenspic-horizon.log
stopwaitsecs=720
```

Queue priority is `media-high`, `media-default`, `exports`, then `maintenance`. Export objects expire after `MEDIA_EXPORT_TTL_MINUTES`; dispatch `PruneExpiredMediaExports` hourly and `ReconcileStorageLedger` daily from the production scheduler.
