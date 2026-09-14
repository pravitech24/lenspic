<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\PhotoController;
use App\Http\Controllers\FaceController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\GuestController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\FolderController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\GroupAccessInviteController;
use App\Http\Controllers\PublicGroupJoinController;
use App\Http\Controllers\MediaDeliveryController;
use App\Http\Controllers\QueuedUploadController;
use App\Http\Controllers\ProcessingController;
use App\Http\Controllers\MediaExportController;
use App\Http\Controllers\BiometricController;
use App\Http\Controllers\BiometricComplianceController;
use App\Http\Controllers\LensPicUiController;
use App\Http\Controllers\GroupCoverController;
use App\Http\Controllers\BusinessBrandingLogoController;
use App\Http\Controllers\{TeamLoginController,TeamInvitationController};
use App\Http\Controllers\{FlipbookSettingsController,FlipbookLogoController,PublicFlipbookController};
use App\Http\Controllers\{WatermarkSettingsController,WatermarkLogoController};
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\MarketingController;
use App\Http\Controllers\ContactEnquiryController;
use App\Http\Controllers\PasswordResetController;

// Public marketing website (kept separate from the authenticated workspace).
Route::controller(MarketingController::class)->group(function () {
    Route::get('/', 'home')->name('home');
    Route::get('/features', 'features')->name('marketing.features');
    Route::get('/how-it-works', 'howItWorks')->name('marketing.how-it-works');
    Route::get('/photographers', 'photographers')->name('marketing.photographers');
    Route::get('/guests', 'guests')->name('marketing.guests');
    Route::get('/ai-photo-discovery', 'discovery')->name('marketing.discovery');
    Route::get('/face-recognition', 'biometric')->name('marketing.biometric');
    Route::redirect('/event-galleries', '/group-galleries', 301);
    Route::get('/group-galleries', 'galleries')->name('marketing.galleries');
    Route::get('/solutions', 'solutions')->name('marketing.solutions');
    Route::get('/solutions/photographers', 'solutionPhotographers')->name('solutions.photographers');
    Route::get('/solutions/weddings', 'solutionWeddings')->name('solutions.weddings');
    Route::get('/solutions/parties-and-celebrations', 'solutionCelebrations')->name('solutions.celebrations');
    Route::get('/solutions/corporate-groups', 'solutionCorporate')->name('solutions.corporate');
    Route::get('/solutions/colleges-and-institutions', 'solutionInstitutions')->name('solutions.institutions');
    Route::get('/solutions/conferences-and-communities', 'solutionConferences')->name('solutions.conferences');
    Route::redirect('/solutions/corporate-events', '/solutions/corporate-gatherings', 301);
    Route::redirect('/solutions/private-events', '/solutions/private-gatherings', 301);
    Route::redirect('/solutions/corporate-gatherings', '/solutions/corporate-groups', 301);
    Route::redirect('/solutions/schools-colleges', '/solutions/colleges-and-institutions', 301);
    Route::redirect('/solutions/conferences', '/solutions/conferences-and-communities', 301);
    Route::redirect('/solutions/festivals-concerts', '/solutions/conferences-and-communities', 301);
    Route::redirect('/solutions/private-gatherings', '/solutions/parties-and-celebrations', 301);
    Route::get('/about', 'about')->name('marketing.about');
    Route::get('/contact', 'contact')->name('marketing.contact');
    Route::get('/pricing', 'pricing')->name('pricing');
    Route::get('/blog', 'blog')->name('marketing.blog');
    Route::redirect('/blog/private-event-photo-delivery', '/blog/private-group-photo-delivery', 301);
    Route::get('/blog/{slug}', 'article')->name('marketing.blog.article');
    Route::get('/faqs', 'faqs')->name('marketing.faqs');
    Route::get('/help', 'help')->name('marketing.help');
    Route::get('/join', 'join')->name('marketing.join');
    Route::get('/privacy', 'privacy')->name('marketing.privacy');
    Route::get('/terms', 'terms')->name('marketing.terms');
    Route::get('/refunds', 'refunds')->name('marketing.refunds');
    Route::get('/cookies', 'cookies')->name('marketing.cookies');
    Route::get('/security', 'security')->name('marketing.security');
    Route::get('/biometric-consent', 'biometricConsent')->name('marketing.biometric-consent');
    Route::get('/data-retention', 'retention')->name('marketing.retention');
    Route::get('/data-deletion', 'deletion')->name('marketing.deletion');
    Route::get('/acceptable-use', 'acceptableUse')->name('marketing.acceptable-use');
    Route::get('/copyright', 'copyright')->name('marketing.copyright');
    Route::get('/sitemap.xml', 'sitemap')->name('marketing.sitemap');
    Route::get('/feed.xml', 'feed')->name('marketing.feed');
});
Route::post('/contact', ContactEnquiryController::class)->middleware('throttle:5,10')->name('marketing.contact.store');
Route::redirect('/landing', '/', 301);
Route::redirect('/landing/home', '/', 301);
Route::redirect('/landing/aboutus', '/about', 301);
Route::redirect('/landing/contactus', '/contact', 301);
Route::redirect('/landing/pricing', '/pricing', 301);
Route::redirect('/landing/join', '/join', 301);
Route::get('/favicon.ico', fn() => response()->noContent())->name('favicon');

