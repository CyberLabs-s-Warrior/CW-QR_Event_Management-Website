<?php

use App\Http\Controllers\api\ApiAuthenticationController;
use App\Http\Controllers\api\ApiEventController;
use App\Http\Controllers\api\ApiScanEventController;
use App\Http\Controllers\api\ApiUserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;




Route::prefix('user')->group(function () {
    Route::post('sign-in', [ApiAuthenticationController::class, 'signIn'])->name('signin');
    Route::post('send-code', [ApiAuthenticationController::class, 'sendCode'])->name('send.code');
    Route::post('verify-code', [ApiAuthenticationController::class, 'verifyCode'])->name('verify.code');
    Route::put('recovery-password', [ApiAuthenticationController::class, 'recoveryPassword']);
});

Route::middleware(['auth:sanctum'])->group(function () {
    // event (one)
    Route::prefix('event')->group(function () {
        Route::post('{id}/scan-attendance', [ApiScanEventController::class, 'scanAttendance']);
        Route::post('{id}/scan-identity', [ApiScanEventController::class, 'scanIdentityCheck']);
        Route::post('{id}/update-attendees', [ApiEventController::class, 'updateAttendees']);
        Route::get('{id}/attendees', [ApiEventController::class, 'getEventAttendees']);
        Route::get('{id}', [ApiEventController::class, 'getEventById']);
    });

    // events (many)
    Route::prefix('events')->group(function () {
        Route::get('/', [ApiEventController::class, 'getAllEvents']);
    });

    // user
    Route::prefix('user')->group(function () {
        Route::get('sign-out', [ApiAuthenticationController::class, 'signOut'])->name('signout');
        Route::get('/', [ApiUserController::class, 'getUserProfile']);
        Route::get('events-summary', [ApiEventController::class, 'getUserEventsSummary']);
        Route::get('events-history', [ApiEventController::class, 'getUserEventHistory']);
        Route::patch('/edit-profile', [ApiUserController::class, 'editProfile']);
        Route::get('refresh-token', [ApiAuthenticationController::class, 'refreshToken']);
        Route::get('{id}/events', [ApiEventController::class, 'getUserEvents']);
        Route::get('{id}/events/search', [ApiEventController::class, 'search']);
        Route::put('{id}/change-password', [ApiUserController::class, 'changePasswordInProfile']);
        Route::get('{id}/events/upcoming', [ApiEventController::class, 'getUserEventsUpcoming']);
        Route::get('{id}/events/done', [ApiEventController::class, 'getUserEventsDone']);
        Route::get('{id}/events/ongoing', [ApiEventController::class, 'getUserEventsOnGoing']);
        Route::get('{id}', [ApiUserController::class, 'getUserById']);
    });
});
