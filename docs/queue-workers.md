# LensPic queue workers

LensPic uses `media-high`, `media-default`, `exports`, and `maintenance`. A worker that listens only to `default` will not process LensPic jobs.

## macOS Herd development

For a one-off foreground worker, run:

```bash
php artisan queue:work database --queue=media-high,media-default,exports,maintenance,default --sleep=1 --tries=4 --timeout=600 --max-time=3600 --verbose
```

For persistent local processing, install `deploy/macos/com.lenspic.queue-worker.plist` in `~/Library/LaunchAgents` and bootstrap it with `launchctl`. It uses Herd PHP 8.3, restarts automatically, and listens to every LensPic queue. After deploying code or changing `.env`, run `php artisan optimize:clear` and `php artisan queue:restart`; launchd will recycle the worker after its configured one-hour maximum lifetime.

Inspect before recovering jobs:

```bash
php artisan queue:failed
php artisan lenspic:reconcile-upload-queues --dry-run
php artisan lenspic:reconcile-upload-queues
```

## Persistent staging/production process

The process manager must use the deployment's real PHP binary, project path, and service account. Configure one database worker with the exact command above (replacing the local project path), `autostart=true`, `autorestart=true`, `stopasgroup=true`, `killasgroup=true`, and `stopwaitsecs=660`. Use at least two processes for user-visible media work. For heavier installations, run a priority worker for `media-high,media-default` and separate workers for `exports,maintenance`.

Horizon is installed but is Redis-only. Use `php artisan horizon` only when `QUEUE_CONNECTION=redis` and Redis is deliberately configured; it does not consume the database queue.
