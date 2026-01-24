<?php

use App\Http\Controllers\API\Global\Captcha\CaptchaController;
use App\Http\Controllers\API\Global\Chunk\ChunkFileController;
use App\Http\Controllers\API\Global\DataEntry\CountryController;
use App\Http\Controllers\API\Global\Help\HelpController;
use App\Http\Controllers\API\Global\Notification\NotificationController;
use App\Http\Controllers\API\Global\Setting\SettingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes (Guest Accessible)
|--------------------------------------------------------------------------
| These routes are accessible without authentication for login/registration
*/
Route::prefix('captcha')->group(function () {
    Route::get('/', [CaptchaController::class, 'generateCaptcha']);
    Route::post('/verify', [CaptchaController::class, 'verifyCaptcha']);
});

/*
|--------------------------------------------------------------------------
| Authenticated Routes (Admin & User)
|--------------------------------------------------------------------------
| These routes require authentication - accessible by both admin and user
*/
Route::middleware(['auth:sanctum'])->group(function () {
    /*
    |--------------------------------------------------------------------------
    | Global Routes
    |--------------------------------------------------------------------------
    */
    Route::post('chunk-file', ChunkFileController::class);

    Route::get('help-configs', [HelpController::class, 'configs']);
    Route::get('help-models', [HelpController::class, 'models']);
    Route::get('help-enums', [HelpController::class, 'enums']);

    Route::put('notifications', [NotificationController::class, 'update']);
    Route::get('notifications', [NotificationController::class, 'index']);

    Route::get('settings', [SettingController::class, 'index']);

    /*
    |--------------------------------------------------------------------------
    | Data Entry Routes
    |--------------------------------------------------------------------------
    */
    Route::get('countries', [CountryController::class, 'index']);
});
