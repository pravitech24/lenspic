# Milestone 1E legacy-popup correction

Audit date: 2026-08-13.

## Root cause

The current Create Group action was already an Inertia link, not a modal trigger. Its earlier error came from the incomplete migration boundary: active compatibility endpoints still reached legacy Blade modules, Group Settings sections used query strings instead of distinct routes, and the post-create flow previously entered a legacy invitation page. The old `groups/show.blade.php` still contains historical share/folder/photo popups and injected HTML, but the active `/groups/{group}` route now renders `Groups/Show` and does not reference that Blade file.

Create Group now uses GET `/groups/create` → `LensPicUiController::create` → `Groups/Create`, POST `/groups` → `GroupController::store`, an idempotency token/cache lock, real validation and authorization, and redirects to `/groups/{group}`. The authenticated browser run created exactly one Group and reached its workspace without a modal or legacy layout.

## Popup and legacy renderer inventory

| Trigger/button | Current route | Renderer | Legacy/popup found | Intended page | Correction status |
|---|---|---|---|---|---|
| New Group / Create Group / dashboard shortcut | `/groups/create` | Inertia `Groups/Create` | Historical Group create Blade remains, no active reference | Create Group page | Connected and browser-tested |
| Group card / workspace | `/groups/{group}` | Inertia `Groups/Show` | `groups/show.blade.php` contains old share/folder/viewer overlays and `innerHTML`; no active route reference | Group Workspace | Active route confirmed Inertia |
| Group Settings | `/groups/{group}/settings/general` | Inertia `Groups/Settings` | Old Group edit/settings Blade retained without active settings route | Direct settings pages | Corrected |
| Participants | settings URL and `/members` | Inertia Settings / `Groups/Members` | Old members Blade retained without active index route | Full page | Connected |
| Privacy | `/settings/privacy` under Group | Inertia Settings | No popup | Direct page | Corrected |
| Folders | settings URL and `/folders` | Inertia Settings / `Groups/Folders` | Old folder index Blade retained; old detail is still a legacy full page | Full page | Index/settings corrected; detail remains limitation |
| Design | `/settings/design` under Group | Inertia Settings | No supported backend values | Direct explanatory page | Correct; no fake controls |
| View & Download | `/settings/downloads` | Inertia Settings | No popup | Direct page | Corrected |
| Branding & Sponsors | `/settings/branding` | Inertia Settings | Sponsor records unsupported | Direct page with real watermark controls | Corrected |
| Client Favourites | settings URL and `/favorites` | Inertia Settings / `Groups/Favorites` | No popup | Full page | Connected |
| Upload/processing/indexing/exports | `/groups/{group}/operations` | Inertia `Groups/Operations` | No popup | Full page | Connected and browser-tested |
| Manual review | `/groups/{group}/face-reviews` | Inertia `Groups/Reviews` | No popup | Full page | Connected and browser-tested |
| Account Settings/Profile | `/settings/*` | Inertia `Settings/Index` | Legacy account views retained for unsupported financial modules | Full page | Connected |
| Old gallery index | `/groups/{group}/photos` | Redirect | Previously delegated to a legacy controller renderer | Group Workspace | Redirect corrected/tested |
| Old selfie/My Photos aliases | `/selfie`, `/my-photos` under Group | Redirect | Previously rendered `face/selfie.blade.php` with a complete AJAX workflow | Connected biometric page | Redirect corrected/tested |
| Onboarding photographer selfie | `/onboarding/selfie` | Blade | Full legacy capture page | Inertia onboarding capture | Remaining active legacy limitation |
| Welcome/start, join-code/complete, OTP | respective routes | Blade | Full legacy pages, not embedded in popups | Future connected pages | Remaining active legacy limitations |

No active Vue page contains an iframe, `v-html`, `innerHTML`, `window.open`, or HTML-fetch/module injection. No Blade page is embedded by an active Vue modal.

## Direct Group Settings routes

- `/groups/{group}/settings/general`
- `/groups/{group}/settings/participants`
- `/groups/{group}/settings/privacy`
- `/groups/{group}/settings/folders`
- `/groups/{group}/settings/design`
- `/groups/{group}/settings/downloads`
- `/groups/{group}/settings/branding`
- `/groups/{group}/settings/favourites`

All use the same `Groups/Settings` component and AppShell, are refresh-safe, validate the section, require `GroupPolicy@update`, omit invitation/share tokens, and were exercised directly at three viewports. `/groups/{group}/settings` remains the compatible General Settings URL.

## Retained confirmation modals

`UiModal` remains only for incorrect-match rejection, consent/search cancellation, consent withdrawal, biometric deletion, participant removal, invitation revocation and empty-folder deletion. These are focused confirmations and do not load routes or legacy HTML.

## Browser verification

An isolated SQLite database was migrated and seeded outside the production database. Headless Chrome logged in, used Create Group, verified Back/Forward navigation, opened every direct settings URL, Group Workspace, operations/indexing/exports, manual review and Account Settings, checked that no dialog/iframe appeared, persisted a real Group name change, and captured 39 screenshots at 375×844, 768×1024 and 1440×1000. All audited page requests succeeded. The only initial console error was the absent favicon; `/favicon.ico` now returns 204. No application JavaScript or network request error occurred.

Screenshots are in `docs/popup-audit-screenshots/`, named `{screen}-{mobile|tablet|desktop}.png`.

## Safety and remaining limitations

No migration was added to the application. No production record or existing media was modified. The isolated browser database exists only under `/tmp`. AWS, Rekognition, Stage 2 and Laravel upgrades remain untouched. Active legacy full pages still exist for photographer onboarding selfie, OTP, welcome/start, join-code completion, folder detail, invitation print, pricing/financial modules and platform administration. None is embedded in an active popup; they require future Inertia replacements before safe deletion. Laravel advisories remain the separate production blocker.
