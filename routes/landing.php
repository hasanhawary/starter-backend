<?php

use App\Http\Controllers\API\Auth\LoginController;
use App\Http\Controllers\API\Auth\LogoutController;
use App\Http\Controllers\API\Auth\OTPController;
use App\Http\Controllers\API\Auth\ResetPasswordController;
use App\Http\Controllers\API\Landing\Profile\ProfileController;
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

Route::middleware(['auth:sanctum'])->group(function () {
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
