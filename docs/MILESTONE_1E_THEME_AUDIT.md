# Milestone 1E unified-theme audit

## Theme consistency checklist

- [x] Central colour, surface, radius, typography, focus, shadow and state tokens live in `resources/css/app.css`.
- [x] Shared application and authentication shells cover desktop, tablet and mobile layouts.
- [x] Shared buttons include primary, secondary, ghost and destructive states plus disabled/loading behavior.
- [x] Shared cards, alerts, badges, modal confirmations, loading indicators, page headings, empty states and error states are available to every Inertia page.
- [x] Form controls share label, help, validation, hover, focus and disabled styling.
- [x] Navigation exposes current-page state, mobile expansion state, keyboard focus and a skip link.
- [x] Authentication, registration, onboarding, dashboard, event list/create/workspace, invitation, biometric consent/selfie and My Photos use the LensPic tokens.
- [x] Destructive biometric actions and incorrect-match rejection use the shared accessible confirmation modal.
- [x] Gallery and My Photos grids retain square immediate wrappers and images at every breakpoint.
- [x] Responsive source audit covers base/mobile, `sm`, `md`, `lg` and `xl` layouts; production CSS and Vue compilation pass.

## Connected Inertia routes using the unified theme

`/login`, `/register`, `/onboarding/account-type`, `/onboarding/profile`, `/dashboard`, `/settings`, `/settings/profile`, `/settings/branding`, `/settings/watermark`, `/settings/team`, `/settings/subscription`, `/groups`, `/groups/create`, `/groups/{group}`, `/groups/{group}/edit`, `/groups/{group}/settings`, `/groups/{group}/members`, `/groups/{group}/operations`, `/groups/{group}/face-reviews`, `/groups/{group}/photos/{photo}`, `/join/{token}`, `/groups/{group}/discover`, and `/face-searches/{uuid}/results`.

## User-facing routes still using legacy Blade

These views remain because their Inertia replacements are not connected and tested yet. They were intentionally retained rather than removed prematurely.

- Public: `/`, `/pricing`, `/join-group`, `/join-group/complete`, `/join-group/success`, `/g/{share_token}`, and legacy guest results.
- Authentication/onboarding: `/verify-otp`, `/onboarding/selfie`, and `/welcome/start`.
- Event administration: access-invitation print and folder management/detail remain legacy. Event edit/settings and participant management now use connected Inertia pages.
- Gallery: full photo index and legacy My Photos/selfie aliases remain for compatibility; the connected photo viewer, favorites and permission-aware downloads now use Inertia.
- Account: Profile, branding, watermark, team, subscription and the Settings entry route now use one normal Inertia page. Portfolio, wallet and transaction history remain legacy because those broader financial modules were not part of the connected Milestone 1E scope.
- Administration: `/admin/*` and `/super-admin/*`.

JSON-only processing, indexing, biometric deletion, export and private-media endpoints do not render a visual theme. Their future Inertia status screens must consume the same shared components.
