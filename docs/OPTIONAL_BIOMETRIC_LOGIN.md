# Optional biometric login correction

## Confirmed redirect chain

Password login and SMS, WhatsApp and email OTP success all call `AuthenticatedLanding::afterAuthentication`. That resolver called `OnboardingState::complete`, which required a passed/manual-review legacy selfie whenever a photographer profile existed. `OnboardingState::step` also selected `selfie` for assigned photographers without one. The resulting chain was login → `/onboarding` → `/onboarding/selfie`, whose Blade view opened legacy capture. Profile POST separately rejected a missing selfie. Skipping role selection had exposed this next mandatory step; it had not made selfie enrollment optional.

A second automatic path was partial Group invitation acceptance → `/groups/{id}/discover`. Neither path represented an explicit Find My Photos choice. Authentication middleware and OTP frontend handlers did not independently require biometric enrollment; the shared backend decision caused the onboarding redirect.

## Result

- General onboarding reads assigned roles as before but checks only non-biometric studio/profile details. Assigned photographers still skip account-type selection. Default User choice, legacy completion rules, Super Admin and team/multi-role routing remain intact.
- Retired onboarding selfie GET/POST URLs resume the correct non-biometric step or landing destination. They cannot store uploads or set enrollment/completion flags.
- Photographer profile saving no longer requires a selfie or writes `selfie_verification_id`. Existing biometric associations are preserved; no biometric data is deleted or consent fabricated.
- Partial invitation acceptance goes to the authorized Group page. Existing gallery filtering, membership restrictions, media policies and entitlements are unchanged.
- Find My Photos links use the dedicated `/groups/{id}/find-my-photos` entry. Guest requests create a 15-minute, single-use session intent; after authentication, active membership, Group state and enabled discovery are rechecked. Invitation and required setup routing retain priority. The destination remains the existing consent page with its entitlement middleware.
- Saved `url.intended` selfie/discovery/entry URLs alone cannot restore capture. Ordinary authorized intended Groups remain supported. An expired or unauthorized explicit intent falls back safely.
- The consent UI starts unchecked and submits the user's actual choice and minor declaration. Capture input appears only with current granted consent and requires another user action; visiting the page does not grant consent or start processing.
- Legacy authenticated upload/recognize and anonymous selfie-processing endpoints return 410 instead of bypassing the consent flow. Their access checks remain. The old guest capture form now links to the authenticated, Group-membership-gated feature; anonymous biometric processing is no longer supported through the legacy endpoint. No membership is automatically granted.

## Files changed for this correction

- `app/Services/Auth/OnboardingState.php`
- `app/Services/Auth/AuthenticatedLanding.php`
- `app/Http/Controllers/OnboardingController.php`
- `app/Http/Controllers/InvitationController.php`
- `app/Http/Controllers/FindMyPhotosController.php` (new)
- `app/Http/Controllers/FaceController.php`
- `app/Http/Controllers/GuestController.php`
- `routes/web.php`
- `resources/js/Pages/Groups/Show.vue`
- `resources/js/Pages/Biometric/ConsentSelfie.vue`
- `resources/views/groups/show.blade.php`
- `resources/views/guest/group.blade.php`
- `tests/Feature/AssignedPhotographerOnboardingTest.php`
- `tests/Feature/OptionalBiometricLoginTest.php` (new)
- `tests/Feature/FaceRecognitionTest.php`
- `tests/Feature/GroupAccessInviteTest.php`
- `docs/ASSIGNED_PHOTOGRAPHER_ONBOARDING.md`
- This report.

No database migration, role reassignment, permission change or data cleanup was performed. Existing unrelated workspace changes were preserved.

## Verification

Regression coverage exercises complete/incomplete photographer setup without selfies; default role choice and stale submissions; password/SMS/WhatsApp/email login; Group members with missing and withdrawn consent; pending partial invitations and validated join sessions; stale capture URLs; expired and deliberately requested feature entry; blocked membership; declined/withdrawn consent search rejection; retired upload endpoints; and terminal dashboard/Group responses to check for loops. Existing consent/search, invitation, team, media authorization and OTP suites are included in the full run.

Provider calls are mocked. Live SMS/WhatsApp/email delivery, interactive browser/camera testing, screenshot/theme comparison and production database concurrency were not tested. No deployment or live biometric processing was performed.

Final results:

- Full Laravel suite: `vendor/bin/phpunit` — **366 tests, 4,174 assertions passed**, 2m 02s.
- Production frontend: `npm run build` — **passed**, 650 modules transformed.
- PHP syntax checks for changed controllers, services, routes and tests — **passed**.
- Targeted `git diff --check` — **passed**.
- `php artisan route:list --path=find-my-photos` — new explicit entry route registered.

The first full run exposed one obsolete partial-invitation redirect expectation and a repeated WhatsApp delivery ID in the test mock. The expectation now asserts the restricted Group destination; the mock returns a unique delivery ID for every request. Repeated-login tests advance the test clock rather than disabling production OTP throttling. The final full run above includes these corrections.
