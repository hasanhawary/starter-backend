<?php

use Illuminate\Support\Facades\Route;
use Modules\Showcase\app\Http\Controllers\Api\ShowcaseCategoryController;
use Modules\Showcase\app\Http\Controllers\Api\ShowcaseController;

/*
    |--------------------------------------------------------------------------
    | API Routes
    |--------------------------------------------------------------------------
    |
    | Here is where you can register API routes for your application. These
    | routes are loaded by the RouteServiceProvider within a group which
    | is assigned the "api" middleware group. Enjoy building your API!
    |
*/

Route::group([], function () {
    Route::group(['middleware' => 'auth:sanctum'], function () {
        /*
        |--------------------------------------------------------------------------
        | Showcase Category Routes
        |--------------------------------------------------------------------------
        */
        Route::group(['prefix' => 'showcase-categories'], function () {
            Route::delete('force-delete', [ShowcaseCategoryController::class, 'forceDelete']);
            Route::delete('delete', [ShowcaseCategoryController::class, 'destroy']);
            Route::post('restore', [ShowcaseCategoryController::class, 'restore']);
            Route::put('toggle-active', [ShowcaseCategoryController::class, 'toggleActive']);

            Route::apiResource('/', ShowcaseCategoryController::class)->names('showcase-categories')->parameters(['' => 'showcase_category'])->except(['destroy']);
        });

        /*
        |--------------------------------------------------------------------------
        | Showcase Routes
        |--------------------------------------------------------------------------
        */
        Route::group(['prefix' => 'showcases'], function () {
            Route::delete('force-delete', [ShowcaseController::class, 'forceDelete']);
            Route::delete('delete', [ShowcaseController::class, 'destroy']);
            Route::post('restore', [ShowcaseController::class, 'restore']);
            Route::put('toggle-active', [ShowcaseController::class, 'toggleActive']);

            Route::post('{showcase}/take-action', [ShowcaseController::class, 'takeAction']);
            Route::post('{showcase}/pin', [ShowcaseController::class, 'pin']);
            Route::post('{showcase}/notes', [ShowcaseController::class, 'addNote']);

            Route::apiResource('/', ShowcaseController::class)->names('showcases')->parameters(['' => 'showcase'])->except(['destroy']);
        });
    });
});
