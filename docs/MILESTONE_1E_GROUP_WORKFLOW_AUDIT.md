# Milestone 1E Group-based workflow and UI correction

Audit date: 2026-08-13. The canonical user-facing project container is **Group**.

## Domain conclusion

There is no Event model, `events` table, event foreign key, Event policy, or Event controller. `groups.id` owns photos, folders, members, invitations, media, upload batches, exports, biometric consent/search/results/deletion, face collections and indexing runs. “Event” was an inconsistent presentation label and appears in a few internal attribute/class names (`event_type`, `event_date`, `EventFaceIndexRun`). It is not a separate business entity. Those internal identifiers remain untouched for data compatibility.

| Current Event functionality | Current Group functionality | Database ownership | Routes/screens | Same or separate | Correction | Risk |
|---|---|---|---|---|---|---|
| Dashboard “events” count/cards | Owned Groups | `groups.creator_id` | `/dashboard` | Same concept | Relabeled Groups and changed props | None |
| Add/Create Event | Create Group | `groups` | `/groups/create`, POST `/groups` | Same concept | Full-page `Groups/Create`; idempotent creation; redirect to workspace | Low; legacy POST remains compatible |
| Event Workspace/Gallery | Group workspace/gallery | `photos.group_id` | `/groups/{group}` | Same concept | `Groups/Show` and Group navigation | None |
| Event participants/invitations | Group memberships/access invites | `group_members`, `group_access_invites` | Group child routes | Same concept | Full-page Group modules | None |
| Event processing/exports | Group upload batches/exports | `upload_batches.group_id`, `media_exports.group_id` | Group operations | Same concept | Group Operations page | None |
| Event biometric collection/index | Group-isolated collection/index | every biometric table uses `group_id` | Group discover/index/review routes | Same concept; internal names only | User copy and navigation use Group; schema preserved | Renaming internals would be high-risk and was not done |
| Event Settings | Group fields | `groups` | edit/settings/update | Same concept | One full Group Settings page | None |

No compatibility/presentation Event model was required because Group already is the backing record.

## Add Event root-cause audit

The active Milestone 1E implementation did **not** contain an Add Event popup or modal. The opening controls were AppShell/Dashboard links to GET `/groups/create`; `LensPicUiController::create` rendered `Events/Create`, and the form posted to `GroupController::store`. The actual defect was mismatched user-facing terminology and a post-create redirect into the legacy access-invite Blade page. There was no modal component, iframe, CSRF bypass or distinct Event endpoint. Validation was performed inline in `GroupController`; authorization was checked both in the page controller and store action. No reproducible Laravel log exception existed in the current code.

Correction: GET `/groups/create` now renders `Groups/Create`; POST `/groups` validates real Group fields, checks the Group policy/plan, accepts a UUID submission token, serializes duplicate requests with a cache lock, creates exactly one Group transaction, and redirects to `/groups/{group}`. The form disables during submission, has field errors and a safe Cancel link.

## Group Settings support matrix

| Setting | Backend field/action | Legacy implementation | New UI | Authorization/test |
|---|---|---|---|---|
| Name, description, type, date, location | `groups` columns | Group edit/settings Blade | General | `GroupPolicy@update`; tested |
| Active state, membership open/closed and limit | `is_active`, `membership_status`, `membership_limit` | Partial legacy support | General | Manager only; tested |
| Visibility | `privacy` | Legacy edit | Privacy | Manager only; tested |
| Link joining / anonymous access | `anyone_with_link_can_join`, `anonymous_access_mode` | Backend access resolver | Supported backend; link join control shown, raw anonymous modes intentionally not exposed | Manager validation |
| Participant upload/identity | `allow_guest_upload`, `participants_can_edit_identity` | Legacy settings | Privacy | Manager only |
| My Photos availability | `face_recognition_enabled` | Legacy settings | Privacy | Manager only; biometric tests |
| Download access | `downloads_enabled` | Backend policy | View & Download | Manager only; tested |
| Watermark | `watermark_enabled`, `watermark_text` | Legacy edit | View & Download / Branding | Manager only |
| Participants/invitations | membership and invitation actions | Legacy pages | Full pages linked from settings | Manager policies; tested |
| Folders | folder CRUD/highlight | Legacy folder pages | Full Groups/Folders page | Folder policy; tested |
| Client favourites | `photo_likes` filtered through current photo/media policy | Photo likes | Full read-only favourites page | Manager only; tested |

