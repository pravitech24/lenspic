# LensPic navigation architecture audit

## Root cause

The authenticated UI had two competing page architectures. Current modules rendered Inertia pages inside `AppShell`, while portfolio, wallet, and transactions still rendered legacy Blade views through `settings.layout`. Settings navigation also used client-side Inertia links while legacy and compatibility entry points used hard links. That inconsistency allowed a host shell or stale client bundle to treat destination content like an embedded workflow even though the route represented a main page.

No global `preventDefault` anchor interceptor, Turbo, HTMX, Livewire navigation, iframe loader, `data-modal`, `data-popup`, or route-to-dialog mapping exists in the active Vue application. The durable correction is therefore structural: one authenticated shell, one shared settings navigation, dedicated page renderers, and plain GET anchors for settings and Create Group entry points.

## Route matrix

| Route | Module | Renderer after audit | Classification | Authorization | Fix and coverage |
|---|---|---|---|---|---|
| `/dashboard` | Dashboard | `Dashboard` + `AppShell` | Full page | Authenticated | Existing Inertia route; audited |
| `/events` | Events index | Redirect to `/groups` | Full page compatibility URL | Authenticated | Redirect target is `Groups/Index`; tested |
| `/events/create` | Create Event | Redirect to `/groups/create` | Full page compatibility URL | Group create policy at target | Hard-page target; tested |
| `/events/{group}` | Event overview | Redirect to Group overview | Full page compatibility URL | Group view policy at target | Existing; tested |
| `/events/{group}/settings` | Event settings | Redirect to Group settings | Full page compatibility URL | Group update policy at target | Added and tested |
| `/events/{group}/uploads` | Event upload centre | Redirect to Group operations | Full page compatibility URL | Group update policy at target | Added |
| `/events/{group}/gallery` | Event gallery | Redirect to Group overview/gallery | Full page compatibility URL | Group view policy at target | Added |
| `/events/{group}/participants` | Event participants | Redirect to Group members | Full page compatibility URL | Manage-members policy at target | Added |
| `/groups` | Groups index | `Groups/Index` + `AppShell` | Full page | Authenticated, scoped queries | Create link changed to hard GET; tested |
| `/groups/create` | Create Group | `Groups/Create` + `AppShell` | Full page | `GroupPolicy::create` | No modal component; tested |
| `/groups/{group}` | Group overview/gallery | `Groups/Show` + `AppShell` | Full page | Group view policy | Existing; tested |
| `/groups/{group}/settings[/section]` | Group settings | `Groups/Settings` + `AppShell` | Full page | Group update policy | Existing direct URLs; tested |
| `/groups/{group}/operations` | Upload, processing, exports | `Groups/Operations` + `AppShell` | Full page | Group update policy | Existing; tested |
| `/groups/{group}/members` | Participants | `Groups/Members` + `AppShell` | Full page | Manage-members policy | Existing; destructive removal modal retained |
| `/groups/{group}/access-invites` | Permissions/invitations | `Groups/Invitations` + `AppShell` | Full page | Manage-invitations policy | Existing; revoke modal retained |
| `/analytics` | Analytics | `Analytics` + `AppShell` | Full page | Group create/studio policy | Added with account-owned aggregates; tested |
| `/notifications` | Notifications | `Notifications` + `AppShell` | Full page | Authenticated | Added; uses real notification table only when available; tested |
| `/settings`, `/settings/profile` | Profile settings | `Settings/Index` + `AppShell` | Full page | Authenticated self | Shared settings navigation; tested |
| `/settings/branding` | Studio settings/branding | `Settings/Index` + `AppShell` | Full page | Group create/studio policy | Authorization tightened; tested |
| `/settings/watermark` | Watermark | `Settings/Index` + `AppShell` | Full page | Group create/studio policy | Authorization tightened; tested |
| `/settings/team` | Team | `Settings/Index` + `AppShell` | Full page | Group create/studio policy | Account-owned Groups only; tested |
| `/settings/privacy` | Privacy and biometrics | `Settings/Utility` + `AppShell` | Full page | Authenticated self | Added; points to event-scoped real controls; tested |
| `/settings/subscription` | Plans and subscription | `Settings/Subscription` + `AppShell` | Full page | Group create/studio policy | Dedicated renderer with real plan, usage, subscription, order and payment configuration state; tested |
| `/settings/transactions` | Billing history | `Settings/Utility` + `AppShell` | Full page | Group create/studio policy | Migrated from legacy Blade; account-owned records; tested |
| `/settings/portfolio` | Portfolio | `Settings/Utility` + `AppShell` | Full page | Group create/studio policy | Migrated from legacy Blade; tested |
| `/settings/wallet` | Wallet | `Settings/Utility` + `AppShell` | Full page | Group create/studio policy | Migrated from legacy Blade; explicitly reports unavailable backend without mock balance; tested |

## Modal classification

Active Vue dialogs remain only for destructive or compact confirmations: remove participant, delete empty folder, revoke invitation, reject a face match, cancel biometric processing, withdraw consent, and request biometric deletion. No module route, create/edit form, settings screen, upload centre, gallery, subscription screen, or billing-history screen is rendered by a modal component.

## Legacy views

The old `resources/views/settings/*` files remain on disk for safe historical compatibility, but no active settings controller route renders portfolio, wallet, transactions, subscription, profile, branding, watermark, or team through them. The active renderer matrix above is authoritative and covered by route tests.

## Deliberately unsupported backend actions

The current application has no studio-subscription cancel/resume endpoint, wallet ledger, invoice-download service, or persistent notifications table in every installation. The UI does not invent those controls or records. It shows truthful empty/unavailable states and exposes only existing pricing and Razorpay upgrade behavior.
