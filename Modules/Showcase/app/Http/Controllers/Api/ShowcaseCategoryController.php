<?php

namespace Modules\Showcase\app\Http\Controllers\Api;

use App\Filters\Global\ActiveFilter;
use App\Filters\Global\JsonNameFilter;
use App\Filters\Global\OrderByFilter;
use App\Filters\Global\TrashedFilter;
use App\Http\Controllers\API\BaseController;
use App\Http\Requests\Global\Other\PageRequest;
use App\Trait\Global\HasDeleteMethods;
use App\Trait\Global\HasToggleActiveMethods;
use HasanHawary\MediaManager\Facades\Media;
use Illuminate\Http\JsonResponse;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Modules\Showcase\app\Http\Requests\ShowcaseCategoryRequest;
use Modules\Showcase\app\Http\Resources\ShowcaseCategoryResource;
use Modules\Showcase\app\Models\ShowcaseCategory;
use Spatie\Permission\Middleware\PermissionMiddleware;

class ShowcaseCategoryController extends BaseController implements HasMiddleware
{
    use HasDeleteMethods, HasToggleActiveMethods;

    public function __construct()
    {
        parent::__construct();
        $this->model = ShowcaseCategory::class;
        $this->beforeDelete('force', fn (ShowcaseCategory $category) => Media::delete($category->getRawOriginal('icon')));
    }

    public static function middleware(): array
    {
        return [
            new Middleware(PermissionMiddleware::using('create-showcase-category'), only: ['store']),
            new Middleware(PermissionMiddleware::using('update-showcase-category'), only: ['update']),
        ];
    }

    public function index(PageRequest $request): JsonResponse
    {
        $query = app(Pipeline::class)
            ->send(ShowcaseCategory::query()->with('creator')->withCount('showcases'))
            ->through([JsonNameFilter::class, TrashedFilter::class, ActiveFilter::class, OrderByFilter::class])
            ->thenReturn();

        return successResponse(wrapPaginate($query, ShowcaseCategoryResource::class));
    }

    public function store(ShowcaseCategoryRequest $request): JsonResponse
    {
        $category = ShowcaseCategory::create($request->validated());

        return successResponse(
            new ShowcaseCategoryResource($category->refresh()),
            __('showcase::api.created_success')
        );
    }

    public function show(ShowcaseCategory $showcaseCategory): JsonResponse
    {
        return successResponse(
            new ShowcaseCategoryResource($showcaseCategory->load(['parent', 'children', 'creator'])->loadCount('showcases'))
        );
    }

    public function update(ShowcaseCategoryRequest $request, ShowcaseCategory $showcaseCategory): JsonResponse
    {
        $showcaseCategory->update($request->validated());

        return successResponse(
            new ShowcaseCategoryResource($showcaseCategory->refresh()),
            __('showcase::api.updated_success')
        );
    }
}
