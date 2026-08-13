# Milestone 1E full UI and functionality correction audit

Audit date: 2026-08-13. Scope: completed Milestones 1A–1E only. Stage 2, live AWS/Rekognition activation, legacy-media mutation and Laravel upgrades were excluded.

## Corrections and root causes

| Root cause | User impact | Correction | Verification |
|---|---|---|---|
| Settings/Profile navigation still rendered legacy Blade modules | The shared sidebar led into a visually separate application | Connected Profile, Settings, Branding, Watermark, Team and Subscription to `Settings/Index.vue` through `SettingsController` and redirected `/profile` to that full page | `FullUiAuditTest`; production build |
| Inertia upload did not expose its durable batch | Upload completion and processing completion were ambiguous | Inertia upload now redirects to authorized Operations with the real batch UUID in the success message; JSON and legacy HTML behavior remain compatible | upload integration test; existing media tests |
| Processing, indexing and export screens displayed state but offered no connected controls | Cancel, retry, start/resume and export creation were unreachable | Added authorized Inertia-compatible actions, per-file sanitized status, gallery selection, ZIP creation and queue refresh | action tests and policy tests |
| Authentication shell could permit narrow-viewport grid overflow | Controls could be cut at mobile width | Added `min-w-0` and horizontal containment to the shared auth shell | 390/768/1440 rendered screenshots |
| Applied Laravel release has known advisories | Production security exposure remains possible | Not changed because this audit explicitly forbids a Laravel upgrade | `composer audit`; production blocker |

## Phase-by-phase result

| Milestone | Verified behavior | Result / limitation |
|---|---|---|
| 1A | Auth, registration, onboarding, owner/member policies, invitation states, session expiry, dashboard and connected account settings | Automated role/authorization coverage passes. OTP/welcome and financial settings remain legacy routes. |
| 1B | Private-local assets, authorized delivery, variants, cross-event denial, checksums, safe object keys, legacy compatibility | Existing test suite passes; no legacy media changed. Folder management remains connected legacy Blade pending a replacement. |
| 1C | Durable batches, retry/concurrency protection, partial failure, ZIP content/failure cleanup, ledger reconciliation, exports | Tests pass. Connected UI now creates exports and controls batches/exports. Export regeneration is not a backend-supported operation and is not shown. |
| 1D | Consent purpose/version, invitation/event gate, minor block, private selfies, outcomes, reauthorization, rejection, deletion, review/index retry and provider isolation | Biometric compliance suite passes with fake provider. Live Rekognition remains intentionally inactive. Collection deletion is a background compliance state, not a separate UI action. |
| 1E | Real Inertia routes for primary photographer/participant flows, shared theme, responsive states and square galleries | Connected pages build and tests pass. Legacy modules are listed below and are not falsely represented as converted. |

## Route and action inventory

The inventory is grouped by complete route/action family; every route in a family shares the recorded renderer and authorization boundary.

