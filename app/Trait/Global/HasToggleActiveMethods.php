<?php

namespace App\Trait\Global;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

trait HasToggleActiveMethods
{
    public string $model;

    protected bool $useTogglePolicy = true;

    protected array $toggleGuards = [];

    protected array $beforeToggleCallbacks = [];

    protected array $afterToggleCallbacks = [];

    /*
    |--------------------------------------------------------------------------
    | Configuration Methods
    |--------------------------------------------------------------------------
    */
    protected function setToggleModel(string $model): self
    {
        $this->model = $model;

        return $this;
    }

    protected function enableTogglePolicy(bool $state = true): self
    {
        $this->useTogglePolicy = $state;

        return $this;
    }

    protected function setToggleGuards(callable|array $guards): self
    {
        $guards = is_array($guards) ? $guards : [$guards];
        $this->toggleGuards = array_merge($this->toggleGuards ?? [], $guards);

        return $this;
    }

    protected function beforeToggle(callable|array $callback): self
    {
        $callback = is_array($callback) ? $callback : [$callback];
        $this->beforeToggleCallbacks = array_merge($this->beforeToggleCallbacks ?? [], $callback);

        return $this;
    }

    protected function afterToggle(callable|array $callback): self
    {
        $callback = is_array($callback) ? $callback : [$callback];
        $this->afterToggleCallbacks = array_merge($this->afterToggleCallbacks ?? [], $callback);

        return $this;
    }

    /*
    |--------------------------------------------------------------------------
    | Public Method
    |--------------------------------------------------------------------------
    */
    public function toggleActive(): JsonResponse
    {
        $ids = $this->resolveToggleIds();
        $query = $this->model::query()->whereIn('id', $ids);
        $models = $query->get();

        // Ensure Is Support IsActive action
        if (! Schema::hasColumn((new $this->model)->getTable(), 'is_active')) {
            return failResponse(__('api.model_not_support_toggle', ['model' => class_basename($this->model)]));
        }

        if ($models->isEmpty()) {
            return failResponse(__('api.record_not_found'));
        }

        foreach ($models as $model) {
            // Policy
            if ($this->useTogglePolicy) {
                $this->applyToggleAuthorize($model);
            }

            // Guards
            if (! $this->passesToggleGuards($model)) {
                abort(403, __('api.not_allowed_to_toggle_active', ['id' => $model->getKey()]));
            }

            // Before callbacks
            $this->runToggleCallbacks($this->beforeToggleCallbacks ?? [], $model);

            // Execute
            $model->update(['is_active' => ! $model->is_active]);

            // After callbacks
            $this->runToggleCallbacks($this->afterToggleCallbacks ?? [], $model);
        }

        // Success message
        $lastModel = $models->last();
        $statusKey = $lastModel->is_active ? 'model_activated' : 'model_deactivated';

        return successResponse(msg: __("api.$statusKey", ['model' => getModelKey($this->model, true)]));
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */
    protected function applyToggleAuthorize(Model $model): void
    {
        $ability = 'toggle-active';

        if (Gate::getPolicyFor($model)) {
            Gate::authorize($ability, $model);
        } else {
            $permission = $ability.'-'.Str::snake(class_basename($model), '-');
            if (! auth()->user()?->hasPermissionTo($permission)) {
                abort(403, __('api.not_allowed_to_toggle_active', ['id' => $model->getKey()]));
            }
        }
    }

    protected function passesToggleGuards(Model $model): bool
    {
        foreach ($this->toggleGuards ?? [] as $guard) {
            if (is_callable($guard) && ! $guard($model)) {
                return false;
            }
        }

        return true;
    }

    protected function runToggleCallbacks(array $callbacks, Model $model): void
    {
        foreach ($callbacks as $callback) {
            $callback($model);
        }
    }

    protected function resolveToggleIds(): array
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
}
