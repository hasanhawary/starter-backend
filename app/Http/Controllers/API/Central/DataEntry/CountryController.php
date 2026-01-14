<?php

namespace App\Http\Controllers\API\Central\DataEntry;

use App\Filters\Central\Global\JsonDisplayNameFilter;
use App\Filters\Central\Global\OrderByFilter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Central\DataEntry\CountryRequest;
use App\Http\Requests\Central\Global\Other\PageRequest;
use App\Http\Resources\Central\DataEntry\CountryResource;
use App\Models\Central\Country;
use App\Trait\Global\HasDeleteMethods;
use Illuminate\Http\JsonResponse;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Spatie\Permission\Middleware\PermissionMiddleware;
use function __;

class CountryController extends Controller implements HasMiddleware
{
    use HasDeleteMethods;

    public function __construct()
    {
        $this->model = Country::class;
    }

    public static function middleware(): array
    {
        return [
            new Middleware(PermissionMiddleware::using('create-country'), only: ['store']),
            new Middleware(PermissionMiddleware::using('update-country'), only: ['update'])
        ];
    }

    /**
     * @param PageRequest $request
     * @return JsonResponse
     */
    public function index(PageRequest $request): JsonResponse
    {
        $query = app(Pipeline::class)
            ->send(Country::query())
            ->through([JsonDisplayNameFilter::class, OrderByFilter::class])
            ->thenReturn();

        return successResponse(fetchData($query, $request->pageSize, CountryResource::class));
    }

    /**
     * @param CountryRequest $request
     * @return JsonResponse
     */
    public function store(CountryRequest $request): JsonResponse
    {
        $country = Country::create($request->validated());

        return successResponse(new CountryResource($country), __('api.created_success'));
    }

    /**
     * @param Country $country
     * @return JsonResponse
     */
    public function show(Country $country): JsonResponse
    {
        return successResponse(new CountryResource($country));
    }

    /**
     * @param CountryRequest $request
     * @param Country $country
     * @return JsonResponse
     */
    public function update(CountryRequest $request, Country $country): JsonResponse
    {
        $country->update($request->validated());

        return successResponse(new CountryResource($country->refresh()), __('api.updated_success'));
    }
}