Unsupported and therefore not shown as working: Digital Flipbook, sponsor records, per-folder participant ACLs, downloadable TXT/CSV favourite reports, gallery themes, download-quality selection, export regeneration, and global cross-Group processing/export indexes. Cover upload remains backend-supported during Group update but was not re-exposed until private cover-media handling replaces the legacy public cover path.

## Route compatibility

| Old Event route | Purpose | Group-facing route | Handling | Authorization/test |
|---|---|---|---|---|
| `/events` | Old project list bookmark | `/groups` | Permanent redirect | Auth; tested |
| `/events/create` | Old create bookmark | `/groups/create` | Permanent redirect | Auth then Group create policy; tested |
| `/events/{group}` | Old workspace bookmark | `/groups/{group}` | Permanent redirect | Destination Group policy; tested |
| Existing `/groups/*` routes | Actual historical backend routes | Same URLs | Retained | Existing and new suites |

## Full-page module map

| Screen | Direct URL | Role | Backend |
|---|---|---|---|
| Groups | `/groups` | authenticated authorized memberships | Group relationships |
| Create Group | `/groups/create` | photographer/platform admin | `GroupController::store` |
| Group Workspace/Gallery | `/groups/{id}` | authorized Group member | Group/Photo policies |
| Group Settings | `/groups/{id}/settings` | Group manager | `GroupController::update` |
| Participants | `/groups/{id}/members` | Group manager | membership actions |
| Invitations | `/groups/{id}/access-invites` | Group manager | GroupAccessInviteController |
| Folders | `/groups/{id}/folders` | viewer; mutations manager-only | FolderController/policy |
| Uploads, Processing, Exports, Indexing | `/groups/{id}/operations` | Group manager | queue/export/index endpoints |
| Manual Review | `/groups/{id}/face-reviews` | Group manager | biometric review endpoint |
| Client Favourites | `/groups/{id}/favorites` | Group manager | authorized ready liked photos |
| Account Settings | `/settings` | account owner | SettingsController |

## Authorization summary

Server policies—not Vue visibility—guard Group view/update/upload/member/invitation/folder/media operations. Export creation reauthorizes each photo; manual review and face indexing require Group management; My Photos reauthorizes every match; invitation and guest sessions remain Group-bound. New workflow tests cover owner, participant, unrelated photographer, old-route compatibility, idempotent creation and sensitive-prop omission. Existing invitation, media, biometric, export and queue suites provide cross-Group coverage.

## Popup audit

No full Group workflow uses a popup, drawer, iframe or embedded legacy page. Retained modals are focused confirmations for participant removal, invitation revocation, folder deletion, incorrect-match rejection, consent withdrawal and biometric deletion. Print QR intentionally opens a print-oriented Blade document in a new tab. The old Group access-invite and folder index Blade views remain in the repository for compatibility history, but their active index routes now render Inertia; print QR and individual legacy folder detail remain Blade.

## Safety and limitations

No migration was added. No Group/Event record was renamed, merged or deleted. No existing media was moved, rewritten or deleted. No AWS/Rekognition activation, Stage 2 work or Laravel upgrade occurred. Internal event-named fields/classes remain for compatibility. Laravel security advisories remain a separate production blocker. Participant-facing copy still uses “event” in a few consent/invitation explanations where it describes the real-world occasion rather than a separate application module; the application navigation and management domain use Group exclusively.

## Verification record

- Production bundle: Vite completed successfully with 573 modules.
- Backend: 98 tests, 634 assertions, zero errors/failures/skips (JUnit record generated without result caching).
- Database: the configured database reported “Nothing to migrate.”
- Dependencies: Composer install and npm install completed; npm reported no vulnerabilities.
- Frontend scripts: the project defines `build` and `dev`; it has no configured frontend test, lint, formatting or static-analysis command.
- Composer audit: three Laravel Framework advisories remain (temporary signed-URL path confusion and CRLF email-rule advisories). The required framework upgrade is explicitly outside this milestone.
- Browser: the already-approved shared authentication shell has prior 390/768/1440 screenshots. A new Playwright/Chrome authenticated Group audit was attempted against a separately migrated SQLite database, but Laravel’s spawned audit server retained a conflicting local server/database process and rejected the isolated audit credentials. No production user or fake production record was created to bypass that boundary. Consequently, this report does **not** claim authenticated browser/console acceptance or provide misleading Group screenshots. Inertia route rendering, navigation props, actions and responsive classes are covered by the automated suite and production build; final visual acceptance of authenticated Group pages remains pending in a normal development login session.
