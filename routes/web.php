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

// Landing
Route::get('/', fn() => view('welcome'))->name('home');

// Authorized private media delivery (supports authenticated and explicit anonymous access)
Route::get("/media/{mediaAsset:uuid}/{variant}",[MediaDeliveryController::class,"show"])->middleware("throttle:120,1")->name("media.show");

// Guest share link (no auth needed)
Route::get('/g/{group:share_token}', [GuestController::class, 'viewGroup'])->name('guest.group');
Route::post('/g/{group:share_token}/selfie', [GuestController::class, 'selfieMatch'])->name('guest.selfie');
Route::get('/invite/{code}', [GuestController::class, 'invite'])->name('invite.show');
Route::get('/join/{token}', [InvitationController::class, 'show'])->name('invitations.show');
Route::get('/join-group', [PublicGroupJoinController::class,'show'])->name('groups.join-code');
Route::post('/api/groups/validate-code', [PublicGroupJoinController::class,'validateCode'])->middleware('throttle:8,1')->name('groups.validate-code');

// Auth
Route::middleware('guest')->group(function () {
    Route::get('/login',    [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login',   [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register',[AuthController::class, 'register']);
    Route::post('/send-otp',   [AuthController::class, 'sendOtp'])->name('send.otp');
    Route::get('/verify-otp', [AuthController::class, 'showOtp'])->name('otp.show');
    Route::post('/verify-otp', [AuthController::class, 'verifyOtp'])->name('verify.otp');
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// Authenticated
Route::middleware('auth')->group(function () {

    Route::get('/onboarding', [OnboardingController::class, 'resume'])->name('onboarding.resume');
    Route::get('/onboarding/account-type', [OnboardingController::class, 'role'])->name('onboarding.role');
    Route::post('/onboarding/account-type', [OnboardingController::class, 'storeRole'])->name('signup.role.store');
    Route::get('/onboarding/selfie', [OnboardingController::class, 'selfie'])->name('onboarding.selfie');
    Route::post('/onboarding/selfie', [OnboardingController::class, 'storeSelfie'])->name('onboarding.selfie.store');
    Route::get('/onboarding/profile', [OnboardingController::class, 'profile'])->name('onboarding.profile');
    Route::post('/onboarding/profile', [OnboardingController::class, 'storeProfile'])->name('onboarding.profile.store');
    Route::get('/welcome/start', [OnboardingController::class, 'welcome'])->name('welcome.start');
    Route::post('/join/{token}', [InvitationController::class, 'accept'])->middleware('throttle:10,1')->name('invitations.accept');
    Route::get('/join-group/complete', [PublicGroupJoinController::class,'complete'])->name('groups.join.complete');
    Route::post('/api/groups/join', [PublicGroupJoinController::class,'join'])->middleware('throttle:10,1')->name('groups.join-api');
    Route::get('/join-group/success', [PublicGroupJoinController::class,'success'])->name('groups.join.success');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Profile
    Route::get('/profile',         [ProfileController::class, 'show'])->name('profile');
    Route::put('/profile',         [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/photo',  [ProfileController::class, 'updatePhoto'])->name('profile.photo');

    // Groups
    Route::get('/groups',                  [GroupController::class, 'index'])->name('groups.index');
    Route::get('/groups/create',           [GroupController::class, 'create'])->name('groups.create');
    Route::post('/groups',                 [GroupController::class, 'store'])->name('groups.store');
    Route::get('/groups/{group}',          [GroupController::class, 'show'])->name('groups.show');
    Route::get('/groups/{group}/settings', [GroupController::class, 'settings'])->name('groups.settings');
    Route::get('/groups/{group}/edit',     [GroupController::class, 'edit'])->name('groups.edit');
    Route::put('/groups/{group}',          [GroupController::class, 'update'])->name('groups.update');
    Route::delete('/groups/{group}',       [GroupController::class, 'destroy'])->name('groups.destroy');
    Route::post('/groups/{group}/join',    [GroupController::class, 'join'])->name('groups.join');
    Route::post('/groups/{group}/leave',   [GroupController::class, 'leave'])->name('groups.leave');
    Route::post('/groups/{group}/invite',  [GroupController::class, 'invite'])->name('groups.invite');
    Route::get('/groups/{group}/members',  [GroupController::class, 'members'])->name('groups.members');
    Route::delete('/groups/{group}/members/{user}', [GroupController::class, 'removeMember'])->name('groups.members.remove');
    Route::post('/groups/{group}/regenerate-token', [GroupController::class, 'regenerateToken'])->name('groups.regenerate-token');
    Route::post('/groups/{group}/regenerate-code', [GroupController::class, 'regenerateCode'])->name('groups.regenerate-code');
    Route::post('/groups/{group}/regenerate-invitation', [GroupController::class, 'regenerateInvitation'])->name('groups.regenerate-invitation');
    Route::post('/groups/{group}/revoke-invitation', [GroupController::class, 'revokeInvitation'])->name('groups.revoke-invitation');
    Route::get('/groups/{group}/access-invites', [GroupAccessInviteController::class,'index'])->name('groups.access-invites.index');
    Route::post('/groups/{group}/access-invites', [GroupAccessInviteController::class,'store'])->name('groups.access-invites.store');
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
    Route::post('/groups/{group}/photos',             [PhotoController::class, 'store'])->name('photos.store');
    Route::get('/groups/{group}/photos/{photo}',      [PhotoController::class, 'show'])->name('photos.show');
    Route::post('/groups/{group}/photos/{photo}/folder', [PhotoController::class, 'assignToFolder'])->name('photos.folder');
    Route::post('/groups/{group}/photos/bulk-folder', [PhotoController::class, 'bulkAssignFolder'])->name('photos.bulk-folder');
    Route::delete('/groups/{group}/photos/{photo}',   [PhotoController::class, 'destroy'])->name('photos.destroy');
    Route::post('/groups/{group}/photos/{photo}/like',[PhotoController::class, 'toggleLike'])->name('photos.like');
    Route::get('/groups/{group}/photos/{photo}/download', [PhotoController::class, 'download'])->name('photos.download');
    Route::get('/groups/{group}/my-photos',           [PhotoController::class, 'myPhotos'])->name('photos.mine');
    Route::post('/groups/{group}/bulk-download',      [PhotoController::class, 'bulkDownload'])->name('photos.bulk-download');

    // Face
    Route::get('/groups/{group}/selfie',    [FaceController::class, 'showSelfie'])->name('face.show');
    Route::post('/groups/{group}/selfie',   [FaceController::class, 'uploadSelfie'])->name('face.selfie');
    Route::post('/groups/{group}/recognize',[FaceController::class, 'recognize'])->name('face.recognize');
});

// Super Admin Routes
Route::middleware(['auth','platform.admin:super'])->prefix('super-admin')->name('super-admin.')->group(function () {
    Route::get('/', [\App\Http\Controllers\SuperAdminController::class, 'dashboard'])->name('dashboard');

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

// Pricing page
Route::get('/pricing', fn() => view('pricing'))->name('pricing');
Route::post('/billing/razorpay/webhook', [BillingController::class, 'webhook'])->name('billing.razorpay.webhook');

Route::middleware('auth')->group(function () {
    Route::post('/billing/razorpay/order', [BillingController::class, 'createOrder'])->name('billing.razorpay.order');
    Route::post('/billing/razorpay/verify', [BillingController::class, 'verify'])->name('billing.razorpay.verify');
});

// Profile Settings (extended)
Route::middleware('auth')->group(function () {
    Route::get('/settings',                    [App\Http\Controllers\SettingsController::class, 'index'])->name('settings');
    Route::get('/settings/profile',            [App\Http\Controllers\SettingsController::class, 'profile'])->name('settings.profile');
    Route::post('/settings/profile',           [App\Http\Controllers\SettingsController::class, 'updateProfile'])->name('settings.profile.update');
    Route::get('/settings/branding',           [App\Http\Controllers\SettingsController::class, 'branding'])->name('settings.branding');
    Route::post('/settings/branding',          [App\Http\Controllers\SettingsController::class, 'updateBranding'])->name('settings.branding.update');
    Route::get('/settings/watermark',          [App\Http\Controllers\SettingsController::class, 'watermark'])->name('settings.watermark');
    Route::post('/settings/watermark',         [App\Http\Controllers\SettingsController::class, 'updateWatermark'])->name('settings.watermark.update');
    Route::get('/settings/team',               [App\Http\Controllers\SettingsController::class, 'team'])->name('settings.team');
    Route::get('/settings/subscription',       [App\Http\Controllers\SettingsController::class, 'subscription'])->name('settings.subscription');
    Route::get('/settings/portfolio',          [App\Http\Controllers\SettingsController::class, 'portfolio'])->name('settings.portfolio');
    Route::get('/settings/wallet',             [App\Http\Controllers\SettingsController::class, 'wallet'])->name('settings.wallet');
    Route::get('/settings/transactions',       [App\Http\Controllers\SettingsController::class, 'transactions'])->name('settings.transactions');
});
