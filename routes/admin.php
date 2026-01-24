<?php

use App\Http\Controllers\API\Auth\LoginController;
use App\Http\Controllers\API\Auth\LogoutController;
use App\Http\Controllers\API\Auth\OTPController;
use App\Http\Controllers\API\Auth\ResetPasswordController;
use App\Http\Controllers\API\DataEntry\CountryController;
use App\Http\Controllers\API\Global\ActivityLog\ActivityLogController;
use App\Http\Controllers\API\Global\Export\ExportController;
use App\Http\Controllers\API\Global\Report\ReportController;
use App\Http\Controllers\API\Global\Setting\SettingController;
use App\Http\Controllers\API\Global\Setting\TestCredentialsController;
use App\Http\Controllers\API\Profile\ProfileController;
use App\Http\Controllers\API\User\AdminController;
use App\Http\Controllers\API\User\PermissionController;
use App\Http\Controllers\API\User\RoleController;
use App\Http\Controllers\API\User\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->group(function () {
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

    Route::middleware(['auth:admin'])->group(function () {
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
        Route::post('countries/restore', [CountryController::class, 'restore']);
        Route::delete('countries/delete', [CountryController::class, 'destroy']);
        Route::delete('countries/force-delete', [CountryController::class, 'forceDelete']);
        Route::apiResource('countries', CountryController::class);

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
