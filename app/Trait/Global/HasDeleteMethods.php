<?php

namespace App\Trait\Global;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

trait HasDeleteMethods
{
    protected string $model;

    /**
     * Action guards (delete|restore|force)
     */
    protected array $guards = [];
    protected bool $usePolicy = true;
    protected array $beforeCallbacks = [];
    protected array $afterCallbacks = [];

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
        $this->usePolicy = $state;
        return $this;
    }

    /**
     * Set guards for an action (except callable or array of callables)
     */
    protected function setDeleteGuards(string $action, callable|array $guards): self
    {
        $guards = is_array($guards) ? $guards : [$guards];
        $this->guards[$action] = array_merge($this->guards[$action] ?? [], $guards);
        return $this;
    }

    protected function beforeDelete(string $action, callable|array $callback): self
    {
        $callback = is_array($callback) ? $callback : [$callback];
        $this->beforeCallbacks[$action] = array_merge($this->beforeCallbacks[$action] ?? [], $callback);
        return $this;
    }

    protected function afterDelete(string $action, callable|array $callback): self
    {
        $callback = is_array($callback) ? $callback : [$callback];
        $this->afterCallbacks[$action] = array_merge($this->afterCallbacks[$action] ?? [], $callback);
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
    protected function handle(string $action): JsonResponse
    {
        $ids = $this->resolveIds();
        $query = $this->buildQuery($action, $ids);
        $models = $query->get();

        if ($models->isEmpty()) {
            return failResponse(msg: __('api.record_not_found'));
        }

        foreach ($models as $model) {
            // Policy
            if ($this->usePolicy) {
                $this->applyAuthorize($action, $model);
            }

            // Custom Guards
            if (!$this->passesGuards($action, $model)) {
                return failResponse(msg: __("api.not_allowed_to_{$action}", ['id' => $model->getKey()]));
            }

            // Before callbacks
            $this->runCallbacks($this->beforeCallbacks[$action] ?? [], $model);

            // Execute action
            $this->execute($model, $action);

            // After callbacks
            $this->runCallbacks($this->afterCallbacks[$action] ?? [], $model);
        }

        return successResponse(msg: __("api." .
            match ($action) {
                'restore' => 'restored_success',
                default => 'deleted_success'
            }
        ));
    }

    protected function applyAuthorize(string $action, Model $model): void
    {
        $ability = match ($action) {
            'force' => 'force-delete',
            default => $action,
        };

        if (Gate::getPolicyFor($model)) {
            Gate::authorize($ability, $model);
        } else {
            // If Gate fails, fallback to Spatie permission in case not have policy only.
            $permission = $ability . '-' . Str::snake(class_basename($model), '-');

            if (!auth()->user()?->hasPermissionTo($permission)) {
                abort403();
            }
        }
    }

    protected function passesGuards(string $action, Model $model): bool
    {
        foreach ($this->guards[$action] ?? [] as $guard) {
            if (is_callable($guard) && !$guard($model)) {
                return false;
            }
        }

        return true;
    }

    protected function execute(Model $model, string $action): void
    {
        match ($action) {
            'restore' => $model->restore(),
            'force' => method_exists($model, 'forceDelete')
                ? $model->forceDelete()
                : $model->delete(),
            default => $model->delete(),
        };
    }

    protected function buildQuery(string $action, array $ids)
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

    protected function resolveIds(): array
    {
        return Arr::wrap(
            request()->input('ids')
            ?? request()->input('id')
            ?? last(explode('/', request()->path()))
        );
    }

    protected function runCallbacks(array $callbacks, Model $model): void
    {
        foreach ($callbacks as $callback) {
            $callback($model);
        }
    }
}
