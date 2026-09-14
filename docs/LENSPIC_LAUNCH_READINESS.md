# LensPic launch-readiness audit

Audit date: 2026-08-26. Scope: the current Laravel 11, Vue/Inertia, Horizon, private-media, Wallet, Razorpay and Python face-service code. This document contains placeholders only.

## Decision

**NO-GO for production.** The application test/build baseline is healthy, but production infrastructure and external-provider evidence are absent. Blocking items are: a high-severity Laravel framework advisory affecting the installed Laravel 11 line; no verified database/object-store restore; no verified production Redis/Horizon/scheduler; no staging smoke test; Razorpay live webhook not verified; mail/WhatsApp delivery not verified; monitoring is not configured; and the shipped Python service is explicitly a scaffold and does not implement the collection/index/search API consumed by `PythonFaceProvider`.

Staging may be configured after the items labelled “before staging” below are completed.

## Changes made during this audit

- Added sanitized local, staging and production environment templates.
- Added `lenspic:check-configuration`; it is read-only, redacts secrets, and returns 0/1/2 for pass/warn/fail.
- Added the missing Redis database/cache configuration and environment-specific prefixes.
- Made session encryption, HttpOnly and SameSite configurable.
- Moved the two application-level `env()` reads into configuration.
- Changed the application timezone default to UTC and made locale/timezone configurable.
- Added host validation and baseline security headers for staging/production.
- Added the Wallet webhook to the narrow CSRF exception list; its controller still verifies the Razorpay signature.
- Made `/recognize` on the Python service fail closed unless a bearer token is configured and supplied.
- Corrected the Python service and Composer package metadata to use LensPic branding consistently.

## Actual environment-variable matrix

Legend: L/S/P = local/staging/production; R = required; O = optional; S = sensitive. Defaults are shown only when present in code.

