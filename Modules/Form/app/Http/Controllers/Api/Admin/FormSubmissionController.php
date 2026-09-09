<?php

namespace Modules\Form\app\Http\Controllers\Api\Admin;

use App\Filters\Global\OrderByFilter;
use App\Http\Controllers\API\BaseController;
use App\Http\Requests\Global\Other\PageRequest;
use App\Trait\Global\HasDeleteMethods;
use Illuminate\Http\JsonResponse;
use Illuminate\Pipeline\Pipeline;
use Modules\Form\app\Filters\FormSubmissionFilter;
use Modules\Form\app\Http\Requests\FormSubmissionRequest;
use Modules\Form\app\Http\Requests\FormSubmissionUpdateRequest;
use Modules\Form\app\Http\Resources\Admin\FormSubmissionResource;
use Modules\Form\app\Models\FormSubmission;
use Modules\Form\Tools\Form\Facades\Form;
use Throwable;

class FormSubmissionController extends BaseController
{
    use HasDeleteMethods;

    public function __construct()
    {
        parent::__construct();
        $this->model = FormSubmission::class;
    }

    public function index(PageRequest $request): JsonResponse
    {
        $query = app(Pipeline::class)
            ->send(FormSubmission::query()->with(['source', 'submission', 'values.field', 'values.step']))
            ->through([FormSubmissionFilter::class, OrderByFilter::class])
            ->thenReturn();

        return successResponse(fetchData($query, $request->pageSize, FormSubmissionResource::class));
    }

    /**
     * @throws Throwable
     */
    public function store(FormSubmissionRequest $request): JsonResponse
    {
        $user = auth()->user();

        $submission = Form::submitForm([
            ...$request->validated(),
            'submission_id' => $user->id,
            'submission_type' => $user->getMorphClass(),
        ]);

        return successResponse(
            new FormSubmissionResource($submission->load(['source', 'submission', 'values.field', 'values.step'])),
            __('form::api.created_success'),
            201
        );
    }

    /**
     * @throws Throwable
     */
    public function update(FormSubmissionUpdateRequest $request, FormSubmission $formSubmission): JsonResponse
    {
        foreach ($request->validated()['fields'] as $field) {
            $formSubmission->values()
                ->where('form_field_id', $field['form_field_id'])
                ->update(['value' => $field['value'] ?? null]);
        }

        return successResponse(
            new FormSubmissionResource($formSubmission->fresh()->load(['source', 'submission', 'values.field', 'values.step'])),
            __('form::api.updated_success')
        );
    }

    public function show(FormSubmission $formSubmission): JsonResponse
    {
        return successResponse(
            new FormSubmissionResource($formSubmission->load(['source', 'submission', 'values.field', 'values.step']))
        );
    }
}
