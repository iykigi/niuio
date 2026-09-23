<?php

use App\Http\Controllers\Admin\ImpersonationController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Files\DownloadController;
use App\Http\Controllers\Releases\PublicAppController;
use App\Http\Controllers\Releases\ReleaseDownloadController;
use App\Http\Controllers\SuspendedController;
use App\Livewire\Admin\AnnouncementsManager;
use App\Livewire\Admin\Overview as AdminOverview;
use App\Livewire\Admin\ReleaseArchive;
use App\Livewire\Admin\RolesManager;
use App\Livewire\Admin\ServerDetail;
use App\Livewire\Admin\ServersManager;
use App\Livewire\Admin\SettingsManager;
use App\Livewire\Admin\SupportInbox;
use App\Livewire\Admin\UserDetail;
use App\Livewire\Admin\UsersManager;
use App\Livewire\Applications\ApplicationDetail;
use App\Livewire\Applications\ApplicationsIndex;
use App\Livewire\Auth\ForgotPassword;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use App\Livewire\Auth\ResetPassword;
use App\Livewire\Auth\TwoFactorChallenge;
use App\Livewire\Dashboard\Home;
use App\Livewire\Databases\DatabaseDetail;
use App\Livewire\Databases\DatabasesIndex;
use App\Livewire\Domains\DomainDetail;
use App\Livewire\Domains\DomainsIndex;
use App\Livewire\Onboarding\Welcome as OnboardingWelcome;
use App\Livewire\Security\SecurityCenter;
use App\Livewire\Settings\AccountSettings;
use App\Livewire\Sites\CreateSite;
use App\Livewire\Sites\SiteShow;
use App\Livewire\Sites\SitesIndex;
use App\Livewire\Support\TicketDetail;
use App\Livewire\Support\TicketsIndex;
use App\Livewire\Usage\UsageOverview;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Support\Facades\Route;

Route::view('/', 'marketing.home')->name('home');
Route::view('/features', 'marketing.features')->name('features');

// --- Public app directory and downloads --------------------------------------
// Only ever serves the live build for a platform; unpublished platforms are
// absent from the page and 404 on the download route.

Route::get('/apps', [PublicAppController::class, 'index'])->name('apps.index');
Route::get('/apps/{application}', [PublicAppController::class, 'show'])->name('apps.show');
Route::get('/apps/{application}/download/{platform}', ReleaseDownloadController::class)
    ->middleware('throttle:60,1')
    ->name('apps.download');

// --- Guest-only auth routes ---------------------------------------------------

Route::middleware('guest')->group(function () {
    Route::get('/register', Register::class)->name('register');
    Route::get('/login', Login::class)->name('login');
    Route::get('/forgot-password', ForgotPassword::class)->name('password.request');
    Route::get('/reset-password/{token}', ResetPassword::class)->name('password.reset');
});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout')->middleware('auth');

// --- Two-factor challenge (authenticated, but not yet 2FA-cleared) -----------

Route::middleware('auth')->group(function () {
    Route::get('/two-factor-challenge', TwoFactorChallenge::class)->name('two-factor.challenge');
    Route::get('/email/verify', EmailVerificationController::class)->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware('signed')->name('verification.verify');
    Route::post('/email/verify/resend', [EmailVerificationController::class, 'resend'])
        ->middleware('throttle:6,1')->name('verification.send');
    Route::get('/suspended', SuspendedController::class)->name('suspended');
});

// --- Authenticated, active-account, 2FA-cleared application ------------------

Route::middleware(['auth', 'verified', 'two-factor', 'active', AuthenticateSession::class])->group(function () {
    Route::get('/dashboard', Home::class)->name('dashboard');
    Route::get('/onboarding', OnboardingWelcome::class)->name('onboarding');

    Route::get('/sites', SitesIndex::class)->name('sites.index');
    Route::get('/sites/create', CreateSite::class)->name('sites.create');
    Route::get('/sites/{site}/{tab?}', SiteShow::class)->name('sites.show');

    Route::get('/domains', DomainsIndex::class)->name('domains.index');
    Route::get('/domains/{domain}/{tab?}', DomainDetail::class)->name('domains.show');

    Route::get('/databases', DatabasesIndex::class)->name('databases.index');
    Route::get('/databases/{database}/{tab?}', DatabaseDetail::class)->name('databases.show');

    Route::get('/applications', ApplicationsIndex::class)->name('applications.index');
    Route::get('/applications/{application}/{tab?}', ApplicationDetail::class)->name('applications.show');
    Route::get('/releases/{release}/download', [ReleaseDownloadController::class, 'build'])->name('releases.download');

    Route::get('/usage', UsageOverview::class)->name('usage');
    Route::get('/security', SecurityCenter::class)->name('security');
    Route::get('/settings', AccountSettings::class)->name('settings');

    Route::get('/support', TicketsIndex::class)->name('support.index');
    Route::get('/support/{ticket}', TicketDetail::class)->name('support.show');

    Route::get('/files/{site}/download', DownloadController::class)->name('files.download');

    Route::post('/impersonate/stop', [ImpersonationController::class, 'stop'])->name('impersonate.stop');

    // --- Admin panel ------------------------------------------------------
    Route::prefix('admin')->name('admin.')->middleware('role:Super Admin|Admin|Support|Moderator')->group(function () {
        Route::get('/', AdminOverview::class)->name('overview');
        Route::get('/users', UsersManager::class)->name('users.index');
        Route::get('/users/{user}', UserDetail::class)->name('users.show');
        Route::post('/users/{target}/impersonate', [ImpersonationController::class, 'start'])->name('users.impersonate');
        Route::get('/servers', ServersManager::class)->name('servers.index');
        Route::get('/servers/{server}', ServerDetail::class)->name('servers.show');
        Route::get('/settings', SettingsManager::class)->name('settings');
        Route::get('/releases', ReleaseArchive::class)->name('releases.archive');
        Route::get('/announcements', AnnouncementsManager::class)->name('announcements');
        Route::get('/support', SupportInbox::class)->name('support.index');
        Route::get('/roles', RolesManager::class)->name('roles');
    });
});