| Variable(s) | Purpose | L | S | P | Sensitive | Default/check | Used by |
|---|---|---:|---:|---:|---:|---|---|
| `APP_NAME`, `APP_ENV`, `APP_KEY`, `APP_DEBUG`, `APP_URL` | Core identity/runtime | R | R | R | key | LensPic, production, false, localhost; validator | `config/app.php` |
| `APP_TIMEZONE`, `APP_LOCALE`, `APP_FALLBACK_LOCALE` | UTC storage/runtime locale | O | R | R | No | UTC/en/en | `config/app.php` |
| `LOG_CHANNEL`, `LOG_LEVEL` | Application logging | O | R | R | No | stack/debug; production info | logging |
| `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | MySQL/SQLite | R | R | R | credentials | local sqlite; staging/production MySQL | database, queues, failed jobs |
| `CACHE_STORE`, `CACHE_DRIVER`, `CACHE_PREFIX`, `CACHE_REDIS_CONNECTION`, `CACHE_REDIS_LOCK_CONNECTION` | Cache and locks | O | R | R | No | file locally; Redis remotely | cache, throttles, locks |
| `DB_CACHE_CONNECTION`, `DB_CACHE_TABLE`, `DB_CACHE_LOCK_CONNECTION`, `DB_CACHE_LOCK_TABLE` | Database cache fallback | O | O | O | No | cache/cache_locks | cache config |
| `SESSION_DRIVER`, `SESSION_LIFETIME`, `SESSION_ENCRYPT`, `SESSION_DOMAIN`, `SESSION_SECURE_COOKIE`, `SESSION_HTTP_ONLY`, `SESSION_SAME_SITE` | Session/cookies | R | R | R | No | file/120/false; remote Redis + secure | session/auth/CSRF |
| `QUEUE_CONNECTION`, `QUEUE_RETRY_AFTER`, `QUEUE_REDIS_CONNECTION`, `REDIS_QUEUE` | Queue runtime | R | R | R | No | local database; remote Redis, retry 900 | queues/Horizon |
| `DB_QUEUE_CONNECTION`, `DB_QUEUE_TABLE`, `DB_QUEUE` | Database queue fallback | O | O | O | No | jobs/default | queue config |
| `REDIS_CLIENT`, `REDIS_URL`, `REDIS_HOST`, `REDIS_USERNAME`, `REDIS_PASSWORD`, `REDIS_PORT`, `REDIS_DB`, `REDIS_CACHE_DB`, `REDIS_CLUSTER`, `REDIS_PREFIX` | Redis connectivity/isolation | O | R | R | credentials | predis/6379/DB 0+1 | database/cache/session/Horizon |
| `HORIZON_NAME`, `HORIZON_DOMAIN`, `HORIZON_PATH`, `HORIZON_PREFIX` | Worker dashboard/namespace | O | R | R | No | horizon path | Horizon |
| `FILESYSTEM_DISK` | Laravel default disk | O | R | R | No | local | general/public assets |
| `MEDIA_DISK`, `MEDIA_DELIVERY_DRIVER` | Private media storage/delivery | R | R | R | No | private_local/local | media services |
| `MEDIA_ORIGINAL_PREFIX`, `MEDIA_OPTIMIZED_PREFIX`, `MEDIA_THUMBNAIL_PREFIX`, `MEDIA_WATERMARK_PREFIX`, `MEDIA_COVER_PREFIX`, `MEDIA_TEMP_SELFIE_PREFIX` | Private object namespaces | O | R | R | No | `media/...` | ingestion/variants/biometrics |
| `PHOTO_UPLOAD_MAX_FILE_MB`, `PHOTO_UPLOAD_MAX_BATCH_FILES`, `MEDIA_MAX_UPLOAD_BYTES`, `MEDIA_EXPORT_TTL_MINUTES`, `MEDIA_MAX_DECODED_PIXELS` | Photo selection/file, upload/export, and decode-safety limits | O | R | R | No | 30 MiB/50/30 MiB/60/80M | upload/processing/export |
| `MEDIA_PROCESSING_VERSION`, `MEDIA_STANDARD_MAX_EDGE`, `MEDIA_STANDARD_QUALITY`, `MEDIA_STANDARD_MIN_QUALITY`, `MEDIA_STANDARD_TARGET_BYTES` | Standard image profile | O | R | R | No | 2/3840/92/85/3 MiB | variant processor |
| `MEDIA_HIGH_RES_MAX_EDGE`, `MEDIA_HIGH_RES_QUALITY`, `MEDIA_HIGH_RES_MIN_QUALITY`, `MEDIA_HIGH_RES_TARGET_BYTES` | High-resolution profile | O | R | R | No | 6000/95/90/4 MiB | variant processor |
| `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_SESSION_TOKEN` | S3/SES credentials | O | conditional | conditional | Yes | Prefer IAM role; blank supported | filesystem/services |
| `AWS_DEFAULT_REGION`, `AWS_BUCKET`, `AWS_ENDPOINT`, `AWS_USE_PATH_STYLE_ENDPOINT` | Private S3 | O | R | R | bucket may be sensitive | ap-south-1/false | filesystem |
| `AWS_REKOGNITION_REGION` | Rekognition region | O | conditional | conditional | No | ap-south-1 | face config |
| `CLOUDFRONT_DOMAIN`, `CLOUDFRONT_KEY_PAIR_ID`, `CLOUDFRONT_PRIVATE_KEY_PATH`, `CLOUDFRONT_URL_TTL_SECONDS`, `CLOUDFRONT_DOWNLOAD_TTL_SECONDS` | Signed media delivery | O | R when selected | R when selected | key/file | 300/900; validator | media signer |
| `MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_ENCRYPTION`, `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME` | SMTP | O | R | R | credentials | local log; SMTP remote | invitations/OTP mail |
| `MAILGUN_DOMAIN`, `MAILGUN_SECRET`, `MAILGUN_ENDPOINT`, `POSTMARK_TOKEN` | Optional mail transports | O | conditional | conditional | Yes | unused unless selected | services config |
| `OTP_DEFAULT_COUNTRY_CODE`, `OTP_EXPIRY_MINUTES`, `OTP_RESEND_SECONDS`, `OTP_MAX_ATTEMPTS`, `OTP_TEST_MODE`, `OTP_TEST_CODE`, `OTP_DRIVER` | Authentication OTP | R | R | R | test code | +91/5/60/5; test mode forbidden remote | OTP services |
| `META_WHATSAPP_GRAPH_VERSION`, `META_WHATSAPP_PHONE_NUMBER_ID`, `META_WHATSAPP_ACCESS_TOKEN`, `META_WHATSAPP_OTP_TEMPLATE`, `META_WHATSAPP_OTP_LANGUAGE`, `META_WHATSAPP_OTP_COPY_CODE_BUTTON` | Meta WhatsApp OTP | O | R when selected | R when selected | Yes | v23.0/en_US | WhatsApp OTP sender |
| `RAZORPAY_KEY_ID`, `RAZORPAY_KEY_SECRET`, `RAZORPAY_WEBHOOK_SECRET`, `RAZORPAY_GST_PERCENT` | Subscription/Wallet payments | O | R to test billing | R | Yes | GST 18 | billing/wallet/webhooks |
| `WALLET_GST_BASIS_POINTS` | Wallet GST | O | R | R | No | 1800 | wallet config |
| `STRIPE_KEY`, `STRIPE_SECRET` | Configured but no active LensPic checkout | O | O | O | Yes | blank | services only; candidate removal |
| `FACE_PROVIDER`, `FACE_MATCH_THRESHOLD`, `FACE_MAX_RESULTS`, `FACE_COLLECTION_PREFIX` | Face provider/isolation | R | R | R | No | fake/95/100/lenspic | face manager |
| `FACE_RECOGNITION_API_URL`, `FACE_RECOGNITION_API_TOKEN`, `FACE_RECOGNITION_PORT`, `FACE_RECOGNITION_HEALTH_PATH`, `FACE_RECOGNITION_MATCH_PATH`, `FACE_RECOGNITION_TIMEOUT`, `FACE_RECOGNITION_PHOTO_LIMIT`, `FACE_RECOGNITION_MIN_SCORE`, `FACE_RECOGNITION_RESULT_LIMIT` | Python legacy match adapter | O | R when python | R when python | token | localhost/health/recognize/90 | Laravel client and local command |
| `FACE_SELFIE_RETENTION_HOURS`, `FACE_RESULT_RETENTION_DAYS`, `FACE_MANUAL_REVIEW_ENABLED`, `FACE_MINOR_PROCESSING_ENABLED`, `FACE_CONSENT_VERSION` | Biometric policy | R | R | R | No | 24/null/true/false/version | consent/retention |
| `NOTIFICATION_RETENTION_DAYS` | Read-notification retention | O | R | R | No | 90 | notification pruning |
| `GROUP_JOINING_TUTORIAL_URL`, `GROUP_JOINING_TUTORIAL_THUMBNAIL`, `GROUP_JOINING_TUTORIAL_DURATION`, `GROUP_JOINING_TUTORIAL_TITLE` | Optional tutorial content | O | O | O | No | duration/title defaults | services/UI |

There are no `VITE_` or `MIX_` environment reads. No tracked `.env`, PEM, private key or credential file was found. The heuristic tracked-file scan found no recognizable live AWS/Razorpay/private-key signature; rotate credentials if a separate history/secret-scanner reports exposure.

## Software and services

Current development evidence: PHP 8.3.22, Composer 2.10.2, Node 20.17.0, npm 10.8.2. Composer requires PHP >=8.2 and Laravel 11. Required loaded PHP capabilities: GD (JPEG/PNG/WebP), EXIF, fileinfo, PDO MySQL, cURL, mbstring, OpenSSL, ZIP; Redis is loaded locally although Predis is also installed. Python image uses 3.12 and unpinned patch-level dependency ranges from `face-api/requirements.txt`. Production must pin a tested image digest/lock before deployment.

MySQL 8.0+, Redis 7+, Nginx, PHP-FPM 8.3, Horizon under systemd/Supervisor, cron and private S3/CloudFront are the recommended staging/production baseline. Exact hosted versions remain an infrastructure verification item.

## Deployment configuration

### Nginx placeholder

```nginx
server {
    listen 443 ssl http2;
    server_name lenspic.example.com;
    root /srv/lenspic/current/public;
    index index.php;
    client_max_body_size 50m; # one 30 MiB photo per request, plus multipart overhead
    client_body_timeout 300s;
    location / { try_files $uri $uri/ /index.php?$query_string; }
    location ~ \.php$ { include fastcgi_params; fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name; fastcgi_pass unix:/run/php/php8.3-fpm.sock; fastcgi_read_timeout 60s; }
    location ~ /\.(?!well-known) { deny all; }
    location ~* \.(?:js|css|png|jpg|jpeg|webp|woff2)$ { expires 1y; add_header Cache-Control "public, immutable"; try_files $uri =404; }
}
```

Set PHP `upload_max_filesize=40M`, `post_max_size=50M`, `max_file_uploads=50`, `memory_limit=512M` or higher based on measured 80 MP GD peaks, `max_execution_time=300`, `max_input_time=300`, `display_errors=Off`, and enable OPcache. The web uploader limits the UI queue to 50, sends one photo per request with concurrency 3, and keeps image processing queued. Confirm CDN/proxy request limits match.

### Horizon/Supervisor

Actual queues are `media-high`, `media-default`, `exports`, and `maintenance`. `config/horizon.php` already gives media-high a 600-second timeout and covers all four. Use `QUEUE_CONNECTION=redis`, ensure Redis `retry_after=900` remains greater than the longest 600-second job, isolate prefixes, run one supervised `php artisan horizon`, and deploy with `php artisan horizon:terminate` (not a redundant `queue:restart`). Horizon access is gated to authenticated admin users outside local.

### Scheduler

```cron
* * * * * cd /srv/lenspic/current && php artisan schedule:run >> /dev/null 2>&1
```

The project schedules export and biometric pruning hourly; storage reconciliation; retained-media purge; notification pruning; failed-job pruning; and Horizon snapshots. Multi-server production should use a shared Redis lock and add `onOneServer()` after confirming one scheduler leader. Subscription expiry, invitation expiry, Wallet reservation expiry and backup monitoring have no scheduled implementation and must be implemented or explicitly operationalized before relying on them.

## Provider checklists

- **AWS console — before staging/production:** private bucket, Block Public Access, OAC-only CloudFront access, TLS-only bucket policy, SSE, least-privilege IAM/workload role, separate environments, abandoned multipart cleanup, export/temp lifecycle, documented versioning/retention. Never run `storage:link` for private media.
- **CloudFront — before production:** trusted key group, protected private-key file/secret manager, 300-second view and 900-second download TTLs, no direct S3 access, non-cacheable authorization route responses.
- **Email provider/DNS — before staging:** verified sender, SPF, DKIM and DMARC, bounce/complaint handling, staging recipient restriction, queued invitation smoke test. Password-reset and ownership-transfer mail flows are not implemented and must not be advertised.
- **Meta dashboard — before production:** approved OTP template, test recipient restriction in staging, token rotation, normalized E.164 numbers. Current integration is outbound OTP only; no WhatsApp webhook/delivery charging implementation exists.
- **Payment dashboard — before staging:** test keys, both webhook URLs, secrets, captured/failed/duplicate tests. Before production repeat with live keys after backup and monitoring. Hosted Razorpay checkout limits PCI scope; browser success is not authoritative.
- **Python server — blocker:** private TLS endpoint, bearer token, resource limits, restart policy and redacted logs. The included service only implements `/recognize`; `PythonFaceProvider` expects `/collections`, indexing, search and deletion endpoints. Complete and test that contract or disable the feature. Legal/privacy approval is human-owned.

## Security, privacy, backup and monitoring

- CSRF remains enabled. Only two signature-verifying Razorpay webhook endpoints are excluded.
- Private originals use an authorized controller/private disk; SVG is not accepted; MIME, extension and decoded-image checks exist.
- Baseline host, nosniff, frame, referrer, permissions and production HSTS headers are installed. A tested CSP remains recommended because Vue/Inertia and payment scripts require an explicit allowlist.
- Portfolio bank/UPI values require a separate encryption-at-rest review before enabling public payment details.
- Biometric launch requires qualified review of consent text/version, minors policy, purpose, regional availability, retention, withdrawal/deletion, incident handling and data-subject requests. Tests do not establish legal compliance.
- **Before production:** encrypted database backups, S3 recovery/versioning policy, off-site retention, documented RPO/RTO and a witnessed restore test. Exclude temporary selfies from long-term backup unless policy requires otherwise.
- **Before production:** application/error latency, Horizon backlog/failed jobs, scheduler heartbeat, webhook failures, storage growth, database/Redis/face health, TLS expiry and backup alerts. No monitoring provider is currently configured.

## Safe deployment and rollback

Staging, then production:

```bash
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
npm ci
npm run build
php artisan lenspic:check-configuration --environment=staging
php artisan optimize:clear
php artisan migrate:status
# backup and verify release, then:
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan horizon:terminate
php artisan schedule:list
```

Use atomic releases. Roll back application symlink/assets and terminate Horizon so workers load matching code. Do not run `migrate:rollback` during an emergency unless the specific migration is proven reversible and no new data depends on it. Never run `migrate:fresh`, `db:wipe`, `migrate:refresh`, or demo seeders remotely. `DatabaseSeeder` currently creates a super-admin via `SuperAdminSeeder`; do not run it automatically in production.

## Smoke and go-live gates

On staging, execute the complete authentication, settings, group upload/process/grid/view/download/delete/restore, cover, watermark, quota, team, portfolio, notification, Wallet, duplicate webhook, export and cross-studio scenarios from the master request. Biometrics require consent/no-match/failure/deletion tests against the completed private Python service. Record queue latency, GD memory, 100-image portfolio behavior and concurrent quota uploads.

| Area | Status | Evidence | Required action / owner |
|---|---|---|---|
| Application/tests/build | Pass | Automated gates run in audit | Re-run in CI / CI server |
| Dependency security | Blocker | Composer reports one high and related medium Laravel advisories; npm reports 0 | Migrate to an officially patched supported Laravel line and rerun all gates / engineering |
| Database/migrations | Pass locally | Migration status checked | Backup then migrate staging / DBA |
| Sessions | Warning | secure config now available | Redis + HTTPS cookie smoke / staging server |
| Redis/queues/Horizon | Blocker | code configured, remote runtime unverified | Provision and monitor / staging+production |
| Scheduler | Blocker | schedule exists, cron unverified | Install heartbeat-monitored cron / servers |
| Upload/private media | Pass in tests | private authorization and media suites | S3/OAC staging smoke / AWS |
| CloudFront | Blocker | signer exists, no deployed evidence | Configure and test expiry / AWS |
| Image processing | Pass in tests | versioned profiles and variants | Load/memory test / staging |
| Biometrics | Blocker | consent lifecycle exists; Python contract incomplete | Complete service + legal review / Python+legal |
| Email | Blocker | invitation mail exists, provider unverified | DNS/provider/staging delivery / email provider |
| WhatsApp | Warning | outbound OTP only | Verify template or disable / Meta dashboard |
| Payments/Wallet | Blocker | signatures/idempotency tested locally | Live/test webhook evidence / Razorpay dashboard |
| Subscriptions/transactions | Warning | current flows/tests exist | expiry/refund/invoice policy / product+finance |
| Notifications | Pass in tests | persistent scoped module | staging trigger smoke / staging |
| Security | Warning | baseline headers/auth tests | CSP, penetration and secret-history scan / security |
| Backups/restore | Blocker | no evidence in repository | Configure and restore-test / infrastructure |
| Monitoring | Blocker | no provider configured | alerts/heartbeat/runbooks / operations |
| DNS/TLS | Blocker | domains/cert unknown | provision and monitor / DNS provider |
| CI/CD | Blocker | no CI workflow found | atomic staging pipeline / DevOps |
| Staging tests | Blocker | not executed externally | complete recorded smoke / QA |

## Ownership and timing

- **Local Mac, before staging:** resolve dependency audits, finish Python contract or disable face features, commit templates/validator, run all tests/build.
- **Staging server, before staging:** secret file, MySQL/Redis/S3/CloudFront test environment, Horizon, cron, mail restriction, Razorpay test keys, validator with no failures.
- **AWS/DNS/provider dashboards, before production:** private storage/CDN, TLS/DNS, verified email and webhooks, separate production namespaces.
- **Production server, before go-live:** verified restore, migrations, caches, Horizon, cron heartbeat, validator, smoke tests and monitoring.
- **After launch:** watch 5xx, queue latency, failed jobs, webhook failures, storage growth, biometric cleanup and backup success continuously for the first 24–72 hours.
