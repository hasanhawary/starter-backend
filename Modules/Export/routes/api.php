<?php

use App\Http\Middleware\LanguageMiddleware;
use Illuminate\Support\Facades\Route;
use Modules\Export\App\Http\Controllers\ExportController;
use Modules\Export\app\Http\Controllers\ExportJobController;

Route::middleware([LanguageMiddleware::class, 'auth:sanctum'])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Export Routes
    |--------------------------------------------------------------------------
    */

    Route::get('export-direct', ExportController::class);
    Route::post('export', [ExportJobController::class, 'export']);

    Route::prefix('export-log')->group(function () {
        Route::get('', [ExportJobController::class, 'index']);
        Route::delete('delete', [ExportJobController::class, 'destroy']);
        Route::post('restore', [ExportJobController::class, 'restore']);
        Route::delete('force-delete', [ExportJobController::class, 'forceDelete']);
    });

});
