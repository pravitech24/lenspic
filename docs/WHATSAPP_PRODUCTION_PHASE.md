# WhatsApp authentication and manual contact phase

## Scope and behavior

Automatic Meta messages are **authentication OTP templates only**. Portfolio contact, official support and Group sharing are ordinary `wa.me` links opened by the user. They never send through Meta, consume Wallet credits, or create marketing consent. Photo-ready, payment, subscription, welcome, promotional and marketing automation remain deferred pending separately approved templates and explicit consent.

The existing synchronous registration flow is retained: Meta has a 3-second connection timeout and 10-second total request timeout, with **no HTTP retries**. A successful browser response follows provider acceptance and storage of the message ID. This avoids a queue dependency or falsely reporting that an unprocessed queued OTP was sent. Local/testing OTP test mode retains the existing log sender; tests explicitly bind the Meta sender with fake HTTP responses.

## Issues corrected

- Removed retrying of non-idempotent Meta POSTs and raw provider exception reporting.
- Serialized challenge creation by a database row keyed with an HMAC of the normalized destination, including the first-request race. New challenges expire older active challenges; failure preserves the cooldown.
- Added opaque challenge identity, browser request identity, authentication channel and disclosure version. A duplicate browser request uses its original challenge and never substitutes a newly generated code into an existing challenge.
- Added durable outbound audit records with unique challenge-derived idempotency keys and Meta IDs. Atomic attempt claiming happens before network I/O. The outbound table stores only masked/HMAC recipients and operational fields, never OTPs, tokens, full phone numbers or raw payloads.
- Added libphonenumber validation for OTP and portfolio WhatsApp numbers, including India and international E.164 numbers.
- Added IP, destination and challenge rate limits. Verification atomically claims attempts and consumes a challenge once. Requests do not disclose whether a number already belongs to an account.
- Added the authentication-only disclosure before requesting a code and on the verification screen.
- Rendered the existing portfolio contact configuration, connected the previously unwired Group WhatsApp function, added the private-link warning, and added support links to active Pricing and Contact pages as well as correcting legacy pricing claims.
- Removed the ineffective WhatsApp notification toggle; server writes always force it false. Wallet UI says Coming soon and Wallet reservations/captures explicitly reject WhatsApp purposes.

## Migration and lifecycle

Run `php artisan migrate --force` as part of the normal deployment after installing dependencies. The new migration is `database/migrations/2026_09_07_100000_create_whatsapp_delivery_tables.php` and adds:

- `otp_destination_locks`: durable destination serialization keys, no raw recipients.
- `otp_requests`: `public_reference`, `request_key`, `authentication_channel`, `disclosure_version`.
- `whatsapp_messages`: opaque reference, nullable user association, purpose/provider, masked/HMAC recipient, template/language, unique idempotency key, unique nullable Meta ID, status, attempt count, attempt/acceptance/delivery timestamps, sanitized error fields and non-sensitive metadata.
- `whatsapp_webhook_events`: deterministic event hash, Meta ID, event type/time, numeric error code, received/processed timestamps. No raw webhook body or recipient is retained.

Lifecycle: `pending → accepted → sent → delivered → read`; rejection or a signed delivery failure records `failed`. Delivered/read evidence takes precedence over failed/sent events, so duplicates and out-of-order delivery cannot downgrade successful delivery. Status timestamps reflect provider event time; accepted time is the application acceptance time.

A connection timeout, malformed success response, or process interruption after claiming an attempt leaves an uncertain pending message. **Do not replay it.** Meta does not provide an application idempotency contract here, so exactly-once external delivery cannot be promised across a network failure. This implementation deliberately permits at most one application POST per challenge. A user may explicitly request a fresh challenge after the cooldown; the earlier challenge is invalidated, and a late message may contain an unusable code. There is no recovery job that replays ambiguous sends.

Register sends an opaque `request_id`; retrying that ID resolves to the same destination-bound challenge even after the cooldown. Resend renders a fresh request ID. Older callers without an ID retain the destination cooldown; integrations should send a UUID and reuse it for retries. A failure on registration replaces the form's request ID so a later deliberate retry can create a usable challenge.

## Meta dashboard setup

The exact callback path for GET verification and POST status notifications is:

`https://<your APP_URL host>/webhooks/whatsapp`

For an application deployed at `https://lenspic.in`, enter **`https://lenspic.in/webhooks/whatsapp`**. Use the actual configured public HTTPS host if different; no production hostname or credentials were changed in this task.

