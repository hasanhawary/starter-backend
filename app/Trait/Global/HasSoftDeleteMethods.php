<?php

namespace App\Trait\Global;

use App\Http\Requests\Central\Global\Other\ModelBatchRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Spatie\Permission\Exceptions\UnauthorizedException;

trait HasSoftDeleteMethods
{
    protected string $model;
    protected array $validations = [];

    /**
     * Set the model class to use.
     *
     * @param string $model
     * @return void
     */
    public function setSoftDeleteModel(string $model): void
    {
        $this->model = $model;
    }

    /**
     * @param array $validations
     * @return void
     */
    public function setDeleteValidations(array $validations = []): void
    {
        $this->validations = $validations;
    }

    /**
     * @param string $action
     * @throws \Spatie\Permission\Exceptions\UnauthorizedException
     * @return void
     */
    private function checkPolicy(string $action): void
    {
        $user = auth()->user();
        $policy = "$action-" . getModelKey($this->model);

        if ($user->hasRole('root')) {
            return;
        }

        if (!$user->can($policy)) {
            throw new UnauthorizedException(403);
        }
    }

    /**
     * @param int|array $ids
     * @return bool
     */
    private function canDelete(int|array $ids): bool
    {
        $objects = $this->model::find(Arr::wrap($ids));

        foreach ($objects as $object) {
            foreach ($this->validations as $validation) {
                if (is_callable($validation) && !$validation($object)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * @param ModelBatchRequest $request
     * @return JsonResponse
     */
    public function destroy(ModelBatchRequest $request): JsonResponse
    {
        $this->checkPolicy('delete');

        $ids = $this->getIds($request);

        if (!$this->canDelete($ids)) {
            return failResponse(msg: resolveTrans('not_allowed_to_delete', 'validation'));
        }

        $this->model::whereIn('id', Arr::wrap($ids))->delete();

        return successResponse(msg: resolveTrans('deleted_success'));
    }

    /**
     * @param ModelBatchRequest $request
     * @return JsonResponse
     */
    public function restore(ModelBatchRequest $request): JsonResponse
    {
        $this->checkPolicy('restore');

        $this->model::onlyTrashed()->whereIn('id', $this->getIds($request))->restore();

        return successResponse(msg: resolveTrans('restored_success'));
    }

    /**
     * @param ModelBatchRequest $request
     * @return JsonResponse
     */
    public function forceDelete(ModelBatchRequest $request): JsonResponse
    {
        $this->checkPolicy('force-delete');

        $ids = $this->getIds($request);

        if (!$this->canDelete($ids)) {
            return failResponse(msg: resolveTrans('not_allowed_to_delete', 'validation'));
        }

        $this->model::onlyTrashed()->whereIn('id', $ids)->forceDelete();

        return successResponse(msg: resolveTrans('deleted_success'));
    }

    private function getIds($request): array|int|null
    {
        return $request->input('ids') ?? last(explode('/', $request->getRequestUri()));
    }
}

