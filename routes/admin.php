<?php

use App\Http\Controllers\API\Admin\User\AdminController;
use App\Http\Controllers\API\Admin\User\PermissionController;
use App\Http\Controllers\API\Admin\User\RoleController;
use App\Http\Controllers\API\Admin\DataEntry\CountryController;
use App\Http\Controllers\API\Admin\Profile\ProfileController;
use App\Http\Controllers\API\Admin\User\UserController;
use App\Http\Controllers\API\Admin\Global\ActivityLog\ActivityLogController;
use App\Http\Controllers\API\Admin\Global\Export\ExportController;
use App\Http\Controllers\API\Admin\Global\Report\ReportController;
use App\Http\Controllers\API\Admin\Global\Setting\SettingController;
use App\Http\Controllers\API\Admin\Global\Setting\TestCredentialsController;
use App\Http\Controllers\API\Global\Auth\LoginController;
use App\Http\Controllers\API\Global\Auth\OTPController;
use App\Http\Controllers\API\Global\Auth\ResetPasswordController;
use App\Http\Controllers\API\Global\Captcha\CaptchaController;
use App\Http\Controllers\API\Global\Chunk\ChunkFileController;
use App\Http\Controllers\API\Global\Help\HelpController;
use App\Http\Controllers\API\Global\Notification\NotificationController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\Global\Auth\LogoutController;

Route::prefix('admin')->group(function () {
    /*
    |--------------------------------------------------------------------------
    | Public Routes (Guest Accessible)
    |--------------------------------------------------------------------------
    */
    Route::prefix('captcha')->group(function () {
        Route::get('/', [CaptchaController::class, 'generateCaptcha']);
        Route::post('/verify', [CaptchaController::class, 'verifyCaptcha']);
    });

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

    Route::middleware(['auth:sanctum'])->group(function () {
        /*
       |--------------------------------------------------------------------------
       | Profile Routes
       |--------------------------------------------------------------------------
       */
        Route::get('me', [ProfileController::class, 'user']);
        Route::post('update-profile', [ProfileController::class, 'updateProfile']);
        Route::post('destroy-avatar', [ProfileController::class, 'destroyAvatar']);
        Route::post('logout', LogoutController::class);

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
        | Data Entry Routes
        |--------------------------------------------------------------------------
        */
        Route::prefix('countries')->group(function () {
            Route::delete('force-delete', [CountryController::class, 'forceDelete']);
            Route::delete('delete', [CountryController::class, 'destroy']);
            Route::post('restore', [CountryController::class, 'restore']);
            Route::put('toggle-active', [CountryController::class, 'toggleActive']);
            Route::apiResource('/', CountryController::class)->parameters(['' => 'country'])->except(['destroy']);
        });

        /*
        |--------------------------------------------------------------------------
        | Global Routes
        |--------------------------------------------------------------------------
        */
        Route::apiResource('settings', SettingController::class)->only(['index', 'update']);
        Route::post('send-test-mail', [TestCredentialsController::class, 'testEmail']);

        Route::get('report', ReportController::class);
        Route::get('export', ExportController::class);

        Route::get('activity-logs', [ActivityLogController::class, 'index']);
        Route::get('activity-logs/{activity}', [ActivityLogController::class, 'show']);

        Route::get('help-configs', [HelpController::class, 'configs']);
        Route::get('help-models', [HelpController::class, 'models']);
        Route::get('help-enums', [HelpController::class, 'enums']);

        Route::put('notifications', [NotificationController::class, 'update']);
        Route::get('notifications', [NotificationController::class, 'index']);
        Route::post('chunk-file', ChunkFileController::class);

        /*
        |--------------------------------------------------------------------------
        | Admin Routes
        |--------------------------------------------------------------------------
        */
        Route::prefix('admins')->group(function () {
            Route::delete('force-delete', [AdminController::class, 'forceDelete']);
            Route::delete('delete', [AdminController::class, 'destroy']);
            Route::post('restore', [AdminController::class, 'restore']);
            Route::put('toggle-active', [AdminController::class, 'toggleActive']);
            Route::apiResource('/', AdminController::class)->parameters(['' => 'admin'])->except(['destroy']);
        });

        /*
        |--------------------------------------------------------------------------
        | User Routes (Admin can manage users)
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
