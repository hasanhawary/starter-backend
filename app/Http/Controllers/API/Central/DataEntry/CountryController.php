<?php

namespace App\Http\Controllers\API\Central\DataEntry;

use App\Filters\Central\Global\JsonNameFilter;
use App\Filters\Central\Global\OrderByFilter;
use App\Http\Controllers\API\BaseController;
use App\Http\Requests\Central\DataEntry\CountryRequest;
use App\Http\Requests\Central\Global\Other\PageRequest;
use App\Http\Resources\Central\DataEntry\CountryResource;
use App\Models\Central\Country;
use App\Trait\Global\HasDeleteMethods;
use App\Trait\Global\HasToggleActiveMethods;
use Illuminate\Http\JsonResponse;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Spatie\Permission\Middleware\PermissionMiddleware;
use function __;

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
            ->through([JsonNameFilter::class, OrderByFilter::class])
            ->thenReturn();

        return successResponse(wrapPaginate($query, CountryResource::class));
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
