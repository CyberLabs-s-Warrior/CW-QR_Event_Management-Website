<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\EventCategoryController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\EventRegistrationController;
use App\Http\Controllers\AttendeeController;
use App\Http\Controllers\LogsController;
use App\Http\Controllers\TrashController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

// ========== ROUTE UNTUK AUTHENTICATION ==========
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login.form');
    Route::post('/login', [AuthController::class, 'login'])->name('login');

    // Forgot Password + OTP
    Route::get('/forgot-password', [AuthController::class, 'showForgotPasswordForm'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'sendResetCode'])->name('password.sendCode');

    Route::get('/verify-code', [AuthController::class, 'showVerifyCodeForm'])->name('password.verify.form');
    Route::post('/verify-code', [AuthController::class, 'verifyCode'])->name('password.verify');

    Route::get('/reset-password', [AuthController::class, 'showResetPasswordForm'])->name('password.reset.form');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.reset');
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// ========== ROUTE UNTUK PESERTA ==========
Route::get('/event/{link}', [EventRegistrationController::class, 'showForm'])->name('registration.form');
Route::post('/event/{link}/submit', [EventRegistrationController::class, 'submit'])->name('registration.submit');

// ========== ROUTE UNTUK FILE PROTECTED ==========
Route::get('/protected/{token}', function ($token) {
    // Cek sudah login atau belum
    // if (!auth()->check()) {
    //     abort(403, 'Unauthorized');
    // }

    // Cek signature (expired juga dicek otomatis)
    if (!request()->hasValidSignature()) {
        abort(403, 'Invalid or expired link');
    }

    // Decode token -> dapat path asli
    try {
        $path = decrypt($token); // decrypt dari blade
    } catch (\Exception $e) {
        abort(403, 'Invalid token');
    }

    if (!Storage::disk('public')->exists($path)) {
        abort(404);
    }

    return response()->file(storage_path("app/public/{$path}"));
})->name('protected.file');

// ========== ROUTE UNTUK ADMIN ==========
Route::prefix('admin')->middleware(['auth', 'can:isSuperOrAdmin'])->group(function () {
    Route::resource('/', DashboardController::class)->names(names: [
        'index' => 'admin.dashboard.index',
    ]);

    Route::get('/profile', [ProfileController::class, 'show'])->name('admin.profile.show');
    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('admin.profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('admin.profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('admin.profile.password');

    Route::resource('/user', UserController::class)->names([
        'index' => 'admin.user.index',
        'create' => 'admin.user.create',
        'store' => 'admin.user.store',
        'edit' => 'admin.user.edit',
        'update' => 'admin.user.update',
        'destroy' => 'admin.user.destroy',
    ])->middleware('can:isSuperAdmin');

    Route::resource('/event_category', EventCategoryController::class)->names([
        'index' => 'admin.event_category.index',
        'create' => 'admin.event_category.create',
        'store' => 'admin.event_category.store',
        'edit' => 'admin.event_category.edit',
        'update' => 'admin.event_category.update',
        'destroy' => 'admin.event_category.destroy',
    ]);

    Route::resource('/event', EventController::class)->names([
        'index' => 'admin.event.index',
        'create' => 'admin.event.create',
        'store' => 'admin.event.store',
        'show' => 'admin.event.show',
        'edit' => 'admin.event.edit',
        'update' => 'admin.event.update',
        'destroy' => 'admin.event.destroy',
    ]);

    Route::put('/event/registration-link/{id}', [EventController::class, 'updateRegistrationLink'])
        ->name('admin.event.registration-link.update');

    Route::get('/event/{event}/input-registration', [EventRegistrationController::class, 'editInputs'])
        ->name('admin.event.input.edit');
    Route::post('/event/{event}/input-registration', [EventRegistrationController::class, 'updateInputs'])
        ->name('admin.event.input.update');

    Route::get('/event/{event}/attendees', [AttendeeController::class, 'index'])
        ->name('admin.attendee.index');

    Route::resource('/attendee', AttendeeController::class)->names([
        'create' => 'admin.attendee.create',
        'store' => 'admin.attendee.store',
        'show' => 'admin.attendee.show',
        'edit' => 'admin.attendee.edit',
        'update' => 'admin.attendee.update',
        'destroy' => 'admin.attendee.destroy',
    ])->except(['index']);
   

    Route::get('/event/{event}/attendee/export-excel', [AttendeeController::class, 'exportExcel'])
        ->name('admin.attendee.exportExcel');
    Route::get('/event/{event}/attendee/export-pdf', [AttendeeController::class, 'exportPdf'])
        ->name('admin.attendee.exportPdf');
        
    // ========== ROUTE UNTUK TRASH ==========
    Route::prefix('trash')->name('admin.trash.')->group(function () {
        // --- hanya super_admin ---
        Route::middleware('can:isSuperAdmin')->group(function () {
            Route::get('events', [TrashController::class, 'events'])->name('events');
            Route::get('categories', [TrashController::class, 'categories'])->name('categories');
            Route::get('users', [TrashController::class, 'users'])->name('users');
        });

        // --- super_admin + admin (khusus attendees) ---
        Route::prefix('attendees')->name('attendees.')->group(function () {
            Route::get('/', [TrashController::class, 'attendeesIndex'])->name('index'); // list event yg punya attendee terhapus
            Route::get('{event}', [TrashController::class, 'attendeesShow'])->name('show'); // list attendees per event
        });

        // Aksi umum restore / force delete
        Route::post('{type}/{id}/restore', [TrashController::class, 'restore'])->name('restore');
        Route::delete('{type}/{id}/force-delete', [TrashController::class, 'forceDelete'])->name('forceDelete');
    });

    // ========== ROUTE UNTUK LOGS ==========
    Route::prefix('logs')->name('admin.logs.')->group(function () {
        // --- hanya super_admin ---
        Route::middleware('can:isSuperAdmin')->group(function () {
            Route::get('qr-logs', [LogsController::class, 'qrLogs'])->name('qr');
            Route::get('users', [LogsController::class, 'userLog'])->name('users');
        });

        // --- super_admin + admin (khusus attendees) ---
        Route::prefix('attendees')->name('attendees.')->group(function () {
            Route::get('/', [TrashController::class, 'attendeesIndex'])->name('index'); // list event yg punya attendee terhapus
            Route::get('{event}', [TrashController::class, 'attendeesShow'])->name('show'); // list attendees per event
        });

        // Aksi umum restore / force delete
        Route::post('{type}/{id}/restore', [TrashController::class, 'restore'])->name('restore');
        Route::delete('{type}/{id}/force-delete', [TrashController::class, 'forceDelete'])->name('forceDelete');
    });
});
