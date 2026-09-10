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
    /** Kept `protected` so the trait composes with HasFileActionsMethods, which declares the same property. */
    protected string $model;

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

        $handled = [];

        foreach ($models as $model) {
            // Policy
            if ($this->useDeletePolicy) {
                $this->applyDeleteAuthorize($action, $model);
            }

            // Custom Guards
            if (! $this->passesDeleteGuards($action, $model)) {
                abort(403, __("api.not_allowed_to_{$action}", ['id' => $model->getKey()]));
            }

            // Model-declared relation guard (delete/force only)
            if (in_array($action, ['delete', 'force'], true)) {
                $this->guardLinkedRelations($model);
            }

            // Before callbacks
            $this->runDeleteCallbacks($this->beforeDeleteCallbacks[$action] ?? [], $model);

            // Execute action
            $this->executeDelete($model, $action);

            // After callbacks
            $this->runDeleteCallbacks($this->afterDeleteCallbacks[$action] ?? [], $model);

            if ($action === 'restore') {
                $handled[] = $model->refresh();
            }
        }

        return successResponse(
            data: $action === 'restore' ? $this->restoredData($handled) : [],
            msg: __('api.'.
                match ($action) {
                    'restore' => 'restored_success',
                    default => 'deleted_success'
                }
            )
        );
    }

    /**
     * Restored records returned in the response payload: a single object when
     * one record was restored, otherwise the list of restored objects.
     *
     * @param  array<int, Model>  $models
     */
    protected function restoredData(array $models): mixed
    {
        return count($models) === 1 ? $models[0] : $models;
    }

    protected function applyDeleteAuthorize(string $action, Model $model): void
    {
        $user = auth()->user();

        // Root bypasses every delete check (parity with the legacy trait).
        if (isRoot($user)) {
            return;
        }

        $ability = match ($action) {
            'force' => 'force-delete',
            default => $action,
        };

        if (Gate::getPolicyFor($model)) {
            Gate::authorize($ability, $model);
        } else {
            // Fallback to the Spatie permission when the model has no policy.
            // Use can() (not hasPermissionTo) so an undefined permission fails
            // gracefully with a 403 instead of throwing.
            $permission = $ability.'-'.Str::snake(class_basename($model), '-');

            if (! $user?->can($permission)) {
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

    /**
     * Prevent deleting a record while it is still referenced by any of the
     * relations the model itself declares as delete-blocking.
     *
     * A model opts in by defining `preventDeleteRelations(): array`. Two shapes
     * are supported:
     *   - a plain list of relation names:  ['causeParticipants', 'contracts']
     *   - a map of relation => validation message key, to override the message:
     *       ['causes' => 'not_allowed_to_delete_linked_cause']
     *
     * When any listed relation still has records, the request is rejected with
     * the matching (or default) message. Models without the method are
     * unaffected.
     */
    protected function guardLinkedRelations(Model $model): void
    {
        if (! method_exists($model, 'preventDeleteRelations')) {
            return;
        }

        foreach ($model->preventDeleteRelations() as $relation => $messageKey) {
            if (is_int($relation)) {
                $relation = $messageKey;
                $messageKey = 'not_allowed_to_delete_linked';
            }

            if ($model->{$relation}()->exists()) {
                abort(403, resolveTrans($messageKey, 'validation'));
            }
        }
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

        // A route-model-bound parameter arrives as a Model instance; unwrap it.
        if ($ids instanceof Model) {
            $ids = $ids->getKey();
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
