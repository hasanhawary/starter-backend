<?php

use App\Http\Controllers\API\Central\Admin\PermissionController;
use App\Http\Controllers\API\Central\Admin\RoleController;
use App\Http\Controllers\API\Central\Admin\AdminController;
use App\Http\Controllers\API\Central\Auth\LoginController;
use App\Http\Controllers\API\Central\Auth\LogoutController;
use App\Http\Controllers\API\Central\Auth\OTPController;
use App\Http\Controllers\API\Central\Auth\ProfileController;
use App\Http\Controllers\API\Central\Auth\ResetPasswordController;
use App\Http\Controllers\API\Central\Subscription\PlanController;
use App\Http\Controllers\API\Central\Subscription\PlanFeatureController;
use App\Http\Controllers\API\Central\Subscription\SubscriptionController;
use App\Http\Controllers\API\Central\Subscription\SubscriptionUsageController;
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
use App\Http\Controllers\API\Central\Tenant\TenantController;
use Illuminate\Support\Facades\Route;

Route::prefix('central')->group(function () {
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

        Route::get('help-configs', [HelpController::class, 'configs']);
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
        | Tenant Routes
        |--------------------------------------------------------------------------
        */
        Route::prefix('tenants')->group(function () {
            Route::delete('force-delete', [TenantController::class, 'forceDelete']);
            Route::delete('delete', [TenantController::class, 'destroy']);
            Route::post('restore', [TenantController::class, 'restore']);
            Route::put('toggle-active', [TenantController::class, 'toggleActive']);
            Route::apiResource('/', TenantController::class)->parameters(['' => 'tenant'])->except(['destroy']);
        });

        /*
        |--------------------------------------------------------------------------
        | Subscription Routes
        |--------------------------------------------------------------------------
        */
        // Plans
        Route::prefix('plans')->group(function () {
            Route::post('delete', [PlanController::class, 'destroy']);
            Route::post('restore', [PlanController::class, 'restore']);
            Route::delete('force-delete', [PlanController::class, 'forceDelete']);
            Route::put('toggle-active', [PlanController::class, 'toggleActive']);
            Route::apiResource('/', TenantController::class)->parameters(['' => 'plan'])->except(['destroy']);
        });

        Route::prefix('plan-features')->group(function () {
            Route::post('delete', [PlanFeatureController::class, 'destroy']);
            Route::put('toggle-active', [PlanFeatureController::class, 'toggleActive']);
            Route::apiResource('/', PlanFeatureController::class)->parameters(['' => 'planFeature'])->except(['destroy']);
        });

        // Subscriptions
        Route::prefix('subscriptions')->group(function () {
            Route::post('delete', [SubscriptionController::class, 'destroy']);
            Route::post('{subscription}/change-status', [SubscriptionController::class, 'changeStatus']);
            Route::post('{subscription}/cancel', [SubscriptionController::class, 'cancel']);
            Route::post('{subscription}/renew', [SubscriptionController::class, 'renew']);
            Route::apiResource('subscriptions', SubscriptionController::class)->parameters(['' => 'subscription'])->except(['destroy']);
        });

        Route::post('delete', [SubscriptionUsageController::class, 'destroy']);
        Route::apiResource('subscription-usage', SubscriptionUsageController::class)->except(['destroy']);
    });
});
