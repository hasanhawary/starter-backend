<?php

use Illuminate\Support\Facades\Route;
use Modules\Form\app\Http\Controllers\Api\Admin\FormController;
use Modules\Form\app\Http\Controllers\Api\Admin\FormSubmissionController;

Route::middleware(['auth:sanctum'])->group(function () {
    /*
    |--------------------------------------------------------------------------
    | Forms Routes
    |--------------------------------------------------------------------------
    */
    Route::get('/forms/{form}/fields', [FormController::class , 'fields']);

    Route::get('forms/validation-rules', [FormController::class, 'getValidationRules']);
    Route::apiResource('/forms', FormController::class)->except(['destroy']);
    Route::delete('forms/force-delete', [FormController::class, 'forceDelete']);
    Route::delete('forms/delete', [FormController::class, 'destroy']);
    Route::post('forms/restore', [FormController::class, 'restore']);
    Route::post('forms/{form}/change-status', [FormController::class, 'changeStatus']);

    /*
    |--------------------------------------------------------------------------
    | Form Submissions Routes
    |--------------------------------------------------------------------------
    */

    Route::get('form-submissions/source', [FormSubmissionController::class, 'showBySource']);
    Route::post('form-submissions/restore', [FormSubmissionController::class, 'restore']);
    Route::delete('form-submissions/delete', [FormSubmissionController::class, 'destroy']);
    Route::delete('form-submissions/force-delete', [FormSubmissionController::class, 'forceDelete']);
    Route::apiResource('form-submissions', FormSubmissionController::class);
});
