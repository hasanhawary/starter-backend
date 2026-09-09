<?php

namespace App\Trait\Global;

use HasanHawary\MediaManager\Facades\Media;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

trait HasFileActionsMethods
{
    protected string $storagePath;

    protected string $model;

    protected bool $useFilePolicy = true;

    protected array $gateConfig = [];

    protected array $replaceFields = ['name', 'path'];

    protected array $fileGuards = [];

    protected array $beforeFileCallbacks = [];

    protected array $afterFileCallbacks = [];

    /*
    |--------------------------------------------------------------------------
    | Configuration Methods
    |--------------------------------------------------------------------------
    */
    protected function setStoragePath(string $path): static
    {
        $this->storagePath = $path;

        return $this;
    }

    protected function setModel(string $model): static
    {
        $this->model = $model;

        return $this;
    }

    protected function enableFilePolicy(bool $state = true): static
    {
        $this->useFilePolicy = $state;

        return $this;
    }

    protected function setGateConfig(string $action, string $ability, string|object|null $model = null): static
    {
        $this->gateConfig[$action] = ['ability' => $ability, 'model' => $model];

        return $this;
    }

    protected function setReplaceFields(array $fields): static
    {
        $this->replaceFields = $fields;

        return $this;
    }

    protected function setFileGuard(string $action, callable|array $guards): static
    {
        $guards = is_array($guards) ? $guards : [$guards];
        $this->fileGuards[$action] = array_merge($this->fileGuards[$action] ?? [], $guards);

        return $this;
    }

    protected function beforeFileAction(string $action, callable|array $callback): static
    {
        $callback = is_array($callback) ? $callback : [$callback];
        $this->beforeFileCallbacks[$action] = array_merge($this->beforeFileCallbacks[$action] ?? [], $callback);

        return $this;
    }

    protected function afterFileAction(string $action, callable|array $callback): static
    {
        $callback = is_array($callback) ? $callback : [$callback];
        $this->afterFileCallbacks[$action] = array_merge($this->afterFileCallbacks[$action] ?? [], $callback);

        return $this;
    }

    /*
    |--------------------------------------------------------------------------
    | Public Methods
    |--------------------------------------------------------------------------
    */
    public function deleteFile(): JsonResponse
    {
        return $this->handleFile('delete');
    }

    public function replaceFile(): JsonResponse
    {
        return $this->handleFile('replace');
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */
    private function handleFile(string $action): JsonResponse
    {
        $file = $this->resolveFileModel();

        if ($this->useFilePolicy) {
            $this->applyFileAuthorize($action, $file);
        }

        if (! $this->passesFileGuards($action, $file)) {
            abort(403, __("api.not_allowed_to_{$action}", ['id' => $file->getKey()]));
        }

        $this->runFileCallbacks($this->beforeFileCallbacks[$action] ?? [], $file);

        $this->executeFileAction($action, $file);

        $this->runFileCallbacks($this->afterFileCallbacks[$action] ?? [], $file);

        $globalKey = $action === 'replace' ? 'replaced' : 'deleted';

        $transKey = (property_exists($this, 'model') && property_exists($this->model, 'transKey') ? $this->model::$transKey : null)
            ?: $this->resolveTransKeyFromClass();

        $msg = $transKey
            ? __("api.global.{$globalKey}", ['item' => __($transKey)])
            : __("api.{$globalKey}_success");

        return successResponse(msg: $msg);
    }

    private function executeFileAction(string $action, Model $file): void
    {
        match ($action) {
            'replace' => $this->performReplace($file),
            default => $this->performDelete($file),
        };
    }

    private function performDelete(Model $file): void
    {
        Media::delete($file->getRawOriginal('path'));
        $file->delete();
    }

    private function performReplace(Model $file): void
    {
        $newFile = request()->file('file');

        Media::replace($file->getRawOriginal('path'));

        $data = [];

        if (in_array('name', $this->replaceFields, true)) {
            $data['name'] = $newFile->getClientOriginalName();
        }

        if (in_array('path', $this->replaceFields, true)) {
            $data['path'] = Media::from($newFile)->to($this->storagePath)->store();
        }

        $file->update($data);
    }

    private function resolveTransKeyFromClass(): ?string
    {
        $segment = Str::snake(str_replace('Controller', '', class_basename($this)));
        $key = "api.messages.{$segment}.{$segment}";

        return __($key) !== $key ? $key : null;
    }

    private function resolveFileModel(): Model
    {
        $routeParams = request()->route()?->parameters() ?? [];
        $id = ! empty($routeParams) ? array_values($routeParams)[0] : null;

        if ($id instanceof Model) {
            return $id;
        }

        abort_if(! $id, 404);

        return $this->model::findOrFail($id);
    }

    private function applyFileAuthorize(string $action, Model $file): void
    {
        if (isset($this->gateConfig[$action])) {
            $config = $this->gateConfig[$action];
            Gate::authorize($config['ability'], $config['model'] ?? $file);

            return;
        }

        $ability = $action === 'delete' ? 'delete' : 'update';
        $permission = $ability.'-'.Str::snake(class_basename($file), '-');

        if (! auth()->user()?->hasPermissionTo($permission)) {
            abort(403, __("api.not_allowed_to_{$action}", ['id' => $file->getKey()]));
        }
    }

    private function passesFileGuards(string $action, Model $file): bool
    {
        foreach ($this->fileGuards[$action] ?? [] as $guard) {
            if (is_callable($guard) && ! $guard($file)) {
                return false;
            }
        }

        return true;
    }

    private function runFileCallbacks(array $callbacks, Model $file): void
    {
        foreach ($callbacks as $callback) {
            $callback($file);
        }
    }
}
