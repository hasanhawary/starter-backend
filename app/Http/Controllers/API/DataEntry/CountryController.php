<?php

namespace App\Http\Controllers\API\DataEntry;

use App\Filters\Global\ActiveFilter;
use App\Filters\Global\JsonNameFilter;
use App\Filters\Global\OrderByFilter;
use App\Filters\Global\TrashedFilter;
use App\Http\Controllers\API\BaseController;
use App\Http\Requests\DataEntry\CountryRequest;
use App\Http\Requests\Global\Other\PageRequest;
use App\Http\Resources\DataEntry\CountryResource;
use App\Models\Country;
use App\Trait\Global\HasDeleteMethods;
use App\Trait\Global\HasToggleActiveMethods;
use Illuminate\Http\JsonResponse;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Spatie\Permission\Middleware\PermissionMiddleware;

class CountryController extends BaseController implements HasMiddleware
{
    use HasDeleteMethods, HasToggleActiveMethods;

    public function __construct()
    {
        parent::__construct();
        $this->model = Country::class;
    }

    public static function middleware(): array
    {
        return [
            new Middleware(PermissionMiddleware::using('create-country'), only: ['store']),
            new Middleware(PermissionMiddleware::using('update-country'), only: ['update']),
        ];
    }

    public function index(PageRequest $request): JsonResponse
    {
        $query = app(Pipeline::class)
            ->send(Country::query())
            ->through([JsonNameFilter::class, TrashedFilter::class, ActiveFilter::class, OrderByFilter::class])
            ->thenReturn();

        return successResponse(wrapPaginate($query, CountryResource::class));
    }

    public function store(CountryRequest $request): JsonResponse
    {
        $country = Country::create($request->validated());

        return successResponse(new CountryResource($country->refresh()), __('api.created_success'));
    }

    public function show(Country $country): JsonResponse
    {
        return successResponse(new CountryResource($country));
    }

    public function update(CountryRequest $request, Country $country): JsonResponse
    {
        $country->update($request->validated());

        return successResponse(new CountryResource($country->refresh()), __('api.updated_success'));
    }
}