// Authorized private media delivery (supports authenticated and explicit anonymous access)
Route::get("/media/{mediaAsset:uuid}/{variant}",[MediaDeliveryController::class,"show"])->middleware("throttle:120,1")->name("media.show");

// Guest share link (no auth needed)
Route::get('/g/{group:share_token}', [GuestController::class, 'viewGroup'])->name('guest.group');
Route::get('/g/{group:share_token}/branding-logo', [BusinessBrandingLogoController::class, 'gallery'])->name('guest.branding.logo');
Route::get('/g/{group:share_token}/flipbook', [PublicFlipbookController::class, 'show'])->name('guest.flipbook');
Route::get('/g/{group:share_token}/flipbook-logo', [FlipbookLogoController::class, 'guest'])->name('guest.flipbook.logo');
Route::get('/groups/{group}/find-my-photos', \App\Http\Controllers\FindMyPhotosController::class)->name('biometric.entry');
Route::post('/g/{group:share_token}/selfie', [GuestController::class, 'selfieMatch'])->name('guest.selfie');
Route::get('/team-invitations/{invitation:uuid}/{token}', [TeamInvitationController::class, 'show'])->middleware('throttle:30,1')->name('team-invitations.show');
Route::post('/team-invitations/{invitation:uuid}/{token}/decline', [TeamInvitationController::class, 'decline'])->middleware('throttle:10,1')->name('team-invitations.decline');
Route::get('/invite/{code}', [GuestController::class, 'invite'])->name('invite.show');
Route::get('/join/{token}', [InvitationController::class, 'show'])->name('invitations.show');
Route::post('/join/{token}/decline', [InvitationController::class, 'decline'])->name('invitations.decline');
Route::get('/join-group', [PublicGroupJoinController::class,'show'])->name('groups.join-code');
Route::post('/api/groups/validate-code', [PublicGroupJoinController::class,'validateCode'])->middleware('throttle:8,1')->name('groups.validate-code');

