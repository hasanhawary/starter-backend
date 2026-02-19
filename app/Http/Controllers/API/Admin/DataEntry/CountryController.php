<?php

namespace App\Http\Controllers\API\Admin\DataEntry;

use App\Filters\Global\JsonNameFilter;
use App\Filters\Global\OrderByFilter;
use App\Http\Controllers\API\BaseController;
use App\Http\Requests\Admin\DataEntry\CountryRequest;
use App\Http\Requests\Global\Other\PageRequest;
use App\Http\Resources\Global\DataEntry\CountryResource;
use App\Models\Country;
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
