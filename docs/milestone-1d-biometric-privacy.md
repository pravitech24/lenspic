# Milestone 1D biometric privacy

LensPic separates provider identifiers from UI-facing identifiers. Controllers expose consent/search UUIDs and photo IDs only. `FaceProvider` supports fake, local Python, and Amazon Rekognition implementations. Production should set `FACE_PROVIDER=rekognition`; local development and tests default to `fake` and require no AWS credentials.

An authenticated active event member must explicitly accept the current privacy notice before a selfie can be submitted. Selfies are stored under the private temporary-selfie prefix, expire after `FACE_SELFIE_RETENTION_HOURS`, and are removed on consent withdrawal or scheduled retention pruning. Consent records retain hashed request metadata for auditability; raw IP addresses and user agents are not stored.

Event photos are indexed only when facial recognition is enabled. Index/search jobs are idempotent and concurrency locked. Match threshold defaults to 95%. Match results contain LensPic media references and similarity only; provider face and collection identifiers remain in provider-specific persistence tables.

Disabling or deleting data must dispatch provider cleanup. Deleting media removes indexed faces; deleting an event removes its provider collection. Provider errors exposed to users are sanitized. Full Rekognition credentials continue to use environment-backed AWS configuration.

Operational checks:

```bash
php artisan queue:work --queue=media-high,media-default,maintenance
php artisan schedule:run
php artisan queue:failed
```

The hourly `PruneBiometricData` job deletes expired private selfies and retained results. A null `FACE_RESULT_RETENTION_DAYS` retains results until consent withdrawal or event/user deletion.
