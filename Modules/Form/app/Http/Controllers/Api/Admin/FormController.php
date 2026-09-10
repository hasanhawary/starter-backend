<?php

namespace Modules\Form\app\Http\Controllers\Api\Admin;

use App\Filters\Global\JsonNameFilter;
use App\Filters\Global\OrderByFilter;
use App\Http\Controllers\API\BaseController;
use App\Http\Requests\Global\Other\PageRequest;
use App\Trait\Global\HasDeleteMethods;
use Illuminate\Http\JsonResponse;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Modules\Form\app\Http\Requests\FormRequest;
use Modules\Form\app\Http\Resources\Admin\FormFieldResource;
use Modules\Form\app\Http\Resources\Admin\FormResource;
use Modules\Form\app\Models\Form as FormModel;
use Modules\Form\Tools\Form\Facades\Form;
use Throwable;

class FormController extends BaseController
{
    use HasDeleteMethods;

    public function __construct()
    {
        parent::__construct();
        $this->model = FormModel::class;
        $this->enableDeletePolicy(false);
    }

    /**
     * @throws Throwable
     */
    public function index(PageRequest $request): JsonResponse
    {
        $query = app(Pipeline::class)
            ->send(FormModel::query()->with(['creator', 'steps.fields']))
            ->through([JsonNameFilter::class, OrderByFilter::class])
            ->thenReturn();

        return successResponse(fetchData($query, $request->pageSize, FormResource::class));
    }

    /**
     * @throws Throwable
     */
    public function store(FormRequest $request): JsonResponse
    {
        Gate::authorize('create', FormModel::class);

        $form = Form::createForm($request->validated());

        return successResponse(
            new FormResource($form->load('creator', 'steps.fields')),
            __('form::api.created_success'),
            201
        );
    }

    public function show(FormModel $form): JsonResponse
    {
        return successResponse(
            new FormResource($form->load(['creator', 'fields']))
        );
    }

    public function fields(FormModel $form): JsonResponse
    {
        $form->load(['fields']);

        return successResponse(
            FormFieldResource::collection($form->fields ?? collect([]))
        );
    }

    /**
     * @throws Throwable
     */
    public function update(FormRequest $request, FormModel $form): JsonResponse
    {
        Gate::authorize('update', $form);

        return DB::transaction(static function () use ($form, $request) {
            $newForm = Form::updateForm($form, $request->validated());

            return successResponse(
                new FormResource($newForm->load(['creator', 'steps.fields'])),
                __('form::api.updated_success')
            );
        });
    }

    /**
     * @throws Throwable
     */
    public function changeStatus(FormModel $form): JsonResponse
    {
        $form->update(['status' => ! $form->status]);

        return successResponse(new FormResource($form), __('form::api.updated_success'));
    }

    public function getValidationRules(): JsonResponse
    {
        return successResponse(Form::getValidationRules());
    }
}
