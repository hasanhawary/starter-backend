<?php

namespace App\Http\Controllers\API\Client;

use App\Filters\Global\OrderByFilter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Central\Client\StageRequest;
use App\Http\Requests\Central\Global\Other\PageRequest;
use App\Http\Resources\Central\Client\StageResource;
use App\Models\Stage;
use App\Trait\Global\HasSoftDeleteMethods;
use Illuminate\Http\JsonResponse;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Spatie\Permission\Middleware\PermissionMiddleware;
use function __;

class StageController extends Controller implements HasMiddleware
{
    use HasSoftDeleteMethods;

    public function __construct()
    {
        $this->setSoftDeleteModel(Stage::class);
    }

    public static function middleware(): array
    {
        return [
            new Middleware(PermissionMiddleware::using('read-stage'), only: ['index', 'show']),
            new Middleware(PermissionMiddleware::using('create-stage'), only: ['store']),
            new Middleware(PermissionMiddleware::using('update-stage'), only: ['update']),
        ];
    }

    /**
     * @param PageRequest $request
     * @return JsonResponse
     */
    public function index(PageRequest $request): JsonResponse
    {
        $query = app(Pipeline::class)
            ->send(Stage::query())
            ->through([OrderByFilter::class])
            ->thenReturn();

        return successResponse(fetchData($query, $request->pageSize, StageResource::class));
    }

    /**
     * @param StageRequest $request
     * @return JsonResponse
     */
    public function store(StageRequest $request): JsonResponse
    {
        $stage = Stage::create($request->validated());

        return successResponse(new StageResource($stage), __('api.created_success'));
    }

    /**
     * @param Stage $stage
     * @return JsonResponse
     */
    public function show(Stage $stage): JsonResponse
    {
        return successResponse(new StageResource($stage));
    }

    /**
     * @param StageRequest $request
     * @param Stage $stage
     * @return JsonResponse
     */
    public function update(StageRequest $request, Stage $stage): JsonResponse
    {
        $stage->update($request->validated());

        return successResponse(new StageResource($stage->refresh()), __('api.updated_success'));
    }
}
