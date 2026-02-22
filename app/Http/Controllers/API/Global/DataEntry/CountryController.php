<?php

namespace App\Http\Controllers\API\Global\DataEntry;

use App\Filters\Global\ActiveFilter;
use App\Filters\Global\JsonNameFilter;
use App\Filters\Global\OrderByFilter;
use App\Filters\Global\TrashedFilter;
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
            ->through([JsonNameFilter::class, TrashedFilter::class, ActiveFilter::class, OrderByFilter::class])
            ->thenReturn();

        return successResponse(wrapPaginate($query, CountryResource::class));
    }
}

