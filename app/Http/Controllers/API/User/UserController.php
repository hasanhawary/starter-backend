<?php

namespace App\Http\Controllers\API\User;

use App\Filters\Global\ActiveFilter;
use App\Filters\Global\OrderByFilter;
use App\Filters\Global\TrashedFilter;
use App\Filters\User\UserFilter;
use App\Http\Controllers\API\BaseController;
use App\Http\Requests\Global\Other\PageRequest;
use App\Http\Requests\User\UserRequest;
use App\Http\Resources\User\UserResource;
use App\Models\User;
use App\Services\User\UserService;
use App\Trait\Global\HasDeleteMethods;
use App\Trait\Global\HasToggleActiveMethods;
use HasanHawary\MediaManager\Facades\Media;
use Illuminate\Http\JsonResponse;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Facades\Gate;
use Throwable;

class UserController extends BaseController
{
    use HasDeleteMethods, HasToggleActiveMethods;

    public function __construct(private readonly UserService $userService)
    {
        parent::__construct();
        $this->model = User::class;
        $this->beforeDelete('force', fn (User $user) => Media::delete($user->avatar));
    }

    public function index(PageRequest $request): JsonResponse
    {
        Gate::authorize('view', User::class);

        $query = app(Pipeline::class)
            ->send(User::with('roles')->related())
            ->through([UserFilter::class, ActiveFilter::class, TrashedFilter::class, OrderByFilter::class])
            ->thenReturn();

        return successResponse(wrapPaginate($query, UserResource::class));
    }

    /**
     * @throws Throwable
     */
    public function store(UserRequest $request): JsonResponse
    {
        Gate::authorize('create', User::class);

        $user = $this->userService->store($request);

        return successResponse(new UserResource($user), __('api.created_success'));
    }

    /**
     * @throws Throwable
     */
    public function update(UserRequest $request, User $user): JsonResponse
    {
        Gate::authorize('update', $user);

        $user = $this->userService->update($user, $request);

        return successResponse(new UserResource($user), __('api.updated_success'));
    }

    public function show(User $user): JsonResponse
    {
        Gate::authorize('view', $user);

        return successResponse(new UserResource($user->load('roles')));
    }
}
