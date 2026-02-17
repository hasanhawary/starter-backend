<?php

use App\Http\Controllers\API\Global\DataEntry\CountryController;
use App\Http\Controllers\API\Global\Setting\SettingController;
use App\Http\Controllers\API\Global\Captcha\CaptchaController;
use App\Http\Controllers\API\Global\Chunk\ChunkFileController;
use App\Http\Controllers\API\Global\Help\HelpController;
use App\Http\Controllers\API\Global\Notification\NotificationController;
use App\Http\Controllers\API\Landing\Profile\ProfileController;
use App\Http\Controllers\API\Global\Auth\LoginController;
use App\Http\Controllers\API\Global\Auth\LogoutController;
use App\Http\Controllers\API\Global\Auth\OTPController;
use App\Http\Controllers\API\Global\Auth\ResetPasswordController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Auth Routes (Public)
|--------------------------------------------------------------------------
*/
Route::post('login', LoginController::class);
Route::post('reset-password', ResetPasswordController::class);

// OTP Routes (Public for login/registration)
Route::post('send-otp', [OTPController::class, 'send']);
Route::post('check-otp', [OTPController::class, 'check']);
Route::post('verify-otp', [OTPController::class, 'verify']);

Route::prefix('captcha')->group(function () {
    Route::get('/', [CaptchaController::class, 'generateCaptcha']);
    Route::post('/verify', [CaptchaController::class, 'verifyCaptcha']);
});

Route::middleware(['auth:user', 'ability:user'])->group(function () {
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

    /*
    |--------------------------------------------------------------------------
    | Auth Routes (Protected)
    |--------------------------------------------------------------------------
    */
    Route::post('logout', LogoutController::class);

    /*
    |--------------------------------------------------------------------------
    | Profile Routes
    |--------------------------------------------------------------------------
    */
    Route::get('me', [ProfileController::class, 'user']);
    Route::post('update-profile', [ProfileController::class, 'updateProfile']);
    Route::post('destroy-avatar', [ProfileController::class, 'destroyAvatar']);
});
