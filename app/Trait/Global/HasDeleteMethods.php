<?php

namespace App\Trait\Global;

use App\Models\Role;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

trait HasDeleteMethods
{
    public string $model;

    /**
     * Action guards (delete|restore|force)
     */
    protected array $deleteGuards = [];

    protected bool $useDeletePolicy = true;

    protected array $beforeDeleteCallbacks = [];

    protected array $afterDeleteCallbacks = [];

    /*
    |--------------------------------------------------------------------------
    | Configuration Methods
    |--------------------------------------------------------------------------
    */
    protected function setDeleteModel(string $model): self
    {
        $this->model = $model;

        return $this;
    }

    protected function enableDeletePolicy(bool $state = true): self
    {
        $this->useDeletePolicy = $state;

        return $this;
    }

    /**
     * Set guards for an action (except callable or array of callables)
     */
    protected function setDeleteGuards(string $action, callable|array $guards): self
    {
        $guards = is_array($guards) ? $guards : [$guards];
        $this->deleteGuards[$action] = array_merge($this->deleteGuards[$action] ?? [], $guards);

        return $this;
    }

    protected function beforeDelete(string $action, callable|array $callback): self
    {
        $callback = is_array($callback) ? $callback : [$callback];
        $this->beforeDeleteCallbacks[$action] = array_merge($this->beforeDeleteCallbacks[$action] ?? [], $callback);

        return $this;
    }

    protected function afterDelete(string $action, callable|array $callback): self
    {
        $callback = is_array($callback) ? $callback : [$callback];
        $this->afterDeleteCallbacks[$action] = array_merge($this->afterDeleteCallbacks[$action] ?? [], $callback);

        return $this;
    }

    /*
    |--------------------------------------------------------------------------
    | Public Methods
    |--------------------------------------------------------------------------
    */
    public function destroy(): JsonResponse
    {
        return $this->handle('delete');
    }

    public function restore(): JsonResponse
    {
        return $this->handle('restore');
    }

    public function forceDelete(): JsonResponse
    {
        return $this->handle('force');
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */
    private function handle(string $action): JsonResponse
    {
        $ids = $this->resolveDeleteIds();
        $query = $this->buildDeleteQuery($action, $ids);
        $models = $query->get();

        if ($models->isEmpty()) {
            return failResponse(__('api.record_not_found'));
        }

        foreach ($models as $model) {
            // Policy
            if ($this->useDeletePolicy) {
                $this->applyDeleteAuthorize($action, $model);
            }

            // Custom Guards
            if (! $this->passesDeleteGuards($action, $model)) {
                abort(403, __("api.not_allowed_to_{$action}", ['id' => $model->getKey()]));
            }

            // Before callbacks
            $this->runDeleteCallbacks($this->beforeDeleteCallbacks[$action] ?? [], $model);

            // Execute action
            $this->executeDelete($model, $action);

            // After callbacks
            $this->runDeleteCallbacks($this->afterDeleteCallbacks[$action] ?? [], $model);
        }

        return successResponse(msg: __('api.'.
            match ($action) {
                'restore' => 'restored_success',
                default => 'deleted_success'
            }
        ));
    }

    protected function applyDeleteAuthorize(string $action, Model $model): void
    {
        $ability = match ($action) {
            'force' => 'force-delete',
            default => $action,
        };

        if (Gate::getPolicyFor($model)) {
            Gate::authorize($ability, $model);
        } else {
            // If Gate fails, fallback to Spatie permission in case not have policy only.
            $permission = $ability.'-'.Str::snake(class_basename($model), '-');

            if (! auth()->user()?->hasPermissionTo($permission)) {
                abort(403, __("api.not_allowed_to_{$action}", ['id' => $model->getKey()]));
            }
        }
    }

    protected function passesDeleteGuards(string $action, Model $model): bool
    {
        foreach ($this->deleteGuards[$action] ?? [] as $guard) {
            if (is_callable($guard) && ! $guard($model)) {
                return false;
            }
        }

        return true;
    }

    protected function executeDelete(Model $model, string $action): void
    {
        match ($action) {
            'restore' => $model->restore(),
            'force' => method_exists($model, 'forceDelete')
                ? $model->forceDelete()
                : $model->delete(),
            default => $model instanceof Role ? $model->deleteQuietly() : $model->delete()
        };
    }

    protected function buildDeleteQuery(string $action, array $ids)
    {
        $query = $this->model::query();

        if (in_array($action, ['restore', 'force'], true) && $this->supportsSoftDeletes()) {
            $query->onlyTrashed();
        }

        return $query->whereIn('id', $ids);
    }

    protected function supportsSoftDeletes(): bool
    {
        return in_array(
            SoftDeletes::class,
            class_uses_recursive($this->model),
            true
        );
    }

    protected function resolveDeleteIds(): array
    {
        $ids = request()->input('ids')
            ?? request()->input('id');

        if (! $ids) {
            $routeParams = request()->route()?->parameters();
            if (! empty($routeParams)) {
                $ids = array_values($routeParams)[0]; // take the first parameter
            }
        }

        return Arr::wrap($ids); // always return as array
    }

    protected function runDeleteCallbacks(array $callbacks, Model $model): void
    {
        foreach ($callbacks as $callback) {
            $callback($model);
        }
    }
}
