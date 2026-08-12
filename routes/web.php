<?php

use App\Http\Controllers\Admin\AdditionalDutyController;
use App\Http\Controllers\Admin\CertificateController;
use App\Http\Controllers\Admin\DecreeController;
use App\Http\Controllers\Admin\DecreeTypeController;
use App\Http\Controllers\Admin\DocumentController;
use App\Http\Controllers\Admin\DutyAssignmentController;
use App\Http\Controllers\Admin\EducationController;
use App\Http\Controllers\Admin\EmployeeController;
use App\Http\Controllers\Admin\EmploymentStatusController;
use App\Http\Controllers\Admin\LegacyDecreeController;
use App\Http\Controllers\Admin\PositionController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\WorkUnitController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordChangeController;
use App\Http\Controllers\Auth\TwoFactorController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Portal\PortalController;
use App\Http\Controllers\Public\VerificationController;
use Illuminate\Support\Facades\Route;

// Verifikasi publik — tanpa auth (PRD F7.7–F7.11)
Route::get('/verifikasi/{uuid}', [VerificationController::class, 'show'])
    ->middleware('throttle:30,1')
    ->name('verification.show');

Route::post('/verifikasi/periksa', [VerificationController::class, 'verifyFile'])
    ->middleware('throttle:10,1')
    ->name('verification.file');

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
            Route::post('settings/signature', [SettingController::class, 'updateSignature'])->name('settings.signature');

            Route::get('certificates', [CertificateController::class, 'index'])->name('certificates.index');
            Route::post('certificates', [CertificateController::class, 'store'])->name('certificates.store');
            Route::post('certificates/generate', [CertificateController::class, 'generate'])->name('certificates.generate');
            Route::get('certificates/{certificate}/detail', [CertificateController::class, 'detail'])->name('certificates.detail');
        });

        // Data GTK — Ketua Yayasan (baca), Admin Yayasan, Admin Satker
        Route::middleware('role:foundation_head,foundation_admin,unit_admin')->prefix('admin')->name('admin.')->group(function () {
            Route::get('employees', [EmployeeController::class, 'index'])->name('employees.index');
            Route::get('employees/create', [EmployeeController::class, 'create'])->name('employees.create');
            Route::post('employees', [EmployeeController::class, 'store'])->name('employees.store');
            Route::post('employees/import/preview', [EmployeeController::class, 'importPreview'])->name('employees.import.preview');
            Route::post('employees/import', [EmployeeController::class, 'importStore'])->name('employees.import');
            Route::get('employees/export', [EmployeeController::class, 'export'])->name('employees.export');
            Route::get('employees/{employee}', [EmployeeController::class, 'show'])->name('employees.show');
            Route::get('employees/{employee}/edit', [EmployeeController::class, 'edit'])->name('employees.edit');
            Route::put('employees/{employee}', [EmployeeController::class, 'update'])->name('employees.update');
            Route::delete('employees/{employee}', [EmployeeController::class, 'destroy'])->name('employees.destroy');

            Route::post('employees/{employee}/educations', [EducationController::class, 'store'])->name('employees.educations.store');
            Route::put('employees/{employee}/educations/{education}', [EducationController::class, 'update'])->name('employees.educations.update');
            Route::delete('employees/{employee}/educations/{education}', [EducationController::class, 'destroy'])->name('employees.educations.destroy');

            Route::post('employees/{employee}/documents', [DocumentController::class, 'store'])->name('employees.documents.store');
            Route::delete('documents/{document}', [DocumentController::class, 'destroy'])->name('documents.destroy');

            Route::get('decree-legacy', [LegacyDecreeController::class, 'index'])->name('decree-legacy.index');
            Route::post('decree-legacy/{decree}/verify', [LegacyDecreeController::class, 'verify'])->name('decree-legacy.verify');
            Route::delete('decree-legacy/{decree}', [LegacyDecreeController::class, 'destroy'])->name('decree-legacy.destroy');

            Route::get('duties', [DutyAssignmentController::class, 'index'])->name('duties.index');
            Route::post('duties', [DutyAssignmentController::class, 'store'])->name('duties.store');
            Route::post('duties/mass', [DutyAssignmentController::class, 'storeMass'])->name('duties.mass');
            Route::put('duties/{assignment}', [DutyAssignmentController::class, 'update'])->name('duties.update');
            Route::delete('duties/{assignment}', [DutyAssignmentController::class, 'destroy'])->name('duties.destroy');

            Route::get('decrees', [DecreeController::class, 'index'])->name('decrees.index');
            Route::get('decrees/create', [DecreeController::class, 'create'])->name('decrees.create');
            Route::post('decrees', [DecreeController::class, 'store'])->name('decrees.store');
            Route::get('decrees/{decree}', [DecreeController::class, 'show'])->name('decrees.show');
            Route::get('decrees/{decree}/preview-pdf', [DecreeController::class, 'previewPdf'])->name('decrees.preview-pdf');
            Route::post('decrees/{decree}/submit', [DecreeController::class, 'submit'])->name('decrees.submit');
            Route::post('decrees/{decree}/verify', [DecreeController::class, 'verify'])->name('decrees.verify');
            Route::post('decrees/{decree}/reject', [DecreeController::class, 'reject'])->name('decrees.reject');
            Route::post('decrees/{decree}/issue', [DecreeController::class, 'issue'])->name('decrees.issue');
            Route::post('decrees/{decree}/cancel', [DecreeController::class, 'cancel'])->name('decrees.cancel');
        });

        Route::get('decrees/{decree}/download', [DecreeController::class, 'downloadPdf'])
            ->middleware(['role:foundation_head,foundation_admin,unit_admin', 'signed'])
            ->name('admin.decrees.download');

        Route::get('documents/{document}/download', [DocumentController::class, 'download'])
            ->middleware(['role:foundation_head,foundation_admin,unit_admin', 'signed'])
            ->name('admin.documents.download');

        // Portal mandiri GTK
        Route::middleware(['role:employee', 'password.changed'])->prefix('portal')->name('portal.')->group(function () {
            Route::get('/', [PortalController::class, 'home'])->name('home');
            Route::put('/profile', [PortalController::class, 'updateProfile'])->name('profile.update');
            Route::post('/documents', [PortalController::class, 'uploadDocument'])->name('documents.store');
            Route::post('/decrees/legacy', [PortalController::class, 'uploadLegacy'])->name('decrees.legacy');
            Route::get('documents/{document}/download', [PortalController::class, 'downloadDocument'])
                ->middleware('signed')
                ->name('documents.download');
            Route::get('decrees/{decree}/download', [PortalController::class, 'downloadDecree'])
                ->middleware('signed')
                ->name('decrees.download');
        });
    });

    Route::get('/password/change', [PasswordChangeController::class, 'show'])->name('password.change');
    Route::post('/password/change', [PasswordChangeController::class, 'store'])->name('password.change.store');
});
