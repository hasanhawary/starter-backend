<?php

use App\Http\Controllers\API\Central\Admin\AdminController;
use App\Http\Controllers\API\Central\Admin\PermissionController;
use App\Http\Controllers\API\Central\Admin\RoleController;
use App\Http\Controllers\API\Central\Auth\LoginController;
use App\Http\Controllers\API\Central\Auth\LogoutController;
use App\Http\Controllers\API\Central\Auth\OTPController;
use App\Http\Controllers\API\Central\Auth\ProfileController;
use App\Http\Controllers\API\Central\Auth\ResetPasswordController;
use App\Http\Controllers\API\Central\DataEntry\CountryController;
use App\Http\Controllers\API\Central\Global\Chunk\ChunkFileController;
use App\Http\Controllers\API\Central\Global\Export\ExportController;
use App\Http\Controllers\API\Central\Global\Help\HelpController;
use App\Http\Controllers\API\Central\Global\Notification\NotificationController;
use App\Http\Controllers\API\Central\Global\Report\ReportController;
use App\Http\Controllers\API\Central\Global\Setting\ActivityLogController;
use App\Http\Controllers\API\Central\Global\Setting\CaptchaController;
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
        | User Routes
        |--------------------------------------------------------------------------
        */
        Route::prefix('admins')->group(function () {
            Route::delete('delete', [AdminController::class, 'destroy']);
            Route::delete('force-delete', [AdminController::class, 'forceDelete']);
            Route::post('restore', [AdminController::class, 'restore']);
            Route::put('{admin}/toggle-active', [AdminController::class, 'toggleActive']);
            Route::apiResource('/', AdminController::class)->parameters(['' => 'admin'])->except(['destroy']);
        });

        /*
        |--------------------------------------------------------------------------
        | Tenant Routes
        |--------------------------------------------------------------------------
        */
        Route::prefix('tenants')->name('tenants.')->group(function () {
            Route::delete('delete-all', [TenantController::class, 'destroyAll'])->name('destroyAll');
            Route::post('{id}/restore', [TenantController::class, 'restore'])->name('restore');
            Route::post('{tenant}/change-status', [TenantController::class, 'changeStatus'])->name('changeStatus');
            Route::delete('{id}/force-delete', [TenantController::class, 'forceDelete'])->name('forceDelete');

            Route::apiResource('/', TenantController::class)->parameters(['' => 'tenant']);
        });

        /*
        |--------------------------------------------------------------------------
        | activity log Routes
        |--------------------------------------------------------------------------
        */
        Route::prefix('activity-logs')->group(function () {
            Route::get('/', [ActivityLogController::class, 'index'])->parameters;
            Route::get('/{activity}', [ActivityLogController::class, 'show']);
        });

        //Role Routes
        Route::delete('roles/delete', [RoleController::class,'destroy']);
        Route::apiResource('roles', RoleController::class)->except(['destroy']);

        Route::get('permissions', [PermissionController::class, 'index']);

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
    });

    Route::prefix('billing')
        ->middleware(['auth:sanctum', 'central'])
        ->group(function () {

            // Plans
            Route::apiResource('plans', PlanController::class);

            // Plan Features
            Route::apiResource('plan-features', PlanFeatureController::class);

            // Subscriptions
            Route::apiResource('subscriptions', SubscriptionController::class);

            // Subscription usage (read-only in most cases)
            Route::apiResource('subscription-usage', SubscriptionUsageController::class)
                ->only(['index', 'show']);

            // Subscription management actions
            Route::prefix('subscriptions')->group(function () {
                Route::post('{subscription}/change-status', [SubscriptionController::class, 'changeStatus'])
                    ->name('billing.subscriptions.change-status');

                Route::post('{subscription}/cancel', [SubscriptionController::class, 'cancel'])
                    ->name('billing.subscriptions.cancel');

                Route::post('{subscription}/renew', [SubscriptionController::class, 'renew'])
                    ->name('billing.subscriptions.renew');
            });
        });

});
