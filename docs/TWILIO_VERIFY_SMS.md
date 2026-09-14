# Twilio Verify SMS integration

## What is connected

LensPic now offers SMS, WhatsApp and Email on its existing registration/OTP screen. SMS uses the official **`twilio/sdk` 8.12.0** package and Twilio **Verify**, not Programmable SMS. Twilio generates the SMS token and decides whether the submitted token is approved. LensPic never generates or persists an SMS OTP/hash.

The existing routes remain:

| Route | SMS behavior |
| --- | --- |
| `GET /register` | Method selector, configured default, accessible loading/errors |
| `POST /register` | Same existing send handler, including SMS |
| `POST /send-otp` | Starts or resends Verify SMS after validation/limits |
| `GET /verify-otp` | Session-bound challenge, masked number and remaining cooldown |
| `POST /verify-otp` | Calls Verify Check and authenticates only on `approved` |

Registration, existing-account phone OTP authentication, onboarding and pending Group invitation/member redirects use this shared flow. Email/password login, team login and email password-reset links retain their existing behavior. There was no phone password-recovery flow to extend; none was introduced.

The existing `OtpSender`, Meta `WhatsAppOtpSender` and `EmailOtpSender` are preserved. `OtpProviderResolver` validates/resolves `sms`, `whatsapp`, `email` and the legacy `mobile` alias. The new `OtpProvider` contract models externally generated/checked codes and is implemented by `TwilioVerifyOtpProvider`. Existing local-code providers retain their existing interface, avoiding an unnecessary rewrite. `OTP_DRIVER=sms` on a legacy mobile request also routes to Verify rather than attempting to send a locally generated code.

No automatic cross-channel fallback exists. SMS failure tells the user to select another verification method. WhatsApp still uses Meta authentication templates and its existing outbound audit/webhooks; Email still uses the existing Laravel mailer and local hashed challenge. SMS does not create WhatsApp messages or consume Wallet credits.

## Configuration and deployment

Added to `.env.example`, `.env.staging.example` and `.env.production.example`:

```dotenv
OTP_DEFAULT_CHANNEL=sms
TWILIO_ACCOUNT_SID=
TWILIO_AUTH_TOKEN=
TWILIO_VERIFY_SERVICE_SID=
TWILIO_OTP_CHANNEL=sms
TWILIO_OTP_LOCALE=en
TWILIO_OTP_EXPIRY_MINUTES=10
TWILIO_OTP_RESEND_SECONDS=60
TWILIO_OTP_MAX_ATTEMPTS=5
```

Credentials are read through `config('services.twilio...')`. No real credentials were added to the repository or JavaScript. The actual local environment was not populated with credentials. Without `OTP_DEFAULT_CHANNEL`, the previous mobile-driver behavior is preserved; the examples explicitly select SMS for new configuration.

Before using SMS on a deployment:

1. Install locked Composer dependencies and build frontend assets.
2. Apply `2026_09_07_110000_add_twilio_verify_challenges.php` using the normal deployment migration process. It makes `otp_requests.otp_hash` nullable and adds `provider`, `delivery_status`, `failure_category`, `destination_masked`, `accepted_at` and `session_hash`. Rollback removes these added fields but deliberately keeps hashes nullable for historical SMS records.
3. Configure a Twilio Verify Service belonging to the configured account and **six-digit** SMS codes. Set the SMS locale and enable the intended destination countries in **Verify Geo Permissions**.
4. Add the real account SID, Auth Token and Verify Service SID privately to the environment/secret manager. Set `OTP_DEFAULT_CHANNEL=sms` when SMS should be the selected default. Keep `TWILIO_OTP_CHANNEL=sms`; other provider channels are rejected.
5. Run `php artisan optimize:clear`, `php artisan config:cache`, and `php artisan lenspic:check-configuration`. The configuration checker includes safe Twilio missing/invalid-field checks when SMS is configured/defaulted.
6. Use HTTPS, secure session cookies, a shared production cache such as Redis for rate limits, and the production SQL database for row locking. At TLS termination, configure only the actual trusted proxies so Laravel recognizes HTTPS. Staging/production HTTP requests are rejected; outbound Twilio cURL transport permits HTTPS only, verifies certificates, and does not follow redirects.

