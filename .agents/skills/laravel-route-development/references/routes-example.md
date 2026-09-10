# Canonical Delegation-Style API Routes

Use this structure for a new module route file. Replace names and add only custom endpoints that belong to the target feature.

```php
<?php

use Illuminate\Support\Facades\Route;
use Modules\Example\App\Http\Controllers\ExampleController;

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
        | Example Routes
        |--------------------------------------------------------------------------
        */
        Route::group(['prefix' => 'examples'], function () {
            Route::delete('force-delete', [ExampleController::class, 'forceDelete']);
            Route::delete('delete', [ExampleController::class, 'destroy']);
            Route::post('restore', [ExampleController::class, 'restore']);

            Route::post('{example}/take-action', [ExampleController::class, 'takeAction']);
            Route::get('can-create', [ExampleController::class, 'canCreate']);

            Route::apiResource('/', ExampleController::class)->names('examples')->parameters(['' => 'example'])->except(['destroy']);
        });
    });
});
```

The first three endpoints are the `HasDeleteMethods` lifecycle. Include `restore` and `force-delete` only for a real soft-delete contract. `take-action` and `can-create` demonstrate route placement only; omit them unless the target controller genuinely owns those actions.

The important invariants are:

1. Lifecycle and other fixed routes precede the resource wildcard.
2. The dedicated `delete` endpoint maps to the trait's `destroy()` action.
3. The resource route excludes `destroy`.
4. The singular resource parameter matches the controller signature and binding.
5. Authentication is applied once at the established group boundary.

For routes added to the shared application `routes/api.php`, preserve the existing outer groups and use this equivalent inner shape:

```php
Route::group(['prefix' => 'examples'], function () {
    Route::delete('force-delete', [ExampleController::class, 'forceDelete']);
    Route::delete('delete', [ExampleController::class, 'destroy']);
    Route::post('restore', [ExampleController::class, 'restore']);

    Route::apiResource('/', ExampleController::class)->names('examples')->parameters(['' => 'example'])->except(['destroy']);
});
```

Do not rewrite existing public routes into this form as incidental cleanup. Use it for new contracts or an explicitly requested route migration after checking all consumers.
