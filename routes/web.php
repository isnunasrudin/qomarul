<?php

use App\Http\Controllers\Admin\AdditionalDutyController;
use App\Http\Controllers\Admin\DecreeTypeController;
use App\Http\Controllers\Admin\EmploymentStatusController;
use App\Http\Controllers\Admin\PositionController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\WorkUnitController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordChangeController;
use App\Http\Controllers\Auth\TwoFactorController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('login.attempt');

    Route::get('/2fa/setup', [TwoFactorController::class, 'setup'])->name('two-factor.setup');
    Route::get('/2fa/verify', [TwoFactorController::class, 'verify'])->name('two-factor.verify');
    Route::post('/2fa/confirm', [TwoFactorController::class, 'confirm'])
        ->middleware('throttle:5,1')
        ->name('two-factor.confirm');
    Route::post('/2fa/challenge', [TwoFactorController::class, 'challenge'])
        ->middleware('throttle:5,1')
        ->name('two-factor.challenge');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    Route::middleware('password.changed')->group(function () {
        Route::get('/', DashboardController::class)->name('dashboard');

        Route::middleware('role:foundation_head,foundation_admin')->prefix('admin')->name('admin.')->group(function () {
            Route::resource('work-units', WorkUnitController::class)->except(['create', 'edit', 'show']);
            Route::resource('positions', PositionController::class)->except(['create', 'edit', 'show']);
            Route::resource('employment-statuses', EmploymentStatusController::class)->except(['create', 'edit', 'show']);
            Route::resource('additional-duties', AdditionalDutyController::class)->except(['create', 'edit', 'show']);
            Route::resource('decree-types', DecreeTypeController::class)->except(['create', 'edit', 'show']);
            Route::resource('users', UserController::class)->except(['create', 'edit', 'show']);
            Route::post('users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');
            Route::post('users/{user}/toggle-active', [UserController::class, 'toggleActive'])->name('users.toggle-active');

            Route::get('settings', [SettingController::class, 'index'])->name('settings.index');
            Route::post('settings', [SettingController::class, 'update'])->name('settings.update');
        });
    });

    Route::get('/password/change', [PasswordChangeController::class, 'show'])->name('password.change');
    Route::post('/password/change', [PasswordChangeController::class, 'store'])->name('password.change.store');
});