The local expiry setting is an application deadline, **not** an instruction to change Twilio's token validity. It is clamped to 1–10 minutes; resend is at least 60 seconds, and local checks are capped at five (or a lower configured maximum). Twilio's default validity is ten minutes, and repeated sends can retain the same token until approval. See [Twilio Verify rate limits and token validity](https://www.twilio.com/docs/verify/api/rate-limits-and-timeouts). Configure the Verify Service to agree with the six-digit UI; a longer application deadline cannot extend Twilio validity.

## Security and failure behavior

- Shared libphonenumber normalization accepts Indian national numbers with the default IN region, explicit international `+` numbers and selected country codes. Explicit international numbers are never prefixed with +91. Letters, extra plus signs, extensions, invalid/incomplete numbers and unsupported regions are rejected. Existing WhatsApp normalization delegates to the same helper.
- The normalized destination remains in the existing server-side challenge/identity fields, with `mobile_e164` hidden from model serialization. SMS UI and audit logs use only `******` plus the last four digits. Resend recovers its destination/channel from the session, not a client-supplied phone or hidden raw number.
- A random server-side browser token is hashed and attached to the SMS challenge. A different browser session cannot view or verify it by supplying a challenge reference. Successful authentication consumes the challenge and clears OTP session state.
- Destination database locking serializes challenge creation, including the first insert. A durable pending record is committed before network I/O. Browser request UUIDs are bound to session and destination. An in-flight duplicate cannot send another SMS; an accepted duplicate returns success without contacting Twilio.
- New sends expire older local SMS challenges. The minimum 60-second cooldown is server-enforced and survives provider rejection/timeouts. A provider/network failure is not automatically retried, because its acceptance may be uncertain. A fresh explicit resend is allowed only after cooldown and limits.
- A destination allows five SMS requests per hour; session allows ten request attempts/hour; IP allows twenty/hour in addition to the existing send-route throttle of ten/minute. Duplicate/cooldown submissions consume the IP/session abuse budget but do not call Twilio. Verify routes retain their IP throttle of fifteen/minute.
- Verification atomically claims one in-progress check and increments its attempt count before contacting Twilio. Parallel checks cannot both claim a challenge; only `approved` can atomically consume it. Resending/expiring a challenge during a check prevents that older challenge from authenticating. A process crash during a check leaves it unavailable for further checks until a deliberate resend; it is never assumed approved.
- The official SDK sends `To` and `Channel=sms` to `/Verifications`, then `To` and `Code` to `/VerificationCheck`. No raw response, SID or Twilio exception message reaches the browser. Twilio notes that expired, approved or exhausted verifications can return 404; these do not authenticate. See [Verify Check documentation](https://www.twilio.com/docs/verify/api/verification-check).
- Strict cURL connect/request timeouts are 3/10 seconds. The transport clears the SDK's retained raw request/response fields after each call. It is explicitly disabled in automated tests unless replaced by the fake HTTP transport.
- Logs contain only an opaque correlation ID, operation, provider, masked destination, safe status and allowlisted failure category. SDK exceptions are consumed without logging or chaining their messages. OTP/code fields are excluded from Laravel flashed input and are not repopulated into HTML.
- Browser routes retain CSRF protection; only allowlisted methods can be selected. No account-existence response is returned. SMS code validation remains six numeric digits, and there are no promotions or marketing subscriptions.

Failure categories distinguish configuration, authentication, trial-recipient restrictions, geographic/policy blocks, rate limits, account/billing problems, uncertain connection failures, invalid/expired codes and provider unavailability. Unknown Twilio errors fail safely as provider rejection/unavailability. These categories are operational hints, not an attempt to infer detailed account status from arbitrary error text. See [Twilio Verify error codes](https://www.twilio.com/docs/verify/api/error-codes).

## Manual trial procedure and production requirements

No live SMS was sent during implementation: all three Twilio credentials were absent, and no authorized Twilio-verified recipient was supplied. A controlled trial test remains pending; automated approval is not evidence of carrier delivery.

1. Configure the private credentials and a Verify Service as above. On a trial account, add and verify the recipient that you own or are authorized to use. Twilio requires verified recipients for trial OTP delivery: [Verification resource/trial restriction](https://www.twilio.com/docs/verify/api/verification).
2. Apply migrations and refresh configuration. Open `/register` over HTTPS on desktop and mobile, choose SMS, and enter the authorized number. Request **one** SMS. Confirm masked confirmation and the visible countdown. In Twilio's controlled account logs confirm the destination is the intended E.164 number; do not copy raw requests into application logs or this report.
3. Attempt an immediate resend: confirm it is blocked without another provider call. After at least 60 seconds, use Resend and confirm it targets the same session-bound number. Twilio may reuse the same still-valid token.
4. Enter an incorrect six-digit code and confirm the safe error. Use the received code on a fresh challenge and confirm approval/onboarding or the pending Group join redirect. Log out and confirm the consumed challenge cannot be reused. Separately exercise expiry and five invalid attempts on controlled challenges.
5. Remove/misconfigure credentials only in a safe test deployment to confirm the alternate-method message. Restore settings and cache. Verify WhatsApp with an authorized Meta test recipient and Email with the existing mail transport independently.
6. Before enabling broad traffic, upgrade the Twilio trial account as needed, enable active billing/funding, confirm Verify service access, intended country permissions, fraud/rate controls, and sender/delivery requirements with Twilio. Configure monitoring/retention for safe audit fields. A successful trial message alone is not production readiness.

**India:** Twilio distinguishes domestic and international delivery. Its India guidance requires company and Sender ID DLT registration for domestic traffic; domestic use must match the approved use case. For LensPic's actual business/route, confirm entity, sender/header, content-template registration and Verify template mapping with Twilio before launch. Twilio lists a separate international ILDO route without DLT registration, but do not assume an Indian business qualifies merely because the API is hosted abroad. Confirm the applicable route and compliance obligations with Twilio/compliance advisers. [Twilio India SMS guidelines](https://www.twilio.com/en-us/guidelines/in/sms), [DLT registration instructions](https://help.twilio.com/articles/21162166457755-Documents-Required-and-Instructions-to-Register-Your-Alphanumeric-Sender-ID-in-India).

## Files for this phase

Created:

- `app/Contracts/OtpProvider.php`
- `app/Services/Otp/OtpSendResult.php`, `OtpVerificationResult.php`, `OtpProviderResolver.php`
- `app/Services/Otp/TwilioVerifyOtpProvider.php`, `PrivateTwilioTransport.php`, `SmsOtpChallenges.php`, `PhoneNumberNormalizer.php`
- `database/migrations/2026_09_07_110000_add_twilio_verify_challenges.php`
- `tests/Fakes/TwilioHttpClient.php`, `tests/Feature/TwilioVerifyOtpTest.php`
- `docs/TWILIO_VERIFY_SMS.md`

Modified:

- `composer.json`, `composer.lock`
- `.env.example`, `.env.staging.example`, `.env.production.example`
- `config/services.php`, `config/otp.php`
- `app/Providers/AppServiceProvider.php`
- `app/Models/OtpRequest.php`
- `app/Http/Controllers/AuthController.php`
- `app/Http/Middleware/ProductionSecurity.php`, `bootstrap/app.php`
- `app/Services/WhatsAppLinks.php`, `app/Services/SmsOtpSender.php`
- `app/Console/Commands/CheckConfiguration.php`
- `resources/js/Pages/Auth/Register.vue`, `resources/views/auth/otp.blade.php`

Existing routes, Meta sender/webhook implementation and email sender were retained. Unrelated existing workspace edits were preserved. The configuration-cache artifact is Git-ignored. The deployment database migration has not been applied to the running application during this task.

## Verification results

Final results are appended after the full test run. Focused tests exercise the actual Twilio PHP SDK against a fake HTTP transport and do not send SMS. They cover normalization, successful send/approval, every implemented failure category, missing configuration, cooldown/hourly/IP/session limits, invalid/expired code, maximum attempts, duplicate/concurrent sends/checks, protected session actions, CSRF/HTTPS, Group-member redirects, masked rendering, flashed-input protection and WhatsApp/Email regression.

Fresh interactive desktop/mobile verification is pending; no screenshot or live-delivery claim is made. Existing browser-tool installation was declined earlier in the session and was not requested again for this phase. Responsive UI markup and the frontend production build are verified automatically.

Final automated results:

- Full suite: `vendor/bin/phpunit` — **340 tests, 3,781 assertions passed** (1m 45s).
- Focused Twilio + WhatsApp suite — **44 tests, 347 assertions passed**. Final full-suite run also covers the final safe-error and channel-display adjustments.
- Frontend: `npm run build` — **passed**, 650 modules transformed.
- PHP syntax checks — **365 files, zero errors**. No PHPStan/Psalm/Pint/ESLint task is configured in this project; syntax checks, PHPUnit and the production compiler were used.
- `composer validate --no-check-publish` — **passed**.
- `php artisan optimize:clear` — **passed** after approved local MySQL cache access. `php artisan config:cache` — **passed**. The generated config cache is ignored by Git.
- Existing send/verify routes confirmed; targeted diff whitespace check passed.
- Secret checks: no literal Twilio account/service SIDs in application/config/UI diff; no SMS test secret in application logs. All actual Twilio credential fields remain unconfigured. Dummy test fixtures are not live credentials.
- Composer reported four advisories affecting two dependency packages during installation. Dependency-wide security remediation was not part of this integration.

**Remaining:** apply the migration to the deployment database, configure the real Twilio Verify account/service/secret, perform the authorized trial recipient test, verify interactive desktop/mobile behavior, and complete the production billing/geographic/India routing and compliance setup above. No real SMS, live Meta OTP or production deployment was claimed or performed. Existing WhatsApp and Email send/verify behavior passed the automated regression suite.
