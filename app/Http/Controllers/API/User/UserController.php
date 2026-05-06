<?php

namespace App\Http\Controllers\API\User;

use App\Enum\Global\NotificationGroupEnum;
use App\Filters\Global\ActiveFilter;
use App\Filters\Global\OrderByFilter;
use App\Filters\Global\TrashedFilter;
use App\Filters\User\UserFilter;
use App\Helpers\DelimiterParamValue;
use App\Http\Controllers\API\BaseController;
use App\Http\Requests\Global\Other\PageRequest;
use App\Http\Requests\User\UserRequest;
use App\Http\Resources\User\UserResource;
use App\Models\Role;
use App\Models\User;
use App\Trait\Global\HasDeleteMethods;
use App\Trait\Global\HasToggleActiveMethods;
use HasanHawary\MediaManager\Facades\Media;
use Illuminate\Http\JsonResponse;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Throwable;

class UserController extends BaseController
{
    use HasDeleteMethods, HasToggleActiveMethods;

    public function __construct()
    {
        parent::__construct();
        $this->model = User::class;
        $this->beforeDelete('force', fn(User $user) => Media::delete($user->avatar));
    }

    /**
     * @param PageRequest $request
     * @return JsonResponse
     */
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
     * @param UserRequest $request
     * @return JsonResponse
     * @throws Throwable
     */
    public function store(UserRequest $request): JsonResponse
    {
        Gate::authorize('create', User::class);

        return DB::transaction(function () use ($request) {
            $user = User::create($request->validated());
            $this->syncRelations($user, $request);

            DB::afterCommit(fn() => $this->sendCredentials($user, $request));

            return successResponse(new UserResource($user->refresh()), __('api.created_success'));
        });
    }

    /**
     * @param UserRequest $request
     * @param User $user
     * @return JsonResponse
     * @throws Throwable
     */
    public function update(UserRequest $request, User $user): JsonResponse
    {
        Gate::authorize('update', $user);

        return DB::transaction(function () use ($user, $request) {
            $user->update($request->validated());
            $this->syncRelations($user, $request);

            DB::afterCommit(fn() => $this->sendCredentials($user->refresh(), $request));

            return successResponse(new UserResource($user->refresh()), __('api.updated_success'));
        });
    }

    /**
     * @param User $user
     * @return JsonResponse
     */
    public function show(User $user): JsonResponse
    {
        Gate::authorize('view', $user);

        return successResponse(new UserResource($user->load('roles')));
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */
    private function syncRelations(User $user, UserRequest $request): void
    {
        when($request->filled('roles'), static fn() => $user->syncRoles(Role::whereId($request->roles)->pluck('name')));
        when($request->filled('permissions'), static fn() => $user->syncPermissions($request->permissions));
    }

    /**
     * @param User $user
     * @param UserRequest $request
     * @param bool $isCreate
     * @return void
     */
    private function sendCredentials(User $user, UserRequest $request, bool $isCreate = true): void
    {
        // Skip update if nothing changed
         if (!$isCreate && !($user->isDirty('email') || $user->isDirty('password'))) {
             return;
         }

        $params = [
            'name' => $request->name,       // optional use types (plain, json, enum) DelimiterParamValue::plain($request->name)
            'email' => $request->email,
            'phone' => $user->getFullPhone(),
            'password' => (string)$request->password,
        ];
        $params[$isCreate ? 'created_at' : 'updated_at'] = DelimiterParamValue::plain(now()->format('Y-m-d H:i'));
        $user->sendNotification([
            'title' => $isCreate ? 'create_admin_data_title' : 'update_admin_data_title',
            'msg' => buildDelimiterMessage($isCreate ? 'create_admin_data_msg' : 'update_admin_data_msg', $params),
            'target_id' => $user->id,
            'target_type' => 'users',
            'group' => NotificationGroupEnum::Global->value
        ], ['email', 'realtime', 'notify']);
    }
}