| Route or action | Role | Milestone | Renderer/layout | Surface | Backend / authorization | Problem and correction | Browser result | Status |
|---|---|---|---|---|---|---|---|---|
| `/`, `/pricing` | Public | 1A/1E | Blade / public | Page | Real | Legacy retained; pricing owns Razorpay flow | Landing/login shell inspected | Active legacy |
| `/login`, `/register` + POST actions | Guest | 1A/1E | Inertia / AuthShell | Page | AuthController, guest middleware | Narrow containment corrected | 390/768/1440 | Connected |
| `/send-otp`, `/verify-otp` | Guest | 1A | Blade | Page | AuthController, throttle/validation | No popup | Route/test audit | Active legacy |
| `/logout` | Authenticated | 1A | Action | Action | Auth middleware/CSRF | No dead UI action | Automated | Connected |
| `/onboarding*`, `/welcome/start` | Authenticated | 1A/1E | Inertia for role/profile; legacy for selfie/welcome | Page | OnboardingController | Core photographer steps use AppShell | Automated | Partial legacy |
| `/dashboard` | Authenticated | 1A/1E | Inertia / AppShell | Page | Real aggregates | None | Build/static route audit | Connected |
| `/profile`, `/settings`, `/settings/{profile,branding,watermark,team,subscription}` | Authenticated | 1A/1E | Inertia / AppShell | Page | SettingsController | Converted from full legacy workflow | Automated | Connected |
| `/settings/{portfolio,wallet,transactions}` | Authenticated | 1A | Blade | Page | Real account data | Outside implemented connected scope | Route audit | Active legacy |
| `/groups`, `/groups/create`, `/groups/{group}`, edit/settings/update | Owner/member as policy allows | 1A/1E | Inertia / AppShell | Page | Group policy/controller | Connected; no module popup | Automated | Connected |
| group join/leave/invite/token/code actions | Auth/member/owner | 1A | Redirect/JSON | Action | GroupController policies | Legacy-compatible endpoints retained | Automated | Connected backend |
| `/groups/{group}/members` + removal | Manager | 1A/1E | Inertia / AppShell | Page + confirmation modal | `manageMembers` policy | Focused delete confirmation retained | Automated | Connected |
| access-invite CRUD/print | Manager | 1A | Blade/redirect | Page/action | Policy/controller | Rich invite-management replacement not yet connected | Automated backend | Active legacy |
| `/join/{token}` accept/decline | Public/auth participant | 1A/1D/1E | Inertia / simplified LensPic | Page | Token/session/event bound | Tokens omitted from props | Automated | Connected |
| `/join-group*`, `/g/{share_token}` | Guest | 1A | Blade/JSON | Page | Event-bound guest controls | Legacy retained for compatibility | Automated | Active legacy |
| event gallery/photo viewer/like/download | Member by access | 1B/1E | Inertia / AppShell | Page | Photo/media policies | Viewer is full page; real favorite/download | Automated | Connected |
| folder CRUD/transfer/cover | Manager | 1B | Blade/redirect | Page/action | FolderController policies | No Inertia gallery manager yet | Automated backend | Active legacy |
| `/media/{uuid}/{variant}` | Authorized user/guest | 1B | Stream/redirect | Delivery | Media policy, throttled | No private path/provider props | Automated | Connected backend |
| upload `/groups/{group}/photos` | Uploader | 1C/1E | Inertia/JSON action | Action | Upload policy/validation/private ingestor | Redirect now exposes durable batch screen | Automated | Corrected |
| processing show/cancel/retry | Batch owner/event owner | 1C/1E | JSON + Inertia action | Page actions | Batch/event authorization | Controls and per-file status connected | Automated | Corrected |
| export create/show/cancel/download | Authorized requester | 1C/1E | JSON + Inertia action/stream | Page actions | Per-photo authorization and owner gate | Gallery selection and controls connected | Automated | Corrected |
| biometric consent/search/status/cancel | Participant | 1D/1E | Inertia + JSON | Page/actions | Consent/event/invite/minor gates | Existing connected state machine | Automated | Connected |
| My Photos/results/reject | Subject owner | 1D/1E | Inertia / AppShell | Page + confirmation modal | Every asset reauthorized | Focused “not me” confirmation retained | Automated | Connected |
| consent withdrawal/deletion request/status | Subject owner/admin status | 1D/1E | Inertia + JSON | Page + confirmation modal | Scope and ownership gates | Focused irreversible confirmations retained | Automated | Connected |
| face index start/status | Event manager | 1D/1E | Inertia action + JSON | Page action | Update policy | Start/resume now connected in Operations | Automated | Corrected |
| face review/review action | Event manager | 1D/1E | Inertia / AppShell | Page/action | `manageMembers` | Full workflow is a page | Automated | Connected |
| `/admin/*`, `/super-admin/*` | Platform admin | 1A legacy | Blade / admin layout | Page/action | Admin middleware | Not converted in connected 1E foundation | Authorization tests | Active legacy |
| Razorpay order/verify/webhook | Auth/public signed provider | Legacy billing | JSON/action | Action | Billing validation | Live external flow not activated | Existing tests | Backend only |

## Clickable-action audit

- Shared sidebar/header: Dashboard, Events, Settings, New event and mobile equivalents resolve to real routes. No `href="#"` or demo controls exist in active Vue pages.
- Event workspace: upload, photo open, selection/export, Participants, Operations and Manual Review are connected. Export button is unavailable until at least one photo is selected.
- Operations: automatic refresh is cleaned up on unmount; cancel/retry/index actions disable while submitted. Finished exports alone expose download.
- My Photos: unauthorized/unready candidates are omitted server-side; rejection uses a focused confirmation and reloads results.
- Destructive participant removal and biometric actions use shared confirmation modals. Manual review uses direct focused actions on a dedicated authorized page.
- Comments remain read-only because no approved comment-write endpoint exists; no nonfunctional Add Comment control is displayed.

## Popup, overlay and legacy-embed audit

Active Vue popups are `UiModal` confirmations for member removal, incorrect-match rejection, consent withdrawal and biometric deletion. Each is short, single-purpose and appropriate. No Inertia module uses an iframe, embedded Blade page, module drawer or full-workflow popup. Legacy Blade uses native confirmation prompts in folder/admin/access-invite pages; these remain only with their still-active legacy routes and are listed as limitations.

## Layout and responsive checklist

- Shared design tokens/components: pass.
- Focus-visible styles and labeled form controls: pass for connected pages.
- Mobile navigation and minimum touch height: pass by source/build audit.
- Horizontal containment: corrected in AuthShell; tables intentionally scroll within `lp-table-wrap`.
- Gallery and My Photos immediate wrappers/images: `border-radius:0!important`; no rounded utility on audited grid media.
- Screenshots: `docs/audit-screenshots/login-{desktop,tablet,mobile}.png`. These are real rendered routes, not mock screenshots.
- Browser console: headless Chrome produced no page JavaScript errors while rendering the login route. Authenticated browser execution was not performed against the existing database because this audit was prohibited from inserting fake production records; authenticated UI behavior is covered by feature/Inertia tests and build/static audits.

## Legacy pages and blockers

Still active by design: OTP, onboarding selfie/welcome, folder manager, access-invitation administration/print, share-link gallery, portfolio/wallet/transactions, pricing and platform-admin modules. Their routes/controllers still reference them, so none were deleted. They are the remaining unified-theme limitation.

Production blocker: `composer audit` reports three Laravel Framework advisories (signed-URL path confusion and two CRLF/email-validation advisories). The required remediation is a framework upgrade, explicitly forbidden in this milestone. Production deployment should not be approved until a separately authorized upgrade is tested.

No migration was added. No existing media or database record was moved, classified, disabled, rewritten or deleted. AWS, Rekognition and Stage 2 remain deferred.
