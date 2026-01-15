<?php

use App\Http\Controllers\API\Central\Admin\PermissionController;
use App\Http\Controllers\API\Central\Admin\RoleController;
use App\Http\Controllers\API\Central\Auth\LoginController;
use App\Http\Controllers\API\Central\Auth\LogoutController;
use App\Http\Controllers\API\Central\Auth\OTPController;
use App\Http\Controllers\API\Central\Auth\ResetPasswordController;
use App\Http\Controllers\API\Central\DataEntry\CountryController;
use App\Http\Controllers\API\Central\Global\Captcha\CaptchaController;
use App\Http\Controllers\API\Central\Global\Chunk\ChunkFileController;
use App\Http\Controllers\API\Central\Global\Export\ExportController;
use App\Http\Controllers\API\Central\Global\Help\HelpController;
use App\Http\Controllers\API\Central\Global\Log\ActivityLogController;
use App\Http\Controllers\API\Central\Global\Notification\NotificationController;
use App\Http\Controllers\API\Central\Global\Report\ReportController;
use App\Http\Controllers\API\Central\Global\Setting\SettingController;
use App\Http\Controllers\API\Central\Global\Setting\TestCredentialsController;
use App\Http\Controllers\API\Tenant\Auth\ProfileController;
use App\Http\Controllers\API\Tenant\User\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware('tenant')->group(function() {

    /*
    |--------------------------------------------------------------------------
    | Captcha Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('captcha')->group(function () {
        Route::get('/', [CaptchaController::class, 'generateCaptcha']);
        Route::post('/verify', [CaptchaController::class, 'verifyCaptcha']);
    });

    /*
    |--------------------------------------------------------------------------
    | Auth Routes
    |--------------------------------------------------------------------------
    */
    Route::post('login', LoginController::class);
    Route::post('reset-password', ResetPasswordController::class);

    // OTP Routes
    Route::post('send-otp', [OTPController::class, 'send']);
    Route::post('check-otp', [OTPController::class, 'check']);
    Route::post('verify-otp', [OTPController::class, 'verify']);

    Route::middleware(['auth:sanctum'])->group(function () {
        /*
       |--------------------------------------------------------------------------
       | Auth Routes
       |--------------------------------------------------------------------------
       */
        Route::get('me', [ProfileController::class, 'user']);
        Route::post('update-profile', [ProfileController::class, 'updateProfile']);
        Route::post('destroy-avatar', [ProfileController::class, 'destroyAvatar']);
        Route::post('logout', LogoutController::class);

        /*
        |--------------------------------------------------------------------------
        | activity log Routes
        |--------------------------------------------------------------------------
        */
        Route::prefix('activity-logs')->group(function () {
            Route::get('/', [ActivityLogController::class, 'index']);
            Route::get('/{activity}', [ActivityLogController::class, 'show']);
        });

        /*
        |--------------------------------------------------------------------------
        | Roles && Permissions Routes
        |--------------------------------------------------------------------------
        */
        Route::get('permissions', [PermissionController::class, 'index']);

        Route::prefix('roles')->group(function () {
            Route::delete('delete', [RoleController::class, 'destroy']);
            Route::apiResource('/', RoleController::class)->parameters(['' => 'role'])->except(['destroy']);
        });

        /*
        |--------------------------------------------------------------------------
        | Global Routes
        |--------------------------------------------------------------------------
        */
        Route::post('chunk-file', ChunkFileController::class);

        Route::get('help-models', [HelpController::class, 'models']);
        Route::get('help-enums', [HelpController::class, 'enums']);

        Route::put('notifications', [NotificationController::class, 'update']);
        Route::get('notifications', [NotificationController::class, 'index']);

        Route::get('report', ReportController::class);
        Route::get('export', ExportController::class);

        /*
        |--------------------------------------------------------------------------
        | Setting Routes
        |--------------------------------------------------------------------------
        */
        Route::apiResource('settings', SettingController::class)->only(['index', 'update']);
        Route::post('send-test-mail', [TestCredentialsController::class, 'testEmail']);

        /*
        |--------------------------------------------------------------------------
        | Data Entry Routes
        |--------------------------------------------------------------------------
        */
        Route::post('countries/restore', [CountryController::class, 'restore']);
        Route::delete('countries/delete', [CountryController::class, 'destroy']);
        Route::delete('countries/force-delete', [CountryController::class, 'forceDelete']);
        Route::apiResource('countries', CountryController::class);

        /*
         |--------------------------------------------------------------------------
         | User Routes
         |--------------------------------------------------------------------------
         */
        Route::prefix('users')->group(function () {
            Route::delete('force-delete', [UserController::class, 'forceDelete']);
            Route::delete('delete', [UserController::class, 'destroy']);
            Route::post('restore', [UserController::class, 'restore']);
            Route::put('toggle-active', [UserController::class, 'toggleActive']);
            Route::apiResource('/', UserController::class)->parameters(['' => 'user'])->except(['destroy']);
        });
    });

});
