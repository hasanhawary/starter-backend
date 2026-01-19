<?php

namespace App\Http\Controllers\API\Global\DataEntry;

use App\Filters\Global\JsonDisplayNameFilter;
use App\Filters\Global\OrderByFilter;
use App\Http\Controllers\API\BaseController;
use App\Http\Requests\Global\Other\PageRequest;
use App\Http\Resources\Global\DataEntry\CountryResource;
use App\Models\Country;
use Illuminate\Http\JsonResponse;
use Illuminate\Pipeline\Pipeline;

class CountryController extends BaseController
{
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
}