// Auth
Route::middleware('guest')->group(function () {
    Route::get('/login',    [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login',   [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register',[AuthController::class, 'register'])->middleware('throttle:10,1');
    Route::post('/send-otp',   [AuthController::class, 'sendOtp'])->middleware('throttle:10,1')->name('send.otp');
    Route::get('/verify-otp', [AuthController::class, 'showOtp'])->name('otp.show');
    Route::post('/verify-otp', [AuthController::class, 'verifyOtp'])->middleware('throttle:15,1')->name('verify.otp');
    Route::get('/forgot-password', [PasswordResetController::class, 'request'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'email'])->middleware('throttle:5,10')->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'reset'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'update'])->middleware('throttle:5,10')->name('password.update');
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// Authenticated
Route::middleware('auth')->group(function () {
    // Compatibility aliases for previously shared Event-facing bookmarks.
    Route::get('/events', fn () => redirect()->route('groups.index', status: 301))->name('events.index');
    Route::get('/events/create', fn () => redirect()->route('groups.create', status: 301))->name('events.create');
    Route::get('/events/{group}', fn (\App\Models\Group $group) => redirect()->route('groups.show', $group, 301))->name('events.show');
    Route::get('/events/{group}/settings', fn (\App\Models\Group $group) => redirect()->route('groups.settings', $group, 301))->name('events.settings');
    Route::get('/events/{group}/uploads', fn (\App\Models\Group $group) => redirect()->route('groups.operations', $group, 301))->name('events.uploads');
    Route::get('/events/{group}/gallery', fn (\App\Models\Group $group) => redirect()->route('groups.show', $group, 301))->name('events.gallery');
    Route::get('/events/{group}/participants', fn (\App\Models\Group $group) => redirect()->route('groups.members', $group, 301))->name('events.participants');

    Route::get('/onboarding', [OnboardingController::class, 'resume'])->name('onboarding.resume');
    Route::get('/onboarding/account-type', [OnboardingController::class, 'role'])->name('onboarding.role');
    Route::post('/onboarding/account-type', [OnboardingController::class, 'storeRole'])->name('signup.role.store');
    Route::get('/onboarding/selfie', [OnboardingController::class, 'selfie'])->name('onboarding.selfie');
    Route::post('/onboarding/selfie', [OnboardingController::class, 'storeSelfie'])->name('onboarding.selfie.store');
    Route::get('/onboarding/profile', [OnboardingController::class, 'profile'])->name('onboarding.profile');
    Route::post('/onboarding/profile', [OnboardingController::class, 'storeProfile'])->name('onboarding.profile.store');
    Route::get('/welcome/start', [OnboardingController::class, 'welcome'])->name('welcome.start');
    Route::post('/join/{token}', [InvitationController::class, 'accept'])->middleware('throttle:10,1')->name('invitations.accept');
    Route::post('/team-invitations/{invitation:uuid}/{token}/accept', [TeamInvitationController::class, 'accept'])->middleware('throttle:10,1')->name('team-invitations.accept');
    Route::get('/join-group/complete', [PublicGroupJoinController::class,'complete'])->name('groups.join.complete');
    Route::post('/api/groups/join', [PublicGroupJoinController::class,'join'])->middleware('throttle:10,1')->name('groups.join-api');
    Route::get('/join-group/success', [PublicGroupJoinController::class,'success'])->name('groups.join.success');

    Route::get('/dashboard', [LensPicUiController::class, 'dashboard'])->name('dashboard');
    Route::get('/analytics', [\App\Http\Controllers\AnalyticsController::class, 'index'])->middleware('analytics.access:view')->name('analytics');
    Route::get('/analytics/export', [\App\Http\Controllers\AnalyticsController::class, 'export'])->middleware(['analytics.access:export', 'throttle:10,1'])->name('analytics.export');
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/account/notifications', [NotificationController::class, 'index'])->name('notifications');
    Route::get('/notifications/unread-count', [NotificationController::class, 'count'])->name('notifications.unread-count');
    Route::patch('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'read'])->name('notifications.read');
    Route::patch('/notifications/{notification}/unread', [NotificationController::class, 'unread'])->name('notifications.unread');
    Route::delete('/notifications/{notification}', [NotificationController::class, 'destroy'])->name('notifications.destroy');

    // Profile
    Route::get('/profile',         [ProfileController::class, 'show'])->name('profile');
    Route::put('/profile',         [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/photo',  [ProfileController::class, 'updatePhoto'])->name('profile.photo');

    // Groups
    Route::get('/groups',                  [LensPicUiController::class, 'events'])->name('groups.index');
    Route::get('/groups/create',           [LensPicUiController::class, 'create'])->name('groups.create');
    Route::post('/groups',                 [GroupController::class, 'store'])->name('groups.store');
    Route::get('/groups/{group}',          [LensPicUiController::class, 'show'])->name('groups.show');
    Route::get('/groups/{group}/gallery',  [LensPicUiController::class, 'show'])->name('groups.gallery');
    Route::get('/groups/{group}/operations', [LensPicUiController::class, 'operations'])->name('groups.operations');
    Route::get('/groups/{group}/face-reviews', [LensPicUiController::class, 'reviews'])->name('groups.face-reviews');
    Route::get('/groups/{group}/settings', [LensPicUiController::class, 'edit'])->name('groups.settings');
    Route::get('/groups/{group}/settings/{section}', [LensPicUiController::class, 'settingsSection'])
        ->whereIn('section', ['general','participants','privacy','folders','design','downloads','branding','favourites'])
        ->name('groups.settings.section');
    Route::get('/groups/{group}/edit',     [LensPicUiController::class, 'edit'])->name('groups.edit');
    Route::put('/groups/{group}',          [GroupController::class, 'update'])->name('groups.update');
    Route::delete('/groups/{group}',       [GroupController::class, 'destroy'])->name('groups.destroy');
    Route::post('/groups/{group}/cover',   [GroupCoverController::class, 'store'])->middleware('throttle:10,1')->name('groups.cover.store');
    Route::delete('/groups/{group}/cover', [GroupCoverController::class, 'destroy'])->middleware('throttle:10,1')->name('groups.cover.destroy');
    Route::post('/groups/{group}/cover/retry', [GroupCoverController::class, 'retry'])->middleware('throttle:10,1')->name('groups.cover.retry');
    Route::post('/groups/{group}/join',    [GroupController::class, 'join'])->name('groups.join');
    Route::post('/groups/{group}/leave',   [GroupController::class, 'leave'])->name('groups.leave');
    Route::post('/groups/{group}/invite',  [GroupController::class, 'invite'])->name('groups.invite');
    Route::get('/groups/{group}/members',  [LensPicUiController::class, 'members'])->name('groups.members');
    Route::get('/groups/{group}/favorites', [LensPicUiController::class, 'favorites'])->middleware('entitlement:client_favourites')->name('groups.favorites');
    Route::delete('/groups/{group}/members/{user}', [GroupController::class, 'removeMember'])->name('groups.members.remove');
    Route::post('/groups/{group}/regenerate-token', [GroupController::class, 'regenerateToken'])->name('groups.regenerate-token');
    Route::post('/groups/{group}/regenerate-code', [GroupController::class, 'regenerateCode'])->name('groups.regenerate-code');
    Route::post('/groups/{group}/regenerate-invitation', [GroupController::class, 'regenerateInvitation'])->name('groups.regenerate-invitation');
    Route::post('/groups/{group}/revoke-invitation', [GroupController::class, 'revokeInvitation'])->name('groups.revoke-invitation');
    Route::get('/groups/{group}/access-invites', [GroupAccessInviteController::class,'index'])->name('groups.access-invites.index');
    Route::post('/groups/{group}/access-invites', [GroupAccessInviteController::class,'store'])->name('groups.access-invites.store');
    Route::post('/groups/{group}/share-link', [GroupAccessInviteController::class,'shareLink'])->name('groups.share-link');
    Route::patch('/groups/{group}/access-invites/{invite}', [GroupAccessInviteController::class,'update'])->name('groups.access-invites.update');
    Route::post('/groups/{group}/access-invites/{invite}/regenerate', [GroupAccessInviteController::class,'regenerate'])->name('groups.access-invites.regenerate');
    Route::post('/groups/{group}/access-invites/{invite}/revoke', [GroupAccessInviteController::class,'revoke'])->name('groups.access-invites.revoke');
    Route::post('/groups/{group}/access-invites/{invite}/reactivate', [GroupAccessInviteController::class,'reactivate'])->name('groups.access-invites.reactivate');
    Route::get('/groups/{group}/access-invites/{invite}/print', [GroupAccessInviteController::class,'print'])->name('groups.access-invites.print');

    // Photos
    Route::get('/groups/{group}/photos',              [PhotoController::class, 'index'])->name('photos.index');
    Route::get('/groups/{group}/folders',              [FolderController::class, 'index'])->name('folders.index');
    Route::post('/groups/{group}/folders',             [FolderController::class, 'store'])->name('folders.store');
    Route::get('/groups/{group}/folders/{folder}',     [FolderController::class, 'show'])->name('folders.show');
    Route::put('/groups/{group}/folders/{folder}',     [FolderController::class, 'update'])->name('folders.update');
    Route::delete('/groups/{group}/folders/{folder}',  [FolderController::class, 'destroy'])->name('folders.destroy');
    Route::post('/groups/{group}/folders/{folder}/transfer', [FolderController::class, 'transferPhotos'])->name('folders.transfer');
    Route::post('/groups/{group}/folders/{folder}/cover', [FolderController::class, 'setCoverPhoto'])->name('folders.cover');
    Route::post('/groups/{group}/photos',             [QueuedUploadController::class, 'store'])->name('photos.store');
    Route::get('/processing/batches/{batch:uuid}', [ProcessingController::class, 'show'])->name('processing.show');
    Route::get('/groups/{group}/processing/active', [ProcessingController::class, 'active'])->name('processing.active');
    Route::post('/processing/batches/{batch:uuid}/cancel', [ProcessingController::class, 'cancel'])->name('processing.cancel');
    Route::post('/processing/batches/{batch:uuid}/retry', [ProcessingController::class, 'retry'])->name('processing.retry');
    Route::post('/groups/{group}/exports', [MediaExportController::class, 'store'])->middleware('entitlement:bulk_download')->name('exports.store');
    Route::get('/exports/{export:uuid}', [MediaExportController::class, 'show'])->name('exports.show');
    Route::post('/exports/{export:uuid}/cancel', [MediaExportController::class, 'cancel'])->name('exports.cancel');
    Route::get('/exports/{export:uuid}/download', [MediaExportController::class, 'download'])->name('exports.download');
    Route::get('/groups/{group}/photos/{photo}',      [LensPicUiController::class, 'photo'])->name('photos.show');
    Route::post('/groups/{group}/photos/{photo}/folder', [PhotoController::class, 'assignToFolder'])->name('photos.folder');
    Route::post('/groups/{group}/photos/bulk-folder', [PhotoController::class, 'bulkAssignFolder'])->name('photos.bulk-folder');
    Route::delete('/groups/{group}/photos/{photo}',   [PhotoController::class, 'destroy'])->name('photos.destroy');
    Route::post('/groups/{group}/photos/{photo}/restore', [PhotoController::class, 'restore'])->name('photos.restore');
    Route::post('/groups/{group}/photos/{photo}/like',[PhotoController::class, 'toggleLike'])->middleware('entitlement:client_favourites')->name('photos.like');
    Route::post('/groups/{group}/photos/{photo}/favourite',[PhotoController::class, 'favourite'])->middleware('entitlement:client_favourites')->name('photos.favourite.store');
    Route::delete('/groups/{group}/photos/{photo}/favourite',[PhotoController::class, 'unfavourite'])->middleware('entitlement:client_favourites')->name('photos.favourite.destroy');
    Route::get('/groups/{group}/photos/{photo}/download', [PhotoController::class, 'download'])->name('photos.download');
    Route::get('/groups/{group}/my-photos',           [PhotoController::class, 'myPhotos'])->name('photos.mine');
    Route::post('/groups/{group}/bulk-download',      [PhotoController::class, 'bulkDownload'])->middleware('entitlement:bulk_download')->name('photos.bulk-download');

    // Face
    Route::get('/groups/{group}/selfie',    [FaceController::class, 'showSelfie'])->name('face.show');
    Route::post('/groups/{group}/selfie',   [FaceController::class, 'uploadSelfie'])->name('face.selfie');
    Route::post('/groups/{group}/recognize',[FaceController::class, 'recognize'])->name('face.recognize');
    Route::post('/groups/{group}/biometric-consents', [BiometricController::class, 'consent'])->name('biometric.consent');
    Route::get('/groups/{group}/discover', [LensPicUiController::class, 'biometric'])->middleware('entitlement:find_my_photos')->name('biometric.page');
    Route::post('/groups/{group}/face-searches', [BiometricController::class, 'search'])->middleware('entitlement:find_my_photos')->name('biometric.search');
    Route::get('/face-searches/{s:uuid}', [BiometricController::class, 'show'])->name('biometric.show');
    Route::post('/face-searches/{s:uuid}/cancel', [BiometricController::class, 'cancel'])->name('biometric.cancel');
    Route::delete('/biometric-consents/{c:uuid}', [BiometricController::class, 'withdraw'])->name('biometric.withdraw');
    Route::get('/face-searches/{s:uuid}/my-photos', [BiometricComplianceController::class, 'myPhotos'])->name('biometric.my-photos');
    Route::get('/face-searches/{s:uuid}/results', [LensPicUiController::class, 'myPhotos'])->name('biometric.results-page');
    Route::post('/face-matches/{m}/reject', [BiometricComplianceController::class, 'reject'])->name('biometric.reject');
    Route::post('/face-matches/{m}/review', [BiometricComplianceController::class, 'review'])->name('biometric.review');
    Route::post('/biometric-deletion-requests', [BiometricComplianceController::class, 'deletion'])->name('biometric.deletion');
    Route::get('/biometric-deletion-requests/{d:uuid}', [BiometricComplianceController::class, 'deletionStatus'])->name('biometric.deletion-status');
    Route::post('/groups/{g}/face-index-runs', [BiometricComplianceController::class, 'startIndex'])->name('biometric.index-start');
    Route::get('/face-index-runs/{run:uuid}', [BiometricComplianceController::class, 'indexStatus'])->name('biometric.index-status');
});

// Super Admin Routes
Route::middleware(['auth','platform.admin:super'])->prefix('super-admin')->name('super-admin.')->group(function () {
    Route::redirect('/', '/super-admin/dashboard');
    Route::get('/whatsapp-deliveries', fn () => view('super-admin.whatsapp-deliveries', ['messages'=>\App\Models\WhatsAppMessage::latest('id')->paginate(30)]))->name('whatsapp-deliveries');
    Route::get('/dashboard', [\App\Http\Controllers\SuperAdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/roles-permissions', [\App\Http\Controllers\SuperAdminRoleAssignmentController::class, 'roles'])->middleware('can:roles.view')->name('roles.index');
    Route::get('/roles-permissions/permissions', [\App\Http\Controllers\SuperAdminRoleAssignmentController::class, 'permissions'])->middleware('can:roles.view')->name('permissions.index');
    Route::get('/role-assignments', [\App\Http\Controllers\SuperAdminRoleAssignmentController::class, 'index'])->middleware('can:roles.view')->name('role-assignments.index');
    Route::redirect('/role-assignments/history', '/super-admin/role-history');
    Route::get('/role-history', [\App\Http\Controllers\SuperAdminRoleAssignmentController::class, 'history'])->middleware('can:roles.view')->name('role-history.index');
    Route::get('/role-assignments/create', [\App\Http\Controllers\SuperAdminRoleAssignmentController::class, 'create'])->middleware('can:roles.assign')->name('role-assignments.create');
    Route::post('/role-assignments', [\App\Http\Controllers\SuperAdminRoleAssignmentController::class, 'store'])->middleware(['can:roles.assign','throttle:10,1'])->name('role-assignments.store');
    Route::get('/role-assignments/{user}/edit', [\App\Http\Controllers\SuperAdminRoleAssignmentController::class, 'edit'])->middleware('can:roles.view')->name('role-assignments.edit');
    Route::put('/role-assignments/{user}', [\App\Http\Controllers\SuperAdminRoleAssignmentController::class, 'update'])->middleware(['can:roles.assign','throttle:20,1'])->name('role-assignments.update');
    Route::get('/plans', [\App\Http\Controllers\SuperAdminSubscriptionPlanController::class,'index'])->name('plans.index');
    Route::get('/features', [\App\Http\Controllers\SuperAdminSubscriptionPlanController::class,'features'])->name('plans.features');
    Route::put('/features/{feature}', [\App\Http\Controllers\SuperAdminSubscriptionPlanController::class,'updateFeature'])->middleware('throttle:30,1')->name('plans.features.update');
    Route::get('/payments', [\App\Http\Controllers\SuperAdminSubscriptionPlanController::class,'payments'])->name('plans.payments');
    Route::get('/plans/{plan}/edit', [\App\Http\Controllers\SuperAdminSubscriptionPlanController::class,'edit'])->name('plans.edit');
    Route::put('/plans/{plan}', [\App\Http\Controllers\SuperAdminSubscriptionPlanController::class,'update'])->middleware('throttle:20,1')->name('plans.update');
    Route::post('/plans/{plan}/toggle', [\App\Http\Controllers\SuperAdminSubscriptionPlanController::class,'toggle'])->middleware('throttle:20,1')->name('plans.toggle');
    Route::get('/plans/{plan}/preview', [\App\Http\Controllers\SuperAdminSubscriptionPlanController::class,'preview'])->name('plans.preview');
    Route::get('/plans/{plan}/audits', [\App\Http\Controllers\SuperAdminSubscriptionPlanController::class,'audits'])->name('plans.audits');

    // Users
    Route::get('/users', [\App\Http\Controllers\SuperAdminController::class, 'users'])->name('users');
    Route::get('/users/{user}', [\App\Http\Controllers\SuperAdminController::class, 'showUser'])->name('users.show');
    Route::get('/users/{user}/edit', [\App\Http\Controllers\SuperAdminController::class, 'editUser'])->name('users.edit');
    Route::put('/users/{user}', [\App\Http\Controllers\SuperAdminController::class, 'updateUser'])->name('users.update');
    Route::post('/users/{user}/promote-admin', [\App\Http\Controllers\SuperAdminController::class, 'promoteToAdmin'])->name('users.promote-admin');
    Route::post('/users/{user}/promote-super-admin', [\App\Http\Controllers\SuperAdminController::class, 'promoteToSuperAdmin'])->name('users.promote-super-admin');
    Route::post('/users/{user}/demote', [\App\Http\Controllers\SuperAdminController::class, 'demoteToUser'])->name('users.demote');
    Route::delete('/users/{user}', [\App\Http\Controllers\SuperAdminController::class, 'deleteUser'])->name('users.delete');
    Route::post('/users/{user}/suspend', [\App\Http\Controllers\SuperAdminController::class, 'suspendUser'])->name('users.suspend');
    Route::post('/users/{user}/ban', [\App\Http\Controllers\SuperAdminController::class, 'banUser'])->name('users.ban');
    Route::post('/users/{user}/reactivate', [\App\Http\Controllers\SuperAdminController::class, 'reactivateUser'])->name('users.reactivate');

    // Subscriptions
    Route::get('/subscriptions', [\App\Http\Controllers\SuperAdminController::class, 'subscriptions'])->name('subscriptions');
    Route::get('/subscriptions/{subscription}', [\App\Http\Controllers\SuperAdminController::class, 'showSubscription'])->name('subscriptions.show');
    Route::put('/subscriptions/{subscription}', [\App\Http\Controllers\SuperAdminController::class, 'updateSubscription'])->name('subscriptions.update');
    Route::post('/subscriptions/{subscription}/suspend', [\App\Http\Controllers\SuperAdminController::class, 'suspendSubscription'])->name('subscriptions.suspend');
    Route::post('/subscriptions/{subscription}/activate', [\App\Http\Controllers\SuperAdminController::class, 'activateSubscription'])->name('subscriptions.activate');
    Route::post('/subscriptions/{subscription}/cancel', [\App\Http\Controllers\SuperAdminController::class, 'cancelSubscription'])->name('subscriptions.cancel');

    // Groups
    Route::get('/groups', [\App\Http\Controllers\SuperAdminController::class, 'groups'])->name('groups');
    Route::get('/groups/{group}', [\App\Http\Controllers\SuperAdminController::class, 'showGroup'])->name('groups.show');
    Route::delete('/groups/{group}', [\App\Http\Controllers\SuperAdminController::class, 'deleteGroup'])->name('groups.delete');
    Route::post('/groups/{group}/toggle', [\App\Http\Controllers\SuperAdminController::class, 'toggleGroupStatus'])->name('groups.toggle');

    // Reports
    Route::get('/reports', [\App\Http\Controllers\SuperAdminController::class, 'reports'])->name('reports');
    Route::post('/reports/export', [\App\Http\Controllers\SuperAdminController::class, 'exportReport'])->name('reports.export');
});

// Admin Routes (legacy)
Route::middleware(['auth','platform.admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/',             [\App\Http\Controllers\AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/users',        [\App\Http\Controllers\AdminController::class, 'users'])->name('users');
    Route::get('/users/{user}/edit',   [\App\Http\Controllers\AdminController::class, 'editUser'])->name('users.edit');
    Route::put('/users/{user}',        [\App\Http\Controllers\AdminController::class, 'updateUser'])->name('users.update');
    Route::delete('/users/{user}',     [\App\Http\Controllers\AdminController::class, 'deleteUser'])->name('users.delete');
    Route::get('/groups',              [\App\Http\Controllers\AdminController::class, 'groups'])->name('groups');
    Route::delete('/groups/{group}',   [\App\Http\Controllers\AdminController::class, 'deleteGroup'])->name('groups.delete');
    Route::post('/groups/{group}/toggle', [\App\Http\Controllers\AdminController::class, 'toggleGroupStatus'])->name('groups.toggle');
    Route::get('/photos',              [\App\Http\Controllers\AdminController::class, 'photos'])->name('photos');
    Route::delete('/photos/{photo}',   [\App\Http\Controllers\AdminController::class, 'deletePhoto'])->name('photos.delete');
});

Route::post('/billing/razorpay/webhook', [BillingController::class, 'webhook'])->name('billing.razorpay.webhook');
Route::post('/billing/razorpay/wallet-webhook', App\Http\Controllers\WalletWebhookController::class)->name('billing.razorpay.wallet-webhook');

Route::middleware('auth')->group(function () {
    Route::post('/billing/razorpay/order', [BillingController::class, 'createOrder'])->name('billing.razorpay.order');
    Route::post('/billing/razorpay/addon-order', [BillingController::class, 'createAddonOrder'])->middleware('throttle:10,1')->name('billing.razorpay.addon-order');
    Route::post('/billing/razorpay/verify', [BillingController::class, 'verify'])->name('billing.razorpay.verify');
    Route::get('/dashboard/subscription/payment/status/{order:uuid}', [\App\Http\Controllers\PaymentStatusController::class, 'show'])->name('billing.payment.status');
    Route::get('/dashboard/subscription/payment/success/{order:uuid}', [\App\Http\Controllers\PaymentStatusController::class, 'show'])->name('billing.payment.success');
    Route::get('/dashboard/subscription/payment/receipt/{order:uuid}', [\App\Http\Controllers\PaymentStatusController::class, 'receipt'])->name('billing.payment.receipt');
    Route::post('/billing/razorpay/orders/{order:uuid}/cancel', [\App\Http\Controllers\PaymentStatusController::class, 'cancel'])->name('billing.razorpay.cancel');
    Route::post('/billing/subscription/schedule-free', [BillingController::class, 'scheduleFree'])->name('billing.subscription.schedule-free');
});

// Profile Settings (extended)
Route::middleware('auth')->group(function () {
    Route::get('/settings',                    fn()=>redirect()->route('settings.profile'))->name('settings');
    Route::get('/settings/profile',            [App\Http\Controllers\SettingsController::class, 'profile'])->name('settings.profile');
    Route::post('/settings/profile',           [App\Http\Controllers\SettingsController::class, 'updateProfile'])->name('settings.profile.update');
    Route::put('/settings/password',           [App\Http\Controllers\SettingsController::class, 'updatePassword'])->middleware('throttle:5,1')->name('settings.password.update');
    Route::get('/settings/account-preferences',[App\Http\Controllers\SettingsController::class, 'preferences'])->name('settings.account-preferences');
    Route::put('/settings/account-preferences',[App\Http\Controllers\SettingsController::class, 'updatePreferences'])->name('settings.account-preferences.update');
    Route::get('/settings/preferences',        fn()=>redirect()->route('settings.account-preferences'))->name('settings.preferences');
    Route::put('/settings/preferences',        [App\Http\Controllers\SettingsController::class, 'updatePreferences'])->name('settings.preferences.update');
    Route::get('/settings/business-branding', [App\Http\Controllers\SettingsController::class, 'branding'])->middleware('entitlement:business_branding')->name('settings.business-branding');
    Route::post('/settings/business-branding',[App\Http\Controllers\SettingsController::class, 'updateBranding'])->middleware('entitlement:business_branding')->name('settings.business-branding.update');
    Route::get('/settings/business-branding/logo',[BusinessBrandingLogoController::class, 'settings'])->name('settings.business-branding.logo');
    Route::get('/settings/branding',           fn()=>redirect()->route('settings.business-branding'))->name('settings.branding');
    Route::post('/settings/branding',          [App\Http\Controllers\SettingsController::class, 'updateBranding'])->middleware('entitlement:business_branding')->name('settings.branding.update');
    Route::get('/settings/branding/logo',      [BusinessBrandingLogoController::class, 'settings'])->name('settings.branding.logo');
    Route::get('/settings/watermark',          [WatermarkSettingsController::class, 'edit'])->middleware('entitlement:watermark')->name('settings.watermark');
    Route::put('/settings/watermark',          [WatermarkSettingsController::class, 'update'])->middleware('entitlement:watermark')->name('settings.watermark.update');
    Route::get('/settings/watermark/logo',     [WatermarkLogoController::class, 'show'])->name('settings.watermark.logo');
    Route::get('/settings/team',               fn()=>redirect()->route('settings.team-login'))->name('settings.team');
    Route::get('/settings/team-login',         [TeamLoginController::class, 'index'])->middleware('entitlement:team_login')->name('settings.team-login');
    Route::get('/settings/flipbook',           [FlipbookSettingsController::class, 'edit'])->name('settings.flipbook');
    Route::put('/settings/flipbook',           [FlipbookSettingsController::class, 'update'])->name('settings.flipbook.update');
    Route::get('/settings/flipbook/logo',      [FlipbookLogoController::class, 'settings'])->name('settings.flipbook.logo');
    Route::post('/settings/team-login/invitations', [TeamLoginController::class, 'store'])->middleware(['throttle:10,1','entitlement:team_login'])->name('settings.team-login.invitations.store');
    Route::post('/settings/team-login/invitations/{invitation:uuid}/resend', [TeamLoginController::class, 'resend'])->middleware(['throttle:6,1','entitlement:team_login'])->name('settings.team-login.invitations.resend');
    Route::post('/settings/team-login/invitations/{invitation:uuid}/revoke', [TeamLoginController::class, 'revoke'])->middleware('entitlement:team_login')->name('settings.team-login.invitations.revoke');
    Route::patch('/settings/team-login/members/{membership:uuid}/role', [TeamLoginController::class, 'role'])->middleware('entitlement:team_login')->name('settings.team-login.members.role');
    Route::patch('/settings/team-login/members/{membership:uuid}/status', [TeamLoginController::class, 'status'])->middleware('entitlement:team_login')->name('settings.team-login.members.status');
    Route::delete('/settings/team-login/members/{membership:uuid}', [TeamLoginController::class, 'destroy'])->middleware('entitlement:team_login')->name('settings.team-login.members.destroy');
    Route::get('/settings/subscription',       [App\Http\Controllers\SettingsController::class, 'subscription'])->name('settings.subscription');
    Route::get('/settings/privacy',            [App\Http\Controllers\SettingsController::class, 'privacy'])->name('settings.privacy');
    Route::get('/settings/portfolio',          [App\Http\Controllers\PortfolioController::class, 'edit'])->middleware('entitlement:portfolio_website')->name('settings.portfolio');
    Route::put('/settings/portfolio',          [App\Http\Controllers\PortfolioController::class, 'update'])->middleware('entitlement:portfolio_website')->name('settings.portfolio.update');
    Route::get('/settings/portfolio/preview',  [App\Http\Controllers\PortfolioController::class, 'preview'])->middleware('entitlement:portfolio_website')->name('settings.portfolio.preview');
    Route::get('/settings/portfolio/cover',    [App\Http\Controllers\PortfolioController::class, 'cover'])->name('settings.portfolio.cover');
    Route::get('/settings/portfolio/assets/{a}',[App\Http\Controllers\PortfolioController::class, 'asset'])->name('settings.portfolio.asset');
    Route::get('/settings/portfolio/images/{i}',[App\Http\Controllers\PortfolioController::class, 'image'])->name('settings.portfolio.image');
    Route::get('/settings/wallet',             [App\Http\Controllers\WalletController::class, 'index'])->name('settings.wallet');
    Route::post('/settings/wallet/top-ups',    [App\Http\Controllers\WalletController::class, 'store'])->middleware('throttle:10,1')->name('settings.wallet.top-ups.store');
    Route::get('/settings/wallet/top-ups/{t}', [App\Http\Controllers\WalletController::class, 'show'])->name('settings.wallet.top-ups.show');
    Route::get('/settings/transactions',       [App\Http\Controllers\TransactionsController::class, 'index'])->name('settings.transactions');
    Route::get('/settings/transactions/export',[App\Http\Controllers\TransactionsController::class, 'export'])->middleware('throttle:10,1')->name('settings.transactions.export');
    Route::get('/settings/transactions/{t}',   [App\Http\Controllers\TransactionsController::class, 'show'])->name('settings.transactions.show');
});
Route::get('/portfolio/{p:slug}',[App\Http\Controllers\PortfolioController::class,'public'])->name('portfolio.show');
Route::get('/portfolio/{p:slug}/cover',[App\Http\Controllers\PortfolioController::class,'cover'])->name('portfolio.cover');
Route::get('/portfolio/{p:slug}/logo',App\Http\Controllers\PortfolioLogoController::class)->name('portfolio.logo');
Route::get('/portfolio/{portfolio:slug}/images/{i}',[App\Http\Controllers\PortfolioController::class,'image'])->name('portfolio.image');

Route::get('/webhooks/whatsapp', [\App\Http\Controllers\WhatsAppWebhookController::class, 'verify'])->middleware('throttle:60,1')->name('whatsapp.webhook.verify');
Route::post('/webhooks/whatsapp', [\App\Http\Controllers\WhatsAppWebhookController::class, 'receive'])->name('whatsapp.webhook');
