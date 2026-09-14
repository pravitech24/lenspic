# LensPic Photographer Settings Audit

The audit matches the requested functionality while retaining LensPic's existing full-page Inertia layout. Unsupported product concepts remain explicitly unavailable rather than showing mock controls or balances.

| Screenshot module | LensPic route | Existing / implemented fields | Missing or not applicable | Backend and permission | Test coverage |
|---|---|---|---|---|---|
| Your Profile | `/settings/profile` | First name, last name, normalized email, E.164-style phone, separate current/new/confirm password, ledger-backed storage utilization | Country picker and an outbound email-verification delivery workflow are not present | Authenticated user only; password is current-password checked, strongly validated and throttled | Profile isolation, email verification reset, password security, storage summary, full-page rendering |
| Account Preferences | `/settings/preferences` | Slideshow interval, default loop and email notification preference | Language, timezone, date format, gallery defaults and permission defaults are omitted because the app does not consume them | Stored in authenticated user's metadata; slideshow settings are consumed by the viewer | Persistence and full-page rendering |
| Business Branding | `/settings/branding` | Studio name, tagline, logo upload, primary/accent colours, business email/phone/address, website and description | Logo removal, social links, custom domain and plan-based LensPic-brand removal are not supported | Photographer/studio authorization; values and files are account-scoped | Existing full UI authorization suite; validation/build coverage |
| Team Login | `/settings/team` | Current Group member counts and links to individual Group access management | No studio-wide team/invitation/role model or team plan limit exists | Studio-capable account only; individual Group authorization remains authoritative | Direct-route and authorization coverage |
| Flipbook Settings | None | None | Pending: no Flipbook model, renderer or delivery workflow | Hidden; no unused form or fake data | Missing functionality is not presented as working |
| Watermark | `/settings/watermark` | Text, position, opacity, preview and per-Group enablement through existing Group settings | Logo watermark, scale/margin/tiling and bulk reprocessing controls are not supported | Studio defaults are account-scoped; existing Group policy controls activation; download processing does not alter masters | Existing Group settings/watermark tests plus validation/build coverage |
| Portfolio | `/settings/portfolio` | Eligibility-aware setup request only | Pending: no portfolio publication/content-selection model exists | Studio-capable account only | Full-page route audit |
| Wallet | `/settings/wallet` | Explicit unavailable state | Not applicable: no wallet or credit ledger exists | Studio-capable account only; no fake balance | Full-page route audit |
| Transactions | `/settings/transactions` | Account-owned subscription history | Pagination, filters, invoices and refund/payment summaries await a unified transaction/invoice model | Studio-capable account only; query is authenticated-user scoped | Route and account-scope coverage in billing/UI suites |
| Subscription | `/settings/subscription` | Current plan/status/cycle/expiry, group and storage quotas, account billing history, available plans and upgrade route | Provider billing-portal update, feature catalogue and add-ons are not modelled | Studio-capable account only; Razorpay records are user scoped | Razorpay billing, storage enforcement and UI tests |

## Functional corrections

- Passwords are no longer accepted by the general profile form and are never prefilled or returned.
- Email changes clear `email_verified_at`; names, email and phone are normalized before persistence.
- Storage use comes from `storage_ledger_entries`, photo count from active owned media, and limits from the current plan.
- The upload pipeline already enforces the same byte and per-Group photo plan limits.
- No delete/re-upload quota, video allowance or retention promise is displayed because those policies are not implemented.
- Settings use direct, refresh-safe full pages and the existing LensPic shell/navigation.
- Studio settings continue to use the existing `Group::create` capability gate; profile and preferences operate only on the authenticated user.

## Verification

- `php artisan optimize:clear`: passed.
- `php artisan route:list --name=settings`: passed; 19 settings-related routes listed.
- `php artisan test`: unavailable in this application's Artisan command registry.
- `./vendor/bin/phpunit`: passed, 148 tests and 1,398 assertions.
- `npm run build`: passed, Vite production build completed.
- Spatie Permission is not installed, so `permission:cache-reset` is not applicable.
