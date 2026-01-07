<?php

use App\Http\Controllers\API\Central\Admin\AdminController;
use App\Http\Controllers\API\Central\Auth\ForgetPasswordController;
use App\Http\Controllers\API\Central\Auth\LoginController;
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
use App\Http\Controllers\API\Central\Admin\PermissionController;
use App\Http\Controllers\API\Central\Admin\ProfileController;
use App\Http\Controllers\API\Central\Admin\RoleController;
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
    Route::post('login', [LoginController::class, 'login']);
    Route::post('forget', [ForgetPasswordController::class, 'forget'])->name('forget');
    Route::post('verify-otp', [ForgetPasswordController::class, 'verify'])->name('verify');
    Route::post('reset', [ResetPasswordController::class, 'reset'])->name('reset');
    Route::apiResource('countries', CountryController::class)->only(['index', 'show']);

    Route::middleware(['auth:sanctum'])->group(function () {
        /*
        |--------------------------------------------------------------------------
        | activity log Routes
        |--------------------------------------------------------------------------
        */
        Route::prefix('get-activity-logs')->group(function () {
            Route::get('/', [ActivityLogController::class, 'index']);
            Route::get('/{activity}', [ActivityLogController::class, 'show']);
        });

        /*
        |--------------------------------------------------------------------------
        | User Routes
        |--------------------------------------------------------------------------
        */
        Route::prefix('admins')->name('admins.')->group(function () {
            Route::delete('delete-all', [AdminController::class, 'destroyAll'])->name('destroyAll');
            Route::post('{id}/restore', [AdminController::class, 'restore'])->name('restore');
            Route::post('{user}/change-status', [AdminController::class, 'changeStatus'])->name('changeStatus');
            Route::delete('{id}/force-delete', [AdminController::class, 'forceDelete'])->name('forceDelete');

            Route::apiResource('/', AdminController::class)->parameters(['' => 'admin']);
        });

        //Role Routes
        Route::apiResource('roles', RoleController::class);

        Route::delete('permissions/delete-all', [PermissionController::class, 'destroyAll']);
        Route::apiResource('permissions', PermissionController::class);

        //Profile Routes
        Route::get('me', [ProfileController::class, 'user']);
        Route::post('update-profile', [ProfileController::class, 'updateProfile']);
        Route::post('destroy-avatar', [ProfileController::class, 'destroyAvatar']);
        Route::post('logout', [LoginController::class, 'logout']);

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
        Route::get('settings', [SettingController::class, 'index']);
        Route::get('settings', [SettingController::class, 'publicSetting']);
        Route::post('set-settings', [SettingController::class, 'setConfigForUser']);
        Route::post('send-test-mail', [SettingController::class, 'testMailCredentials']);

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