1. Configure the existing Meta business phone number ID, access token, Graph version and approved authentication template/language. Set `OTP_DRIVER=whatsapp` and disable OTP test mode for production.
2. Set `META_APP_SECRET` to the Meta **application secret**, not the access token. Generate a separate unpredictable `META_WHATSAPP_WEBHOOK_VERIFY_TOKEN` and configure it on the server.
3. In the Meta app's WhatsApp configuration, enter the callback URL above and the matching verification token. The GET handler returns the challenge only for a matching subscription token.
4. Subscribe the WhatsApp Business Account's **messages** webhook field. Delivery statuses are processed only for the configured phone number ID.
5. POST requests require `X-Hub-Signature-256`, calculated as HMAC-SHA256 over the exact raw request bytes using the application secret. Verification precedes application JSON parsing; the webhook is CSRF-exempt, not signature-exempt. See [Meta's webhook verification documentation](https://whatsapp.github.io/WhatsApp-Nodejs-SDK/api-reference/webhooks/start/) and [Meta's payload reference](https://www.postman.com/meta/whatsapp-business-platform/folder/tduohwq/webhook-payload-reference).
6. Deploy migrations, refresh the configuration cache and run `php artisan lenspic:check-configuration`. The command now checks all Meta fields and prints only safe missing-setting descriptions.
7. Perform a controlled delivery to an authorized test recipient and confirm accepted, delivered/read events in `/super-admin/whatsapp-deliveries` before enabling real traffic.

Webhook processing is synchronous, DB-only, without external I/O or a queue dependency, and acknowledges with 204 after durable processing. Payload size is limited to 1 MiB. GET verification is IP-throttled; POST has no generic IP throttle that would block shared Meta infrastructure. Apply suitable infrastructure request-size/abuse limits while preserving legitimate signed delivery traffic. Unknown message IDs are retained without recipient data; the sender reconciles them when acceptance is saved, handling events that arrive before the Meta response is written. Duplicate events are harmless. DB failures return an error so Meta can retry.

Use a shared production cache such as Redis for rate limits and the production SQL database for row locks. SQLite tests verify behavior but do not reproduce multi-process production row-lock contention. Retain outbound and event records according to an approved operational retention policy; do not delete idempotency records during an active challenge/retry window. Do not enable HTTP request-body debugging or log raw Meta traffic in infrastructure.

## Environment variables

All three example environment files retain the existing OTP/Meta variable names and contain blank entries for new configuration, without real credentials.

Existing: `OTP_DRIVER`, `OTP_DEFAULT_COUNTRY_CODE`, `OTP_EXPIRY_MINUTES`, `OTP_RESEND_SECONDS`, `OTP_MAX_ATTEMPTS`, `OTP_TEST_MODE`, `OTP_TEST_CODE`, `META_WHATSAPP_GRAPH_VERSION`, `META_WHATSAPP_PHONE_NUMBER_ID`, `META_WHATSAPP_ACCESS_TOKEN`, `META_WHATSAPP_OTP_TEMPLATE`, `META_WHATSAPP_OTP_LANGUAGE`, `META_WHATSAPP_OTP_COPY_CODE_BUTTON`.

New: `OTP_DEFAULT_REGION`, `OTP_SUPPORTED_REGIONS`, `META_WHATSAPP_WEBHOOK_VERIFY_TOKEN`, `META_APP_SECRET`, `WHATSAPP_PORTFOLIO_ENABLED`, `WHATSAPP_SUPPORT_ENABLED`, `WHATSAPP_SUPPORT_NUMBER`, `WHATSAPP_SUPPORT_COUNTRY_CODE`, `WHATSAPP_SUPPORT_MESSAGE`, `WHATSAPP_SUPPORT_AVAILABILITY`.

Set the default region to `IN`; an empty supported-regions list permits all valid regions, or use comma-separated ISO region codes such as `IN,GB,US`. Country-code selection still defaults to `+91`. Explicitly set the portfolio flag true to enable contact links. Support is disabled by default; enable it only with a valid official support number and truthful availability text. Generic support text must not contain account/payment information. Empty/invalid support configuration renders Contact LensPic Support instead of a broken WhatsApp link.

## UI and authorization

- Registration / mobile OTP: authentication-only disclosure, server-side safe failures, resend cooldown, opaque request identity.
- Public Portfolio: visible, valid and feature-enabled WhatsApp number renders Chat on WhatsApp, with encoded generic text and `noopener noreferrer`. Disabled contacts do not expose the phone in the page. Private galleries receive no portfolio WhatsApp contact field.
- Active Pricing and Contact: central official support component, or existing contact form fallback. Legacy pricing no longer claims 24x7 support.
- Group gallery / Share Group Invite: explicit Share on WhatsApp, existing clipboard/QR and device sharing, access-credential warning and existing expiry display. The server's existing `manageInvitations` authorization generates the opaque invite URL; members/outsiders cannot obtain privileged share links. No signed media or biometric data is included.
- Photo viewer: existing manual share preserved with private-link warning.
- Settings / Account preferences: unsupported WhatsApp notifications cannot be enabled.
- Settings / Wallet: WhatsApp automation Coming soon; no misleading active rate or credit deduction.
- Super Admin / WhatsApp deliveries: read-only paginated masked records and timestamps under existing `platform.admin:super` authorization. Photographer and Group Member roles are denied.

## Files changed for this phase

Created: the migration above; `app/Models/WhatsAppMessage.php`; `app/Services/WhatsAppLinks.php`; `app/Services/WhatsAppStatusProcessor.php`; `app/Http/Controllers/WhatsAppWebhookController.php`; `config/whatsapp.php`; `resources/views/components/whatsapp-support.blade.php`; `resources/views/super-admin/whatsapp-deliveries.blade.php`; `tests/Feature/WhatsAppProductionTest.php`; this report.

Updated: `composer.json`, `composer.lock`; `.env.example`, `.env.staging.example`, `.env.production.example`; `config/otp.php`; `app/Models/OtpRequest.php`; `app/Services/WhatsAppOtpSender.php`; `app/Http/Controllers/AuthController.php`; `app/Http/Controllers/SettingsController.php`; `app/Http/Requests/UpdateBusinessBrandingRequest.php`; `app/Services/Branding/BusinessBrandingPresenter.php`; `app/Services/Wallet/WalletService.php`; `app/Console/Commands/CheckConfiguration.php`; `bootstrap/app.php`; `routes/web.php`; `resources/js/Pages/Auth/Register.vue`; `resources/js/Pages/Groups/Show.vue`; `resources/js/Pages/Photos/Show.vue`; `resources/js/Pages/Settings/Index.vue`; `resources/js/Pages/Settings/Wallet.vue`; `resources/views/auth/mobile.blade.php`; `resources/views/auth/otp.blade.php`; `resources/views/portfolio/show.blade.php`; `resources/views/pricing.blade.php`; `resources/views/marketing/pricing.blade.php`; `resources/views/marketing/contact.blade.php`; `resources/views/super-admin/layout.blade.php`; `tests/Feature/WhatsAppOtpSenderTest.php`.

The workspace already contained extensive unrelated edits. They were preserved; this report lists only phase-related work.

## Verification

Results are recorded below after final checks. No live Meta delivery or production migration was performed. Automated tests use HTTP fakes and an isolated SQLite database.

Browser tooling installation was declined. Consequently interactive manual desktop/mobile verification and fresh screenshots are **not completed**. Existing unrelated screenshots are not presented as evidence for this phase. Before launch, verify the mobile disclosure, fake/sandbox failure and resend states, public contact visibility, support destination, Group share dialog and access warning, and masked Super Admin history at desktop and mobile widths. Use a controlled Meta recipient to verify actual delivery separately.

Final automated results:

- `vendor/bin/phpunit`: **318 tests, 3,605 assertions passed** (final run, 1m 42s).
- `vendor/bin/phpunit --filter WhatsApp`: **22 tests, 171 assertions passed**; includes in-flight duplicate suppression, delayed duplicate request identity, resend/failure cooldown, provider error cases, ambiguous timeout/missing ID, disclosure, number normalization, webhook authentication/order/race, contact/support visibility, Group permissions, Wallet prevention and admin restrictions.
- Expanded focused run covering WhatsApp, Photo Viewer sharing, Wallet and Business Branding: **52 tests, 493 assertions passed** before the final additional regression.
- `npm run build`: **passed**, 650 modules transformed.
- PHP syntax lint: **354 files, zero errors**; subsequent changed PHP files were checked again successfully.
- `composer validate --no-check-publish`: **passed**. No project PHPStan/Psalm/ESLint command is configured; PHP syntax lint was used in addition to tests and the Vue production compiler.
- Webhook and delivery administration routes confirmed with `php artisan route:list`; targeted diff whitespace check passed.
- `php artisan test` is unavailable in this project's dependency setup; PHPUnit was invoked directly instead.
- Composer added `giggsey/libphonenumber-for-php` 9.0.38 and `giggsey/locale` 2.9.0. Installation also reported four dependency advisories affecting two packages; a separate dependency security audit/remediation was not performed in this phase.

Deployment and manual verification remain distinct from these passing automated checks. Apply the migration and configure Meta/support settings before enabling this feature on a running deployment. Fresh screenshots, interactive desktop/mobile verification and actual Meta delivery remain unverified as explained above.
