<?php

use App\Http\Controllers\API\Auth\LoginController;
use App\Http\Controllers\API\Auth\LogoutController;
use App\Http\Controllers\API\Auth\OTPController;
use App\Http\Controllers\API\Auth\ResetPasswordController;
use App\Http\Controllers\API\DataEntry\CountryController;
use App\Http\Controllers\API\Global\ActivityLog\ActivityLogController;
use App\Http\Controllers\API\Global\Captcha\CaptchaController;
use App\Http\Controllers\API\Global\Chunk\ChunkFileController;
use App\Http\Controllers\API\Global\Help\HelpController;
use App\Http\Controllers\API\Global\Notification\NotificationController;
use App\Http\Controllers\API\Global\Report\ReportController;
use App\Http\Controllers\API\Global\Setting\SettingController;
use App\Http\Controllers\API\Global\Setting\TestCredentialsController;
use App\Http\Controllers\API\Profile\ProfileController;
use App\Http\Controllers\API\User\PermissionController;
use App\Http\Controllers\API\User\RoleController;
use App\Http\Controllers\API\User\UserController;
use Illuminate\Support\Facades\Route;

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
    Route::post('update-setting', [ProfileController::class, 'updateSetting']);
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
    Route::get('settings', [SettingController::class, 'index']);
    Route::put('settings', [SettingController::class, 'update']);

    Route::post('send-test-mail', [TestCredentialsController::class, 'testEmail']);

    Route::get('report', ReportController::class);

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


use App\Http\Controllers\API\Commercial\CommercialCustomerController;
use App\Http\Controllers\API\Commercial\DeploymentController;
use App\Http\Controllers\API\Commercial\FleetController;
use App\Http\Controllers\API\Commercial\LicenseManagementController;
use App\Http\Controllers\API\Commercial\PlanController;
use App\Http\Controllers\API\Commercial\ProvisioningController;
use App\Http\Controllers\API\Commercial\ReleaseController;
use App\Http\Controllers\API\Bootstrap\ProvisioningBootstrapController;
use App\Http\Middleware\PermissionMiddleware;
use App\Http\Middleware\ResolvePosContext;

Route::middleware(['auth:sanctum'])->group(function () {
    // Commercial Customers / Organizations Administration
    Route::prefix('v1/commercial/organizations')->middleware(PermissionMiddleware::using('manage-commercial-license|manage-commercial-organizations'))->group(function (): void {
        Route::get('/', [CommercialCustomerController::class, 'index']);
        Route::post('/', [CommercialCustomerController::class, 'store']);
        Route::get('{organization}', [CommercialCustomerController::class, 'show']);
        Route::post('{organization}/licenses', [CommercialCustomerController::class, 'issueLicense']);
        Route::get('{organization}/audit-events', [CommercialCustomerController::class, 'auditEvents']);
    });

    // Commercial Plans Administration
    Route::prefix('v1/commercial/plans')->middleware(PermissionMiddleware::using('manage-commercial-plans|manage-commercial-license'))->group(function (): void {
        Route::get('catalog', [PlanController::class, 'catalog']);
        Route::get('/', [PlanController::class, 'index']);
        Route::post('/', [PlanController::class, 'store']);
        Route::get('{plan}', [PlanController::class, 'show']);
        Route::put('{plan}', [PlanController::class, 'update']);
        Route::post('{plan}/archive', [PlanController::class, 'archive']);
        Route::post('{plan}/restore', [PlanController::class, 'restore']);
        Route::delete('{plan}', [PlanController::class, 'destroy']);
    });

    // Commercial License Authority Management
    Route::prefix('v1/commercial/licenses')->middleware([ResolvePosContext::class, PermissionMiddleware::using('manage-commercial-license')])->group(function (): void {
        Route::post('{license}/preview-plan', [LicenseManagementController::class, 'previewPlan']);
        Route::post('{license}/change-plan', [LicenseManagementController::class, 'changePlan']);
        Route::put('{license}/entitlements', [LicenseManagementController::class, 'updateEntitlements']);
        Route::put('{license}/limits', [LicenseManagementController::class, 'updateLimits']);
        Route::post('{license}/status', [LicenseManagementController::class, 'updateStatus']);
        Route::post('{license}/renew', [LicenseManagementController::class, 'renew']);
    });

    Route::prefix('v1/commercial/releases')->middleware(PermissionMiddleware::using('publish-release'))->group(function (): void {
        Route::get('/', [ReleaseController::class, 'index']);
        Route::post('/', [ReleaseController::class, 'store']);
        Route::post('/{release}/publish', [ReleaseController::class, 'publish']);
        Route::post('/{release}/pause', [ReleaseController::class, 'pause']);
        Route::post('/{release}/revoke', [ReleaseController::class, 'revoke']);
    });

    // Deployments
    Route::prefix('v1/commercial/deployments')->middleware(PermissionMiddleware::using('manage-deployments|manage-commercial-license'))->group(function (): void {
        Route::get('/', [DeploymentController::class, 'index']);
    });

    // Provisioning
    Route::prefix('v1/commercial/provisioning')->middleware(PermissionMiddleware::using('provision-clients|manage-commercial-organizations'))->group(function (): void {
        Route::post('/', [ProvisioningController::class, 'provision']);
    });

    // Fleet Management
    Route::prefix('v1/commercial/fleet')->middleware(PermissionMiddleware::using('view-fleet|manage-commercial-license'))->group(function (): void {
        Route::get('overview', [FleetController::class, 'overview']);
    });
});

// Unauthenticated Machine Bootstrap
Route::prefix('v1/bootstrap')->group(function (): void {
    Route::post('provision', [ProvisioningBootstrapController::class, 'consumeToken']);
});