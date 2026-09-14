# Assigned Photographer onboarding correction

## Root cause

LensPic's role governance assigns Photographer through `users.account_type` while leaving the platform `role` as `user`. Team/group assignments also live in scoped memberships. The old onboarding controller trusted `onboarding_step` independently: `role_pending` sent an assigned Photographer back to account-type selection, and direct GET access always rendered the form. POST accepted that stale form and overwrote both account type and platform role. Password login also used an unchecked intended URL, whereas OTP used separate redirect logic. The Vue profile form always displayed studio fields, even for ordinary User setup.

## Changes

- Added `OnboardingState`, which reads the existing server-side assignment fields and resolves role choice, non-biometric profile, or completion separately. It does not infer Photographer from broad permissions or Group ownership.
- Assigned Photographer/Studio accounts never receive the role-selection page. Incomplete studio details lead directly to studio/profile completion. Selfie capture and biometric consent are optional and never affect general onboarding. Merely having the assignment does not set any onboarding-completion field.
- Saved studio fields are checked for completeness. Existing legacy completion markers are honored because the original migration explicitly marked pre-existing accounts complete without studio records. A later Photographer assignment cannot inherit completion of an earlier User profile; an existing incomplete studio record also cannot be bypassed using an old timestamp.
- A plain default `role=user` or `account_type=user` does not count as an explicit choice. Explicit choice/assignment state and existing active team membership are handled separately.
- GET and POST onboarding endpoints use the same backend step guard. Role POST locks and reloads the user before deciding whether choice is permitted. Stale submissions are redirected without altering assigned roles, memberships, permissions or studio records. Genuine new choices update `account_type`, preserving the platform `role`.
- Password and SMS/WhatsApp/email OTP completion now share `AuthenticatedLanding::afterAuthentication`. Incomplete Photographer setup takes precedence over intended destinations. Complete users can resume an owned/authorized Group or supported workspace destination; unsafe, unauthorized or onboarding intended URLs fall back to the existing role-aware landing page. Pending Group joins, Group invitations and team invitations survive the remaining setup steps.
- Super Admin keeps its dashboard priority. Completed users with active team/Group membership retain the existing Groups workspace priority, including multi-role users.
- Invitation display/acceptance uses the same completion decision, avoiding contradictory state for a complete saved studio with stale onboarding flags.
- Vue receives the server-resolved account type and existing profile defaults. The current theme is retained, with appropriate ordinary-user versus studio fields, visible validation errors and an explicit choice only when needed. The ordinary User welcome screen remains available after profile submission.

No new roles, permissions, migrations, studio tables or local-storage rules were added. Existing `PhotographerProfile::updateOrCreate` and the unique user key remain in place.

## Files

Created:

- `app/Services/Auth/OnboardingState.php`
- `tests/Feature/AssignedPhotographerOnboardingTest.php`
- `docs/ASSIGNED_PHOTOGRAPHER_ONBOARDING.md`

Modified:

- `app/Services/Auth/AuthenticatedLanding.php`
- `app/Http/Controllers/AuthController.php`
- `app/Http/Controllers/OnboardingController.php`
- `app/Http/Controllers/InvitationController.php`
- `resources/js/Pages/Onboarding.vue`

The existing onboarding routes already use authentication middleware; no separate onboarding middleware was present or added. The shared backend resolver and endpoint guards handle this correction. Unrelated workspace changes were preserved.

## Verification

Regression coverage includes complete/incomplete Photographer assignments, Studio aliases, default User choice, stale/downgrade submissions, direct URLs, no redirect loops, saved complete/incomplete studio records, old completion timestamps followed by new assignments, profile completion without duplicate studios, authorized/unauthorized intended destinations, pending Group/team invitations, Super Admin and multi-role routing, and password plus all three OTP channels. Provider calls are mocked.

Final test/build results are appended below. Live SMS/Meta/email delivery was not tested for this routing-only change. Interactive browser/screenshot comparison and production multi-process database lock contention were not tested; backend rendering, redirects, role preservation and frontend compilation were checked automatically.

Historical verification before the optional-biometric correction (see `OPTIONAL_BIOMETRIC_LOGIN.md` for current results):

- Focused onboarding/authentication/OTP routing suite: **70 tests, 562 assertions passed**.
- Full Laravel suite (`vendor/bin/phpunit`): **356 tests, 3,941 assertions passed**.
- Production frontend build (`npm run build`): **passed**, 650 modules transformed.
- Changed PHP files passed syntax checks; targeted diff whitespace checks passed.
- No database migration, role reassignment, permission migration or live provider send was performed.
