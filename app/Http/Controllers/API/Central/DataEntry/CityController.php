<?php

namespace App\Http\Controllers\API\DataEntry;

use App\Http\Controllers\Controller;
use App\Http\Requests\DataEntry\CityRequest;
use App\Http\Requests\Global\Other\PageRequest;
use App\Filters\Global\OrderByFilter;
use App\Http\Resources\DataEntry\CityResource;
use App\Models\City;
use App\Trait\Global\HasSoftDeleteMethods;
use Illuminate\Http\JsonResponse;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Spatie\Permission\Middleware\PermissionMiddleware;
use function __;

class CityController extends Controller implements HasMiddleware
{
    use HasSoftDeleteMethods;

    public function __construct()
    {
        $this->setSoftDeleteModel(City::class);
    }

    public static function middleware(): array
    {
        return [
            new Middleware(PermissionMiddleware::using('read-city'), only: ['index', 'show']),
            new Middleware(PermissionMiddleware::using('create-city'), only: ['store']),
            new Middleware(PermissionMiddleware::using('update-city'), only: ['update']),
        ];
    }

    /**
     * @param PageRequest $request
     * @return JsonResponse
     */
    public function index(PageRequest $request): JsonResponse
    {
        $query = app(Pipeline::class)
            ->send(City::query())
            ->through([OrderByFilter::class])
            ->thenReturn();

        return successResponse(fetchData($query, $request->pageSize, CityResource::class));
    }

    /**
     * @param CityRequest $request
     * @return JsonResponse
     */
    public function store(CityRequest $request): JsonResponse
    {
        $city = City::create($request->validated());

        return successResponse(new CityResource($city), __('api.created_success'));
    }

    /**
     * @param City $city
     * @return JsonResponse
     */
    public function show(City $city): JsonResponse
    {
        return successResponse(new CityResource($city));
    }

    /**
     * @param CityRequest $request
     * @param City $city
     * @return JsonResponse
     */
    public function update(CityRequest $request, City $city): JsonResponse
    {
        $city->update($request->validated());

        return successResponse(new CityResource($city->refresh()), __('api.updated_success'));
    }
}
