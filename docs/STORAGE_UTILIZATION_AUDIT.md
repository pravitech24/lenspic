# Storage Utilization Audit

## Authoritative source

The Standard-plan quotas are defined once in `config/plans.php` and resolved by `App\Services\Storage\PlanEntitlements`:

- 100,000 logical photos
- 5,000 MB video storage
- 200,000 photo upload/delete actions per billing period
- 10,000 MB video upload/delete actions per billing period
- 24-hour deleted-media quota retention

`StorageUsage` is the authorized server-side resource used by Photographer Settings. Both ingestion pipelines call the same service under a database owner-row lock before creating a logical media record. Subscription features receive the same resolved entitlement array.

## Counting and deletion

- One valid gallery `MediaAsset` with a `photo_id` counts once, regardless of variants.
- Covers, biometric selfies, exports, failed placeholders and generated variants do not increase the logical photo count.
- Physical bytes come from `storage_ledger_entries`; new private ingestion records masters and generated variants.
- Deletion soft-deletes the Photo and MediaAsset immediately, records one idempotent usage event, and schedules purge after 24 hours.
- A retained deleted asset remains counted. Restore within 24 hours restores both records and does not add an upload event.
- Purge verifies eligibility, removes every private variant, writes one idempotent negative physical-ledger entry, then removes the database media records.
- Group deletion applies the same per-photo lifecycle and idempotency keys; the Group itself is soft-deleted so tenant group counts update without cascading media prematurely.

## Interface

Photographer Profile contains non-navigating Storage Limit and Delete & Re-upload tabs. Additional Info is an anchored ARIA menu. Group Upload Stats and Summary open intentional real-data modals with Escape, overlay and X close behavior, focus containment/restoration, and background scroll locking.

## Operations

Run `php artisan lenspic:reconcile-storage-usage --dry-run` to report account counter/ledger drift without writes. Run it without `--dry-run` to correct only the safe cached byte counter. It never deletes media. The retention sweep and counter reconciliation are scheduled independently.

Binary units are used consistently: 1 MB = 1,048,576 bytes and 1 GB = 1,073,741,824 bytes.
