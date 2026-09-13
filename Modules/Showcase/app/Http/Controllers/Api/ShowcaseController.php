<?php

namespace Modules\Showcase\app\Http\Controllers\Api;

use App\Filters\Global\DateFilter;
use App\Filters\Global\OrderByFilter;
use App\Filters\Global\TrashedFilter;
use App\Http\Controllers\API\BaseController;
use App\Http\Requests\Global\Other\PageRequest;
use App\Trait\Global\HasDeleteMethods;
use App\Trait\Global\HasPinMethods;
use App\Trait\Global\HasToggleActiveMethods;
use HasanHawary\MediaManager\Facades\Media;
use Illuminate\Http\JsonResponse;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Modules\Showcase\app\Filters\ShowcaseFilter;
use Modules\Showcase\app\Http\Requests\ShowcaseActionRequest;
use Modules\Showcase\app\Http\Requests\ShowcaseNoteRequest;
use Modules\Showcase\app\Http\Requests\ShowcaseRequest;
use Modules\Showcase\app\Http\Resources\ShowcaseNoteResource;
use Modules\Showcase\app\Http\Resources\ShowcaseResource;
use Modules\Showcase\app\Models\Showcase;
use Modules\Showcase\app\Services\ShowcaseService;
use Modules\Showcase\app\Tools\Status\ShowcaseStatusContext;
use Modules\Showcase\app\Tools\Status\ShowcaseStatusFactory;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Throwable;

class ShowcaseController extends BaseController implements HasMiddleware
{
    use HasDeleteMethods, HasPinMethods, HasToggleActiveMethods;

    public function __construct(protected ShowcaseService $service)
    {
        parent::__construct();
        $this->model = Showcase::class;
        $this->beforeDelete('force', fn (Showcase $showcase) => Media::delete($showcase->getRawOriginal('cover')));
    }

    public static function middleware(): array
    {
        return [
            new Middleware(PermissionMiddleware::using('pin-showcase'), only: ['pin']),
        ];
    }

    public function index(PageRequest $request): JsonResponse
    {
        Gate::authorize('viewAny', Showcase::class);

        $query = app(Pipeline::class)
            ->send(Showcase::query()->visibleTo()->withListingData())
            ->through([ShowcaseFilter::class, TrashedFilter::class, DateFilter::class, OrderByFilter::class])
            ->thenReturn();

        return successResponse(wrapPaginate($query, ShowcaseResource::class));
    }

    /**
     * @throws Throwable
     */
    public function store(ShowcaseRequest $request): JsonResponse
    {
        Gate::authorize('create', Showcase::class);

        return DB::transaction(function () use ($request) {
            $data = $request->validated();

            $showcase = $this->service->store($data);
            $showcase->syncTags($data['tag_ids'] ?? [], $data['primary_tag_id'] ?? null);
            $showcase->syncOpeningNote($data['note'] ?? null);

            return successResponse(
                new ShowcaseResource($showcase->loadDetailData()),
                __('showcase::api.created_success')
            );
        });
    }

    public function show(Showcase $showcase): JsonResponse
    {
        Gate::authorize('view', $showcase);

        return successResponse(new ShowcaseResource($showcase->loadDetailData()));
    }

    /**
     * @throws Throwable
     */
    public function update(ShowcaseRequest $request, Showcase $showcase): JsonResponse
    {
        Gate::authorize('update', $showcase);

        return DB::transaction(function () use ($request, $showcase) {
            $data = $request->validated();

            $showcase = $this->service->update($showcase, $data);
            $showcase->syncTags($data['tag_ids'] ?? [], $data['primary_tag_id'] ?? null);

            return successResponse(
                new ShowcaseResource($showcase->loadDetailData()),
                __('showcase::api.updated_success')
            );
        });
    }

    /**
     * @throws Throwable
     */
    public function takeAction(ShowcaseActionRequest $request, Showcase $showcase): JsonResponse
    {
        return DB::transaction(static function () use ($request, $showcase) {
            $showcase = $showcase->lockFresh();

            (new ShowcaseStatusContext)
                ->setStatus(ShowcaseStatusFactory::guess($request->validated('status'), $showcase, auth()->user()))
                ->handle($request->validated());

            return successResponse(
                new ShowcaseResource($showcase->refresh()->loadDetailData()),
                __('showcase::api.action_taken_success')
            );
        });
    }

    public function addNote(ShowcaseNoteRequest $request, Showcase $showcase): JsonResponse
    {
        Gate::authorize('update', $showcase);

        $note = $showcase->addNote($request->validated('body'), $request->validated('type'));

        return successResponse(
            new ShowcaseNoteResource($note->load('author')),
            __('showcase::api.note_added_success')
        );
    }
}
