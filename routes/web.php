<?php

use App\Http\Controllers\Admin\AppointmentController;
use App\Http\Controllers\Admin\ConversationController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\PatientController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Platform\DashboardController as PlatformDashboardController;
use App\Http\Controllers\Platform\OrganizationsController as PlatformOrganizationsController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Landing');
})->name('home');

Route::get('/dashboard', function (Illuminate\Http\Request $request) {
    return $request->user()->isSuperAdmin()
        ? redirect()->route('platform.dashboard')
        : redirect()->route('admin.dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/appointments', [AppointmentController::class, 'index'])->name('appointments.index');
    Route::get('/conversations', [ConversationController::class, 'index'])->name('conversations.index');
    Route::get('/conversations/{conversation}', [ConversationController::class, 'show'])->name('conversations.show');
    Route::get('/patients', [PatientController::class, 'index'])->name('patients.index');

    // Settings — clinic configuration
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::post('/settings/professionals', [SettingsController::class, 'storeProfessional'])->name('settings.professionals.store');
    Route::put('/settings/professionals/{professional}', [SettingsController::class, 'updateProfessional'])->name('settings.professionals.update');
    Route::delete('/settings/professionals/{professional}', [SettingsController::class, 'destroyProfessional'])->name('settings.professionals.destroy');
    Route::post('/settings/services', [SettingsController::class, 'storeService'])->name('settings.services.store');
    Route::put('/settings/services/{service}', [SettingsController::class, 'updateService'])->name('settings.services.update');
    Route::delete('/settings/services/{service}', [SettingsController::class, 'destroyService'])->name('settings.services.destroy');
    Route::put('/settings/schedules/{professional}', [SettingsController::class, 'updateSchedules'])->name('settings.schedules.update');
});

Route::middleware(['auth', 'verified', 'superadmin'])
    ->prefix('platform')
    ->name('platform.')
    ->group(function () {
        Route::get('/', [PlatformDashboardController::class, 'index'])->name('dashboard');
        Route::post('/organizations', [PlatformOrganizationsController::class, 'store'])->name('organizations.store');
        Route::put('/organizations/{organization}', [PlatformOrganizationsController::class, 'update'])->name('organizations.update');
        Route::delete('/organizations/{organization}', [PlatformOrganizationsController::class, 'destroy'])->name('organizations.destroy');
    });

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
